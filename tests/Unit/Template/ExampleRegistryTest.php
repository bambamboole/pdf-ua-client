<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Template\ExampleRegistry;

it('registers titled template+data entries, lists, and flushes', function (): void {
    $registry = new ExampleRegistry;
    $registry->register('A', ['version' => 1, 'config' => [], 'rows' => []], ['x' => ['text' => 'hi']]);
    expect($registry->all())->toHaveCount(1)
        ->and($registry->all()[0]['title'])->toBe('A')
        ->and($registry->all()[0]['data'])->toBe(['x' => ['text' => 'hi']]);

    $registry->flush()->register('B', ['version' => 1, 'config' => [], 'rows' => []]);
    expect($registry->all())->toHaveCount(1)
        ->and($registry->all()[0]['title'])->toBe('B')
        ->and($registry->all()[0]['data'])->toBe([]);
});
