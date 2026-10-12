<?php

declare(strict_types = 1);

namespace Inane\Routing\Tests\Fixture\Controller;

use Inane\Http\HttpMethod;
use Inane\Routing\Route;

/**
 * BlogController
 *
 * Controller fixture carrying `Route` attributes.
 */
class BlogController {
    #[Route(path: '/blog', name: 'blog-index', label: 'Blog', methods: [HttpMethod::Get], extra: [
        'icon' => 'book',
        'api'  => '/api/blog/{id}',
    ])]
    public function indexTask(): string {
        return 'index';
    }

    #[Route(path: '/blog/{id<\d+>}', name: 'blog-view', methods: [HttpMethod::Get])]
    public function viewTask(): string {
        return 'view';
    }

    #[Route(path: '/blog/{section}/{slug}', name: 'blog-article')]
    public function articleTask(): string {
        return 'article';
    }

    public function notARouteTask(): string {
        return 'none';
    }
}
