<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Blocks\TableBlock;
use Bambamboole\PdfUaClient\Fonts\FontRegistry;
use Bambamboole\PdfUaClient\Template\ExampleRegistry;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixtureBlock;

beforeEach(function () {
    $this->registry = new BlockRegistry;
    $this->registry->register(TestFixtureBlock::class);
    $this->compiler = new TemplateSchemaCompiler(new PropsReflector, new ExampleRegistry);
});

it('compiles a root schema referencing config, $defs/row, $defs/block', function () {
    $schema = $this->compiler->compile($this->registry);

    expect($schema['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema');
    expect($schema['type'])->toBe('object');
    expect($schema['required'])->toBe(['version', 'config', 'rows']);
    expect($schema['properties']['version'])->toBe(['const' => 1, 'type' => 'integer']);
    expect($schema['properties']['rows']['items']['$ref'])->toBe('#/$defs/row');
    expect($schema['properties']['config'])->toBe(['$ref' => '#/$defs/templateConfig']);
    expect($schema['$defs']['row']['properties']['blocks']['minItems'])->toBe(1);
    expect($schema['$defs']['row']['properties'])->not->toHaveKey('columnWidths');
});

it('declares a shared blockBase $def with the common envelope fields', function () {
    $schema = $this->compiler->compile($this->registry);

    $blockBase = $schema['$defs']['blockBase'];
    expect($blockBase['required'])->toBe(['type']);
    expect($blockBase['properties']['type'])->toBe(['type' => 'string']);
    expect($blockBase['properties']['id'])->toBe(['type' => 'string']);
});

it('emits each block as plain oneOf of $refs into per-block defs', function () {
    $schema = $this->compiler->compile($this->registry);

    expect($schema['$defs']['block'])->toBe([
        'oneOf' => [['$ref' => '#/$defs/testFixtureBlock']],
    ]);
});

it('composes per-block defs from blockBase via allOf with unevaluatedProperties:false', function () {
    $schema = $this->compiler->compile($this->registry);

    $blockDef = $schema['$defs']['testFixtureBlock'];
    expect($blockDef['allOf'])->toBe([['$ref' => '#/$defs/blockBase']]);
    expect($blockDef['unevaluatedProperties'])->toBeFalse();
    expect($blockDef['properties']['type'])->toBe(['const' => 'test-fixture', 'type' => 'string']);
    expect($blockDef['properties'])->not->toHaveKey('props');
    expect($blockDef['properties']['config'])->toBe(['$ref' => '#/$defs/testFixtureBlockConfig']);
    expect($schema['$defs'])->toHaveKey('testFixtureProps');
    expect($schema['$defs']['testFixtureProps'])->toHaveKey('properties');
    expect($blockDef)->not->toHaveKey('additionalProperties');
});

it('exposes templateConfig via a $defs entry with shared $refs', function () {
    $schema = $this->compiler->compile($this->registry);

    $templateConfig = $schema['$defs']['templateConfig'];
    expect($templateConfig['properties'])->toHaveKeys(['page', 'typography']);
    expect($templateConfig)->not->toHaveKey('required');

    expect($templateConfig['properties']['page'])->toMatchArray(['$ref' => '#/$defs/pageConfig']);
    expect($templateConfig['properties']['typography'])->toHaveKey('$ref');
    expect($schema['$defs'])->toHaveKeys(['pageConfig', 'pageFooterConfig', 'typographyConfig', 'spacingConfig', 'pageNumbersConfig']);

    $pageConfig = $schema['$defs']['pageConfig'];
    expect($pageConfig['properties'])->toHaveKeys(['format', 'locale', 'margins', 'pageNumbers', 'footer']);
    expect($pageConfig['properties']['margins'])->toMatchArray([
        '$ref' => '#/$defs/spacingConfig',
        'title' => 'Margins',
        'description' => 'Page margins in millimetres.',
    ]);
    expect($pageConfig['properties']['pageNumbers'])->toMatchArray([
        '$ref' => '#/$defs/pageNumbersConfig',
        'title' => 'Pagination',
        'description' => 'Page number display settings.',
    ]);
    expect($pageConfig['properties']['footer'])->toMatchArray([
        '$ref' => '#/$defs/pageFooterConfig',
        'title' => 'Footer',
        'description' => 'Repeated page footer rows.',
    ]);
    expect($schema['$defs']['pageFooterConfig']['properties']['repeat'])->toMatchArray([
        'type' => 'boolean',
        'title' => 'Repeat',
        'description' => 'Repeat the footer on every rendered page.',
        'default' => true,
    ]);
    expect($schema['$defs']['pageFooterConfig']['properties']['rows'])->toMatchArray([
        'type' => 'array',
        'items' => ['$ref' => '#/$defs/row'],
    ]);
    expect($schema['$defs']['pageFooterConfig']['properties'])->not->toHaveKey('pageNumbers');
});

it('emits titles and descriptions for shared config properties', function () {
    $schema = $this->compiler->compile($this->registry);

    expect($schema['$defs']['spacingConfig']['properties']['top'])->toMatchArray([
        'title' => 'Top',
        'description' => 'Top spacing in millimetres.',
        'minimum' => 0,
    ]);
    expect($schema['$defs']['blockConfig']['properties']['align'])->toMatchArray([
        'title' => 'Alignment',
        'description' => 'Horizontal placement of this block within its row cell.',
    ]);
});

it('emits pagination enabled and enum position settings', function () {
    $schema = $this->compiler->compile($this->registry);

    expect($schema['$defs']['pageNumbersConfig']['properties']['enabled'])->toMatchArray([
        'type' => 'boolean',
        'title' => 'Enabled',
        'description' => 'Show page numbers in the page footer.',
        'default' => false,
    ]);
    expect($schema['$defs']['pageNumbersConfig']['properties']['position'])->toMatchArray([
        'type' => 'string',
        'enum' => ['left', 'center', 'right'],
        'title' => 'Position',
        'description' => 'Footer position used for page numbers.',
        'default' => 'center',
    ]);
});

it('composes a per-block config schema from its parent via allOf and only emits own properties', function () {
    $schema = $this->compiler->compile($this->registry);

    expect($schema['$defs'])->toHaveKey('testFixtureBlockConfig');
    expect($schema['$defs'])->toHaveKey('blockConfig');

    $blockConfig = $schema['$defs']['testFixtureBlockConfig'];
    expect($blockConfig['allOf'])->toBe([['$ref' => '#/$defs/blockConfig']]);
    expect($blockConfig['unevaluatedProperties'])->toBeFalse();
    expect(array_keys($blockConfig['properties']))->toBe(['level']);
    expect($blockConfig)->not->toHaveKey('required');

    $baseConfig = $schema['$defs']['blockConfig'];
    expect($baseConfig['properties'])->toHaveKeys(['typography', 'spacing', 'width', 'align']);
});

it('emits renderable array item schemas for table config lists', function () {
    $this->registry->register(TableBlock::class);

    $schema = $this->compiler->compile($this->registry);
    $tableConfig = $schema['$defs']['tableConfig']['properties'];

    expect($tableConfig['columnAlignments'])->toMatchArray([
        'type' => ['array', 'null'],
        'items' => ['type' => 'string'],
    ]);
    expect($tableConfig['columnWidths'])->toMatchArray([
        'type' => ['array', 'null'],
        'items' => ['type' => ['integer', 'string']],
    ]);
});

it('emits registered fonts as select options for typography family', function () {
    $fonts = new FontRegistry;
    $fonts->register(
        key: 'inter',
        label: 'Inter',
        family: 'Inter',
        url: 'https://example.test/inter.woff2',
    );
    $compiler = new TemplateSchemaCompiler(new PropsReflector($fonts), new ExampleRegistry);

    $schema = $compiler->compile($this->registry);

    expect($schema['$defs']['typographyConfig']['properties']['family'])->toMatchArray([
        'type' => ['string', 'null'],
        'enum' => ['inter'],
        'enumNames' => ['Inter'],
    ]);
});
