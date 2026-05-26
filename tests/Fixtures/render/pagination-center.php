<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

$lineItems = array_map(
    static fn (int $i): array => [
        'Line '.$i,
        'Pagination placement verification item.',
        number_format(75 + $i, 2, ',', '.').' EUR',
    ],
    range(1, 54),
);

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => [
            'page' => [
                'format' => 'A4',
                'pageNumbers' => ['enabled' => true, 'position' => 'center'],
            ],
        ],
        'rows' => [
            ['blocks' => [[
                'type' => 'heading',
                'id' => 'title',
                'config' => ['level' => 1],
            ]]],
            ['blocks' => [[
                'type' => 'table',
                'id' => 'items',
                'config' => [
                    'style' => 'striped',
                    'columnAlignments' => ['left', 'left', 'right'],
                ],
            ]]],
        ],
    ],
    data: [
        'title' => ['text' => 'Pagination Center'],
        'items' => [
            'headers' => ['Item', 'Description', 'Amount'],
            'rows' => $lineItems,
        ],
    ],
    pdf: 'pagination-center.pdf',
    html: null,
);
