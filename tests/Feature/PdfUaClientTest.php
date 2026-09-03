<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Attachment;
use Bambamboole\PdfUaClient\Contracts\PdfClient;
use Bambamboole\PdfUaClient\Enums\AttachmentRelationship;
use Bambamboole\PdfUaClient\Exceptions\UnexpectedPdfResponse;
use Bambamboole\PdfUaClient\HtmlDocument;
use Bambamboole\PdfUaClient\PdfTemplate;
use Bambamboole\PdfUaClient\PdfUaClient;
use Bambamboole\PdfUaClient\XmpProperty;
use Bambamboole\PdfUaClient\XmpSchema;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('pdf-ua-client.base_url', 'https://pdf.example.test/api');
    config()->set('pdf-ua-client.token', 'secret-token');
    config()->set('pdf-ua-client.connect_timeout', 4);
    config()->set('pdf-ua-client.timeout', 90);
});

it('binds the configured client to its contract', function (): void {
    expect(app(PdfClient::class)::class)->toBe(PdfUaClient::class);
});

it('renders HTML as an inline PDF with document metadata', function (): void {
    Http::preventStrayRequests();
    Http::fake(function (Request $request, array $options) {
        expect($options['connect_timeout'])->toBe(4)
            ->and($options['timeout'])->toBe(90);

        return Http::response('%PDF-1.7 inline', 200, [
            'Content-Type' => 'application/pdf; version=1.7',
            'X-Document-UUID' => ' document-uuid ',
        ]);
    });

    $document = new HtmlDocument(
        html: '<html><body>Hello</body></html>',
        baseUrl: 'https://assets.example.test/',
        attachments: [new Attachment(
            name: 'source.xml',
            content: '<invoice/>',
            mimeType: 'application/xml',
            relationship: AttachmentRelationship::Source,
        )],
        xmpSchemas: [new XmpSchema(
            namespace: 'https://example.test/schema/',
            prefix: 'example',
            name: 'Example schema',
            properties: [new XmpProperty('Identifier', 'INV-1')],
        )],
        embedColorProfile: false,
    );

    $result = app(PdfClient::class)->renderHtml($document);

    expect($result->contents)->toBe('%PDF-1.7 inline')
        ->and($result->documentUuid)->toBe('document-uuid');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://pdf.example.test/api/render/html'
        && $request->hasHeader('Accept', 'application/pdf')
        && $request->hasHeader('Authorization', 'Bearer secret-token')
        && $request->data() === [
            'html' => '<html><body>Hello</body></html>',
            'baseUrl' => 'https://assets.example.test/',
            'attachments' => [[
                'name' => 'source.xml',
                'content' => 'PGludm9pY2UvPg==',
                'mimeType' => 'application/xml',
                'relationship' => 'Source',
            ]],
            'xmpSchemas' => [[
                'namespace' => 'https://example.test/schema/',
                'prefix' => 'example',
                'name' => 'Example schema',
                'properties' => [[
                    'name' => 'Identifier',
                    'value' => 'INV-1',
                    'valueType' => 'Text',
                    'category' => 'external',
                ]],
            ]],
            'embedColorProfile' => false,
        ]);
});

it('renders a v2 template as an inline PDF', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://pdf.example.test/api/render/template' => Http::response('%PDF-1.7 template', 200, [
            'Content-Type' => 'application/pdf',
        ]),
    ]);
    $template = new PdfTemplate(
        template: [
            'version' => 2,
            'config' => ['title' => 'Quote'],
            'rows' => [['blocks' => [['type' => 'text', 'id' => 'body', 'text' => '']]]],
        ],
        data: ['body' => ['text' => 'Thank you for your business.']],
        attachments: [Attachment::facturX('<invoice/>')],
    );

    $result = app(PdfClient::class)->renderTemplate($template);

    expect($result->contents)->toBe('%PDF-1.7 template')
        ->and($result->documentUuid)->toBeNull();
    Http::assertSent(fn (Request $request): bool => $request->data() === [
        'template' => [
            'version' => 2,
            'config' => ['title' => 'Quote'],
            'rows' => [['blocks' => [['type' => 'text', 'id' => 'body', 'text' => '']]]],
            'attachments' => [[
                'name' => 'factur-x.xml',
                'content' => 'PGludm9pY2UvPg==',
                'mimeType' => 'text/xml',
                'relationship' => 'Alternative',
                'description' => 'Factur-X/ZUGFeRD XML invoice',
            ]],
        ],
        'data' => ['body' => ['text' => 'Thank you for your business.']],
    ]);
});

it('renders HTML directly to a caller supplied upload URL', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://pdf.example.test/api/render/html' => Http::response(status: 204, headers: [
            'X-Document-UUID' => 'uploaded-document-uuid',
        ]),
    ]);

    $result = app(PdfClient::class)->renderHtmlTo(
        new HtmlDocument('<html><body>Upload</body></html>'),
        'https://bucket.example.test/document.pdf?signature=secret',
    );

    expect($result->documentUuid)->toBe('uploaded-document-uuid');
    Http::assertSent(fn (Request $request): bool => $request->hasHeader(
        'X-Upload-Url',
        'https://bucket.example.test/document.pdf?signature=secret',
    ));
});

it('renders a v2 template directly to a caller supplied upload URL', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://pdf.example.test/api/render/template' => Http::response(status: 204),
    ]);

    $result = app(PdfClient::class)->renderTemplateTo(
        new PdfTemplate(['version' => 2, 'rows' => []]),
        'https://bucket.example.test/template.pdf?signature=secret',
    );

    expect($result->documentUuid)->toBeNull();
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://pdf.example.test/api/render/template'
        && $request->hasHeader(
            'X-Upload-Url',
            'https://bucket.example.test/template.pdf?signature=secret',
        ));
});

it('rejects an inline response that is not a PDF', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://pdf.example.test/api/render/html' => Http::response('{}', 200, [
            'Content-Type' => 'application/json',
        ]),
    ]);

    expect(fn () => app(PdfClient::class)->renderHtml(new HtmlDocument('<html></html>')))
        ->toThrow(UnexpectedPdfResponse::class);
});

it('rejects an upload response that is not empty', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://pdf.example.test/api/render/html' => Http::response('%PDF-1.7', 200, [
            'Content-Type' => 'application/pdf',
        ]),
    ]);

    expect(fn () => app(PdfClient::class)->renderHtmlTo(
        new HtmlDocument('<html></html>'),
        'https://bucket.example.test/document.pdf?signature=secret',
    ))->toThrow(UnexpectedPdfResponse::class);
});

it('surfaces API failures without retrying them', function (): void {
    Http::preventStrayRequests();
    Http::fakeSequence('https://pdf.example.test/api/render/html')
        ->push(['message' => 'Invalid HTML'], 400)
        ->push('%PDF-1.7 retry would have succeeded', 200, ['Content-Type' => 'application/pdf']);

    expect(fn () => app(PdfClient::class)->renderHtml(new HtmlDocument('invalid')))
        ->toThrow(RequestException::class);
    Http::assertSentCount(1);
});
