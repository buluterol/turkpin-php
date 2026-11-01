<?php

declare(strict_types=1);

namespace Turkpin;

/**
 * Thrown when the API answers with an error code other than "000",
 * or when the HTTP request / XML response cannot be processed.
 */
class TurkpinException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $apiCode = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
