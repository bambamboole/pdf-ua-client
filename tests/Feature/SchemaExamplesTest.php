<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Opis\JsonSchema\Validator;
use Workbench\App\Support\TemplateFixtureRepository;

it('keeps example documents out of the package schema compiler', function (): void {
    $schema = app(TemplateSchemaCompiler::class)->compile(app(BlockRegistry::class));

    expect($schema)->not->toHaveKey('examples');
});

it('the invoice structure validates against schema #1 and its data against schema #2', function (): void {
    $fixture = app(TemplateFixtureRepository::class)->examples()[0];
    $template = app(TemplateFactory::class)->fromArray($fixture->template);

    $dataSchema = app(DataSchemaCompiler::class)->compile($template);
    $result = (new Validator)->validate(
        json_decode((string) json_encode($fixture->data)),
        json_decode((string) json_encode($dataSchema)),
    );

    expect($result->isValid())->toBeTrue();
});
