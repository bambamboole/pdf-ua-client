<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\TextBlock;
use Bambamboole\PdfUaClient\Config\BlockConfig;

it('wraps single-paragraph text in a <p>', function () {
    $block = new TextBlock(text: 'Hello world.');
    expect((string) $block->render(new BlockConfig))
        ->toBe('<p>Hello world.</p>');
});

it('splits double-newlines into multiple paragraphs', function () {
    $block = new TextBlock(text: "First paragraph.\n\nSecond paragraph.");
    $html = (string) $block->render(new BlockConfig);

    expect($html)->toContain('<p>First paragraph.</p>');
    expect($html)->toContain('<p>Second paragraph.</p>');
});

it('preserves single newlines as <br>', function () {
    $block = new TextBlock(text: "Line one\nLine two");
    $html = (string) $block->render(new BlockConfig);
    expect($html)->toContain('Line one<br>Line two');
});

it('escapes text', function () {
    $block = new TextBlock(text: '<b>x</b>');
    expect((string) $block->render(new BlockConfig))->toContain('&lt;b&gt;x&lt;/b&gt;');
});
