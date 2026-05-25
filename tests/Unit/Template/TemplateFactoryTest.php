<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Enums\PageFormat;
use Bambamboole\PdfUaClient\Enums\PageNumberPosition;
use Bambamboole\PdfUaClient\Exceptions\TemplateValidationException;
use Bambamboole\PdfUaClient\Template\ExampleRegistry;
use Bambamboole\PdfUaClient\Template\Template;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixtureBlock;

beforeEach(function () {
    $registry = new BlockRegistry;
    $registry->register(TestFixtureBlock::class);
    $this->factory = new TemplateFactory(
        $registry,
        new TemplateSchemaCompiler(new PropsReflector, new ExampleRegistry),
    );
});

it('builds a Template from valid JSON', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'test-fixture', 'config' => ['level' => 2]]]],
        ],
    ]);

    expect($template)->toBeInstanceOf(Template::class);
    expect($template->version)->toBe(1);
    expect($template->config->page->format)->toBe(PageFormat::A4);
    expect($template->rows)->toHaveCount(1);
    expect($template->rows[0]->blocks[0]->type)->toBe('test-fixture');
    expect($template->rows[0]->blocks[0]->id)->toBe('r0b0');
});

it('applies defaults for omitted page fields', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A5']],
        'rows' => [
            ['blocks' => [['type' => 'test-fixture']]],
        ],
    ]);

    expect($template->config->page->format)->toBe(PageFormat::A5);
    expect($template->config->page->locale)->toBe('de_DE');
    expect($template->config->page->margins->top)->toBe(20);
    expect($template->config->page->margins->left)->toBe(25);
    expect($template->config->page->pageNumbers->enabled)->toBeFalse();
    expect($template->config->page->pageNumbers->position)->toBe(PageNumberPosition::Center);
});

it('builds page number settings with a backed enum position', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['pageNumbers' => ['enabled' => true, 'position' => 'right']]],
        'rows' => [],
    ]);

    expect($template->config->page->pageNumbers->enabled)->toBeTrue();
    expect($template->config->page->pageNumbers->position)->toBe(PageNumberPosition::Right);
});

it('builds footer rows and footer page numbers', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [
            'page' => [
                'footer' => [
                    'repeat' => true,
                    'pageNumbers' => ['enabled' => true, 'position' => 'right'],
                    'rows' => [[
                        'columnWidths' => ['70%', '30%'],
                        'blocks' => [
                            ['type' => 'test-fixture', 'id' => 'footer_note'],
                            ['type' => 'test-fixture', 'id' => 'footer_meta'],
                        ],
                    ]],
                ],
            ],
        ],
        'rows' => [],
    ]);

    expect($template->config->page->footer->repeat)->toBeTrue();
    expect($template->config->page->footer->pageNumbers->enabled)->toBeTrue();
    expect($template->config->page->footer->pageNumbers->position)->toBe(PageNumberPosition::Right);
    expect($template->config->page->footer->rows)->toHaveCount(1);
    expect($template->config->page->footer->rows[0]->columnWidths)->toBe(['70%', '30%']);
    expect($template->config->page->footer->rows[0]->blocks[0]->id)->toBe('footer_note');
});

it('parses nested per-block config', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['typography' => ['family' => 'Inter', 'size' => 10]],
        'rows' => [
            ['blocks' => [[
                'type' => 'test-fixture',
                'config' => [
                    'typography' => ['size' => 18, 'weight' => 700],
                    'spacing' => ['bottom' => 4],
                ],
            ]]],
        ],
    ]);

    $instance = $template->rows[0]->blocks[0];
    expect($instance->config)->toMatchArray([
        'typography' => ['size' => 18, 'weight' => 700],
        'spacing' => ['bottom' => 4],
    ]);
});

it('throws TemplateValidationException for missing required keys', function () {
    expect(fn () => $this->factory->fromArray([
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [],
    ]))->toThrow(TemplateValidationException::class);
});

it('throws TemplateValidationException for unknown block types', function () {
    expect(fn () => $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'unknown-block-xyz']]],
        ],
    ]))->toThrow(TemplateValidationException::class);
});

it('accepts an empty config object', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [],
    ]);

    expect($template->version)->toBe(1);
});

it('accepts a fully-default template via fromArray', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [],
    ]);

    expect($template->version)->toBe(1);
});

it('accepts deeply nested empty configs', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => []],
        'rows' => [
            ['blocks' => [
                ['type' => 'test-fixture', 'config' => []],
            ]],
        ],
    ]);

    expect($template->version)->toBe(1);
});

it('rejects a block that carries inline props', function () {
    expect(fn () => $this->factory->fromArray([
        'version' => 1, 'config' => [],
        'rows' => [['blocks' => [['type' => 'test-fixture', 'props' => ['text' => 'x']]]]],
    ]))->toThrow(TemplateValidationException::class);
});
