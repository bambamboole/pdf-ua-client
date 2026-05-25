<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Attributes\ArrayOf;
use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Example;
use Bambamboole\PdfUaClient\Attributes\Format;
use Bambamboole\PdfUaClient\Attributes\Length;
use Bambamboole\PdfUaClient\Attributes\Max;
use Bambamboole\PdfUaClient\Attributes\Min;
use Bambamboole\PdfUaClient\Attributes\Pattern;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Config\PageConfig;
use Bambamboole\PdfUaClient\Config\SpacingConfig;
use Bambamboole\PdfUaClient\Config\TypographyConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;
use Bambamboole\PdfUaClient\Enums\Align;

#[Block('scalars-block')]
final readonly class ScalarsBlock implements BlockInterface
{
    public function __construct(
        public string $title,
        public int $count = 0,
        public float $ratio = 1.0,
        public bool $enabled = true,
    ) {}

    public function render(BlockConfig $config): string
    {
        return '';
    }
}

it('reflects scalar properties into a JSON Schema object', function () {
    $reflector = new PropsReflector;

    $schema = $reflector->reflect(ScalarsBlock::class);

    expect($schema['type'])->toBe('object');
    expect($schema['additionalProperties'])->toBeFalse();
    expect($schema['required'])->toBe(['title']);
    expect($schema['properties']['title'])->toBe(['type' => 'string']);
    expect($schema['properties']['count'])->toBe(['type' => 'integer', 'default' => 0]);
    expect($schema['properties']['ratio'])->toBe(['type' => 'number', 'default' => 1.0]);
    expect($schema['properties']['enabled'])->toBe(['type' => 'boolean', 'default' => true]);
});

#[Block('attr-block')]
final readonly class AttrBlock implements BlockInterface
{
    public function __construct(
        #[Min(1)] #[Max(6)] public int $level = 2,
        public Align $align = Align::Left,
        public ?string $color = null,
        #[Length(min: 1, max: 200)] public string $caption = '',
        #[Pattern('^#[0-9A-Fa-f]{6}$')] public string $hex = '#000000',
        #[Format('email')] public string $email = '',
        #[Description('Vertical spacing in mm')] public int $gap = 0,
        #[Title('Heading level')]
        #[Description('Pick 1 (largest) through 6 (smallest).')]
        public int $headingLevel = 2,
    ) {}

    public function render(BlockConfig $config): string
    {
        return '';
    }
}

it('applies #[Min] and #[Max] to numeric properties', function () {
    $schema = (new PropsReflector)->reflect(AttrBlock::class);
    expect($schema['properties']['level']['minimum'])->toBe(1);
    expect($schema['properties']['level']['maximum'])->toBe(6);
});

it('reflects backed enums to a JSON Schema enum', function () {
    $schema = (new PropsReflector)->reflect(AttrBlock::class);
    expect($schema['properties']['align']['enum'])->toBe(['left', 'center', 'right']);
});

it('reflects nullable types using type union with null', function () {
    $schema = (new PropsReflector)->reflect(AttrBlock::class);
    expect($schema['properties']['color']['type'])->toBe(['string', 'null']);
});

it('applies #[Length] / #[Pattern] / #[Format] / #[Description]', function () {
    $schema = (new PropsReflector)->reflect(AttrBlock::class);
    expect($schema['properties']['caption']['minLength'])->toBe(1);
    expect($schema['properties']['caption']['maxLength'])->toBe(200);
    expect($schema['properties']['hex']['pattern'])->toBe('^#[0-9A-Fa-f]{6}$');
    expect($schema['properties']['email']['format'])->toBe('email');
    expect($schema['properties']['gap']['description'])->toBe('Vertical spacing in mm');
});

it('applies #[Title] alongside #[Description] on the same parameter', function () {
    $schema = (new PropsReflector)->reflect(AttrBlock::class);
    expect($schema['properties']['headingLevel']['title'])->toBe('Heading level');
    expect($schema['properties']['headingLevel']['description'])->toBe('Pick 1 (largest) through 6 (smallest).');
});

final readonly class Pair
{
    public function __construct(public string $label, public string $value) {}
}

final readonly class ScalarListConfig
{
    public function __construct(
        #[ArrayOf('string')]
        public ?array $names = null,
        #[ArrayOf('int', 'string')]
        public ?array $widths = null,
    ) {}
}

#[Block('nested-block')]
final readonly class NestedBlock implements BlockInterface
{
    /** @param list<Pair> $entries */
    public function __construct(
        #[ArrayOf(Pair::class)]
        public array $entries = [],
        public ?Pair $primary = null,
    ) {}

    public function render(BlockConfig $config): string
    {
        return '';
    }
}

it('reflects #[ArrayOf] to a typed item schema', function () {
    $schema = (new PropsReflector)->reflect(NestedBlock::class);

    expect($schema['properties']['entries']['type'])->toBe('array');
    expect($schema['properties']['entries']['items']['type'])->toBe('object');
    expect($schema['properties']['entries']['items']['required'])->toBe(['label', 'value']);
    expect($schema['properties']['entries']['items']['properties']['label'])->toBe(['type' => 'string']);
});

it('reflects scalar #[ArrayOf] items for nullable arrays', function () {
    $schema = (new PropsReflector)->reflect(ScalarListConfig::class);

    expect($schema['properties']['names'])->toMatchArray([
        'type' => ['array', 'null'],
        'items' => ['type' => 'string'],
    ]);
    expect($schema['properties']['widths'])->toMatchArray([
        'type' => ['array', 'null'],
        'items' => ['type' => ['integer', 'string']],
    ]);
});

it('reflects nested object properties recursively', function () {
    $schema = (new PropsReflector)->reflect(NestedBlock::class);

    expect($schema['properties']['primary']['type'])->toBe(['object', 'null']);
    expect($schema['properties']['primary']['properties']['label'])->toBe(['type' => 'string']);
});

final readonly class SplitBlockConfig extends BlockConfig
{
    public function __construct(
        ?TypographyConfig $typography = null,
        ?SpacingConfig $spacing = null,
        ?string $width = null,
        ?Align $align = null,
        public int $level = 2,
        public Align $textAlign = Align::Left,
    ) {
        parent::__construct($typography, $spacing, $width, $align);
    }
}

#[Block('split-block', config: SplitBlockConfig::class)]
final readonly class SplitBlock implements BlockInterface
{
    public function __construct(public string $text = '') {}

    public function render(BlockConfig $config): string
    {
        return '';
    }
}

it('reflectBlock() exposes data and config sub-schemas separately', function () {
    $schemas = (new PropsReflector)->reflectBlock(SplitBlock::class);

    expect($schemas['data']['properties'])->toHaveKeys(['text']);
    expect($schemas['data']['properties'])->not->toHaveKey('level');
    expect($schemas['config']['properties'])->toHaveKeys(['level', 'textAlign', 'typography', 'spacing']);
    expect($schemas['config']['properties'])->not->toHaveKey('text');
});

it('reflectBlock() falls back to BlockConfig when the block has no #[Block(config:)]', function () {
    $schemas = (new PropsReflector)->reflectBlock(ScalarsBlock::class);

    expect($schemas['data']['properties'])->toHaveKeys(['title', 'count', 'ratio', 'enabled']);
    expect($schemas['config']['properties'])->toHaveKeys(['typography', 'spacing']);
});

it('emits the backing value as the default for a backed enum constructor parameter', function () {
    $schema = (new PropsReflector)->reflect(PageConfig::class);

    expect($schema['properties']['format']['default'])->toBe('A4');
});

it('emits examples from the Example attribute', function (): void {
    $class = new readonly class('x')
    {
        public function __construct(
            #[Example('Sample heading')]
            public string $text,
        ) {}
    };

    $schema = (new PropsReflector)->reflect($class::class);

    expect($schema['properties']['text']['examples'])->toBe(['Sample heading']);
});
