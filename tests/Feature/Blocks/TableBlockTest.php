<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\TableBlock;
use Bambamboole\PdfUaClient\Config\TableColumn;
use Bambamboole\PdfUaClient\Config\TableConfig;
use Bambamboole\PdfUaClient\Enums\Align;

it('renders configured columns and object rows inside a <table>', function () {
    $block = new TableBlock(
        rows: [
            ['description' => 'Service', 'quantity' => '1', 'total' => '100,00 €'],
            ['description' => 'Other', 'quantity' => '2', 'total' => '200,00 €'],
        ],
    );

    $html = (string) $block->render(new TableConfig(columns: [
        new TableColumn(key: 'description', label: 'Description'),
        new TableColumn(key: 'quantity', label: 'Qty'),
        new TableColumn(key: 'total', label: 'Total'),
    ]));

    expect($html)->toContain('<table class="data-table">');
    expect($html)->toContain('<thead>');
    expect($html)->toContain('<th>Description</th>');
    expect($html)->toContain('<td>Service</td>');
    expect($html)->toContain('100,00');
});

it('keeps per-cell text-align inline (per-column, not consolidatable into the id)', function () {
    $block = new TableBlock(
        rows: [['first' => '1', 'second' => '2']],
    );

    $html = (string) $block->render(new TableConfig(
        columns: [
            new TableColumn(key: 'first', label: 'A', align: Align::Left),
            new TableColumn(key: 'second', label: 'B', align: Align::Right),
        ],
    ));

    expect($html)->toContain('text-align: right');
    expect($html)->toContain('text-align: left');
});

it('renders configured column widths from the column definition', function (): void {
    $block = new TableBlock(
        rows: [['sku' => 'A-100', 'description' => 'Accessible PDF setup']],
    );

    $html = (string) $block->render(new TableConfig(columns: [
        new TableColumn(key: 'sku', label: 'SKU', width: '7%'),
        new TableColumn(key: 'description', label: 'Description', width: '38%'),
    ]));

    expect($html)->toContain('<col style="width: 7%;">')
        ->and($html)->toContain('<col style="width: 38%;">');
});

it('can render an auto incrementing row number column', function (): void {
    $block = new TableBlock(
        rows: [
            ['description' => 'Accessible PDF setup'],
            ['description' => 'Structure review'],
        ],
    );

    $html = (string) $block->render(new TableConfig(
        columns: [new TableColumn(key: 'description', label: 'Description')],
        numberRows: true,
    ));

    expect($html)->toContain('>#</th>')
        ->and($html)->toContain('>1</td>')
        ->and($html)->toContain('>2</td>');
});

it('renders configured columns from object rows', function (): void {
    $block = new TableBlock(
        rows: [
            ['sku' => 'A-100', 'description' => 'Accessible PDF setup', 'quantity' => '2'],
            ['sku' => 'B-200', 'description' => 'Structure review', 'quantity' => '1'],
        ],
    );

    $html = (string) $block->render(new TableConfig(columns: [
        new TableColumn(key: 'sku', label: 'SKU'),
        new TableColumn(key: 'description', label: 'Description'),
        new TableColumn(key: 'quantity', label: 'Qty'),
    ]));

    expect($html)->toContain('<th>SKU</th>')
        ->and($html)->toContain('<td>A-100</td>')
        ->and($html)->toContain('<td>Accessible PDF setup</td>')
        ->and($html)->toContain('<td>2</td>');
});
