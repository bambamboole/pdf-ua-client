<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Exceptions\TemplateValidationException;
use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
use Bambamboole\PdfUaClient\Template\TemplateFactory;

it('sizes a multi-block row from each block config width and does not double-apply to the div', function (): void {
    $template = app(TemplateFactory::class)->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [[
            'blocks' => [
                ['type' => 'text', 'id' => 'l', 'config' => ['width' => '70%']],
                ['type' => 'text', 'id' => 'r', 'config' => ['width' => '30%']],
            ],
        ]],
    ]);

    $html = app(TemplateRenderer::class)->render($template, ['l' => ['text' => 'L'], 'r' => ['text' => 'R']]);

    // cells sized from config.width
    expect($html)->toContain('<td style="width: 70%;">');
    expect($html)->toContain('<td style="width: 30%;">');
    // div width NOT also applied (no .block-N { width: ... } rule)
    expect($html)->not->toContain('.block-1 { width: 70%');
    expect($html)->not->toContain('.block-2 { width: 30%');
});

it('rejects row columnWidths because block width owns column sizing', function (): void {
    expect(fn () => app(TemplateFactory::class)->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [[
            'columnWidths' => ['60%', '40%'],
            'blocks' => [
                ['type' => 'text', 'id' => 'l', 'config' => ['width' => '50%']],
                ['type' => 'text', 'id' => 'r'],
            ],
        ]],
    ]))->toThrow(TemplateValidationException::class);
});
