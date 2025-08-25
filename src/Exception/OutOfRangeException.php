<?php

/**
 * Inane: Routing
 *
 * HTTP Routing using attributes.
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.4
 *
 * @author Philip Michael Raab<philip@cathedral.co.za>
 * @package inanepain\routing
 * @category routing
 *
 * @license UNLICENSE
 * @license https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types=1);

namespace Inane\Routing\Exception;

use Inane\Stdlib\Exception\OutOfRangeException as InaneOutOfRangeException;

/**
 * Exception thrown when an illegal index was requested. This represents errors that should be detected at compile time.
 *
 * @implements \Inane\Stdlib\Exception\ExceptionInterface
 * @version 0.1.0
 */
class OutOfRangeException extends InaneOutOfRangeException {
}
