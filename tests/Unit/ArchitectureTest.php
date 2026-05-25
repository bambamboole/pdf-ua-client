<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Blocks\DividerBlock;
use Bambamboole\PdfUaClient\Blocks\HeadingBlock;
use Bambamboole\PdfUaClient\Blocks\HtmlBlock;
use Bambamboole\PdfUaClient\Blocks\ImageBlock;
use Bambamboole\PdfUaClient\Blocks\KeyValueBlock;
use Bambamboole\PdfUaClient\Blocks\SpacerBlock;
use Bambamboole\PdfUaClient\Blocks\TableBlock;
use Bambamboole\PdfUaClient\Blocks\TextBlock;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;

arch('package source uses strict types')
    ->expect('Bambamboole\PdfUaClient')
    ->toUseStrictTypes();

arch('package source does not depend on the workbench app')
    ->expect('Bambamboole\PdfUaClient')
    ->not->toUse('Workbench\App');

arch('registered block classes implement the block contract')
    ->expect([
        DividerBlock::class,
        HeadingBlock::class,
        HtmlBlock::class,
        ImageBlock::class,
        KeyValueBlock::class,
        SpacerBlock::class,
        TableBlock::class,
        TextBlock::class,
    ])
    ->classes()
    ->toHaveAttribute(Block::class)
    ->toImplement(BlockInterface::class);
