<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockHydrator;
use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Blocks\DividerBlock;
use Bambamboole\PdfUaClient\Blocks\HeadingBlock;
use Bambamboole\PdfUaClient\Blocks\HtmlBlock;
use Bambamboole\PdfUaClient\Blocks\ImageBlock;
use Bambamboole\PdfUaClient\Blocks\KeyValueBlock;
use Bambamboole\PdfUaClient\Blocks\SpacerBlock;
use Bambamboole\PdfUaClient\Blocks\TableBlock;
use Bambamboole\PdfUaClient\Blocks\TextBlock;
use Bambamboole\PdfUaClient\Exceptions\DataValidationException;
use Bambamboole\PdfUaClient\Exceptions\TemplateValidationException;
use Bambamboole\PdfUaClient\Fonts\FontRegistry;
use Bambamboole\PdfUaClient\Rendering\RenderOptions;
use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Bambamboole\PdfUaClient\Template\ExampleRegistry;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;

beforeEach(function () {
    $registry = new BlockRegistry;
    foreach ([
        HeadingBlock::class,
        TextBlock::class,
        HtmlBlock::class,
        ImageBlock::class,
        SpacerBlock::class,
        DividerBlock::class,
        KeyValueBlock::class,
        TableBlock::class,
    ] as $blockClass) {
        $registry->register($blockClass);
    }

    $reflector = new PropsReflector;
    $this->factory = new TemplateFactory($registry, new TemplateSchemaCompiler($reflector, new ExampleRegistry));
    $this->fonts = new FontRegistry;
    $this->renderer = new TemplateRenderer(
        new BlockHydrator($registry),
        new DataSchemaCompiler($reflector, $registry, $this->factory),
        $this->fonts,
    );
});

it('wraps a one-block row in a presentation table with a single td', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]],
        ],
    ]);

    $html = $this->renderer->render($template, ['h' => ['text' => 'Invoice 001']]);

    expect($html)->toContain('<!DOCTYPE html>');
    expect($html)->toContain('<table class="row" role="presentation"><tr><td><div class="block-1"><h1>Invoice 001</h1></div></td></tr></table>');
    expect($html)->toContain('<style>');
    expect($html)->toContain('@page');
});

it('renders a multi-block row as a presentation table with one td per block', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [[
            'blocks' => [
                ['type' => 'text', 'id' => 'l', 'config' => ['width' => '60%']],
                ['type' => 'text', 'id' => 'r', 'config' => ['width' => '40%']],
            ],
        ]],
    ]);

    $html = $this->renderer->render($template, ['l' => ['text' => 'Left column'], 'r' => ['text' => 'Right column']]);

    expect($html)->toContain('<table class="row" role="presentation">');
    expect($html)->toContain('<td style="width: 60%;">');
    expect($html)->toContain('<td style="width: 40%;">');
    expect($html)->toContain('<div class="block-1"><p>Left column</p></div>');
    expect($html)->toContain('<div class="block-2"><p>Right column</p></div>');
});

it('emits @page in print mode and body padding in preview mode', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4', 'margins' => ['top' => 25, 'right' => 20, 'bottom' => 20, 'left' => 25]]],
        'rows' => [['blocks' => [['type' => 'text', 'id' => 'x']]]],
    ]);

    $print = $this->renderer->render($template, ['x' => ['text' => 'x']], options: new RenderOptions(mode: 'print'));
    $preview = $this->renderer->render($template, ['x' => ['text' => 'x']], options: new RenderOptions(mode: 'preview'));

    expect($print)->toContain('@page');
    expect($print)->toContain('margin: 25mm 20mm 20mm 25mm');
    expect($preview)->not->toContain('@page');
    expect($preview)->toContain('padding: 25mm 20mm 20mm 25mm');
});

it('supplies block content via runtime data by block id', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]]]],
        ],
    ]);

    $html = $this->renderer->render($template, runtimeData: ['title' => ['text' => 'Runtime title']]);

    expect($html)->toContain('Runtime title');
});

it('emits per-block typography as a wrapper-class-scoped CSS rule', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 2, 'typography' => ['family' => 'Inter', 'size' => 14]]]]],
        ],
    ]);

    $html = $this->renderer->render($template, ['h' => ['text' => 'A']]);

    expect($html)->toContain('<div class="block-1"><h2>A</h2></div>');
    expect($html)->toContain(".block-1 { font-family: 'Inter'; font-size: 14pt; }");
});

it('resolves registered font keys to font face rules and CSS families', function () {
    $this->fonts->register(
        key: 'inter',
        label: 'Inter',
        family: 'Inter',
        url: 'https://example.test/inter.woff2',
        weight: '400 700',
    );

    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4'], 'typography' => ['family' => 'inter', 'size' => 10]],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 2, 'typography' => ['family' => 'inter']]]]],
        ],
    ]);

    $html = $this->renderer->render($template, ['h' => ['text' => 'A']]);

    expect(substr_count($html, '@font-face'))->toBe(1);
    expect($html)->toContain("font-family: 'Inter'");
    expect($html)->toContain('src: url("https://example.test/inter.woff2") format("woff2")');
    expect($html)->not->toContain("font-family: 'inter'");
});

it('emits per-block alignment as a wrapper-class-scoped CSS rule', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1, 'typography' => ['align' => 'center']]]]],
        ],
    ]);

    $html = $this->renderer->render($template, ['h' => ['text' => 'A']]);

    expect($html)->toContain('.block-1 { text-align: center; }');
});

it('produces HTML that uses only safe CSS', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]],
            ['blocks' => [['type' => 'text', 'id' => 't']]],
        ],
    ]);

    expect($this->renderer->render($template, ['h' => ['text' => 'Safe'], 't' => ['text' => 'Body']]))->toContainOnlySafeCss();
});

it('emits per-instance spacing as a wrapper-class-scoped CSS rule', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [[
                'type' => 'text',
                'id' => 's',
                'config' => ['spacing' => ['bottom' => 4]],
            ]]],
        ],
    ]);

    $html = $this->renderer->render($template, ['s' => ['text' => 'spaced']]);

    expect($html)->toContain('<div class="block-1"><p>spaced</p></div>');
    expect($html)->toContain('.block-1 { margin-bottom: 4mm; }');
});

it('emits no per-class rule for blocks whose config emits no CSS', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'html', 'id' => 'x']]],
        ],
    ]);

    $html = $this->renderer->render($template, ['x' => ['html' => '<span>plain</span>']]);

    expect($html)->toContain('<div class="block-1"><span>plain</span></div>');
    expect($html)->not->toContain('.block-1 {');
});

it('emits base document and table styling once per rendered document', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [['blocks' => [['type' => 'text', 'id' => 'x']]]],
    ]);

    $html = $this->renderer->render($template, ['x' => ['text' => 'x']]);

    expect(substr_count((string) $html, 'hr { border: none; border-top: 1px solid #d1d5db; margin: 2.5mm 0; }'))->toBe(1);
    expect($html)->toContain('.key-value { display: inline-table; border-collapse: collapse; text-align: left; }');
    expect($html)->toContain('.key-value td { padding: 0.65mm 0 0.65mm 3mm; vertical-align: top; }');
    expect($html)->toContain('.data-table { width: 100%; border-collapse: collapse; text-align: left; -fs-table-paginate: paginate; }');
});

it('emits divider style on the hr only', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [[
            'blocks' => [[
                'type' => 'divider',
                'config' => ['thickness' => 2, 'lineColor' => '#475569', 'style' => 'dashed'],
            ]],
        ]],
    ]);

    $html = $this->renderer->render($template);

    expect($html)->toContain('.block-1 hr { border-top-width: 2pt; border-top-color: #475569; border-top-style: dashed; }');
    expect($html)->not->toContain('.block-1 { border-top-width');
});

it('emits template typography once on the body and lets CSS inheritance handle the rest', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [
            'page' => ['format' => 'A4'],
            'typography' => ['family' => 'Inter', 'size' => 10],
        ],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]],
        ],
    ]);

    $html = $this->renderer->render($template, ['h' => ['text' => 'Title']]);

    expect($html)->toContain("body { font-family: 'Inter'; font-size: 10pt; }");
    expect(substr_count((string) $html, "font-family: 'Inter'"))->toBe(1);
});

it('combines per-block typography and spacing into a single wrapper-class-scoped rule', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [
            'page' => ['format' => 'A4'],
            'typography' => ['family' => 'Inter', 'size' => 10],
        ],
        'rows' => [
            ['blocks' => [[
                'type' => 'heading',
                'id' => 'h',
                'config' => [
                    'level' => 1,
                    'typography' => ['size' => 24],
                    'spacing' => ['bottom' => 6],
                ],
            ]]],
        ],
    ]);

    $html = $this->renderer->render($template, ['h' => ['text' => 'Title']]);

    expect($html)->toContain('.block-1 { font-size: 24pt; margin-bottom: 6mm; }');
});

it('emits width and centered align as a wrapper-class-scoped positioning rule', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [[
                'type' => 'heading',
                'id' => 'h',
                'config' => ['level' => 1, 'width' => '50%', 'align' => 'center'],
            ]]],
        ],
    ]);

    $html = $this->renderer->render($template, ['h' => ['text' => 'Centered']]);

    expect($html)->toContain('<div class="block-1"><h1>Centered</h1></div>');
    expect($html)->toContain('.block-1 { width: 50%; margin-left: auto; margin-right: auto; }');
});

it('emits right align as margin-left auto with right-aligned inline content', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [[
                'type' => 'heading',
                'id' => 'h',
                'config' => ['level' => 1, 'width' => '30mm', 'align' => 'right'],
            ]]],
        ],
    ]);

    $html = $this->renderer->render($template, ['h' => ['text' => 'Right']]);

    expect($html)->toContain('.block-1 { width: 30mm; margin-left: auto; text-align: right; }');
});

it('emits no positioning rule when width and align are both unset', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'html', 'id' => 'x']]],
        ],
    ]);

    $html = $this->renderer->render($template, ['x' => ['html' => '<span>x</span>']]);

    expect($html)->not->toContain('.block-1 {');
});

it('emits @page page-number rule only when page numbers are enabled', function () {
    $without = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4', 'pageNumbers' => ['enabled' => false, 'position' => 'right']]],
        'rows' => [['blocks' => [['type' => 'text', 'id' => 'x']]]],
    ]);

    $with = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4', 'pageNumbers' => ['enabled' => true, 'position' => 'right']]],
        'rows' => [['blocks' => [['type' => 'text', 'id' => 'x']]]],
    ]);

    expect($this->renderer->render($without, ['x' => ['text' => 'x']]))->not->toContain('@bottom-');
    expect($this->renderer->render($with, ['x' => ['text' => 'x']]))->toContain('@bottom-right');
});

it('renders fold and punch marks in print mode', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4', 'foldMarks' => true, 'punchMarks' => true]],
        'rows' => [['blocks' => [['type' => 'text', 'id' => 'x']]]],
    ]);

    $printHtml = $this->renderer->render($template, ['x' => ['text' => 'Body']], options: new RenderOptions(mode: 'print'));
    $previewHtml = $this->renderer->render($template, ['x' => ['text' => 'Body']], options: new RenderOptions(mode: 'preview'));

    expect($printHtml)->toContain('<div class="page-marks" aria-hidden="true"><span class="page-mark page-mark-fold-top"></span><span class="page-mark page-mark-fold-bottom"></span><span class="page-mark page-mark-punch"></span></div>');
    expect($printHtml)->toContain('@page { @left-top { content: element(pageMarkFoldTop); width: 25mm; } @left-bottom { content: element(pageMarkFoldBottom); width: 25mm; } }');
    expect($printHtml)->toContain('@page { @left-middle { content: element(pageMarkPunch); width: 25mm; } }');
    expect($printHtml)->toContain('.page-mark { display: block; width: 5mm; border-top: 0.2mm solid #9ca3af; }');
    expect($printHtml)->toContain('.page-mark-fold-top { position: running(pageMarkFoldTop); margin-top: 67mm; }');
    expect($printHtml)->toContain('.page-mark-fold-bottom { position: running(pageMarkFoldBottom); margin-bottom: 85mm; }');
    expect($printHtml)->toContain('.page-mark-punch { position: running(pageMarkPunch); }');
    expect($previewHtml)->not->toContain('page-marks');
});

it('renders repeated footer rows and page numbers through the page margin box in print mode', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [
            'page' => [
                'format' => 'A4',
                'margins' => ['top' => 25, 'right' => 20, 'bottom' => 20, 'left' => 25],
                'pageNumbers' => ['enabled' => true, 'position' => 'right'],
                'footer' => [
                    'repeat' => true,
                    'rows' => [[
                        'blocks' => [
                            ['type' => 'text', 'id' => 'footer_note', 'config' => ['width' => '70%']],
                            ['type' => 'text', 'id' => 'footer_meta', 'config' => ['width' => '30%']],
                        ],
                    ]],
                ],
            ],
        ],
        'rows' => [['blocks' => [['type' => 'text', 'id' => 'body']]]],
    ]);

    $html = $this->renderer->render($template, [
        'body' => ['text' => 'Body'],
        'footer_note' => ['text' => 'Confidential'],
        'footer_meta' => ['text' => 'ACME GmbH'],
    ], options: new RenderOptions(mode: 'print'));

    expect($html)->toContain('@page { size: A4; margin: 25mm 20mm 28mm 25mm; }');
    expect($html)->toContain('@page { @bottom-center { content: element(pageFooter); } }');
    expect($html)->toContain('@page { @bottom-right { content: counter(page) " / " counter(pages); font-size: 8pt; color: #9ca3af; vertical-align: bottom; padding-bottom: 4mm; } }');
    expect($html)->not->toContain('.page-footer-repeated::after');
    expect($html)->toContain('<footer class="page-footer page-footer-repeated" role="contentinfo">');
    expect($html)->toContain('<p>Confidential</p>');
    expect($html)->toContain('position: running(pageFooter); width: 100%;');
    expect($html)->toContain('.page-footer-repeated .row > tbody > tr > td:first-child, .page-footer-repeated .row > tr > td:first-child { padding-left: 2.2mm; }');
    expect($html)->toContain('.page-footer-repeated .row > tbody > tr > td:last-child, .page-footer-repeated .row > tr > td:last-child { padding-right: 2.2mm; }');
});

it('renders footer rows inline in preview mode', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [
            'page' => [
                'format' => 'A4',
                'footer' => [
                    'repeat' => true,
                    'rows' => [[
                        'blocks' => [['type' => 'text', 'id' => 'footer_note']],
                    ]],
                ],
            ],
        ],
        'rows' => [['blocks' => [['type' => 'text', 'id' => 'body']]]],
    ]);

    $html = $this->renderer->render($template, [
        'body' => ['text' => 'Body'],
        'footer_note' => ['text' => 'Preview footer'],
    ], options: new RenderOptions(mode: 'preview'));

    expect($html)->not->toContain('<footer class="page-footer page-footer-repeated"');
    expect($html)->toContain('<footer class="page-footer page-footer-preview" role="contentinfo">');
    expect($html)->toContain('<p>Preview footer</p>');
});

it('rejects a data payload that violates the template data contract', function () {
    $template = $this->factory->fromArray([
        'version' => 1, 'config' => ['page' => ['format' => 'A4']],
        'rows' => [['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]]],
    ]);

    expect(fn () => $this->renderer->render($template, []))
        ->toThrow(DataValidationException::class);
});

it('rejects a block config that violates a schema constraint', function () {
    expect(fn () => $this->factory->fromArray([
        'version' => 1, 'config' => ['page' => ['format' => 'A4']],
        'rows' => [['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 7]]]]],
    ]))->toThrow(TemplateValidationException::class);
});
