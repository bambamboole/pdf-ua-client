<?php

declare(strict_types=1);

namespace Bambamboole\PdfUaClient;

use Bambamboole\PdfUaClient\Contracts\PdfClient;
use Bambamboole\PdfUaClient\Exceptions\UnexpectedPdfResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class PdfUaClient implements PdfClient
{
    public function renderHtml(HtmlDocument $document): RenderedPdf
    {
        return $this->renderedPdf($this->post('render/html', $document->toPayload()));
    }

    public function renderHtmlTo(HtmlDocument $document, string $uploadUrl): UploadedPdf
    {
        return $this->uploadedPdf($this->post('render/html', $document->toPayload(), $uploadUrl));
    }

    public function renderTemplate(PdfTemplate $template): RenderedPdf
    {
        return $this->renderedPdf($this->post('render/template', $template->toPayload()));
    }

    public function renderTemplateTo(PdfTemplate $template, string $uploadUrl): UploadedPdf
    {
        return $this->uploadedPdf($this->post('render/template', $template->toPayload(), $uploadUrl));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $path, array $payload, ?string $uploadUrl = null): Response
    {
        return $this->request($uploadUrl)
            ->post($path, $payload)
            ->throw();
    }

    private function request(?string $uploadUrl): PendingRequest
    {
        $baseUrl = Str::of((string) config('pdf-ua-client.base_url'))
            ->rtrim('/')
            ->append('/')
            ->toString();
        $request = Http::baseUrl($baseUrl)
            ->accept('application/pdf')
            ->connectTimeout((int) config('pdf-ua-client.connect_timeout', 5))
            ->timeout((int) config('pdf-ua-client.timeout', 120));
        $token = config('pdf-ua-client.token');

        if (is_string($token) && Str::of($token)->trim()->isNotEmpty()) {
            $request = $request->withToken($token);
        }

        if ($uploadUrl !== null) {
            return $request->withHeader('X-Upload-Url', $uploadUrl);
        }

        return $request;
    }

    private function renderedPdf(Response $response): RenderedPdf
    {
        $contentType = (string) $response->header('Content-Type');

        if (! $response->ok() || ! Str::startsWith(Str::lower($contentType), 'application/pdf')) {
            throw new UnexpectedPdfResponse(
                "Expected an inline PDF response, received status {$response->status()} with content type [{$contentType}].",
            );
        }

        return new RenderedPdf($response->body(), $this->documentUuid($response));
    }

    private function uploadedPdf(Response $response): UploadedPdf
    {
        if (! $response->noContent()) {
            throw new UnexpectedPdfResponse(
                "Expected an empty upload response, received status {$response->status()}.",
            );
        }

        return new UploadedPdf($this->documentUuid($response));
    }

    private function documentUuid(Response $response): ?string
    {
        $documentUuid = Str::of((string) $response->header('X-Document-UUID'))->trim()->toString();

        return $documentUuid !== '' ? $documentUuid : null;
    }
}
