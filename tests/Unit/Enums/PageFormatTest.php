<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Enums\PageFormat;

it('returns the correct height in mm for each page format', function (PageFormat $format, float $expected) {
    expect($format->heightMm())->toBe($expected);
})->with([
    [PageFormat::A4, 297.0],
    [PageFormat::A5, 210.0],
    [PageFormat::Letter, 279.4],
    [PageFormat::Legal, 355.6],
]);
