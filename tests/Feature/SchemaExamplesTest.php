<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Examples\InvoiceExample;
use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Opis\JsonSchema\Validator;

it('attaches structure-only example documents to the compiled schema root', function (): void {
    $schema = app(TemplateSchemaCompiler::class)->compile(app(BlockRegistry::class));

    expect($schema['examples'])->toBeArray()
        ->and($schema['examples'][0]['title'])->toBe('Invoice');

    foreach ($schema['examples'][0]['rows'] as $row) {
        foreach ($row['blocks'] as $block) {
            expect($block)->not->toHaveKey('props');
        }
    }
});

it('the invoice structure validates against schema #1 and its data against schema #2', function (): void {
    $template = app(TemplateFactory::class)->fromArray(InvoiceExample::document());

    $dataSchema = app(DataSchemaCompiler::class)->compile($template);
    $result = (new Validator)->validate(
        json_decode((string) json_encode(InvoiceExample::data())),
        json_decode((string) json_encode($dataSchema)),
    );

    expect($result->isValid())->toBeTrue();
});
