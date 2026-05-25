<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [
                ['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]],
            ]],
        ],
    ],
    data: ['h' => ['text' => 'Invoice 2026-001']],
    html: '<table class="row" role="presentation"><tr><td><div class="block-1"><h1>Invoice 2026-001</h1></div></td></tr></table>',
);
