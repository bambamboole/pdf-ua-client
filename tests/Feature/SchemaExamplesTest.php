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

it('validates every example structure and data contract', function (): void {
    foreach (app(TemplateFixtureRepository::class)->examples() as $fixture) {
        $template = app(TemplateFactory::class)->fromArray($fixture->template);

        $dataSchema = app(DataSchemaCompiler::class)->compile($template);
        $result = (new Validator)->validate(
            json_decode((string) json_encode($fixture->data)),
            json_decode((string) json_encode($dataSchema)),
        );

        expect($result->isValid())->toBeTrue("Example {$fixture->slug} data failed schema validation.");
    }
});

it('keeps example contract files in sync with compiled data schemas', function (): void {
    foreach (app(TemplateFixtureRepository::class)->examples() as $fixture) {
        if ($fixture->contract === null) {
            continue;
        }

        $template = app(TemplateFactory::class)->fromArray($fixture->template);
        $dataSchema = app(DataSchemaCompiler::class)->compile($template);

        expect($fixture->contract)->toEqual($dataSchema, "Example {$fixture->slug} contract does not match compiled data schema.");
    }
});
