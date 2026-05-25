<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Http\Exceptions;

use RuntimeException;
use Throwable;

class PdfApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $responseBody,
        public readonly string $endpoint,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
