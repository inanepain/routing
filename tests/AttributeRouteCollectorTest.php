<?php

declare(strict_types = 1);

namespace Inane\Routing\Tests;

use Inane\File\Path;
use Inane\Routing\AttributeRouteCollector;
use Inane\Routing\Router;
use Inane\Routing\Tests\Fixture\Controller\BlogController;
use Inane\Routing\Tests\Fixture\Controller\ShopController;
use PHPUnit\Framework\TestCase;

/**
 * AttributeRouteCollectorTest
 */
final class AttributeRouteCollectorTest extends TestCase {
    public static function setUpBeforeClass(): void {
        require_once __DIR__ . '/Fixture/Controller/AbstractBaseController.php';
        require_once __DIR__ . '/Fixture/Controller/BlogController.php';
        require_once __DIR__ . '/Fixture/Controller/ShopController.php';
    }

    /**
     * Collector configured to scan the fixture controllers.
     *
     * @param array $options configuration merged over the fixture defaults
     *
     * @return AttributeRouteCollector
     */
    private function collector(array $options = []): AttributeRouteCollector {
        return new AttributeRouteCollector([
            'controller' => [
                'glob' => 'Fixture/*/*Controller.php',
                'path' => new Path(__DIR__),
            ],
            ...$options,
        ]);
    }

    public function testCollectFindsTheFixtureControllers(): void {
        $collected = $this->collector()
            ->collect()
            ->toArray()
        ;

        $this->assertContains(BlogController::class, $collected);
        $this->assertContains(ShopController::class, $collected);
    }

    public function testCollectHonoursTheIgnoreExpression(): void {
        $collected = $this->collector()
            ->collect()
            ->toArray()
        ;

        $this->assertNotContains(Fixture\Controller\AbstractBaseController::class, $collected);
    }

    public function testCollectAppendsTheDefaultControllers(): void {
        $collected = $this->collector([
            'controller' => [
                'glob'    => 'Fixture/*/*Controller.php',
                'path'    => new Path(__DIR__),
                'default' => [BlogController::class],
            ],
        ])
            ->collect()
            ->toArray()
        ;

        $this->assertSame([BlogController::class, ShopController::class], $collected);
    }

    public function testCollectIsEmptyWhenNothingMatchesTheGlob(): void {
        $collected = $this->collector([
            'controller' => [
                'glob' => 'Fixture/*/*Nothing.php',
                'path' => new Path(__DIR__),
            ],
        ])
            ->collect()
            ->toArray()
        ;

        $this->assertSame([], $collected);
    }

    public function testCollectIntoPopulatesTheGivenRouter(): void {
        $router = $this->collector()
            ->collectInto(new Router());

        $this->assertSame('/blog', $router->url('blog-index'));
        $this->assertSame('/shop', $router->url('shop-index'));
    }

    public function testCreateRouterReturnsAPopulatedRouter(): void {
        $router = $this->collector()
            ->createRouter('/app');

        $this->assertInstanceOf(Router::class, $router);
        $this->assertSame('/app/blog', $router->url('blog-index'));
        $this->assertFalse($router->splitQuerystring);
    }

    public function testCreateRouterPassesTheSplitQuerystringSetting(): void {
        $router = $this->collector(['splitQuerystring' => true])
            ->createRouter();

        $this->assertTrue($router->splitQuerystring);
    }

    public function testPathCanBeGivenAsAString(): void {
        $collector = new AttributeRouteCollector([
            'controller' => [
                'glob' => 'Fixture/*/*Controller.php',
                'path' => __DIR__,
            ],
        ]);

        $this->assertContains(BlogController::class, $collector->collect()
            ->toArray());
    }
}
