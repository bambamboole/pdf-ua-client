<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Config\TableConfig;

it('emits striped style as wrapper-scoped descendant CSS', function () {
    $config = new TableConfig(style: 'striped');

    expect($config->cssRules('block-1'))
        ->toBe('.block-1 tbody tr:nth-child(even) { background-color: #f9fafb; }');
});

it('emits bordered style as wrapper-scoped descendant CSS', function () {
    $config = new TableConfig(style: 'bordered');

    $css = $config->cssRules('block-1');

    expect($css)->toContain('.block-1 { border-collapse: collapse; }');
    expect($css)->toContain('.block-1 th, .block-1 td { border: 1px solid #d1d5db; }');
});

it('emits minimal style as wrapper-scoped descendant CSS', function () {
    $config = new TableConfig(style: 'minimal');

    $css = $config->cssRules('block-1');

    expect($css)->toContain('.block-1 thead tr { border-bottom: 2px solid #1a1a2e; }');
    expect($css)->toContain('.block-1 tbody tr { border-bottom: 1px solid #e5e7eb; }');
});

it('emits an empty string for an unknown style', function () {
    $config = new TableConfig(style: 'unknown');

    expect($config->cssRules('block-1'))->toBe('');
});
