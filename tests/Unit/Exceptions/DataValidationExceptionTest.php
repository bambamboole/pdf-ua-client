<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Exceptions\DataValidationException;

it('is a runtime exception carrying an optional validation error', function (): void {
    $exception = new DataValidationException('Data failed schema validation');

    expect($exception)->toBeInstanceOf(RuntimeException::class)
        ->and($exception->getMessage())->toBe('Data failed schema validation')
        ->and($exception->error)->toBeNull();
});
