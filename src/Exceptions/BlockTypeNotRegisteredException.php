<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Exceptions;

use RuntimeException;

final class BlockTypeNotRegisteredException extends RuntimeException
{
    public static function forType(string $type): self
    {
        return new self("No block registered for type identifier: {$type}");
    }
}
