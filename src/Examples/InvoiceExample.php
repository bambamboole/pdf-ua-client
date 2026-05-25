<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Examples;

final class InvoiceExample
{
    /** @return array<string, mixed> */
    public static function document(): array
    {
        return [
            'title' => 'Invoice',
            'version' => 1,
            'config' => ['page' => ['format' => 'A4']],
            'rows' => [
                ['blocks' => [
                    ['type' => 'heading', 'id' => 'company', 'config' => ['level' => 1, 'width' => '60%']],
                    ['type' => 'key-value', 'id' => 'invoice-meta', 'config' => ['align' => 'right', 'width' => '40%']],
                ]],
                ['blocks' => [
                    ['type' => 'key-value', 'id' => 'from', 'config' => ['width' => '50%']],
                    ['type' => 'key-value', 'id' => 'to', 'config' => ['width' => '50%']],
                ]],
                ['blocks' => [['type' => 'divider', 'id' => 'rule']]],
                ['blocks' => [['type' => 'table', 'id' => 'items']]],
                ['blocks' => [['type' => 'key-value', 'id' => 'totals', 'config' => ['align' => 'right']]]],
                ['blocks' => [['type' => 'text', 'id' => 'footer']]],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function data(): array
    {
        return [
            'company' => ['text' => 'ACME GmbH'],
            'invoice-meta' => ['entries' => [
                ['label' => 'Invoice', 'value' => '2026-001'],
                ['label' => 'Date', 'value' => '2026-05-25'],
                ['label' => 'Due', 'value' => '2026-06-08'],
            ]],
            'from' => ['entries' => [['label' => 'From', 'value' => 'ACME GmbH, Main St 1']]],
            'to' => ['entries' => [['label' => 'Bill to', 'value' => 'Beta Ltd, 2nd Ave']]],
            'items' => [
                'headers' => ['Description', 'Qty', 'Unit', 'Amount'],
                'rows' => [['Consulting', '10', '€100', '€1000'], ['License', '1', '€250', '€250']],
            ],
            'totals' => ['entries' => [
                ['label' => 'Subtotal', 'value' => '€1250'],
                ['label' => 'Tax (19%)', 'value' => '€237.50'],
                ['label' => 'Total', 'value' => '€1487.50'],
            ]],
            'footer' => ['text' => 'Payment due within 14 days. Thank you for your business.'],
        ];
    }
}
