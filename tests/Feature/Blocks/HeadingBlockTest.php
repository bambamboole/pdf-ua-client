<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\HeadingBlock;
use Bambamboole\PdfUaClient\Config\HeadingConfig;

it('renders an h{level} element with the text', function () {
    $block = new HeadingBlock(text: 'Hello');

    $html = (string) $block->render(new HeadingConfig(level: 2));

    expect($html)->toBe('<h2>Hello</h2>');
});

it('escapes text content', function () {
    $block = new HeadingBlock(text: '<script>x</script>');
    $html = (string) $block->render(new HeadingConfig);
    expect($html)->not->toContain('<script>');
    expect($html)->toContain('&lt;script&gt;');
});
