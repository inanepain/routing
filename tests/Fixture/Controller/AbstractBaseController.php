<?php

declare(strict_types = 1);

namespace Inane\Routing\Tests\Fixture\Controller;

use Inane\Routing\Route;

/**
 * AbstractBaseController
 *
 * Ignored by the collector's `glob_ignore` expression.
 */
abstract class AbstractBaseController {
    #[Route(path: '/abstract', name: 'abstract')]
    public function indexTask(): string {
        return 'abstract';
    }
}
