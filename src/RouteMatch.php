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

use Inane\Stdlib\Options;

use function is_null;
use function explode;
use function implode;
use function str_replace;
use function array_pop;

/**
 * RouteMatch
 *
 * A route matched with a request
 *
 * There is even a bit of ease of use with the templateParser which uses a function (customisable) to parse the view template.
 *
 * @package Inane\Routing
 * @version 0.1.0
 */
class RouteMatch {
	private Options $routeMatch;

	public string $class {
		get {
			return $this->routeMatch->class;
		}
	}

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
	 * parameters from the uri
	 *
	 * @var array
	 */
	public array $params {
		get {
			return $this->routeMatch->params->toArray();
		}
	}

	/**
	 * Route matched agains
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
	 * RouteMatch constructor
	 *
	 * @param array $route
	 * @param callable|null $templateParser
	 */
	public function __construct(array $route, ?callable $templateParser = null) {
		$route['templateParser'] = !is_null($templateParser) ? $templateParser : function(string $class, string $method) {
			$path = explode('\\', $class);
			return implode(DIRECTORY_SEPARATOR, [
				str_replace('Controller', '', array_pop($path)),
				str_replace('Task', '', $method)
			]);
		};

		$this->routeMatch = new Options($route, false);
	}
}
