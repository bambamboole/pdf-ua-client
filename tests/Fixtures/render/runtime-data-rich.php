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
                    'id' => 'invoice-title',
                    'config' => ['level' => 1],
                ],
            ]],
            ['blocks' => [
                [
                    'type' => 'key-value',
                    'id' => 'customer-meta',
                ],
            ]],
            ['blocks' => [
                [
                    'type' => 'table',
                    'id' => 'line-items',
                    'config' => ['style' => 'striped'],
                ],
            ]],
        ],
    ],
    data: [
        'invoice-title' => ['text' => 'Invoice 2026-042'],
        'customer-meta' => ['entries' => [
            ['label' => 'Customer', 'value' => 'Globex AG'],
            ['label' => 'Invoice number', 'value' => 'RE-2026-000042'],
            ['label' => 'Due date', 'value' => '2026-06-21'],
        ]],
        'line-items' => [
            'headers' => ['Description', 'Amount'],
            'rows' => [
                ['Consulting (May)', '2.400,00 €'],
                ['Implementation (May)', '5.600,00 €'],
                ['Travel reimbursement', '312,40 €'],
            ],
        ],
    ],
    html: '<table class="row" role="presentation"><tr><td><div class="block-1"><h1>Invoice 2026-042</h1></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-2"><table class="key-value"><tbody><tr><td>Customer</td><td>Globex AG</td></tr><tr><td>Invoice number</td><td>RE-2026-000042</td></tr><tr><td>Due date</td><td>2026-06-21</td></tr></tbody></table></div></td></tr></table><table class="row" role="presentation"><tr><td><div class="block-3"><table class="data-table"><thead><tr><th>Description</th><th>Amount</th></tr></thead><tbody><tr><td>Consulting (May)</td><td>2.400,00 €</td></tr><tr><td>Implementation (May)</td><td>5.600,00 €</td></tr><tr><td>Travel reimbursement</td><td>312,40 €</td></tr></tbody></table></div></td></tr></table>',
);
