<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Max;
use Bambamboole\PdfUaClient\Attributes\Min;
use Bambamboole\PdfUaClient\Block\BlockHydrator;
use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Config\SpacingConfig;
use Bambamboole\PdfUaClient\Config\TypographyConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;
use Bambamboole\PdfUaClient\Enums\Align;
use Bambamboole\PdfUaClient\Enums\FontWeight;
use Bambamboole\PdfUaClient\Exceptions\BlockDataValidationException;
use Bambamboole\PdfUaClient\Template\BlockInstance;

final readonly class HydrationBlockConfig extends BlockConfig
{
    public function __construct(
        ?TypographyConfig $typography = null,
        ?SpacingConfig $spacing = null,
        ?string $width = null,
        ?Align $align = null,
        #[Min(1)] #[Max(6)]
        public int $level = 2,
        public Align $textAlign = Align::Left,
    ) {
        parent::__construct($typography, $spacing, $width, $align);
    }
}

#[Block('hydration-block', config: HydrationBlockConfig::class)]
final class HydrationBlock implements BlockInterface
{
    public function __construct(public readonly string $text) {}

    public function render(HydrationBlockConfig $config): string
    {
        return '';
    }
}

beforeEach(function () {
    $registry = new BlockRegistry;
    $registry->register(HydrationBlock::class);
    $this->hydrator = new BlockHydrator($registry, new PropsReflector);
});

it('constructs a typed Block from raw props and config arrays', function () {
    $hydrated = $this->hydrator->hydrate(new BlockInstance(
        type: 'hydration-block',
        props: ['text' => 'hello'],
        config: ['level' => 3, 'textAlign' => 'center'],
    ));

    expect($hydrated->block)->toBeInstanceOf(HydrationBlock::class);
    expect($hydrated->block->text)->toBe('hello');
    expect($hydrated->config)->toBeInstanceOf(HydrationBlockConfig::class);
    /** @var HydrationBlockConfig $config */
    $config = $hydrated->config;
    expect($config->level)->toBe(3);
    expect($config->textAlign)->toBe(Align::Center);
});

it('uses constructor defaults when config is empty', function () {
    $hydrated = $this->hydrator->hydrate(new BlockInstance(
        type: 'hydration-block',
        props: ['text' => 'hi'],
    ));

    /** @var HydrationBlockConfig $config */
    $config = $hydrated->config;
    expect($config->level)->toBe(2);
    expect($config->textAlign)->toBe(Align::Left);
});

it('throws BlockDataValidationException when required props are missing', function () {
    expect(fn () => $this->hydrator->hydrate(new BlockInstance(
        type: 'hydration-block',
        props: [],
    )))->toThrow(BlockDataValidationException::class);
});

it('throws BlockDataValidationException when config violates a constraint', function () {
    expect(fn () => $this->hydrator->hydrate(new BlockInstance(
        type: 'hydration-block',
        props: ['text' => 'ok'],
        config: ['level' => 999],
    )))->toThrow(BlockDataValidationException::class);
});

it('coerces int values into int-backed enums', function () {
    $hydrated = $this->hydrator->hydrate(new BlockInstance(
        type: 'hydration-block',
        props: ['text' => 'Hi'],
        config: ['typography' => ['weight' => 700]],
    ));

    /** @var HydrationBlockConfig $config */
    $config = $hydrated->config;
    expect($config->typography?->weight)->toBe(FontWeight::Bold);
});
