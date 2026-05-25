<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Tests\Support\PdfPageComparator;

beforeEach(function () {
    if (! extension_loaded('imagick')) {
        $this->markTestSkipped('ext-imagick is required for PDF comparison tests.');
    }
});

function pdfUaSolidPage(int $width, int $height, string $color): Imagick
{
    $page = new Imagick;
    $page->newImage($width, $height, new ImagickPixel($color));
    $page->setImageFormat('png');

    return $page;
}

function pdfUaDocument(Imagick ...$pages): Imagick
{
    $document = new Imagick;
    foreach ($pages as $page) {
        $document->addImage($page);
    }
    $document->setIteratorIndex(0);

    return $document;
}

/** @param list<array{int, int}> $pageSizes */
function pdfUaPdfBytes(array $pageSizes): string
{
    $document = new Imagick;
    foreach ($pageSizes as [$width, $height]) {
        $page = new Imagick;
        $page->newImage($width, $height, new ImagickPixel('white'));
        $page->setImageFormat('pdf');
        $document->addImage($page);
    }
    $document->setImageFormat('pdf');

    return $document->getImagesBlob();
}

function pdfUaCanRasterize(): bool
{
    try {
        $reader = new Imagick;
        $reader->setResolution(36, 36);
        $reader->readImageBlob(pdfUaPdfBytes([[10, 10]]));

        return $reader->getNumberImages() >= 1;
    } catch (Throwable) {
        return false;
    }
}

it('reports zero distance for identical pages', function () {
    $result = (new PdfPageComparator)->compare(
        pdfUaDocument(pdfUaSolidPage(100, 100, 'white')),
        pdfUaDocument(pdfUaSolidPage(100, 100, 'white')),
    );

    expect($result->pageCountMatches())->toBeTrue();
    expect($result->worstDistance())->toBe(0.0);
    expect($result->matches(PdfPageComparator::THRESHOLD))->toBeTrue();
});

it('detects a visually different page above the threshold', function () {
    $result = (new PdfPageComparator)->compare(
        pdfUaDocument(pdfUaSolidPage(100, 100, 'white')),
        pdfUaDocument(pdfUaSolidPage(100, 100, 'black')),
    );

    expect($result->worstDistance())->toBeGreaterThan(PdfPageComparator::THRESHOLD);
    expect($result->matches(PdfPageComparator::THRESHOLD))->toBeFalse();
});

it('flags a page-count mismatch as a failure', function () {
    $result = (new PdfPageComparator)->compare(
        pdfUaDocument(pdfUaSolidPage(100, 100, 'white')),
        pdfUaDocument(pdfUaSolidPage(100, 100, 'white'), pdfUaSolidPage(100, 100, 'white')),
    );

    expect($result->pageCountMatches())->toBeFalse();
    expect($result->expectedPages)->toBe(1);
    expect($result->actualPages)->toBe(2);
    expect($result->matches(PdfPageComparator::THRESHOLD))->toBeFalse();
});

it('exposes a per-page diff image for each compared page', function () {
    $result = (new PdfPageComparator)->compare(
        pdfUaDocument(pdfUaSolidPage(100, 100, 'white')),
        pdfUaDocument(pdfUaSolidPage(100, 100, 'black')),
    );

    expect($result->pages)->toHaveCount(1);
    expect($result->pages[0]->pageIndex)->toBe(0);
    expect($result->pages[0]->diffImage)->toBeInstanceOf(Imagick::class);
});

it('treats a page with mismatched dimensions as a failure', function () {
    $result = (new PdfPageComparator)->compare(
        pdfUaDocument(pdfUaSolidPage(100, 100, 'white')),
        pdfUaDocument(pdfUaSolidPage(120, 90, 'white')),
    );

    expect($result->matches(PdfPageComparator::THRESHOLD))->toBeFalse();
    expect($result->worstDistance())->toBeGreaterThan(PdfPageComparator::THRESHOLD);
});

it('reports rasterization support matching the environment', function () {
    expect(PdfPageComparator::isSupported())->toBe(pdfUaCanRasterize());
});

it('rasterizes every page of a PDF to a separate image', function () {
    if (! pdfUaCanRasterize()) {
        $this->markTestSkipped('Ghostscript is required to rasterize PDFs.');
    }

    $pages = (new PdfPageComparator)->rasterize(pdfUaPdfBytes([[120, 160], [120, 160], [120, 160]]), 72);

    expect($pages->getNumberImages())->toBe(3);
});
