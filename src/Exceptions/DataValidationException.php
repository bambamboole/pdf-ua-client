<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Exceptions;

use Opis\JsonSchema\Errors\ValidationError;
use RuntimeException;

final class DataValidationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?ValidationError $error = null,
    ) {
        parent::__construct($message);
    }
}
