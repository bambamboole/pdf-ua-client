<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
use Bambamboole\PdfUaClient\Template\TemplateFactory;

it('sizes a multi-block row from each block config width and does not double-apply to the div', function (): void {
    $template = app(TemplateFactory::class)->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [[
            'blocks' => [
                ['type' => 'text', 'props' => ['text' => 'L'], 'config' => ['width' => '70%']],
                ['type' => 'text', 'props' => ['text' => 'R'], 'config' => ['width' => '30%']],
            ],
        ]],
    ]);

    $html = app(TemplateRenderer::class)->render($template);

    // cells sized from config.width
    expect($html)->toContain('<td style="width: 70%;">');
    expect($html)->toContain('<td style="width: 30%;">');
    // div width NOT also applied (no .block-N { width: ... } rule)
    expect($html)->not->toContain('.block-1 { width: 70%');
    expect($html)->not->toContain('.block-2 { width: 30%');
});

it('still honors row columnWidths and keeps the div width rule (legacy path)', function (): void {
    $template = app(TemplateFactory::class)->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [[
            'columnWidths' => ['60%', '40%'],
            'blocks' => [
                ['type' => 'text', 'props' => ['text' => 'L'], 'config' => ['width' => '50%']],
                ['type' => 'text', 'props' => ['text' => 'R']],
            ],
        ]],
    ]);
    $html = app(TemplateRenderer::class)->render($template);
    expect($html)->toContain('<td style="width: 60%;">');   // columnWidths wins
    expect($html)->toContain('.block-1 { width: 50%');        // div width still emitted
});
