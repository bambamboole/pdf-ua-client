<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Blocks\DividerBlock;
use Bambamboole\PdfUaClient\Blocks\HeadingBlock;
use Bambamboole\PdfUaClient\Blocks\KeyValueBlock;
use Bambamboole\PdfUaClient\Blocks\TableBlock;
use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;

beforeEach(function (): void {
    $registry = new BlockRegistry;
    $registry->register(HeadingBlock::class);
    $registry->register(KeyValueBlock::class);
    $registry->register(TableBlock::class);
    $registry->register(DividerBlock::class);
    $reflector = new PropsReflector;
    $this->factory = new TemplateFactory($registry, new TemplateSchemaCompiler($reflector));
    $this->compiler = new DataSchemaCompiler($reflector, $registry, $this->factory);
});

it('keys content-bearing blocks by id and requires those with required props', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]]]],
            ['blocks' => [['type' => 'divider', 'id' => 'rule']]],
            ['blocks' => [[
                'type' => 'table',
                'id' => 'items',
                'config' => [
                    'columns' => [
                        ['key' => 'description', 'label' => 'Description'],
                    ],
                ],
            ]]],
        ],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema')
        ->and($schema['type'])->toBe('object')
        ->and($schema['additionalProperties'])->toBeFalse()
        ->and(array_keys($schema['properties']))->toBe(['title', 'items']) // divider omitted (no props)
        ->and($schema['properties']['title']['properties'])->toHaveKey('text')
        ->and($schema['properties']['title']['additionalProperties'])->toBeFalse()
        ->and($schema['required'])->toBe(['title', 'items']);
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

it('includes footer blocks in the data schema', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [
            'page' => [
                'footer' => [
                    'rows' => [[
                        'blocks' => [['type' => 'heading', 'id' => 'footer_heading', 'config' => ['level' => 2]]],
                    ]],
                ],
            ],
        ],
        'rows' => [['blocks' => [['type' => 'heading', 'id' => 'body_heading', 'config' => ['level' => 1]]]]],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties'])->toHaveKeys(['body_heading', 'footer_heading']);
    expect($schema['required'])->toBe(['body_heading', 'footer_heading']);
});

it('includes required runtime PDF attachments in the data schema', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [['blocks' => [['type' => 'divider', 'id' => 'rule']]]],
        'attachmentRequirements' => [[
            'id' => 'factur-x',
            'name' => 'factur-x.xml',
            'mimeType' => 'application/xml',
            'relationship' => 'Alternative',
        ]],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties']['attachments'])->toMatchArray([
        'type' => 'object',
        'properties' => [
            'factur-x' => [
                'type' => 'object',
                'required' => ['contentBase64'],
                'properties' => [
                    'contentBase64' => [
                        'type' => 'string',
                        'minLength' => 1,
                        'contentEncoding' => 'base64',
                        'title' => 'factur-x.xml',
                        'description' => 'Base64 encoded attachment content.',
                    ],
                ],
                'additionalProperties' => false,
            ],
        ],
        'required' => ['factur-x'],
        'additionalProperties' => false,
    ]);
    expect($schema['required'])->toBe(['attachments']);
});

it('includes optional runtime PDF attachments without requiring them', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [['blocks' => [['type' => 'divider', 'id' => 'rule']]]],
        'attachmentRequirements' => [[
            'id' => 'supporting-data',
            'name' => 'supporting-data.json',
            'mimeType' => 'application/json',
            'required' => false,
        ]],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties']['attachments']['properties'])->toHaveKey('supporting-data');
    expect($schema['properties']['attachments'])->not->toHaveKey('required');
    expect($schema)->not->toHaveKey('required');
});

it('annotates block schemas with fallback defaults and omits locked constants from the runtime contract', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]]]],
        ],
        'data' => [
            'defaults' => ['title' => ['text' => 'Fallback title']],
            'constants' => ['title' => ['badge' => 'Locked badge']],
        ],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties']['title']['properties']['text']['default'])->toBe('Fallback title')
        ->and($schema['properties']['title']['properties'])->not->toHaveKey('badge')
        ->and($schema)->not->toHaveKey('required');
});

it('omits a block from the runtime contract when every field is locked', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]]]],
        ],
        'data' => [
            'constants' => ['title' => ['text' => 'Locked title']],
        ],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties'])->toBeInstanceOf(stdClass::class)
        ->and($schema)->not->toHaveKey('required');
});

it('emits flat key value schemas from configured fields', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [[
            'blocks' => [[
                'type' => 'key-value',
                'id' => 'invoice-meta',
                'config' => [
                    'fields' => [
                        ['key' => 'invoiceNumber', 'label' => 'Invoice number'],
                        ['key' => 'currency', 'label' => 'Currency'],
                        ['key' => 'issueDate', 'label' => 'Issue date'],
                    ],
                ],
            ]],
        ]],
        'data' => [
            'defaults' => ['invoice-meta' => ['invoiceNumber' => 'RE-2026-001234']],
            'constants' => ['invoice-meta' => ['currency' => 'EUR']],
        ],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties']['invoice-meta']['properties'])->toHaveKeys(['invoiceNumber', 'issueDate'])
        ->and($schema['properties']['invoice-meta']['properties']['invoiceNumber']['type'])->toBe(['string', 'null'])
        ->and($schema['properties']['invoice-meta']['properties']['invoiceNumber']['default'])->toBe('RE-2026-001234')
        ->and($schema['properties']['invoice-meta']['properties']['issueDate']['type'])->toBe('string')
        ->and($schema['properties']['invoice-meta']['properties'])->not->toHaveKey('currency')
        ->and($schema['properties']['invoice-meta']['required'])->toBe(['issueDate'])
        ->and($schema['required'])->toBe(['invoice-meta']);
});

it('does not expose legacy key value entries when configured fields are missing', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [[
            'blocks' => [[
                'type' => 'key-value',
                'id' => 'invoice-meta',
                'config' => [],
            ]],
        ]],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties'])->toBeInstanceOf(stdClass::class)
        ->and($schema)->not->toHaveKey('required');
});

it('emits array item schemas from configured table columns', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [[
            'blocks' => [[
                'type' => 'table',
                'id' => 'lineItems',
                'config' => [
                    'columns' => [
                        ['key' => 'sku', 'label' => 'SKU'],
                        ['key' => 'description', 'label' => 'Description'],
                        ['key' => 'quantity', 'label' => 'Qty'],
                    ],
                ],
            ]],
        ]],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties']['lineItems'])->toMatchArray([
        'type' => 'array',
        'items' => [
            'type' => 'object',
            'properties' => [
                'sku' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'quantity' => ['type' => 'string'],
            ],
            'additionalProperties' => false,
            'required' => ['sku', 'description', 'quantity'],
        ],
    ])->and($schema['required'])->toBe(['lineItems']);
});

it('does not expose a table runtime contract when configured columns are missing', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [[
            'blocks' => [[
                'type' => 'table',
                'id' => 'lineItems',
                'config' => [],
            ]],
        ]],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties'])->toBeInstanceOf(stdClass::class)
        ->and($schema)->not->toHaveKey('required');
});
