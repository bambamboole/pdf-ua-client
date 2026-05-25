<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Attributes\CssRule;
use Bambamboole\PdfUaClient\Enums\FontWeight;
use Bambamboole\PdfUaClient\Support\CssRuleEmitter;

final readonly class EmitterScalarFixture
{
    public function __construct(
        #[CssRule(key: 'font-family', value: "'{value}'")]
        public ?string $family = null,
        #[CssRule(key: 'font-size', value: '{value}pt')]
        public ?int $size = null,
    ) {}
}

final readonly class EmitterEnumFixture
{
    public function __construct(
        #[CssRule(key: 'font-weight', value: '{value}')]
        public ?FontWeight $weight = null,
    ) {}
}

final readonly class EmitterUnannotatedFixture
{
    public function __construct(
        public ?string $name = null,
        #[CssRule(key: 'color', value: '{value}')]
        public ?string $color = null,
    ) {}
}

final readonly class EmitterNestedFixture
{
    public function __construct(
        public ?EmitterScalarFixture $typography = null,
        #[CssRule(key: 'color', value: '{value}')]
        public ?string $color = null,
    ) {}
}

final readonly class EmitterSelectorFixture
{
    public function __construct(
        #[CssRule(key: 'max-height', value: '{value}px', selector: 'img')]
        public ?int $maxHeight = null,
        #[CssRule(key: 'color', value: '{value}')]
        public ?string $color = null,
    ) {}
}

it('returns an empty array for null input', function () {
    expect(CssRuleEmitter::for(null))->toBe([]);
});

it('emits a key:value pair for a scalar property annotated with CssRule', function () {
    $css = CssRuleEmitter::for(new EmitterScalarFixture(family: 'Inter', size: 11));

    expect($css)->toBe(['' => "font-family: 'Inter'; font-size: 11pt;"]);
});

it('substitutes the backed enum value when emitting', function () {
    $css = CssRuleEmitter::for(new EmitterEnumFixture(weight: FontWeight::Bold));

    expect($css)->toBe(['' => 'font-weight: 700;']);
});

it('skips properties without a CssRule attribute', function () {
    $css = CssRuleEmitter::for(new EmitterUnannotatedFixture(name: 'ignored', color: '#000'));

    expect($css)->toBe(['' => 'color: #000;']);
});

it('recurses into nested object properties', function () {
    $config = new EmitterNestedFixture(
        typography: new EmitterScalarFixture(family: 'Inter', size: 10),
        color: '#111',
    );

    expect(CssRuleEmitter::for($config))->toBe(['' => "font-family: 'Inter'; font-size: 10pt; color: #111;"]);
});

it('skips null properties', function () {
    $css = CssRuleEmitter::for(new EmitterScalarFixture(family: 'Inter'));

    expect($css)->toBe(['' => "font-family: 'Inter';"]);
});

it('groups descendant-selector rules by their selector suffix', function () {
    $css = CssRuleEmitter::for(new EmitterSelectorFixture(maxHeight: 80, color: '#222'));

    expect($css)->toBe([
        'img' => 'max-height: 80px;',
        '' => 'color: #222;',
    ]);
});
