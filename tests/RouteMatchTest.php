<?php

declare(strict_types = 1);

namespace Inane\Routing\Tests;

use Inane\Routing\Route;
use Inane\Routing\RouteMatch;
use Inane\Routing\Tests\Fixture\Controller\BlogController;
use PHPUnit\Framework\TestCase;

use const DIRECTORY_SEPARATOR;

/**
 * RouteMatchTest
 */
final class RouteMatchTest extends TestCase {
    public static function setUpBeforeClass(): void {
        require_once __DIR__ . '/Fixture/Controller/BlogController.php';
    }

    protected function tearDown(): void {
        RouteMatch::$stripClass = 'Controller';
        RouteMatch::$stripMethod = 'Task';

        parent::tearDown();
    }

    /**
     * A route match for the blog view route.
     *
     * @param null|callable $templateParser optional template parser
     *
     * @return RouteMatch
     */
    private function routeMatch(?callable $templateParser = null): RouteMatch {
        return new RouteMatch([
            'class'  => BlogController::class,
            'method' => 'viewTask',
            'route'  => new Route(path: '/blog/{id<\d+>}', name: 'blog-view', extra: ['api' => '/api/blog/{id}']),
            'params' => ['id' => '42'],
            'uri'    => '/blog/42',
        ], $templateParser);
    }

    public function testExposesTheMatchedRouteDetails(): void {
        $match = $this->routeMatch();

        $this->assertSame(BlogController::class, $match->class);
        $this->assertSame('viewTask', $match->method);
        $this->assertSame('/blog/42', $match->uri);
        $this->assertSame(['id' => '42'], $match->params);
        $this->assertSame('blog-view', $match->route->getName());
    }

    public function testDefaultTemplateParserStripsClassAndMethodSuffixes(): void {
        $this->assertSame('Blog' . DIRECTORY_SEPARATOR . 'view', $this->routeMatch()->template);
    }

    public function testTemplateParserCanBeReplaced(): void {
        $match = $this->routeMatch(static fn(string $class, string $method): string => "$class::$method");

        $this->assertSame(BlogController::class . '::viewTask', $match->template);
    }

    public function testStripValuesAreConfigurable(): void {
        RouteMatch::$stripClass = 'Blog';
        RouteMatch::$stripMethod = 'view';

        $this->assertSame('Controller' . DIRECTORY_SEPARATOR . 'Task', $this->routeMatch()->template);
    }

    public function testRoutePropertyDelegatesToTheRoute(): void {
        $match = $this->routeMatch();

        $this->assertSame('/api/blog/42', $match->routeProperty('api', ['id' => 42]));
        $this->assertSame('', $match->routeProperty('missing'));
    }
}
