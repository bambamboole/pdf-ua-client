<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [
                ['type' => 'heading', 'id' => 'h1', 'config' => ['level' => 2]],
            ]],
            ['blocks' => [
                ['type' => 'text', 'id' => 't1'],
            ]],
            ['blocks' => [
                ['type' => 'html', 'id' => 'raw1'],
            ]],
            ['blocks' => [
                ['type' => 'image', 'id' => 'img1'],
            ]],
            ['blocks' => [
                ['type' => 'spacer', 'config' => ['height' => 8]],
            ]],
            ['blocks' => [
                ['type' => 'divider', 'config' => ['thickness' => 2, 'lineColor' => '#475569', 'style' => 'dashed']],
            ]],
            ['blocks' => [
                ['type' => 'key-value', 'id' => 'kv1', 'config' => ['typography' => ['size' => 11, 'color' => '#374151']]],
            ]],
            ['blocks' => [
                ['type' => 'table', 'id' => 'tbl1', 'config' => ['style' => 'bordered', 'spacing' => ['bottom' => 4]]],
            ]],
        ],
    ],
    data: [
        'h1' => ['text' => 'Catalog overview'],
        't1' => ['text' => 'A paragraph of text.'],
        'raw1' => ['html' => '<em>Raw HTML</em>'],
        'img1' => ['src' => 'logo.png', 'alt' => 'Logo'],
        'kv1' => ['entries' => [
            ['label' => 'Status', 'value' => 'Active'],
            ['label' => 'Owner', 'value' => 'Acme GmbH'],
        ]],
        'tbl1' => [
            'headers' => ['Item', 'Qty'],
            'rows' => [
                ['Widget A', '3'],
                ['Widget B', '7'],
            ],
        ],
    ],
    html: '<table class="row" role="presentation"><tr><td><div class="block-1"><h2>Catalog overview</h2></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-2"><p>A paragraph of text.</p></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-3"><em>Raw HTML</em></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-4"><img src="logo.png" alt="Logo"></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-5"></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-6"><hr></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-7"><table class="key-value"><tbody><tr><td>Status</td><td>Active</td></tr><tr><td>Owner</td><td>Acme GmbH</td></tr></tbody></table></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-8"><table class="data-table"><thead><tr><th>Item</th><th>Qty</th></tr></thead><tbody><tr><td>Widget A</td><td>3</td></tr><tr><td>Widget B</td><td>7</td></tr></tbody></table></div></td></tr></table>',
);
