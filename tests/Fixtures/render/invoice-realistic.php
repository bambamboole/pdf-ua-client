<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Examples\InvoiceExample;
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

$logoSrc = InvoiceExample::data()['logo']['src'];

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => [
            'page' => [
                'format' => 'A4',
                'locale' => 'de_DE',
                'margins' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 25],
                'pageNumbers' => ['enabled' => true, 'position' => 'center'],
            ],
            'typography' => ['family' => 'Inter', 'size' => 10],
        ],
        'rows' => [
            ['blocks' => [
                [
                    'type' => 'image',
                    'id' => 'logo',
                    'config' => ['width' => '58%', 'maxHeight' => 28],
                ],
                ['type' => 'key-value', 'id' => 'meta', 'config' => ['width' => '42%', 'align' => 'right']],
            ]],
            [
                'blocks' => [
                    ['type' => 'text', 'id' => 'addr', 'config' => ['width' => '60%']],
                    ['type' => 'text', 'id' => 'intro', 'config' => ['width' => '40%', 'align' => 'right']],
                ],
            ],
            ['blocks' => [
                [
                    'type' => 'table',
                    'id' => 'items',
                    'config' => [
                        'style' => 'striped',
                        'columnAlignments' => ['left', 'right', 'right', 'right'],
                    ],
                ],
            ]],
            ['blocks' => [
                ['type' => 'divider'],
            ]],
            [
                'blocks' => [
                    ['type' => 'spacer', 'config' => ['width' => '60%']],
                    ['type' => 'key-value', 'id' => 'totals', 'config' => ['width' => '40%']],
                ],
            ],
            ['blocks' => [
                [
                    'type' => 'text',
                    'id' => 'footer',
                    'config' => ['spacing' => ['top' => 4]],
                ],
            ]],
        ],
    ],
    data: [
        'logo' => [
            'src' => $logoSrc,
            'alt' => 'PDF UA Kit GmbH logo',
        ],
        'addr' => ['text' => "Muster GmbH\n\nBeispielstraße 1\n\n10115 Berlin"],
        'intro' => ['text' => 'Invoice 2026-001'],
        'meta' => ['entries' => [
            ['label' => 'Invoice number', 'value' => 'RE-2026-001234'],
            ['label' => 'Issue date', 'value' => '2026-02-17'],
            ['label' => 'Due date', 'value' => '2026-03-19'],
        ]],
        'items' => [
            'headers' => ['Description', 'Qty', 'Unit price', 'Total'],
            'rows' => [
                ['Web development', '40', '95,00 €', '3.800,00 €'],
                ['UI/UX design', '16', '85,00 €', '1.360,00 €'],
                ['Project management', '8', '90,00 €', '720,00 €'],
                ['Hosting (annual)', '1', '240,00 €', '240,00 €'],
            ],
        ],
        'totals' => ['entries' => [
            ['label' => 'Net', 'value' => '6.120,00 €'],
            ['label' => 'VAT (19%)', 'value' => '1.162,80 €'],
            ['label' => 'Grand total', 'value' => '7.282,80 €'],
        ]],
        'footer' => ['text' => 'Please transfer the total amount within 30 days to the bank account stated above. Thank you for your business.'],
    ],
    pdf: 'invoice-realistic.pdf',
    html: null,
);
