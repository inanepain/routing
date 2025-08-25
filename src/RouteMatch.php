<?php

/**
 * Inane: Routing
 *
 * HTTP Routing using attributes.
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.4
 *
 * @author Philip Michael Raab<philip@cathedral.co.za>
 * @package inanepain\routing
 * @category routing
 *
 * @license UNLICENSE
 * @license https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types=1);

namespace Inane\Routing;

use Inane\Stdlib\Options;

use function array_pop;
use function explode;
use function implode;
use function is_null;
use function str_replace;

use const false;

/**
 * RouteMatch
 *
 * A route matched with a request
 *
 * There is even a bit of ease of use with the templateParser which uses a function (customisable) to parse the view template.
 * 
 * // TODO: version bump
 * 
 * @version 0.1.0
 */
class RouteMatch {
	/**
	 * The string to be stripped from class names during route matching.
	 *
	 * @var string
	 */
	public static string $stripClass = 'Controller';
	
	/**
	 * The string to be stripped from method names during route matching.
	 *
	 * @var string
	 */
	public static string $stripMethod = 'Task';

	/**
	 * Holds the matched route options for the current request.
	 *
	 * @var Options $routeMatch The options associated with the matched route.
	 */
	private Options $routeMatch;

	/**
	 * Controller class fully qualified
	 *
	 * @var string
	 */
	public string $class {
		get {
			return $this->routeMatch->class;
		}
	}

	/**
	 * Controller method called
	 *
	 * @var string
	 */
	public string $method {
		get {
			return $this->routeMatch->method;
		}
	}

	/**
	 * uri used for matching
	 *
	 * @var string
	 */
	public string $uri {
		get {
			return $this->routeMatch->uri;
		}
	}

	/**
	 * parameters from the uri path specified by regex
	 *
	 * @var array
	 */
	public array $params {
		get {
			return $this->routeMatch->params->toArray();
		}
	}

	/**
	 * The matched Route
	 *
	 * - path - route path regex
	 * - name - route name
	 * - methods - allowed http methods
	 *
	 * @var Route
	 */
	public Route $route {
		get {
			return $this->routeMatch->route;
		}
	}

	/**
	 * View template parsed using configurable function
	 *
	 * @var string
	 */
	public string $template {
		get {
			return $this->routeMatch['templateParser']($this->class, $this->method);
		}
	}

	/**
     * property - gets extra route property information
     * 
     * @since 0.1.0
     *
     * @param string $name the extra route info property name
	 * @param array $params params to fill out property value (if its a template)
     *
     * @return string extra route info property value OR empty string if not valid.
     */
	public function routeProperty(string $name, array $params = []): string {
	    return $this->route->property($name, $params);
	}

	/**
	 * RouteMatch constructor
	 *
	 * @param array $route route match
	 * @param callable|\Closure|null $templateParser
	 */
	public function __construct(array $route, callable|\Closure|null $templateParser = null) {
		$route['templateParser'] = !is_null($templateParser) ? $templateParser : function(string $class, string $method) {
			$path = explode('\\', $class);
			return implode(DIRECTORY_SEPARATOR, [
				str_replace(static::$stripClass, '', array_pop($path)),
				str_replace(static::$stripMethod, '', $method)
			]);
		};

		$this->routeMatch = new Options($route, false);
	}
}
