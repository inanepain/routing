<?php

declare(strict_types = 1);

namespace Inane\Routing\Tests\Fixture\Controller;

use Inane\Http\HttpMethod;
use Inane\Routing\Route;

/**
 * ShopController
 *
 * Second controller fixture, used by the collector tests.
 */
class ShopController {
    #[Route(path: '/shop', name: 'shop-index', methods: [HttpMethod::Get, HttpMethod::Post])]
    public function indexTask(): string {
        return 'shop';
    }
}
