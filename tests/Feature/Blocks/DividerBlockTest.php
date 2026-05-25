<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\DividerBlock;
use Bambamboole\PdfUaClient\Config\DividerConfig;

it('renders an <hr>', function () {
    $block = new DividerBlock;
    $html = (string) $block->render(new DividerConfig(thickness: 1, lineColor: '#cccccc', style: 'dashed'));

    expect($html)->toBe('<hr>');
});
