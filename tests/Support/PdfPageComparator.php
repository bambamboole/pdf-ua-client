<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Tests\Support;

use Imagick;
use ImagickPixel;
use Throwable;

final class PdfPageComparator
{
    public const float THRESHOLD = 0.01;

    public const int DPI = 150;

    public static function isSupported(): bool
    {
        if (! extension_loaded('imagick')) {
            return false;
        }

        try {
            $probe = new Imagick;
            $probe->newImage(8, 8, new ImagickPixel('white'));
            $probe->setImageFormat('pdf');

            $reader = new Imagick;
            $reader->setResolution(36, 36);
            $reader->readImageBlob($probe->getImagesBlob());

            return $reader->getNumberImages() >= 1;
        } catch (Throwable) {
            return false;
        }
    }

    public function rasterize(string $pdfBytes, int $dpi = self::DPI): Imagick
    {
        $source = new Imagick;
        $source->setResolution($dpi, $dpi);
        $source->readImageBlob($pdfBytes);

        $pages = new Imagick;
        $pageCount = $source->getNumberImages();
        for ($index = 0; $index < $pageCount; $index++) {
            $source->setIteratorIndex($index);
            $page = $source->getImage();
            $page->setImageBackgroundColor(new ImagickPixel('white'));
            $page->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $page->setImageFormat('png');
            $pages->addImage($page);
        }
        $pages->setIteratorIndex(0);

        return $pages;
    }

    public function compare(Imagick $expected, Imagick $actual): ComparisonResult
    {
        $expectedPages = $expected->getNumberImages();
        $actualPages = $actual->getNumberImages();

        if ($expectedPages !== $actualPages) {
            return new ComparisonResult([], $expectedPages, $actualPages);
        }

        $pages = [];
        for ($index = 0; $index < $expectedPages; $index++) {
            $expected->setIteratorIndex($index);
            $actual->setIteratorIndex($index);

            $pages[] = $this->comparePage($index, $expected->getImage(), $actual->getImage());
        }

        return new ComparisonResult($pages, $expectedPages, $actualPages);
    }

    private function comparePage(int $index, Imagick $expected, Imagick $actual): PageComparison
    {
        if ($expected->getImageGeometry() !== $actual->getImageGeometry()) {
            return new PageComparison($index, 1.0, $actual);
        }

        [$diffImage, $distance] = $expected->compareImages($actual, Imagick::METRIC_ROOTMEANSQUAREDERROR);

        return new PageComparison($index, (float) $distance, $diffImage);
    }
}
