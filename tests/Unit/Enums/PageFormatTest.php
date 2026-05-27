<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Enums\PageFormat;

it('returns page dimensions and CSS size for each page format', function (string $format, float $width, float $height, string $cssSize) {
    $format = PageFormat::from($format);

    expect($format->widthMm())->toBe($width)
        ->and($format->heightMm())->toBe($height)
        ->and($format->cssSize())->toBe($cssSize);
})->with([
    ['A4', 210.0, 297.0, 'A4'],
    ['A5', 148.0, 210.0, 'A5'],
    ['Letter', 215.9, 279.4, 'Letter'],
    ['Legal', 215.9, 355.6, 'Legal'],
    ['ParcelLabel4x6', 101.6, 152.4, '101.6mm 152.4mm'],
]);
