<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\ImageBlock;
use Bambamboole\PdfUaClient\Config\ImageConfig;

it('renders an <img> with src and alt', function () {
    $block = new ImageBlock(src: 'logo.png', alt: 'Logo');
    $html = (string) $block->render(new ImageConfig(maxHeight: 80));

    expect($html)->toBe('<img src="logo.png" alt="Logo">');
});

it('escapes src and alt to prevent injection', function () {
    $block = new ImageBlock(src: 'x"onerror="alert(1)', alt: '<>');
    $html = (string) $block->render(new ImageConfig);
    expect($html)->not->toContain('"onerror');
    expect($html)->toContain('alt="&lt;&gt;"');
});
