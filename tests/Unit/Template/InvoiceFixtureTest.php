<?php

declare(strict_types=1);

use Workbench\App\Support\TemplateFixtureRepository;

it('models a complete realistic invoice document structure', function (): void {
    $fixture = collect(app(TemplateFixtureRepository::class)->examples())->firstWhere('slug', 'invoice');
    expect($fixture)->not->toBeNull();

    $document = $fixture->template;
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
        'lineItems',
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
    expect($blocks->get('lineItems')['config'])->toMatchArray([
        'style' => 'striped',
        'numberRows' => true,
        'columns' => [
            ['key' => 'description', 'label' => 'Description', 'align' => 'left', 'width' => '38%'],
            ['key' => 'quantity', 'label' => 'Qty', 'align' => 'right', 'width' => '12%'],
            ['key' => 'unitPrice', 'label' => 'Unit price', 'align' => 'right', 'width' => '16%'],
            ['key' => 'vatRate', 'label' => 'VAT', 'align' => 'right', 'width' => '11%'],
            ['key' => 'total', 'label' => 'Total', 'align' => 'right', 'width' => '16%'],
        ],
    ]);
    expect($blocks->get('invoice-meta')['config']['fields'])->toContain(
        ['key' => 'invoiceNumber', 'label' => 'Invoice number'],
        ['key' => 'currency', 'label' => 'Currency'],
    );
    expect($footerBlocks->get('footer-meta')['config']['fields'])->toContain(
        ['key' => 'registration', 'label' => 'Registry'],
        ['key' => 'taxNumber', 'label' => 'Tax no.'],
    );
    expect($document['data']['example'])->toHaveKeys(['invoice-meta', 'buyer', 'lineItems', 'vat-breakdown', 'totals', 'payment']);
    expect($document['data']['defaults'])->toHaveKeys(['notice', 'payment']);
    expect($document['data']['constants'])->toHaveKeys(['logo', 'seller', 'invoice-meta', 'footer-legal', 'footer-meta']);
    expect($document['attachments'][0])->toMatchArray([
        'name' => 'factur-x.xml',
        'mimeType' => 'application/xml',
        'description' => 'Factur-X invoice data',
        'relationship' => 'Alternative',
    ]);
});

it('provides realistic runtime example data without locked constants', function (): void {
    $fixture = collect(app(TemplateFixtureRepository::class)->examples())->firstWhere('slug', 'invoice');
    expect($fixture)->not->toBeNull();

    $data = $fixture->data;

    expect($data)->not->toHaveKeys(['logo', 'seller', 'footer-legal', 'footer-meta']);
    expect($data['buyer'])->toMatchArray([
        'name' => 'Musterkunde AG',
        'reference' => '04011000-12345-67',
    ]);
    expect($data['lineItems'])->toHaveCount(4);
    expect($data['lineItems'][0])->toMatchArray([
        'description' => 'Accessible PDF template implementation',
        'quantity' => '40',
    ]);
    expect($data['vat-breakdown'][0])->toMatchArray([
        'vatCategory' => 'Standard rate',
        'rate' => '19%',
    ]);
    expect($data['totals'])->toMatchArray([
        'netAmount' => '6.120,00 €',
        'amountDue' => '7.282,80 €',
    ]);
    expect($data['payment'])->toMatchArray([
        'reference' => 'RE-2026-001234',
    ]);
    expect($data['invoice-meta'])->toMatchArray([
        'invoiceNumber' => 'RE-2026-001234',
    ]);
});

it('models a dunning notice example document structure', function (): void {
    $fixture = collect(app(TemplateFixtureRepository::class)->examples())->firstWhere('slug', 'dunning-notice');

    expect($fixture)->not->toBeNull()
        ->and($fixture->title)->toBe('Dunning Notice')
        ->and($fixture->template['config']['page']['format'])->toBe('A4')
        ->and($fixture->template['config']['page']['locale'])->toBe('de_DE');

    $blocks = collect($fixture->template['rows'])
        ->flatMap(fn (array $row): array => $row['blocks'])
        ->keyBy('id');

    expect($blocks->keys()->all())->toContain('creditor', 'debtor', 'notice-meta', 'overdue-invoices', 'payment', 'notice-text');

    $columns = collect($blocks->get('overdue-invoices')['config']['columns'])->keyBy('key');

    expect($columns->keys()->all())->toBe(['invoiceNumber', 'issueDate', 'dueDate', 'daysOverdue', 'amount']);
    expect($columns->get('invoiceNumber'))->toMatchArray(['label' => 'Invoice no.', 'align' => 'left'])
        ->and($columns->get('issueDate'))->toMatchArray(['label' => 'Issue date', 'align' => 'left'])
        ->and($columns->get('dueDate'))->toMatchArray(['label' => 'Due date', 'align' => 'left'])
        ->and($columns->get('daysOverdue'))->toMatchArray(['label' => 'Days overdue', 'align' => 'right'])
        ->and($columns->get('amount'))->toMatchArray(['label' => 'Amount', 'align' => 'right']);

    expect($fixture->data)->toHaveKeys(['debtor', 'notice-meta', 'overdue-invoices', 'payment'])
        ->and($fixture->data['notice-meta'])->toMatchArray([
            'noticeNumber' => 'MA-2026-00018',
            'noticeDate' => '2026-04-24',
        ])
        ->and($fixture->data['overdue-invoices'])->toHaveCount(2)
        ->and($fixture->data['payment'])->toMatchArray([
            'reference' => 'MA-2026-00018',
            'amountDue' => '4.831,40 €',
        ]);
});

it('models a shipping label example document structure', function (): void {
    $fixture = collect(app(TemplateFixtureRepository::class)->examples())->firstWhere('slug', 'shipping-label');

    expect($fixture)->not->toBeNull()
        ->and($fixture->title)->toBe('Shipping Label')
        ->and($fixture->template['config']['page']['format'])->toBe('ParcelLabel4x6')
        ->and($fixture->template['config']['page']['pageNumbers']['enabled'])->toBeFalse();

    $blocks = collect($fixture->template['rows'])
        ->flatMap(fn (array $row): array => $row['blocks'])
        ->keyBy('id');

    expect($blocks->keys()->all())->toContain('carrier', 'service', 'recipient', 'sender', 'tracking', 'routing-code', 'barcode')
        ->and($blocks->get('barcode')['type'])->toBe('image');

    expect($fixture->data)->toHaveKeys(['recipient', 'sender', 'service', 'tracking', 'routing-code'])
        ->and($fixture->data['tracking'])->toMatchArray([
            'number' => '1Z999AA10123456784',
        ]);
});

it('models a packing slip example document structure', function (): void {
    $fixture = collect(app(TemplateFixtureRepository::class)->examples())->firstWhere('slug', 'packing-slip');

    expect($fixture)->not->toBeNull()
        ->and($fixture->title)->toBe('Packing Slip')
        ->and($fixture->template['config']['page']['format'])->toBe('Letter')
        ->and($fixture->template['config']['page']['locale'])->toBe('en_US')
        ->and($fixture->template['config']['typography'])->toBe(['family' => 'Inter', 'size' => 10]);

    $blocks = collect($fixture->template['rows'])
        ->flatMap(fn (array $row): array => $row['blocks'])
        ->keyBy('id');

    expect($blocks->keys()->all())->toContain('warehouse', 'ship-to', 'order-meta', 'items', 'package-summary', 'notes');

    $columns = collect($blocks->get('items')['config']['columns'])->keyBy('key');

    expect($columns->keys()->all())->toBe(['sku', 'description', 'ordered', 'shipped', 'backordered']);
    expect($columns->get('sku'))->toMatchArray(['label' => 'SKU', 'align' => 'left'])
        ->and($columns->get('description'))->toMatchArray(['label' => 'Description', 'align' => 'left'])
        ->and($columns->get('ordered'))->toMatchArray(['label' => 'Ordered', 'align' => 'right'])
        ->and($columns->get('shipped'))->toMatchArray(['label' => 'Shipped', 'align' => 'right'])
        ->and($columns->get('backordered'))->toMatchArray(['label' => 'Backordered', 'align' => 'right']);

    expect($fixture->data)->toHaveKeys(['ship-to', 'order-meta', 'items', 'package-summary', 'notes'])
        ->and($fixture->data['ship-to'])->toMatchArray([
            'name' => 'Nina Patel',
            'company' => 'Northwind Field Services',
        ])
        ->and($fixture->data['order-meta'])->toMatchArray([
            'orderNumber' => 'SO-2026-10482',
        ])
        ->and($fixture->data['items'])->toHaveCount(3)
        ->and($fixture->data['items'][2])->toMatchArray([
            'sku' => 'KIT-ADP-014',
            'backordered' => '1',
        ])
        ->and($fixture->data['package-summary'])->toMatchArray([
            'packages' => '2 cartons',
            'totalWeight' => '8.6 kg',
        ]);
});
