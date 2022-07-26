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

use Inane\Http\HttpMethod;

use function array_combine;
use function preg_match;
use function preg_match_all;

/**
 * Route
 *
 * @package Inane\Routing
 * @version 1.0.0
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Route {
    /**
     * Default regular expression when none is defined in the parameter
     */
    public const DEFAULT_REGEX = '[\w\-]+';

    /**
     * @var array $parameters Keeps the parameters cached with the associated regex
     */
    /**
     * Parameter regex and value cache
     *
     * @var array
     */
    private array $parameters;
    // private array $parameters = [];

    /**
     * Route Attribute
     *
     * @param string $path url
     * @param string $name route name
     * @param \Inane\Http\HttpMethod[] $methods http methods
     */
    public function __construct(
        /**
         * Route path
         *
         * @var string
         */
        private string $path,
        /**
         * Route name
         *
         * Is set to $path if not set.
         *
         * @var string
         */
        private string $name = '',
        /**
         * Route methods
         *
         * @var \Inane\Http\HttpMethod[]
         */
        private array $methods = [HttpMethod::Get],
    ) {
        if (empty($name)) $this->name = $path;
    }

    /**
     * Path
     *
     * @return string
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * Name
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Methods
     *
     * @return \Inane\Http\HttpMethod[]
     */
    public function getMethods(): array {
        return $this->methods;
    }

    /**
     * Checks the presence of parameters in the path of the route
     *
     * @return bool
     */
    public function hasParams(): bool {
        return preg_match('/{([\w\-%]+)(<(.+)>)?}/', $this->path);
    }

    /**
     * Retrieves in key of the array, the names of the parameters as well as the regular expression (if there is one)
     * in value
     *
     * @return array
     */
    public function fetchParams(): array {
        if (empty($this->parameters)) {
            preg_match_all('/{([\w\-%]+)(?:<(.+)>)?}/', $this->getPath(), $params);
            $this->parameters = array_combine($params[1], $params[2]);
        }

        return $this->parameters;
    }
}
