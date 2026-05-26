<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\KeyValueBlock;
use Bambamboole\PdfUaClient\Blocks\KeyValuePair;
use Bambamboole\PdfUaClient\Config\KeyValueConfig;

it('renders a label/value table with rows for each pair', function () {
    $block = new KeyValueBlock(entries: [
        new KeyValuePair(label: 'IBAN', value: 'DE89 3704 0044 0532 0130 00'),
        new KeyValuePair(label: 'BIC', value: 'COBADEFFXXX'),
    ]);

    $html = (string) $block->render(new KeyValueConfig);

    expect($html)->toContain('<table class="key-value">');
    expect($html)->toContain('IBAN');
    expect($html)->toContain('DE89 3704 0044 0532 0130 00');
    expect($html)->toContain('BIC');
});

it('renders configured fields from flat keyed values', function () {
    $block = new KeyValueBlock(values: [
        'invoiceNumber' => 'RE-2026-001234',
        'issueDate' => '2026-02-17',
    ]);

    $html = (string) $block->render(new KeyValueConfig(fields: [
        ['key' => 'invoiceNumber', 'label' => 'Invoice number'],
        ['key' => 'issueDate', 'label' => 'Issue date'],
    ]));

    expect($html)->toContain('Invoice number')
        ->and($html)->toContain('RE-2026-001234')
        ->and($html)->toContain('Issue date')
        ->and($html)->toContain('2026-02-17')
        ->and($html)->not->toContain('invoiceNumber');
});
