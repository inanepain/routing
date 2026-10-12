<?php

declare(strict_types = 1);

namespace Inane\Routing\Tests;

use Inane\Http\HttpMethod;
use Inane\Routing\Route;
use PHPUnit\Framework\TestCase;

/**
 * RouteTest
 */
final class RouteTest extends TestCase {
    public function testNameDefaultsToPath(): void {
        $route = new Route('/blog');

        $this->assertSame('/blog', $route->getPath());
        $this->assertSame('/blog', $route->getName());
    }

    public function testNameIsKeptWhenSupplied(): void {
        $route = new Route(path: '/blog', name: 'blog-index');

        $this->assertSame('blog-index', $route->getName());
    }

    public function testDefaultMethodsCoverTheCommonVerbs(): void {
        $route = new Route('/blog');

        $this->assertSame([
            HttpMethod::Get,
            HttpMethod::Post,
            HttpMethod::Put,
            HttpMethod::Delete,
            HttpMethod::Patch,
            HttpMethod::Options,
        ], $route->getMethods());
    }

    public function testStringMethodsAreConvertedToEnumCases(): void {
        $route = new Route(path: '/blog', methods: ['GET', 'POST']);

        $this->assertSame([HttpMethod::Get, HttpMethod::Post], $route->getMethods());
    }

    public function testFetchParamsReturnsNamesAndRegularExpressions(): void {
        $route = new Route('/archive/{section}/year/{id<\d+>}');

        $this->assertSame([
            'section' => '',
            'id'      => '\d+',
        ], $route->fetchParams());
    }

    public function testFetchParamsIsEmptyForAStaticPath(): void {
        $route = new Route('/blog');

        $this->assertSame([], $route->fetchParams());
    }

    public function testPropertyReturnsExtraValue(): void {
        $route = new Route(path: '/blog', extra: ['icon' => 'book']);

        $this->assertSame('book', $route->property('icon'));
    }

    public function testPropertyPopulatesTemplatePlaceholders(): void {
        $route = new Route(path: '/blog', extra: ['api' => '/api/blog/{id}']);

        $this->assertSame('/api/blog/42', $route->property('api', ['id' => 42]));
    }

    public function testPropertyReturnsEmptyStringForUnknownName(): void {
        $route = new Route(path: '/blog', extra: ['icon' => 'book']);

        $this->assertSame('', $route->property('missing'));
    }

    public function testDefaultRegexConstant(): void {
        $this->assertSame('[\w\-\.]+', Route::DEFAULT_REGEX);
    }
}
