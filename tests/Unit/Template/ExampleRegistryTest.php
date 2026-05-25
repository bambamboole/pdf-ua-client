<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Template\ExampleRegistry;

it('registers, lists, and flushes example documents', function (): void {
    $registry = new ExampleRegistry;
    $registry->register(['title' => 'A', 'version' => 1, 'config' => [], 'rows' => []]);
    expect($registry->all())->toHaveCount(1);
    $registry->flush()->register(['title' => 'B', 'version' => 1, 'config' => [], 'rows' => []]);
    expect($registry->all())->toHaveCount(1)
        ->and($registry->all()[0]['title'])->toBe('B');
});
