<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Http\PdfApiClient;
use Workbench\App\Support\ExampleRegistry;
use Workbench\App\Support\TemplateFixtureRepository;

it('resolves PdfApiClient from the container with config values', function () {
    config()->set('pdf-ua-client.base_url', 'http://example.test');
    config()->set('pdf-ua-client.token', 'tok_abc');

    $client = $this->app->make(PdfApiClient::class);

    expect($client)->toBeInstanceOf(PdfApiClient::class);
});

it('registers user-facing examples from fixture files', function (): void {
    $fixtures = collect($this->app->make(TemplateFixtureRepository::class)->examples());
    $examples = collect($this->app->make(ExampleRegistry::class)->all());
    $invoice = $fixtures->firstWhere('slug', 'invoice');
    $registeredInvoice = $examples->firstWhere('title', 'Invoice');

    expect($fixtures->pluck('slug')->all())->toContain('invoice')
        ->and($examples->pluck('title')->all())->toContain('Invoice')
        ->and($registeredInvoice['template'])->toBe($invoice->template)
        ->and($registeredInvoice['data'])->toBe($invoice->data);
});
