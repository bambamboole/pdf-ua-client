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
                    'id' => 'h1',
                    'config' => ['level' => 2, 'width' => '60%', 'align' => 'right'],
                ],
            ]],
            ['blocks' => [
                [
                    'type' => 'heading',
                    'id' => 'h2',
                    'config' => ['level' => 3, 'width' => '50%', 'align' => 'center'],
                ],
            ]],
            ['blocks' => [
                [
                    'type' => 'text',
                    'id' => 't1',
                    'config' => ['width' => '80%'],
                ],
            ]],
            [
                'blocks' => [
                    [
                        'type' => 'text',
                        'id' => 't2',
                        'config' => ['width' => '50%', 'align' => 'center'],
                    ],
                    [
                        'type' => 'text',
                        'id' => 't3',
                        'config' => ['width' => '50%'],
                    ],
                ],
            ],
            ['blocks' => [
                ['type' => 'spacer', 'config' => ['height' => 10]],
            ]],
        ],
    ],
    data: [
        'h1' => ['text' => 'Right-aligned narrow heading'],
        'h2' => ['text' => 'Centered medium heading'],
        't1' => ['text' => 'A constrained-width paragraph with default left positioning.'],
        't2' => ['text' => 'Centered, half-width inside its cell.'],
        't3' => ['text' => 'Spans the full cell.'],
    ],
    html: '<table class="row" role="presentation"><tr><td><div class="block-1"><h2>Right-aligned narrow heading</h2></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-2"><h3>Centered medium heading</h3></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-3"><p>A constrained-width paragraph with default left positioning.</p></div></td></tr></table><table class="row" role="presentation"><tr><td style="width: 50%;"><div class="block-4"><p>Centered, half-width inside its cell.</p></div></td><td style="width: 50%;"><div class="block-5"><p>Spans the full cell.</p></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-6"></div></td></tr></table>',
);
