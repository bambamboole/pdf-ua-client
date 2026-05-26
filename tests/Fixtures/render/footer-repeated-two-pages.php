<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

$lineItems = array_map(
    static fn (int $i): array => [
        'Service line '.$i,
        'Detailed project delivery item for page flow verification.',
        '1',
        number_format(125 + $i, 2, ',', '.').' EUR',
    ],
    range(1, 42),
);

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => [
            'page' => [
                'format' => 'A4',
                'locale' => 'de_DE',
                'margins' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 25],
                'pageNumbers' => ['enabled' => true, 'position' => 'right'],
                'footer' => [
                    'repeat' => true,
                    'rows' => [[
                        'blocks' => [
                            ['type' => 'text', 'id' => 'footer_note', 'config' => ['width' => '70%']],
                            ['type' => 'text', 'id' => 'footer_meta', 'config' => ['width' => '30%', 'typography' => ['align' => 'right']]],
                        ],
                    ]],
                ],
            ],
            'typography' => ['family' => 'Inter', 'size' => 9],
        ],
        'rows' => [
            ['blocks' => [[
                'type' => 'heading',
                'id' => 'title',
                'config' => ['level' => 1],
            ]]],
            ['blocks' => [[
                'type' => 'text',
                'id' => 'intro',
            ]]],
            ['blocks' => [[
                'type' => 'table',
                'id' => 'items',
                'config' => [
                    'style' => 'striped',
                    'columnAlignments' => ['left', 'left', 'right', 'right'],
                ],
            ]]],
        ],
    ],
    data: [
        'title' => ['text' => 'Repeated Footer Stress Invoice'],
        'intro' => ['text' => 'This fixture intentionally spans multiple pages so the footer repeat behavior is covered by visual PDF comparison.'],
        'items' => [
            'headers' => ['Item', 'Description', 'Qty', 'Total'],
            'rows' => $lineItems,
        ],
        'footer_note' => ['text' => 'PDF UA Kit GmbH · Musterstrasse 1 · 10115 Berlin'],
        'footer_meta' => ['text' => 'Page footer'],
    ],
    pdf: 'footer-repeated-two-pages.pdf',
    html: null,
);
