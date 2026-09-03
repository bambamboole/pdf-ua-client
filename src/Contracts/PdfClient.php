<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient\Contracts;

use Bambamboole\PdfUaClient\HtmlDocument;
use Bambamboole\PdfUaClient\PdfTemplate;
use Bambamboole\PdfUaClient\RenderedPdf;
use Bambamboole\PdfUaClient\UploadedPdf;

interface PdfClient
{
    public function renderHtml(HtmlDocument $document): RenderedPdf;

    public function renderHtmlTo(HtmlDocument $document, string $uploadUrl): UploadedPdf;

    public function renderTemplate(PdfTemplate $template): RenderedPdf;

    public function renderTemplateTo(PdfTemplate $template, string $uploadUrl): UploadedPdf;
}
