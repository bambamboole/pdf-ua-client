<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Fonts\FontDefinition;
use Bambamboole\PdfUaClient\Fonts\FontRegistry;

it('loads font definitions from config shaped arrays', function () {
    $registry = FontRegistry::fromConfig([
        'inter' => [
            'label' => 'Inter',
            'family' => 'Inter',
            'url' => 'https://example.test/inter.woff2',
            'weight' => '400 700',
            'style' => 'normal',
        ],
    ]);

    expect($registry->keys())->toBe(['inter']);
    expect($registry->get('inter'))->toEqual(new FontDefinition(
        key: 'inter',
        label: 'Inter',
        family: 'Inter',
        url: 'https://example.test/inter.woff2',
        weight: '400 700',
        style: 'normal',
    ));
});

it('registers additional fonts dynamically', function () {
    $registry = new FontRegistry;

    $registry->register(
        key: 'brand',
        label: 'Brand Sans',
        family: 'Brand Sans',
        url: 'https://example.test/brand.woff2',
    );

    expect($registry->keys())->toBe(['brand']);
    expect($registry->get('brand')?->family)->toBe('Brand Sans');
});

it('accepts system fonts without a public font file url', function () {
    $registry = FontRegistry::fromConfig([
        'Georgia' => [
            'label' => 'Georgia',
            'family' => 'Georgia',
        ],
    ]);

    expect($registry->get('Georgia'))->toEqual(new FontDefinition(
        key: 'Georgia',
        label: 'Georgia',
        family: 'Georgia',
    ));
});
