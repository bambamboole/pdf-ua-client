<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Blocks\DividerBlock;
use Bambamboole\PdfUaClient\Blocks\HeadingBlock;
use Bambamboole\PdfUaClient\Blocks\TableBlock;
use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Bambamboole\PdfUaClient\Template\ExampleRegistry;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;

beforeEach(function (): void {
    $registry = new BlockRegistry;
    $registry->register(HeadingBlock::class);
    $registry->register(TableBlock::class);
    $registry->register(DividerBlock::class);
    $reflector = new PropsReflector;
    $this->factory = new TemplateFactory($registry, new TemplateSchemaCompiler($reflector, new ExampleRegistry));
    $this->compiler = new DataSchemaCompiler($reflector, $registry, $this->factory);
});

it('keys content-bearing blocks by id and requires those with required props', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]]]],
            ['blocks' => [['type' => 'divider', 'id' => 'rule']]],
            ['blocks' => [['type' => 'table', 'id' => 'items']]],
        ],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema')
        ->and($schema['type'])->toBe('object')
        ->and($schema['additionalProperties'])->toBeFalse()
        ->and(array_keys($schema['properties']))->toBe(['title', 'items']) // divider omitted (no props)
        ->and($schema['properties']['title']['properties'])->toHaveKey('text')
        ->and($schema['properties']['title']['additionalProperties'])->toBeFalse()
        ->and($schema['required'])->toBe(['title', 'items']); // heading.text + table.headers/rows are required
});

it('emits an empty-object schema with no required when no block needs data', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [['blocks' => [['type' => 'divider', 'id' => 'rule']]]],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties'])->toBeInstanceOf(stdClass::class)
        ->and($schema)->not->toHaveKey('required')
        ->and($schema)->not->toHaveKey('$defs'); // standalone: props are inlined, no $defs needed
});

it('dataSchemaFor accepts a raw array by building it through the factory', function (): void {
    $schema = $this->compiler->dataSchemaFor([
        'version' => 1,
        'config' => [],
        'rows' => [['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]]],
    ]);

    expect(array_keys($schema['properties']))->toBe(['h']);
});
