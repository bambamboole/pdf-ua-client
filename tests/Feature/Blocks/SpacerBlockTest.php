<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\SpacerBlock;
use Bambamboole\PdfUaClient\Config\SpacerConfig;

it('renders empty content; the wrapper supplies the height', function () {
    $block = new SpacerBlock;
    expect((string) $block->render(new SpacerConfig(height: 10)))->toBe('');
});
