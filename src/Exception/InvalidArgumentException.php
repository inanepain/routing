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

use Inane\Stdlib\Exception\InvalidArgumentException as InaneInvalidArgumentException;

/**
 * Exception thrown if an argument is not of the expected type.
 *
 * @package Inane\Exception
 * @implements \Inane\Stdlib\Exception\ExceptionInterface
 * @version 0.3.0
 */
class InvalidArgumentException extends InaneInvalidArgumentException {
    // base code for invalid argument exceptions
    protected $code = 750;
}
