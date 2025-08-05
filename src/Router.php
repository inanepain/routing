<?php

/**
 * Inane: Routing
 *
 * PHP version 8.1
 *
 * @author Philip Michael Raab<peep@inane.co.za>
 * @package Inane\Routing
 *
 * @license UNLICENSE
 * @license https://github.com/inanepain/stdlib/raw/develop/UNLICENSE UNLICENSE
 *
 * @version $Id$
 * $Date$
 */

declare(strict_types=1);

namespace Inane\Routing;

use Inane\Http\Request;
use Inane\Stdlib\Options;

use function array_diff_key;
use function array_filter;
use function array_keys;
use function array_values;
use function class_exists;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_null;
use function is_string;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function sprintf;
use function str_starts_with;
use function str_contains;
use function str_replace;
use function parse_str;
use const false;
use const null;
use const true;

use Inane\Routing\Exception\{
    InvalidArgumentException,
    InvalidRouteException,
    OutOfRangeException
};

/**
 * Router
 *
 * @package Inane\Routing
 *
 * @version 1.4.0
 */
class Router {
    /**
     * Array that will contain in value, each of the routes defined in the controllers with the target class and
     * method and the name of the route as the key.
     *
     * @var array $routes
     */
    private array $routes = [];

    /**
     * Allows to define with a single call to the constructor, all the configuration necessary for the operation
     * of the router
     *
     * route config:
     *  [
     *      MainController::class,
     *      'backups' => [
     *          'route' => [
     *              'path' => '/archive/{section}/year/{id<\d+>}',
     *              'methods' => ['GET'],
     *          ],
     *          'class'  => HistoryController::class,
     *          'method' => 'list',
     *      ],
     *  ]
     *
     * @param array  $routes Classes containing Route attributes or configurations
     * @param string $baseURI Part of the URI to exclude
     *
     * @throws \ReflectionException when the controller does not exist
     */
    public function __construct(
        array $routes = [],
        private string $baseURI = '',
        protected(set) bool $splitQuerystring = false,
    ) {
        if (!empty($routes))
            $this->addRoutes($routes);
    }

    /**
     * Add Route to router
     *
     * @since 1.2.0
     *
     * @param array|\Inane\Routing\Route $route
     * @param string $class
     * @param string $method
     *
     * @return void
     */
    private function addRoute(array|Route $route, string $class, string $method): void {
        if (is_array($route)) $route = new Route(...$route);

        $this->routes[$route->getName()] = [
            'class'  => $class,
            'method' => $method,
            'route'  => $route,
        ];
    }

    /**
     * Create route from attribute
     *
     * @since 1.2.0
     *
     * @param string $controller class to parse for Route Attribute
     *
     * @return void
     */
    private function parseRouteAttribute(string $controller): void {
        $reflectionController = new \ReflectionClass($controller);

        foreach ($reflectionController->getMethods() as $reflectionMethod) {
            $routeAttributes = $reflectionMethod->getAttributes(Route::class);

            foreach ($routeAttributes as $routeAttribute) {
                $route = $routeAttribute->newInstance();
                $this->addRoute($route, $reflectionMethod->class, $reflectionMethod->name);
            }
        }
    }

    /**
     * Create route from config
     *
     * @since 1.2.0
     *
     * @param string $name
     * @param array|\Inane\Stdlib\Options $config
     *
     * @return void
     */
    private function parseRouteConfig(string $name, array|Options $config = []): void {
        $config['route']['name'] = $name;
        $this->addRoute(...(is_array($config) ? $config : $config->toArray()));
    }

    /**
     * Check if the user's request matches the given route
     *
     * @param \Inane\Http\Request $request Request
     * @param \Inane\Routing\Route $route Route
     * @param null|array $params Array that will be filled with the parameters and their value provided in the request
     *
     * @return bool
     *
     * @throws \Inane\Stdlib\Exception\UnexpectedValueException
     * @throws \Inane\Stdlib\Exception\BadMethodCallException
     */
    private function matchRequest(Request $request, Route $route, ?array &$params = []): bool {
        $url = $request->getUri()->getPath();
        $query = [];
        if ($this->splitQuerystring && !empty($request->getUri()->getQuery())) {
            if (!$params) $params = [];
            $query = $request->getUri()->getQuery();
            $url = str_replace("?$query", '', $url);
            parse_str($query, $query);
            $params['query-string'] = $query;
        }

        $requestArray = explode('/', $url);
        $pathArray = explode('/', $route->getPath());

        // Remove empty values in arrays
        $requestArray = array_values(array_filter($requestArray, 'strlen'));
        $pathArray = array_values(array_filter($pathArray, 'strlen'));

        if (
            !(count($requestArray) === count($pathArray))
            || !(in_array($request->getHttpMethod(), $route->getMethods(), true))
        )
            return false;

        foreach ($pathArray as $index => $urlPart) {
            if (isset($requestArray[$index])) {
                if (str_starts_with($urlPart, '{')) {
                    $routeParameter = explode(' ', preg_replace('/{([\w\-%]+)(<(.+)>)?}/', '$1 $3', $urlPart));
                    $paramName = $routeParameter[0];
                    $paramRegExp = (empty($routeParameter[1]) ? '[\w\-]+' : $routeParameter[1]);

                    if (preg_match('/^' . $paramRegExp . '$/', $requestArray[$index])) {
                        $params[$paramName] = $requestArray[$index];

                        continue;
                    }
                } elseif ($urlPart === $requestArray[$index])
                    continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Define the base URI in order to exclude it in the route correspondence.
     *
     * Useful when the project is called from a sub-folder.
     *
     * @param string $baseURI Part of the URI to exclude
     *
     * @return \Inane\Routing\Router router
     */
    public function setBaseURI(string $baseURI): self {
        $this->baseURI = $baseURI;

        return $this;
    }

    /**
     * Adds routes from config array
     *
     * items:
     * - controller using Route attributes
     * - route config
     *
     * route config:
     *  [
     *      MainController::class,
     *      'backups' => [
     *          'route' => [
     *              'path' => '/archive/{section}/year/{id<\d+>}',
     *              'methods' => ['GET'],
     *          ],
     *          'class'  => HistoryController::class,
     *          'method' => 'list',
     *      ],
     *  ]
     *
     * @param array|Inane\Stdlib\Options $routes array of route configurations and controllers
     *
     * @return \Inane\Routing\Router router
     *
     * @throws \ReflectionException when the controller does not exist
     */
    public function addRoutes(array|Options $routes): self {
        foreach($routes as $n => $r) {
            if (is_array($r) || $r instanceof Options)
                $this->parseRouteConfig($n, $r);
            else if (is_string($r) && class_exists($r))
                $this->parseRouteAttribute($r);
            else {
                $message = 'Invalid Route: ';
                if (is_string($r)) $message .= $r;
                else $message .= 'UNKNOWN';

                throw new InvalidRouteException($message);
            }
        }

        return $this;
    }

    /**
     * Builds a URL for the routeName and parameters supplied
     *
     * @since 1.2.0
     *
     * @param string $routeName  name of route to build
     * @param array  $parameters used to populate route
     *
     * @return string url
     *
     * @throws \Inane\Routing\Exception\OutOfRangeException If route does not exist
     * @throws \Inane\Routing\Exception\InvalidArgumentException If not all route parameters are provided
     */
    public function url(string $routeName, array $parameters = []): string {
        if (!isset($this->routes[$routeName]))
            throw new OutOfRangeException(sprintf(
                'The route does not exist. Check that the given route name "%s" is valid.',
                $routeName
            ));

        /** @var Route $route */
        $route = $this->routes[$routeName]['route'];
        $path = $route->getPath();

        if ($route->hasParams()) {
            $routeParams = $route->fetchParams();

            // Checks that all parameters are provided
            if ($missingParameters = array_diff_key($routeParams, $parameters))
                throw new InvalidArgumentException(sprintf(
                    'The following parameters are missing for generating the route "%s": %s',
                    $routeName,
                    implode(', ', array_keys($missingParameters))
                ));

            // Compare each of the values provided with the regular expressions contained in the path and replace it in
            // the path if it is valid
            foreach ($routeParams as $paramName => $regex) {
                $regex = (!empty($regex) ? $regex : Route::DEFAULT_REGEX);

                if (!preg_match("/^$regex$/", (string) $parameters[$paramName]))
                    throw new InvalidArgumentException(sprintf(
                        'The "%s" route parameter value given does not match the regular expression',
                        $paramName
                    ));

                $path = preg_replace('/{' . $paramName . '(<.+>)?}/', (string) $parameters[$paramName], $path);
            }
        }

        return $this->baseURI . $path;
    }

    /**
     * property - gets extra route property information
     * 
     * @since 0.1.0
     *
     * @param string $routeName  name of route to build
     * @param string $property  the extra route info property name
     * @param array $params params to fill out property value (if its a template)
     *
     * @return string extra route info property value OR empty string if not valid.
     */
    public function routeProperty(string $routeName, string $property, array $params = []): string {
        if (!isset($this->routes[$routeName]))
            throw new OutOfRangeException(sprintf(
                'The route does not exist. Check that the given route name "%s" is valid.',
                $routeName
            ));

        /** @var Route $route */
        return ($this->routes[$routeName]['route'])->property($property, $params);
    }

    /**
     * Returns the route that corresponds to the request.
     *
     * Iterate over all the attributes of the controllers in order to find the first one corresponding to the request.
     * If a match is found then an array is returned with the class, method and parameters, otherwise null is returned.
     *
     * @since 0.1.3 Route & uri are now returned as part of the array
     * @since 1.4.0 method returns a RouteMatch object
     *
     * @param null|\Inane\Http\Request $request if not the current request
     *
     * @return null|\Inane\Routing\RouteMatch
     *
     * @throws \Inane\Stdlib\Exception\UnexpectedValueException
     * @throws \Inane\Stdlib\Exception\BadMethodCallException
     */
    public function match(?Request $request = null): ?RouteMatch {
        if (is_null($request)) $request = new Request();
        $uri = $request->getUri()->getPath();

        if (!empty($this->baseURI)) {
            $baseURI = preg_quote($this->baseURI, '/');
            $uri = preg_replace("/^{$baseURI}/", '', $uri);
        }
        $uri = (empty($uri) ? '/' : $uri);

        foreach ($this->routes as $route){
            // dd($route, 'route for matching');
            if ($this->matchRequest($request, $route['route'], $params)) {
				$route['params'] = $params ?? [];
				$route['uri'] = $uri;

                $rm = new RouteMatch($route);
                // dd($rm, 'Matched Route');
				// return new RouteMatch($route);
				return $rm;
            }}

        return null;
    }
}
