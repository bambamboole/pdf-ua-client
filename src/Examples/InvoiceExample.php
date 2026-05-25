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
            'config' => [
                'page' => [
                    'format' => 'A4',
                    'locale' => 'de_DE',
                    'margins' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 25],
                    'pageNumbers' => ['enabled' => true, 'position' => 'center'],
                ],
                'typography' => ['family' => 'Inter', 'size' => 10],
            ],
            'rows' => [
                [
                    'blocks' => [
                        ['type' => 'image', 'id' => 'logo', 'config' => ['width' => '58%', 'maxHeight' => 28]],
                        ['type' => 'key-value', 'id' => 'invoice-meta', 'config' => ['width' => '42%', 'align' => 'right']],
                    ],
                ],
                [
                    'blocks' => [
                        ['type' => 'key-value', 'id' => 'seller', 'config' => ['width' => '50%']],
                        ['type' => 'key-value', 'id' => 'buyer', 'config' => ['width' => '50%']],
                    ],
                ],
                ['blocks' => [['type' => 'divider', 'id' => 'address-rule']]],
                ['blocks' => [[
                    'type' => 'table',
                    'id' => 'items',
                    'config' => [
                        'style' => 'striped',
                        'columnAlignments' => ['center', 'left', 'right', 'right', 'right', 'right'],
                        'columnWidths' => ['7%', '38%', '12%', '16%', '11%', '16%'],
                    ],
                ]]],
                [
                    'blocks' => [
                        ['type' => 'table', 'id' => 'vat-breakdown', 'config' => [
                            'style' => 'minimal',
                            'width' => '54%',
                            'columnAlignments' => ['left', 'right', 'right', 'right'],
                        ]],
                        ['type' => 'key-value', 'id' => 'totals', 'config' => ['width' => '46%', 'align' => 'right']],
                    ],
                ],
                [
                    'blocks' => [
                        ['type' => 'text', 'id' => 'notice', 'config' => ['width' => '54%', 'spacing' => ['top' => 4]]],
                        ['type' => 'key-value', 'id' => 'payment', 'config' => ['width' => '46%', 'align' => 'right']],
                    ],
                ],
                ['blocks' => [['type' => 'divider', 'id' => 'footer-rule']]],
                ['blocks' => [['type' => 'text', 'id' => 'footer']]],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function data(): array
    {
        return [
            'logo' => [
                'src' => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNjAiIGhlaWdodD0iNzIiIHZpZXdCb3g9IjAgMCAyNjAgNzIiPjx0ZXh0IHg9IjAiIHk9IjQyIiBmaWxsPSIjMTExODI3IiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMzAiIGZvbnQtd2VpZ2h0PSI3MDAiPlBERiBVQSBLaXQ8L3RleHQ+PC9zdmc+',
                'alt' => 'PDF UA Kit GmbH logo',
            ],
            'invoice-meta' => ['entries' => [
                ['label' => 'Invoice number', 'value' => 'RE-2026-001234'],
                ['label' => 'Issue date', 'value' => '2026-02-17'],
                ['label' => 'Due date', 'value' => '2026-03-19'],
                ['label' => 'Currency', 'value' => 'EUR'],
            ]],
            'seller' => ['entries' => [
                ['label' => 'Seller', 'value' => 'PDF UA Kit GmbH'],
                ['label' => 'Address', 'value' => 'Musterstraße 1, 10115 Berlin, DE'],
                ['label' => 'Contact', 'value' => 'Max Mustermann'],
                ['label' => 'Email', 'value' => 'billing@pdfua-kit.example'],
                ['label' => 'VAT ID', 'value' => 'DE123456789'],
            ]],
            'buyer' => ['entries' => [
                ['label' => 'Buyer', 'value' => 'Musterkunde AG'],
                ['label' => 'Address', 'value' => 'Käuferweg 2, 80331 München, DE'],
                ['label' => 'Email', 'value' => 'invoice@musterkunde.example'],
                ['label' => 'Buyer reference', 'value' => '04011000-12345-67'],
            ]],
            'items' => [
                'headers' => ['#', 'Description', 'Qty', 'Unit price', 'VAT', 'Total'],
                'rows' => [
                    ['1', 'Accessible PDF template implementation', '40', '95,00 €', '19%', '3.800,00 €'],
                    ['2', 'Document structure and tagging review', '16', '85,00 €', '19%', '1.360,00 €'],
                    ['3', 'Project management and acceptance testing', '8', '90,00 €', '19%', '720,00 €'],
                    ['4', 'Annual hosting and maintenance package', '1', '240,00 €', '19%', '240,00 €'],
                ],
            ],
            'vat-breakdown' => [
                'headers' => ['VAT category', 'Rate', 'Taxable amount', 'VAT amount'],
                'rows' => [
                    ['Standard rate', '19%', '6.120,00 €', '1.162,80 €'],
                ],
            ],
            'totals' => ['entries' => [
                ['label' => 'Net amount', 'value' => '6.120,00 €'],
                ['label' => 'VAT 19%', 'value' => '1.162,80 €'],
                ['label' => 'Grand total', 'value' => '7.282,80 €'],
                ['label' => 'Amount due', 'value' => '7.282,80 €'],
            ]],
            'notice' => ['text' => 'Please transfer the amount due within 30 days. Include the invoice number as the payment reference.'],
            'payment' => ['entries' => [
                ['label' => 'Bank', 'value' => 'Musterbank Berlin'],
                ['label' => 'IBAN', 'value' => 'DE89370400440532013000'],
                ['label' => 'BIC', 'value' => 'COBADEFFXXX'],
                ['label' => 'Payment reference', 'value' => 'RE-2026-001234'],
            ]],
            'footer' => ['text' => 'PDF UA Kit GmbH · Musterstraße 1 · 10115 Berlin · Germany · VAT ID DE123456789 · Invoice was created electronically and is valid without signature.'],
        ];
    }
}
