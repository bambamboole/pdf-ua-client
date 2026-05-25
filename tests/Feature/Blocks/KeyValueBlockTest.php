<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Blocks\KeyValueBlock;
use Bambamboole\PdfUaClient\Blocks\KeyValuePair;
use Bambamboole\PdfUaClient\Config\KeyValueConfig;

it('renders a label/value table with rows for each pair', function () {
    $block = new KeyValueBlock(entries: [
        new KeyValuePair(label: 'IBAN', value: 'DE89 3704 0044 0532 0130 00'),
        new KeyValuePair(label: 'BIC', value: 'COBADEFFXXX'),
    ]);

    $html = (string) $block->render(new KeyValueConfig);

    expect($html)->toContain('<table>');
    expect($html)->toContain('IBAN');
    expect($html)->toContain('DE89 3704 0044 0532 0130 00');
    expect($html)->toContain('BIC');
});
