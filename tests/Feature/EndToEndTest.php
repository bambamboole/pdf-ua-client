<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Http\PdfApiClient;
use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Illuminate\Support\Facades\Http;

it('builds + renders + sends a Template through PdfApiClient end-to-end', function () {
    Http::fake([
        'http://pdf-ua-api:8888/convert' => Http::response('FAKE-PDF-BYTES', 200, ['Content-Type' => 'application/pdf']),
    ]);

    config()->set('pdf-ua-client.base_url', 'http://pdf-ua-api:8888');

    $factory = $this->app->make(TemplateFactory::class);
    $renderer = $this->app->make(TemplateRenderer::class);
    $client = $this->app->make(PdfApiClient::class);

    $template = $factory->fromArray([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'props' => ['text' => 'Test Document'], 'config' => ['level' => 1]]]],
            ['blocks' => [
                ['type' => 'text', 'props' => ['text' => 'First column']],
                ['type' => 'text', 'props' => ['text' => 'Second column']],
            ]],
        ],
    ]);

    $html = $renderer->render($template);
    $pdf = $client->convert($html);

    expect($pdf)->toBe('FAKE-PDF-BYTES');

    Http::assertSent(fn ($req) => $req->url() === 'http://pdf-ua-api:8888/convert' && str_contains($req['html'], 'Test Document'));
});
