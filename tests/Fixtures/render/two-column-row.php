<?php

declare(strict_types=1);
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

return new TestFixture(
    spec: [
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            [
                'blocks' => [
                    ['type' => 'text', 'id' => 'l', 'config' => ['width' => '60%']],
                    ['type' => 'text', 'id' => 'r', 'config' => ['width' => '40%']],
                ],
            ],
        ],
    ],
    data: [
        'l' => ['text' => 'Left column'],
        'r' => ['text' => 'Right column'],
    ],
    html: '<table class="row" role="presentation"><tr><td style="width: 60%;"><div class="block-1"><p>Left column</p></div></td><td style="width: 40%;"><div class="block-2"><p>Right column</p></div></td></tr></table>',
);
