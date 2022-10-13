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

/**
 * Exception thrown when variable is not a Route
 *
 * @package Inane\Routing\Exception
 *
 * @implements \Inane\Stdlib\Exception\ExceptionInterface
 *
 * @version 0.1.0
 */
class InvalidRouteException extends InvalidArgumentException {
    // base code for invalid argument exceptions
    protected $code = 755;
}
