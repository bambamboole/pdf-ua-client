<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\KeyValueBlock;
use Bambamboole\PdfUaClient\Config\KeyValueConfig;
use Bambamboole\PdfUaClient\Config\KeyValueField;

it('renders a label/value table with rows for each field', function () {
    $block = new KeyValueBlock(values: [
        'iban' => 'DE89 3704 0044 0532 0130 00',
        'bic' => 'COBADEFFXXX',
    ]);

    $html = (string) $block->render(new KeyValueConfig(fields: [
        new KeyValueField(key: 'iban', label: 'IBAN'),
        new KeyValueField(key: 'bic', label: 'BIC'),
    ]));

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
        new KeyValueField(key: 'invoiceNumber', label: 'Invoice number'),
        new KeyValueField(key: 'issueDate', label: 'Issue date'),
    ]));

    expect($html)->toContain('Invoice number')
        ->and($html)->toContain('RE-2026-001234')
        ->and($html)->toContain('Issue date')
        ->and($html)->toContain('2026-02-17')
        ->and($html)->not->toContain('invoiceNumber');
});
