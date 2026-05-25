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
                    ['type' => 'heading', 'id' => 'company', 'config' => ['level' => 1, 'width' => '60%'], 'props' => ['text' => 'ACME GmbH']],
                    ['type' => 'key-value', 'id' => 'invoice-meta', 'config' => ['align' => 'right', 'width' => '40%'], 'props' => ['entries' => [
                        ['label' => 'Invoice', 'value' => '2026-001'],
                        ['label' => 'Date', 'value' => '2026-05-25'],
                        ['label' => 'Due', 'value' => '2026-06-08'],
                    ]]],
                ]],
                ['blocks' => [
                    ['type' => 'key-value', 'id' => 'from', 'config' => ['width' => '50%'], 'props' => ['entries' => [['label' => 'From', 'value' => 'ACME GmbH, Main St 1']]]],
                    ['type' => 'key-value', 'id' => 'to', 'config' => ['width' => '50%'], 'props' => ['entries' => [['label' => 'Bill to', 'value' => 'Beta Ltd, 2nd Ave']]]],
                ]],
                ['blocks' => [['type' => 'divider', 'id' => 'rule']]],
                ['blocks' => [['type' => 'table', 'id' => 'items', 'props' => [
                    'headers' => ['Description', 'Qty', 'Unit', 'Amount'],
                    'rows' => [['Consulting', '10', '€100', '€1000'], ['License', '1', '€250', '€250']],
                ]]]],
                ['blocks' => [['type' => 'key-value', 'id' => 'totals', 'config' => ['align' => 'right'], 'props' => ['entries' => [
                    ['label' => 'Subtotal', 'value' => '€1250'],
                    ['label' => 'Tax (19%)', 'value' => '€237.50'],
                    ['label' => 'Total', 'value' => '€1487.50'],
                ]]]]],
                ['blocks' => [['type' => 'text', 'id' => 'footer', 'props' => ['text' => 'Payment due within 14 days. Thank you for your business.']]]],
            ],
        ];
    }
}
