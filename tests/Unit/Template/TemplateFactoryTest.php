<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Enums\Align;
use Bambamboole\PdfUaClient\Enums\PageFormat;
use Bambamboole\PdfUaClient\Enums\PageNumberPosition;
use Bambamboole\PdfUaClient\Exceptions\TemplateValidationException;
use Bambamboole\PdfUaClient\Template\Template;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixtureBlock;

beforeEach(function () {
    $registry = new BlockRegistry;
    $registry->register(TestFixtureBlock::class);
    $this->factory = new TemplateFactory(
        $registry,
        new TemplateSchemaCompiler(new PropsReflector),
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
    expect($template->config->page->margins->right)->toBe(20);
    expect($template->config->page->margins->bottom)->toBe(20);
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

it('respects the page numbers config default when enabled is omitted', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['pageNumbers' => ['position' => 'right']]],
        'rows' => [],
    ]);

    expect($template->config->page->pageNumbers->enabled)->toBeFalse();
    expect($template->config->page->pageNumbers->position)->toBe(PageNumberPosition::Right);
});

it('preserves typography align and color fields', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['typography' => ['family' => 'Inter', 'align' => 'center', 'color' => '#222']],
        'rows' => [],
    ]);

    expect($template->config->typography->family)->toBe('Inter');
    expect($template->config->typography->align)->toBe(Align::Center);
    expect($template->config->typography->color)->toBe('#222');
});

it('builds footer rows without pagination settings', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [
            'page' => [
                'footer' => [
                    'repeat' => true,
                    'rows' => [[
                        'blocks' => [
                            ['type' => 'test-fixture', 'id' => 'footer_note', 'config' => ['width' => '70%']],
                            ['type' => 'test-fixture', 'id' => 'footer_meta', 'config' => ['width' => '30%']],
                        ],
                    ]],
                ],
            ],
        ],
        'rows' => [],
    ]);

    expect($template->config->page->footer->repeat)->toBeTrue();
    expect(property_exists($template->config->page->footer, 'pageNumbers'))->toBeFalse();
    expect($template->config->page->footer->rows)->toHaveCount(1);
    expect($template->config->page->footer->rows[0]->blocks[0]->id)->toBe('footer_note');
    expect($template->config->page->footer->rows[0]->blocks[0]->config['width'])->toBe('70%');
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

it('builds template data layers from the top-level data object', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [
            ['blocks' => [['type' => 'test-fixture', 'id' => 'title']]],
        ],
        'data' => [
            'example' => ['title' => ['text' => 'Example title']],
            'defaults' => ['title' => ['text' => 'Fallback title']],
            'constants' => ['title' => ['badge' => 'Locked']],
        ],
    ]);

    expect($template->data->example)->toBe(['title' => ['text' => 'Example title']])
        ->and($template->data->defaults)->toBe(['title' => ['text' => 'Fallback title']])
        ->and($template->data->constants)->toBe(['title' => ['badge' => 'Locked']]);
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
