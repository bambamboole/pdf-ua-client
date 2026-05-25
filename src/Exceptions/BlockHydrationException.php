<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Exceptions;

use RuntimeException;
use Throwable;

final class BlockHydrationException extends RuntimeException
{
    public static function forBlock(string $blockClass, string $reason, ?Throwable $previous = null): self
    {
        return new self("Failed to hydrate {$blockClass}: {$reason}", 0, $previous);
    }
}
