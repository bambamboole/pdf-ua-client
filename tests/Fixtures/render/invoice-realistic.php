<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => [
            'page' => [
                'format' => 'A4',
                'locale' => 'de_DE',
                'margins' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 25],
                'pageNumbers' => ['position' => 'center'],
            ],
            'typography' => ['family' => 'Inter', 'size' => 10],
        ],
        'rows' => [
            ['blocks' => [
                [
                    'type' => 'heading',
                    'id' => 'title',
                    'config' => ['level' => 1, 'typography' => ['align' => 'center']],
                ],
            ]],
            [
                'columnWidths' => ['60%', '40%'],
                'blocks' => [
                    ['type' => 'text', 'id' => 'addr'],
                    ['type' => 'key-value', 'id' => 'meta'],
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
                'columnWidths' => ['60%', '40%'],
                'blocks' => [
                    ['type' => 'spacer'],
                    ['type' => 'key-value', 'id' => 'totals'],
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
        'title' => ['text' => 'Invoice 2026-001'],
        'addr' => ['text' => "Muster GmbH\n\nBeispielstraße 1\n\n10115 Berlin"],
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
    html: '<table class="row" role="presentation"><tr><td><div class="block-1"><h1>Invoice 2026-001</h1></div></td></tr></table><table class="row" role="presentation"><tr><td style="width: 60%;"><div class="block-2"><p>Muster GmbH</p><p>Beispielstraße 1</p><p>10115 Berlin</p></div></td><td style="width: 40%;"><div class="block-3"><table><tbody><tr><td>Invoice number</td><td>RE-2026-001234</td></tr><tr><td>Issue date</td><td>2026-02-17</td></tr><tr><td>Due date</td><td>2026-03-19</td></tr></tbody></table></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-4"><table><thead><tr><th style="text-align: left;">Description</th><th style="text-align: right;">Qty</th><th style="text-align: right;">Unit price</th><th style="text-align: right;">Total</th></tr></thead><tbody><tr><td style="text-align: left;">Web development</td><td style="text-align: right;">40</td><td style="text-align: right;">95,00 €</td><td style="text-align: right;">3.800,00 €</td></tr><tr><td style="text-align: left;">UI/UX design</td><td style="text-align: right;">16</td><td style="text-align: right;">85,00 €</td><td style="text-align: right;">1.360,00 €</td></tr><tr><td style="text-align: left;">Project management</td><td style="text-align: right;">8</td><td style="text-align: right;">90,00 €</td><td style="text-align: right;">720,00 €</td></tr><tr><td style="text-align: left;">Hosting (annual)</td><td style="text-align: right;">1</td><td style="text-align: right;">240,00 €</td><td style="text-align: right;">240,00 €</td></tr></tbody></table></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-5"><hr></div></td></tr></table><table class="row" role="presentation"><tr><td style="width: 60%;"><div class="block-6"></div></td><td style="width: 40%;"><div class="block-7"><table><tbody><tr><td>Net</td><td>6.120,00 €</td></tr><tr><td>VAT (19%)</td><td>1.162,80 €</td></tr><tr><td>Grand total</td><td>7.282,80 €</td></tr></tbody></table></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-8"><p>Please transfer the total amount within 30 days to the bank account stated above. Thank you for your business.</p></div></td></tr></table>',
);
