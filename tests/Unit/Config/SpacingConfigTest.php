<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Config\SpacingConfig;
use Bambamboole\PdfUaClient\Support\CssRuleEmitter;

it('emits CSS properties only for set sides', function () {
    expect(CssRuleEmitter::for(new SpacingConfig(top: 10, bottom: 4)))
        ->toBe(['' => 'margin-top: 10mm; margin-bottom: 4mm;']);
});

it('emits an empty array when all sides are null', function () {
    expect(CssRuleEmitter::for(new SpacingConfig))->toBe([]);
});

it('emits all four sides in top, right, bottom, left order', function () {
    expect(CssRuleEmitter::for(new SpacingConfig(top: 1, right: 2, bottom: 3, left: 4)))
        ->toBe(['' => 'margin-top: 1mm; margin-right: 2mm; margin-bottom: 3mm; margin-left: 4mm;']);
});
