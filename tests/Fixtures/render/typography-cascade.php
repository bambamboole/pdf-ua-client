<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => [
            'page' => ['format' => 'A4'],
            'typography' => ['family' => 'Inter', 'size' => 10],
        ],
        'rows' => [
            ['blocks' => [
                ['type' => 'heading', 'props' => ['text' => 'Inherits template typography'], 'config' => ['level' => 2]],
            ]],
            ['blocks' => [
                [
                    'type' => 'heading',
                    'props' => ['text' => 'Overrides size and weight'],
                    'config' => ['level' => 2, 'typography' => ['size' => 24, 'weight' => 700]],
                ],
            ]],
            ['blocks' => [
                [
                    'type' => 'heading',
                    'props' => ['text' => 'Centered danger heading'],
                    'config' => ['level' => 2, 'typography' => ['align' => 'center', 'color' => '#dc2626']],
                ],
            ]],
            ['blocks' => [
                [
                    'type' => 'text',
                    'props' => ['text' => 'Body copy rendered in Georgia for contrast.'],
                    'config' => ['typography' => ['family' => 'Georgia']],
                ],
            ]],
        ],
    ],
    data: [],
    html: '<table class="row" role="presentation"><tr><td><div class="block-1"><h2>Inherits template typography</h2></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-2"><h2>Overrides size and weight</h2></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-3"><h2>Centered danger heading</h2></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-4"><p>Body copy rendered in Georgia for contrast.</p></div></td></tr></table>',
);
