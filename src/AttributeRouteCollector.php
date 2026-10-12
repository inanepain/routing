<?php

/**
 * AttributeRouteCollector
 *
 * Inane Library
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.5
 *
 * @author   Philip Michael Raab <philip@cathedral.co.za>
 * @package  inanepain\routing
 * @category routing
 *
 * @license  UNLICENSE
 * @license  https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types = 1);

namespace Inane\Routing;

use Inane\File\Path;
use Inane\Routing\Exception\InvalidRouteException;
use Inane\Stdlib\Array\OptionsInterface;
use Inane\Stdlib\Exception\Exception;
use Inane\Stdlib\Exception\JsonException;
use Inane\Stdlib\Options;
use Inane\Stdlib\Utility\ClassUtility;
use ReflectionException;

use function is_string;
use function preg_match;

use const GLOB_BRACE;
use const GLOB_NOSORT;
use const PREG_OFFSET_CAPTURE;

/**
 * AttributeRouteCollector
 *
 * Discovers controller classes, that carry `Route` attributes, by scanning the filesystem with a glob pattern and
 * collects them into a route collection ready for `Router::addRoutes`.
 *
 * config:
 *  [
 *      'splitQuerystring' => false,
 *      'controller'       => [
 *          'glob'        => 'src/*\/*Controller.php',
 *          'glob_ignore' => '/(Abstract)/',
 *          'default'     => [],
 *          'path'        => new Path(),
 *      ],
 *  ]
 *
 * inane-fw
 *
 * @version 0.1.0
 */
class AttributeRouteCollector {
    //#region Properties
    /**
     * Collector configuration.
     *
     * @var Options
     */
    protected Options $config {
        get => $this->config ??= new Options([
            'splitQuerystring' => false,
            'controller'       => [
                'glob'        => 'src/*/*Controller.php',
                'glob_ignore' => '/(Abstract)/',
                'default'     => [],
                'path'        => new Path(),
            ],
        ]);
        set => $this->config = $value;
    }

    //#endregion Properties

    /**
     * AttributeRouteCollector
     *
     * @param null|array|Options|OptionsInterface $options Configuration overriding the defaults.
     *
     * @throws JsonException
     */
    public function __construct(null|array|Options|OptionsInterface $options = null) {
        if ($options) $this->config->merge($options);

        if (is_string($this->config->controller->path))
            $this->config->controller->path = new Path($this->config->controller->path);
    }

    /**
     * The search path used when resolving the glob pattern.
     *
     * @return Path
     */
    protected function path(): Path {
        return $this->config->controller->path;
    }

    /**
     * Collects the controllers and route configurations.
     *
     * Classes are gathered from the files matching the configured `glob` pattern; any matching the `glob_ignore`
     * expression are skipped; thereafter the `default` entries are appended.
     *
     * @return Options Controller classes and route configurations.
     *
     * @throws Exception If a matched file can not be read.
     */
    public function collect(): Options {
        $controllers = new Options();

        if ($controller = $this->config->controller) {
            if ($glob = $controller->glob) {
                foreach($this->path()->getFiles($glob, GLOB_BRACE | GLOB_NOSORT) ?? [] as $file) {
                    if ($ignore = $controller->glob_ignore) {
                        preg_match($ignore, $file->getFilename(), $matches, PREG_OFFSET_CAPTURE);
                        if (!empty($matches)) continue;
                    }
                    if ($ns = ClassUtility::getClassFromFile($file)) $controllers[] = $ns;
                }
            }

            if ($default = $controller->default) {
                $controllers->merge($default)
                    ->unique()
                ;
            }
        }

        return $controllers;
    }

    /**
     * Collects the controllers and adds their routes to the router.
     *
     * @param Router $router Router to populate.
     *
     * @return Router The populated router.
     *
     * @throws InvalidRouteException If a collected item is not a valid route.
     * @throws ReflectionException|Exception If a collected controller does not exist.
     */
    public function collectInto(Router $router): Router {
        return $router->addRoutes($this->collect());
    }

    /**
     * Creates a router populated with the collected routes.
     *
     * @param string $baseURI Part of the URI to exclude.
     *
     * @return Router The populated router.
     *
     * @throws InvalidRouteException If a collected item is not a valid route.
     * @throws ReflectionException|Exception If a collected controller does not exist.
     */
    public function createRouter(string $baseURI = ''): Router {
        return $this->collectInto(new Router(
            baseURI: $baseURI,
            splitQuerystring: (bool) $this->config->splitQuerystring,
        ));
    }
}
