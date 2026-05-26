<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Examples\InvoiceExample;

it('models a complete realistic invoice document structure', function () {
    $document = InvoiceExample::document();
    $blocks = collect($document['rows'])
        ->flatMap(fn (array $row): array => $row['blocks'])
        ->keyBy('id');
    $footerBlocks = collect($document['config']['page']['footer']['rows'])
        ->flatMap(fn (array $row): array => $row['blocks'])
        ->keyBy('id');

    expect($document['config']['page']['format'])->toBe('A4')
        ->and($document['config']['page']['locale'])->toBe('de_DE')
        ->and($document['config']['page']['margins'])->toBe(['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 25])
        ->and($document['config']['page']['pageNumbers'])->toBe(['enabled' => true, 'position' => 'center'])
        ->and($document['config']['page']['footer']['repeat'])->toBeTrue();
    expect($document['config']['typography'])->toBe(['family' => 'Inter', 'size' => 10]);
    expect($blocks->keys()->all())->toContain(
        'logo',
        'seller',
        'buyer',
        'invoice-meta',
        'items',
        'vat-breakdown',
        'totals',
        'payment',
    );
    expect($footerBlocks->keys()->all())->toBe(['footer-legal', 'footer-meta']);
    expect($blocks->get('logo'))->toMatchArray([
        'type' => 'image',
        'id' => 'logo',
        'config' => ['width' => '58%', 'maxHeight' => 28],
    ]);
    expect($blocks->get('items')['config'])->toMatchArray([
        'style' => 'striped',
        'columnAlignments' => ['center', 'left', 'right', 'right', 'right', 'right'],
    ]);
    expect($blocks->get('invoice-meta')['config']['fields'])->toContain(
        ['key' => 'invoiceNumber', 'label' => 'Invoice number'],
        ['key' => 'currency', 'label' => 'Currency'],
    );
    expect($footerBlocks->get('footer-meta')['config']['fields'])->toContain(
        ['key' => 'registration', 'label' => 'Registry'],
        ['key' => 'taxNumber', 'label' => 'Tax no.'],
    );
    expect($document['data']['example'])->toHaveKeys(['invoice-meta', 'buyer', 'items', 'vat-breakdown', 'totals', 'payment']);
    expect($document['data']['defaults'])->toHaveKeys(['notice', 'payment']);
    expect($document['data']['constants'])->toHaveKeys(['logo', 'seller', 'invoice-meta', 'footer-legal', 'footer-meta']);
});

it('provides realistic merged seller, buyer, line items, totals, payment, and footer data', function () {
    $data = InvoiceExample::data();

    expect($data['logo']['src'])->toStartWith('data:image/');
    expect($data['logo']['alt'])->toBe('PDF UA Kit GmbH logo');
    expect($data['seller'])->toMatchArray([
        'name' => 'PDF UA Kit GmbH',
        'vatId' => 'DE123456789',
    ]);
    expect($data['buyer'])->toMatchArray([
        'name' => 'Musterkunde AG',
        'reference' => '04011000-12345-67',
    ]);
    expect($data['items']['headers'])->toBe(['#', 'Description', 'Qty', 'Unit price', 'VAT', 'Total']);
    expect($data['items']['rows'])->toHaveCount(4);
    expect($data['vat-breakdown']['headers'])->toBe(['VAT category', 'Rate', 'Taxable amount', 'VAT amount']);
    expect($data['totals'])->toMatchArray([
        'netAmount' => '6.120,00 €',
        'amountDue' => '7.282,80 €',
    ]);
    expect($data['payment'])->toMatchArray([
        'bank' => 'Musterbank Berlin',
        'iban' => 'DE89370400440532013000',
        'reference' => 'RE-2026-001234',
    ]);
    expect($data['invoice-meta'])->toMatchArray([
        'invoiceNumber' => 'RE-2026-001234',
        'currency' => 'EUR',
    ]);
    expect($data['footer-legal']['text'])->toContain('PDF UA Kit GmbH');
    expect($data['footer-meta'])->toMatchArray([
        'registration' => 'HRB 123456 B',
        'taxNumber' => 'DE123456789',
    ]);
});
