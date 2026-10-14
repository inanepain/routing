<?php

declare(strict_types = 1);

namespace Inane\Routing\Tests;

use Inane\Http\HttpMethod;
use Inane\Routing\Exception\InvalidArgumentException;
use Inane\Routing\Route;
use PHPUnit\Framework\TestCase;

/**
 * RouteTest
 */
final class RouteTest extends TestCase {
    /**
     * Static paths ignore unused URL parameters.
     */
    public function testGetUrlReturnsStaticPath(): void {
        $route = new Route('/blog');

        $this->assertSame('/blog', $route->getURL(['unused' => 'value']));
    }

    /**
     * URL generation substitutes default and constrained parameters.
     */
    public function testGetUrlSubstitutesParametersWithoutChangingThePath(): void {
        $route = new Route('/archive/{section}/year/{id<\d+>}');

        $this->assertSame('/archive/news.item-1/year/0', $route->getURL(['section' => 'news.item-1', 'id' => 0]));
        $this->assertSame('/archive/sport/year/2026', $route->getURL(['section' => 'sport', 'id' => 2026]));
        $this->assertSame('/archive/{section}/year/{id<\d+>}', $route->getPath());
    }

    /**
     * Missing parameters identify the route and missing names.
     */
    public function testGetUrlRejectsMissingParameters(): void {
        $route = new Route(path: '/blog/{section}/{slug}', name: 'article');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The following parameters are missing for generating the route "article": section, slug.');

        $route->getURL();
    }

    /**
     * Explicit constraints validate the complete parameter value.
     */
    public function testGetUrlRejectsValuesOutsideTheConstraint(): void {
        $route = new Route('/blog/{id<\d+>}');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "id" route parameter value given does not match the regular expression.');

        $route->getURL(['id' => '42abc']);
    }

    /**
     * Default constraints reject path separators.
     */
    public function testGetUrlRejectsMultiplePathSegments(): void {
        $route = new Route('/blog/{slug}');

        $this->expectException(InvalidArgumentException::class);

        $route->getURL(['slug' => 'news/article']);
    }

    /**
     * Default constraints reject empty values.
     */
    public function testGetUrlRejectsEmptyParameters(): void {
        $route = new Route('/blog/{slug}');

        $this->expectException(InvalidArgumentException::class);

        $route->getURL(['slug' => '']);
    }

    /**
     * Active state starts false and can be reset explicitly.
     */
    public function testActiveStateIsWritable(): void {
        $route = new Route('/blog');

        $this->assertFalse($route->active);
        $route->active = true;
        $this->assertTrue($route->active);
        $route->active = false;
        $this->assertFalse($route->active);
    }

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
