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

namespace Inane\Routing\Exception;

use Inane\Stdlib\Exception\OutOfRangeException as InaneOutOfRangeException;

/**
 * Exception thrown when an illegal index was requested. This represents errors that should be detected at compile time.
 *
 * @package Inane\Routing
 * @implements \Inane\Stdlib\Exception\ExceptionInterface
 * @version 0.1.0
 */
class OutOfRangeException extends InaneOutOfRangeException {
}
