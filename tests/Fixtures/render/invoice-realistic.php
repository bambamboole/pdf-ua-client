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
    html: '<table class="row" role="presentation"><tr><td style="width: 58%;"><div class="block-1"><svg xmlns="http://www.w3.org/2000/svg" width="260" height="72" viewBox="0 0 260 72" role="img" aria-label="PDF UA Kit GmbH logo"><text x="0" y="42" fill="#111827" font-family="Arial, sans-serif" font-size="30" font-weight="700">PDF UA Kit</text></svg></div></td><td style="width: 42%;"><div class="block-2"><table class="key-value"><tbody><tr><td>Invoice number</td><td>RE-2026-001234</td></tr><tr><td>Issue date</td><td>2026-02-17</td></tr><tr><td>Due date</td><td>2026-03-19</td></tr></tbody></table></div></td></tr></table><table class="row" role="presentation"><tr><td style="width: 60%;"><div class="block-3"><p>Muster GmbH</p><p>Beispielstraße 1</p><p>10115 Berlin</p></div></td><td style="width: 40%;"><div class="block-4"><p>Invoice 2026-001</p></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-5"><table class="data-table"><thead><tr><th style="text-align: left;">Description</th><th style="text-align: right;">Qty</th><th style="text-align: right;">Unit price</th><th style="text-align: right;">Total</th></tr></thead><tbody><tr><td style="text-align: left;">Web development</td><td style="text-align: right;">40</td><td style="text-align: right;">95,00 €</td><td style="text-align: right;">3.800,00 €</td></tr><tr><td style="text-align: left;">UI/UX design</td><td style="text-align: right;">16</td><td style="text-align: right;">85,00 €</td><td style="text-align: right;">1.360,00 €</td></tr><tr><td style="text-align: left;">Project management</td><td style="text-align: right;">8</td><td style="text-align: right;">90,00 €</td><td style="text-align: right;">720,00 €</td></tr><tr><td style="text-align: left;">Hosting (annual)</td><td style="text-align: right;">1</td><td style="text-align: right;">240,00 €</td><td style="text-align: right;">240,00 €</td></tr></tbody></table></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-6"><hr></div></td></tr></table><table class="row" role="presentation"><tr><td style="width: 60%;"><div class="block-7"></div></td><td style="width: 40%;"><div class="block-8"><table class="key-value"><tbody><tr><td>Net</td><td>6.120,00 €</td></tr><tr><td>VAT (19%)</td><td>1.162,80 €</td></tr><tr><td>Grand total</td><td>7.282,80 €</td></tr></tbody></table></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-9"><p>Please transfer the total amount within 30 days to the bank account stated above. Thank you for your business.</p></div></td></tr></table>',
);
