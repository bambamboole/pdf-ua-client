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
use Bambamboole\PdfUaClient\Rendering\RenderOptions;
use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
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
    $this->factory = new TemplateFactory($registry, new TemplateSchemaCompiler($reflector));
    $this->renderer = new TemplateRenderer(
        new BlockHydrator($registry, $reflector),
    );
});

it('wraps a one-block row in a presentation table with a single td', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'props' => ['text' => 'Invoice 001'], 'config' => ['level' => 1]]]],
        ],
    ]);

    $html = $this->renderer->render($template);

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
            'columnWidths' => ['60%', '40%'],
            'blocks' => [
                ['type' => 'text', 'props' => ['text' => 'Left column']],
                ['type' => 'text', 'props' => ['text' => 'Right column']],
            ],
        ]],
    ]);

    $html = $this->renderer->render($template);

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
        'rows' => [['blocks' => [['type' => 'text', 'props' => ['text' => 'x']]]]],
    ]);

    $print = $this->renderer->render($template, options: new RenderOptions(mode: 'print'));
    $preview = $this->renderer->render($template, options: new RenderOptions(mode: 'preview'));

    expect($print)->toContain('@page');
    expect($print)->toContain('margin: 25mm 20mm 20mm 25mm');
    expect($preview)->not->toContain('@page');
    expect($preview)->toContain('padding: 25mm 20mm 20mm 25mm');
});

it('merges runtime data over inline props by block id', function () {
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
            ['blocks' => [['type' => 'heading', 'props' => ['text' => 'A'], 'config' => ['level' => 2, 'typography' => ['family' => 'Inter', 'size' => 14]]]]],
        ],
    ]);

    $html = $this->renderer->render($template);

    expect($html)->toContain('<div class="block-1"><h2>A</h2></div>');
    expect($html)->toContain(".block-1 { font-family: 'Inter'; font-size: 14pt; }");
});

it('emits per-block alignment as a wrapper-class-scoped CSS rule', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'props' => ['text' => 'A'], 'config' => ['level' => 1, 'typography' => ['align' => 'center']]]]],
        ],
    ]);

    $html = $this->renderer->render($template);

    expect($html)->toContain('.block-1 { text-align: center; }');
});

it('produces HTML that uses only safe CSS', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'props' => ['text' => 'Safe'], 'config' => ['level' => 1]]]],
            ['blocks' => [['type' => 'text', 'props' => ['text' => 'Body']]]],
        ],
    ]);

    expect($this->renderer->render($template))->toContainOnlySafeCss();
});

it('emits per-instance spacing as a wrapper-class-scoped CSS rule', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [[
                'type' => 'text',
                'props' => ['text' => 'spaced'],
                'config' => ['spacing' => ['bottom' => 4]],
            ]]],
        ],
    ]);

    $html = $this->renderer->render($template);

    expect($html)->toContain('<div class="block-1"><p>spaced</p></div>');
    expect($html)->toContain('.block-1 { margin-bottom: 4mm; }');
});

it('emits no per-class rule for blocks whose config emits no CSS', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'html', 'props' => ['html' => '<span>plain</span>']]]],
        ],
    ]);

    $html = $this->renderer->render($template);

    expect($html)->toContain('<div class="block-1"><span>plain</span></div>');
    expect($html)->not->toContain('.block-1 {');
});

it('emits a hr { border: none; } base rule once per rendered document', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [['blocks' => [['type' => 'text', 'props' => ['text' => 'x']]]]],
    ]);

    $html = $this->renderer->render($template);

    expect(substr_count($html, 'hr { border: none; }'))->toBe(1);
});

it('emits template typography once on the body and lets CSS inheritance handle the rest', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [
            'page' => ['format' => 'A4'],
            'typography' => ['family' => 'Inter', 'size' => 10],
        ],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'props' => ['text' => 'Title'], 'config' => ['level' => 1]]]],
        ],
    ]);

    $html = $this->renderer->render($template);

    expect($html)->toContain("body { font-family: 'Inter'; font-size: 10pt; }");
    expect(substr_count($html, "font-family: 'Inter'"))->toBe(1);
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
                'props' => ['text' => 'Title'],
                'config' => [
                    'level' => 1,
                    'typography' => ['size' => 24],
                    'spacing' => ['bottom' => 6],
                ],
            ]]],
        ],
    ]);

    $html = $this->renderer->render($template);

    expect($html)->toContain('.block-1 { font-size: 24pt; margin-bottom: 6mm; }');
});

it('emits width and centered align as a wrapper-class-scoped positioning rule', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [[
                'type' => 'heading',
                'props' => ['text' => 'Centered'],
                'config' => ['level' => 1, 'width' => '50%', 'align' => 'center'],
            ]]],
        ],
    ]);

    $html = $this->renderer->render($template);

    expect($html)->toContain('<div class="block-1"><h1>Centered</h1></div>');
    expect($html)->toContain('.block-1 { width: 50%; margin-left: auto; margin-right: auto; }');
});

it('emits right align as a single margin-left: auto declaration', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [[
                'type' => 'heading',
                'props' => ['text' => 'Right'],
                'config' => ['level' => 1, 'width' => '30mm', 'align' => 'right'],
            ]]],
        ],
    ]);

    $html = $this->renderer->render($template);

    expect($html)->toContain('.block-1 { width: 30mm; margin-left: auto; }');
});

it('emits no positioning rule when width and align are both unset', function () {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'html', 'props' => ['html' => '<span>x</span>']]]],
        ],
    ]);

    $html = $this->renderer->render($template);

    expect($html)->not->toContain('.block-1 {');
});

it('emits @page page-number rule only when pageNumbers is configured', function () {
    $without = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [['blocks' => [['type' => 'text', 'props' => ['text' => 'x']]]]],
    ]);

    $with = $this->factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4', 'pageNumbers' => ['position' => 'right']]],
        'rows' => [['blocks' => [['type' => 'text', 'props' => ['text' => 'x']]]]],
    ]);

    expect($this->renderer->render($without))->not->toContain('@bottom-');
    expect($this->renderer->render($with))->toContain('@bottom-right');
});
