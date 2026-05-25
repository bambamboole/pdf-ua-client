<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [
                ['type' => 'heading', 'props' => ['text' => 'Catalog overview'], 'config' => ['level' => 2]],
            ]],
            ['blocks' => [
                ['type' => 'text', 'props' => ['text' => 'A paragraph of text.']],
            ]],
            ['blocks' => [
                ['type' => 'html', 'props' => ['html' => '<em>Raw HTML</em>']],
            ]],
            ['blocks' => [
                ['type' => 'image', 'props' => ['src' => 'logo.png', 'alt' => 'Logo']],
            ]],
            ['blocks' => [
                ['type' => 'spacer', 'config' => ['height' => 8]],
            ]],
            ['blocks' => [
                ['type' => 'divider', 'config' => ['thickness' => 2, 'lineColor' => '#475569', 'style' => 'dashed']],
            ]],
            ['blocks' => [
                ['type' => 'key-value', 'props' => ['entries' => [
                    ['label' => 'Status', 'value' => 'Active'],
                    ['label' => 'Owner', 'value' => 'Acme GmbH'],
                ]], 'config' => ['typography' => ['size' => 11, 'color' => '#374151']]],
            ]],
            ['blocks' => [
                ['type' => 'table', 'props' => [
                    'headers' => ['Item', 'Qty'],
                    'rows' => [
                        ['Widget A', '3'],
                        ['Widget B', '7'],
                    ],
                ], 'config' => ['style' => 'bordered', 'spacing' => ['bottom' => 4]]],
            ]],
        ],
    ],
    data: [],
    html: '<table class="row" role="presentation"><tr><td><div class="block-1"><h2>Catalog overview</h2></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-2"><p>A paragraph of text.</p></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-3"><em>Raw HTML</em></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-4"><img src="logo.png" alt="Logo"></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-5"></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-6"><hr></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-7"><table><tbody><tr><td>Status</td><td>Active</td></tr><tr><td>Owner</td><td>Acme GmbH</td></tr></tbody></table></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-8"><table><thead><tr><th>Item</th><th>Qty</th></tr></thead><tbody><tr><td>Widget A</td><td>3</td></tr><tr><td>Widget B</td><td>7</td></tr></tbody></table></div></td></tr></table>',
);
