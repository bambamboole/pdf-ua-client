<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [
                ['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]],
            ]],
        ],
    ],
    data: ['title' => ['text' => 'Runtime-supplied heading']],
    html: '<table class="row" role="presentation"><tr><td><div class="block-1"><h1>Runtime-supplied heading</h1></div></td></tr></table>',
);
