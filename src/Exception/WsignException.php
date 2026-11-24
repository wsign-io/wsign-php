<?php

declare(strict_types=1);

namespace Wsign\Exception;

/**
 * Base class for runtime errors raised by the library.
 */
class WsignException extends \RuntimeException implements ExceptionInterface
{
}
