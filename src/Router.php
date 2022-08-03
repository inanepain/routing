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
use const false;
use const null;
use const true;

/**
 * Router
 *
 * @package Inane\Routing
 * @version 1.2.0
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
    ) {
        if (!empty($routes))
            $this->addRoutes($routes);
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
        $requestArray = explode('/', "$request");
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
     * Define the base URI in order to exclude it in the route correspondence, useful when the project is called from a
     * sub-folder
     *
     * @param string $baseURI Part of the URI to exclude
     */
    public function setBaseURI(string $baseURI): void {
        $this->baseURI = $baseURI;
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
    protected function addRoute(array|Route $route, string $class, string $method): void {
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
     * @param string $controller
     *
     * @return void
     */
    protected function parseRouteController(string $controller): void {
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
     * @param string $config
     *
     * @return void
     */
    protected function parseRouteConfig(string $name, array $config = []): void {
        $config['route']['name'] = $name;
        $this->addRoute(...$config);
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
     * @param array $routes array of route configurations and controllers
     *
     * @throws \ReflectionException when the controller does not exist
     */
    public function addRoutes(array $routes): void {
        foreach($routes as $n => $r) {
            if (is_array($r))
                $this->parseRouteConfig($n, $r);
            else if (is_string($r) && class_exists($r))
                $this->parseRouteController($r);
            // else
                // invalid route
        }
    }

    /**
     * Generate a URL according to the name of the route
     *
     * @param string $routeName  The name of the route to generate
     * @param array  $parameters The parameters to provide if it is a dynamic route
     *
     * @return string
     *
     * @throws \OutOfRangeException If route does not exist
     * @throws \InvalidArgumentException If not all route parameters are provided
     */
    public function generateUrl(string $routeName, array $parameters = []): string {
        if (!isset($this->routes[$routeName]))
            throw new \OutOfRangeException(sprintf(
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
                throw new \InvalidArgumentException(sprintf(
                    'The following parameters are missing for generating the route "%s": %s',
                    $routeName,
                    implode(', ', array_keys($missingParameters))
                ));

            // Compare each of the values provided with the regular expressions contained in the path and replace it in
            // the path if it is valid
            foreach ($routeParams as $paramName => $regex) {
                $regex = (!empty($regex) ? $regex : Route::DEFAULT_REGEX);

                if (!preg_match("/^$regex$/", $parameters[$paramName]))
                    throw new \InvalidArgumentException(sprintf(
                        'The "%s" route parameter value given does not match the regular expression',
                        $paramName
                    ));

                $path = preg_replace('/{' . $paramName . '(<.+>)?}/', $parameters[$paramName], $path);
            }
        }

        return $this->baseURI . $path;
    }

    /**
     * Iterate over all the attributes of the controllers in order to find the first one corresponding to the request.
     * If a match is found then an array is returned with the class, method and parameters, otherwise null is returned
     *
     * @param null|\Inane\Http\Request $request if not the current request
     *
     * @return null|array
     *
     * @throws \Inane\Stdlib\Exception\UnexpectedValueException
     * @throws \Inane\Stdlib\Exception\BadMethodCallException
     */
    public function match(?Request $request = null): ?array {
        if (is_null($request)) $request = new Request();
        $uri = "$request";

        if (!empty($this->baseURI)) {
            $baseURI = preg_quote($this->baseURI, '/');
            $uri = preg_replace("/^{$baseURI}/", '', $uri);
        }
        $uri = (empty($uri) ? '/' : $uri);

        foreach ($this->routes as $route)
            if ($this->matchRequest($request, $route['route'], $params))
                return [
                    'class'  => $route['class'],
                    'method' => $route['method'],
                    'params' => $params ?? [],
                ];

        return null;
    }
}
