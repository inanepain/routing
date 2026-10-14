<?php

declare(strict_types = 1);

namespace Inane\Routing\Tests;

use Inane\Http\HttpMethod;
use Inane\Http\Request;
use Inane\Routing\Exception\InvalidArgumentException;
use Inane\Routing\Exception\InvalidRouteException;
use Inane\Routing\Exception\OutOfRangeException;
use Inane\Routing\Route;
use Inane\Routing\RouteMatch;
use Inane\Routing\Router;
use Inane\Routing\Tests\Fixture\Controller\BlogController;
use PHPUnit\Framework\TestCase;

use function array_keys;

/**
 * RouterTest
 */
final class RouterTest extends TestCase {
    public static function setUpBeforeClass(): void {
        require_once __DIR__ . '/Fixture/Controller/BlogController.php';
    }

    /**
     * A router holding the routes declared by the `BlogController` attributes.
     *
     * @return Router
     */
    private function attributeRouter(): Router {
        return new Router([BlogController::class]);
    }

    /**
     * Builds a request for the given path and method.
     *
     * @param string     $path   request path
     * @param HttpMethod $method request method
     *
     * @return Request
     */
    private function request(string $path, HttpMethod $method = HttpMethod::Get): Request {
        return new Request($method, 'http://localhost' . $path);
    }

    public function testRoutesAreCollectedFromControllerAttributes(): void {
        $router = $this->attributeRouter();

        $this->assertSame('/blog', $router->url('blog-index'));
        $this->assertSame('/blog/42', $router->url('blog-view', ['id' => 42]));
    }

    /**
     * Repeated method attributes register independent routes.
     */
    public function testRepeatedAttributesMatchTheSameControllerMethod(): void {
        $router = $this->attributeRouter();

        $this->assertSame('/articles', $router->url('blog-alias'));
        foreach (['/articles', '/blog'] as $path) {
            $match = $router->match($this->request($path));

            $this->assertInstanceOf(RouteMatch::class, $match);
            $this->assertSame(BlogController::class, $match->class);
            $this->assertSame('indexTask', $match->method);
        }
    }

    /**
     * Lookup returns the registered object without the router base URI.
     */
    public function testGetRouteReturnsTheRegisteredRoute(): void {
        $router = $this->attributeRouter()->setBaseURI('/app');
        $route = $router->getRoute('blog-view');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame($route, $router->getRoute('blog-view'));
        $this->assertSame('blog-view', $route->getName());
        $this->assertSame('/blog/7', $route->getURL(['id' => 7]));
        $this->assertSame('/app/blog/7', $router->url('blog-view', ['id' => 7]));
    }

    /**
     * Unknown route lookup returns null rather than throwing.
     */
    public function testGetRouteReturnsNullForAnUnknownName(): void {
        $this->assertNull($this->attributeRouter()->getRoute('missing-route'));
    }

    /**
     * Matching activates the same route object exposed by lookup.
     */
    public function testMatchActivatesOnlyTheMatchedRoute(): void {
        $router = $this->attributeRouter();
        $route = $router->getRoute('blog-index');
        $otherRoute = $router->getRoute('blog-view');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertInstanceOf(Route::class, $otherRoute);
        $this->assertFalse($route->active);
        $this->assertNull($router->match($this->request('/nowhere')));
        $this->assertFalse($route->active);

        $match = $router->match($this->request('/blog'));

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertSame($route, $match->route);
        $this->assertTrue($route->active);
        $this->assertFalse($otherRoute->active);
    }

    public function testRoutesCanBeDefinedWithConfiguration(): void {
        $router = new Router([
            'backups' => [
                'route'  => [
                    'path'    => '/archive/{section}/year/{id<\d+>}',
                    'methods' => ['GET'],
                ],
                'class'  => BlogController::class,
                'method' => 'indexTask',
            ],
        ]);

        $this->assertSame('/archive/news/year/2026', $router->url('backups', [
            'section' => 'news',
            'id'      => 2026,
        ]));
    }

    public function testInvalidRouteThrows(): void {
        $this->expectException(InvalidRouteException::class);

        new Router(['\\No\\Such\\Controller']);
    }

    public function testUrlPrefixesTheBaseUri(): void {
        $router = $this->attributeRouter()
            ->setBaseURI('/app')
        ;

        $this->assertSame('/app/blog', $router->url('blog-index'));
    }

    public function testUrlThrowsForAnUnknownRoute(): void {
        $this->expectException(OutOfRangeException::class);

        $this->attributeRouter()
            ->url('missing-route')
        ;
    }

    public function testUrlThrowsWhenAParameterIsMissing(): void {
        $this->expectException(InvalidArgumentException::class);

        $this->attributeRouter()
            ->url('blog-article', ['section' => 'news'])
        ;
    }

    public function testUrlThrowsWhenAParameterFailsItsRegularExpression(): void {
        $this->expectException(InvalidArgumentException::class);

        $this->attributeRouter()
            ->url('blog-view', ['id' => 'abc'])
        ;
    }

    public function testRoutePropertyReturnsExtraInformation(): void {
        $router = $this->attributeRouter();

        $this->assertSame('book', $router->routeProperty('blog-index', 'icon'));
        $this->assertSame('/api/blog/7', $router->routeProperty('blog-index', 'api', ['id' => 7]));
    }

    public function testRoutePropertyThrowsForAnUnknownRoute(): void {
        $this->expectException(OutOfRangeException::class);

        $this->attributeRouter()
            ->routeProperty('missing-route', 'icon')
        ;
    }

    public function testMatchReturnsRouteMatchForAStaticPath(): void {
        $match = $this->attributeRouter()
            ->match($this->request('/blog'))
        ;

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertSame(BlogController::class, $match->class);
        $this->assertSame('indexTask', $match->method);
        $this->assertSame('/blog', $match->uri);
        $this->assertSame([], $match->params);
    }

    public function testMatchExtractsPathParameters(): void {
        $match = $this->attributeRouter()
            ->match($this->request('/blog/42'))
        ;

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertSame('viewTask', $match->method);
        $this->assertSame(['id' => '42'], $match->params);
    }

    public function testMatchFallsThroughToTheLessSpecificRoute(): void {
        $match = $this->attributeRouter()
            ->match($this->request('/blog/news'))
        ;

        $this->assertNull($match, 'A single non numeric segment matches no route.');
    }

    public function testMatchHonoursMultipleParameters(): void {
        $match = $this->attributeRouter()
            ->match($this->request('/blog/news/hello-world'))
        ;

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertSame('articleTask', $match->method);
        $this->assertSame([
            'section' => 'news',
            'slug'    => 'hello-world',
        ], $match->params);
    }

    public function testMatchRespectsTheHttpMethod(): void {
        $this->assertNull($this->attributeRouter()
            ->match($this->request('/blog', HttpMethod::Post)));
    }

    public function testMatchReturnsNullWhenNothingMatches(): void {
        $this->assertNull($this->attributeRouter()
            ->match($this->request('/nowhere')));
    }

    public function testMatchStripsTheBaseUriFromTheReportedUri(): void {
        $router = new Router([
            'home' => [
                'route'  => [
                    'path'    => '/app/blog',
                    'methods' => ['GET'],
                ],
                'class'  => BlogController::class,
                'method' => 'indexTask',
            ],
        ], '/app');

        $match = $router->match($this->request('/app/blog'));

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertSame('/blog', $match->uri);
    }

    public function testSplitQuerystringAddsTheQueryParameters(): void {
        $router = new Router([BlogController::class], splitQuerystring: true);

        $this->assertTrue($router->splitQuerystring);

        $match = $router->match($this->request('/blog?page=2&sort=desc'));

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertSame(['query-string'], array_keys($match->params));
        $this->assertSame([
            'page' => '2',
            'sort' => 'desc',
        ], $match->params['query-string']);
    }
}
