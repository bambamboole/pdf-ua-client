<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Config\TypographyConfig;
use Bambamboole\PdfUaClient\Enums\FontWeight;
use Bambamboole\PdfUaClient\Support\CssRuleEmitter;

it('emits CSS properties only for set fields', function () {
    $t = new TypographyConfig(family: 'Inter', size: 11, weight: FontWeight::Bold);

    expect(CssRuleEmitter::for($t))->toBe(['' => "font-family: 'Inter'; font-size: 11pt; font-weight: 700;"]);
});

it('emits an empty array when all properties are null', function () {
    expect(CssRuleEmitter::for(new TypographyConfig))->toBe([]);
});

it('omits unset properties when emitting CSS', function () {
    expect(CssRuleEmitter::for(new TypographyConfig(size: 14)))->toBe(['' => 'font-size: 14pt;']);
});
