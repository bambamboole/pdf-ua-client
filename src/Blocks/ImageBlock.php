<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Blocks;

use Bambamboole\PdfUaClient\Attributes\Block;
use Bambamboole\PdfUaClient\Attributes\Description;
use Bambamboole\PdfUaClient\Attributes\Example;
use Bambamboole\PdfUaClient\Attributes\Length;
use Bambamboole\PdfUaClient\Attributes\Title;
use Bambamboole\PdfUaClient\Config\ImageConfig;
use Bambamboole\PdfUaClient\Contracts\BlockInterface;
use DOMDocument;
use DOMElement;

#[Block('image', config: ImageConfig::class)]
#[Title('Image')]
final readonly class ImageBlock implements BlockInterface
{
    public function __construct(
        #[Title('Image source')]
        #[Description('Public image URL or uploaded image data URL. Uploads are limited to 200 KB.')]
        #[Length(max: 280000)]
        #[Example('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCAyMDAgODAiPjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iODAiIGZpbGw9IiMxMTE4MjciLz48dGV4dCB4PSIyNCIgeT0iNDgiIGZpbGw9IiNmOWZhZmIiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIyMiIgZm9udC13ZWlnaHQ9IjcwMCI+TG9nbzwvdGV4dD48L3N2Zz4=')]
        public string $src,
        #[Title('Alt text')]
        #[Description('Alternative text for screen readers and PDF accessibility.')]
        #[Example('Logo')]
        public string $alt = '',
    ) {}

    public function render(ImageConfig $config): string
    {
        $svg = $this->inlineSvg($this->src);
        if ($svg !== null) {
            return $svg;
        }

        return '<img src="'.e($this->src).'" alt="'.e($this->alt).'">';
    }

    private function inlineSvg(string $source): ?string
    {
        $svg = $this->svgSource($source);
        if ($svg === null) {
            return null;
        }

        $document = new DOMDocument;
        if (! @$document->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return null;
        }

        $root = $document->documentElement;
        if (! $root instanceof DOMElement || strtolower($root->localName) !== 'svg') {
            return null;
        }

        $this->sanitizeSvgElement($root);
        $root->setAttribute('role', 'img');
        if ($this->alt !== '') {
            $root->setAttribute('aria-label', $this->alt);
        }

        return (string) $document->saveXML($root);
    }

    private function svgSource(string $source): ?string
    {
        $trimmed = trim($source);
        if (str_starts_with($trimmed, '<svg')) {
            return $trimmed;
        }

        if (preg_match('/^data:image\/svg\+xml(?:;charset=[^;,]+)?;base64,/i', $trimmed, $match) !== 1) {
            return null;
        }

        $decoded = base64_decode(substr($trimmed, strlen($match[0])), true);

        return is_string($decoded) ? $decoded : null;
    }

    private function sanitizeSvgElement(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = trim(strtolower($attribute->nodeValue ?? ''));

            if (str_starts_with($name, 'on') || (($name === 'href' || $name === 'xlink:href') && str_starts_with($value, 'javascript:'))) {
                $element->removeAttributeNode($attribute);
            }
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($child->localName), ['script', 'foreignobject'], true)) {
                $element->removeChild($child);

                continue;
            }

            $this->sanitizeSvgElement($child);
        }
    }
}
