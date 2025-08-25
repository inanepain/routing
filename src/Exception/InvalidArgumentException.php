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

use Inane\Stdlib\Exception\InvalidArgumentException as InaneInvalidArgumentException;

/**
 * Exception thrown if an argument is not of the expected type.
 *
 * @implements \Inane\Stdlib\Exception\ExceptionInterface
 * @version 0.3.0
 */
class InvalidArgumentException extends InaneInvalidArgumentException {
    // base code for invalid argument exceptions
    protected $code = 750;
}
