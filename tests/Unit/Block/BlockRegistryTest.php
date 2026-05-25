<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Config\BlockConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;
use Bambamboole\PdfUaClient\Exceptions\BlockTypeNotRegisteredException;
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixtureBlock;
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixtureBlockConfig;

it('registers a block and resolves it by type identifier', function () {
    $registry = new BlockRegistry;

    $registry->register(TestFixtureBlock::class);

    expect($registry->resolve('test-fixture'))->toBe(TestFixtureBlock::class);
});

it('lists all registered block classes', function () {
    $registry = new BlockRegistry;
    $registry->register(TestFixtureBlock::class);

    expect($registry->all())->toBe(['test-fixture' => TestFixtureBlock::class]);
});

it('throws when the type is not registered', function () {
    $registry = new BlockRegistry;

    expect(fn () => $registry->resolve('unknown'))
        ->toThrow(BlockTypeNotRegisteredException::class, 'unknown');
});

it('throws when registering a class without #[Block] attribute', function () {
    $registry = new BlockRegistry;

    expect(fn () => $registry->register(stdClass::class))
        ->toThrow(InvalidArgumentException::class, 'must declare #[Block(...)]');
});

#[Block('misaligned-fixture', config: TestFixtureBlockConfig::class)]
final class MisalignedRenderBlock implements BlockInterface
{
    public function render(BlockConfig $config): string
    {
        return '';
    }
}

it('throws when the render() parameter type does not match #[Block(config:)]', function () {
    $registry = new BlockRegistry;

    expect(fn () => $registry->register(MisalignedRenderBlock::class))
        ->toThrow(InvalidArgumentException::class, 'must match the #[Block(config:)]');
});
