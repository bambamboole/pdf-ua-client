<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\PropsReflector;
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

it('renders svg data urls inline so the pdf renderer can display them', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><script>alert(1)</script><rect width="10" height="10" fill="#111827"/></svg>';
    $block = new ImageBlock(src: 'data:image/svg+xml;base64,'.base64_encode($svg), alt: 'Logo');

    $html = (string) $block->render(new ImageConfig);

    expect($html)->toStartWith('<svg ');
    expect($html)->toContain('role="img"');
    expect($html)->toContain('aria-label="Logo"');
    expect($html)->toContain('<rect');
    expect($html)->not->toContain('<script');
});

it('documents image sources as uploadable image data urls or urls', function () {
    $schema = (new PropsReflector)->reflect(ImageBlock::class);

    expect($schema['properties']['src'])->toMatchArray([
        'title' => 'Image source',
        'description' => 'Public image URL or uploaded image data URL. Uploads are limited to 200 KB.',
        'maxLength' => 280000,
    ]);
    expect($schema['properties']['src']['examples'][0])->toStartWith('data:image/svg+xml;base64,');
});
