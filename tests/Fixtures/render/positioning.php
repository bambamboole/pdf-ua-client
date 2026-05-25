<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [
                [
                    'type' => 'heading',
                    'props' => ['text' => 'Right-aligned narrow heading'],
                    'config' => ['level' => 2, 'width' => '60%', 'align' => 'right'],
                ],
            ]],
            ['blocks' => [
                [
                    'type' => 'heading',
                    'props' => ['text' => 'Centered medium heading'],
                    'config' => ['level' => 3, 'width' => '50%', 'align' => 'center'],
                ],
            ]],
            ['blocks' => [
                [
                    'type' => 'text',
                    'props' => ['text' => 'A constrained-width paragraph with default left positioning.'],
                    'config' => ['width' => '80%'],
                ],
            ]],
            [
                'columnWidths' => ['50%', '50%'],
                'blocks' => [
                    [
                        'type' => 'text',
                        'props' => ['text' => 'Centered, half-width inside its cell.'],
                        'config' => ['width' => '50%', 'align' => 'center'],
                    ],
                    [
                        'type' => 'text',
                        'props' => ['text' => 'Spans the full cell.'],
                    ],
                ],
            ],
            ['blocks' => [
                ['type' => 'spacer', 'config' => ['height' => 10]],
            ]],
        ],
    ],
    data: [],
    html: '<table class="row" role="presentation"><tr><td><div class="block-1"><h2>Right-aligned narrow heading</h2></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-2"><h3>Centered medium heading</h3></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-3"><p>A constrained-width paragraph with default left positioning.</p></div></td></tr></table><table class="row" role="presentation"><tr><td style="width: 50%;"><div class="block-4"><p>Centered, half-width inside its cell.</p></div></td><td style="width: 50%;"><div class="block-5"><p>Spans the full cell.</p></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-6"></div></td></tr></table>',
);
