<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Examples\InvoiceExample;
use Bambamboole\PdfUaClient\Template\Template;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;

it('attaches the registered example documents to the compiled schema root', function (): void {
    $schema = app(TemplateSchemaCompiler::class)->compile(app(BlockRegistry::class));

    expect($schema['examples'])->toBeArray()
        ->and($schema['examples'][0]['title'])->toBe('Invoice');
});

it('the invoice example validates against the schema', function (): void {
    $doc = InvoiceExample::document();
    $data = [];
    $rows = array_map(function (array $row) use (&$data): array {
        $row['blocks'] = array_map(function (array $b) use (&$data): array {
            if (isset($b['props'], $b['id'])) {
                $data[$b['id']] = $b['props'];
            }
            unset($b['props']);

            return $b;
        }, $row['blocks']);

        return $row;
    }, $doc['rows']);

    $built = app(TemplateFactory::class)->fromArray([
        'version' => $doc['version'],
        'config' => $doc['config'],
        'rows' => $rows,
    ]);

    expect($built)->toBeInstanceOf(Template::class);
});
