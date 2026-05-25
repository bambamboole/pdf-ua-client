<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\HtmlBlock;
use Bambamboole\PdfUaClient\Config\BlockConfig;

it('emits raw HTML as-is', function () {
    $block = new HtmlBlock(html: '<div class="custom">Raw</div>');
    expect((string) $block->render(new BlockConfig))
        ->toBe('<div class="custom">Raw</div>');
});
