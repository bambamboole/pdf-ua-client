<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Examples\InvoiceExample;

it('models a complete realistic invoice document structure', function () {
    $document = InvoiceExample::document();
    $blocks = collect($document['rows'])
        ->flatMap(fn (array $row): array => $row['blocks'])
        ->keyBy('id');

    expect($document['config']['page'])->toMatchArray([
        'format' => 'A4',
        'locale' => 'de_DE',
        'margins' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 25],
        'pageNumbers' => ['enabled' => true, 'position' => 'center'],
    ]);
    expect($document['config']['typography'])->toBe(['family' => 'Inter', 'size' => 10]);
    expect($blocks->keys()->all())->toContain(
        'seller',
        'buyer',
        'invoice-meta',
        'items',
        'vat-breakdown',
        'totals',
        'payment',
        'footer',
    );
    expect($blocks->get('items')['config'])->toMatchArray([
        'style' => 'striped',
        'columnAlignments' => ['center', 'left', 'right', 'right', 'right', 'right'],
    ]);
});

it('provides realistic seller, buyer, line items, totals, and payment data', function () {
    $data = InvoiceExample::data();

    expect($data['seller']['entries'])->toContain(
        ['label' => 'Seller', 'value' => 'PDF UA Kit GmbH'],
        ['label' => 'VAT ID', 'value' => 'DE123456789'],
    );
    expect($data['buyer']['entries'])->toContain(
        ['label' => 'Buyer', 'value' => 'Musterkunde AG'],
        ['label' => 'Buyer reference', 'value' => '04011000-12345-67'],
    );
    expect($data['items']['headers'])->toBe(['#', 'Description', 'Qty', 'Unit price', 'VAT', 'Total']);
    expect($data['items']['rows'])->toHaveCount(4);
    expect($data['vat-breakdown']['headers'])->toBe(['VAT category', 'Rate', 'Taxable amount', 'VAT amount']);
    expect($data['totals']['entries'])->toContain(
        ['label' => 'Net amount', 'value' => '6.120,00 €'],
        ['label' => 'Amount due', 'value' => '7.282,80 €'],
    );
    expect($data['payment']['entries'])->toContain(
        ['label' => 'IBAN', 'value' => 'DE89370400440532013000'],
        ['label' => 'Payment reference', 'value' => 'RE-2026-001234'],
    );
    expect($data['footer']['text'])->toContain('PDF UA Kit GmbH');
});
