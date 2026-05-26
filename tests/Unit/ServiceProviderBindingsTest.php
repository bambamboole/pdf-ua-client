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
    $fixtures = $this->app->make(TemplateFixtureRepository::class)->examples();
    $examples = $this->app->make(ExampleRegistry::class)->all();

    expect($fixtures)->toHaveCount(1)
        ->and($fixtures[0]->slug)->toBe('invoice')
        ->and($examples)->toHaveCount(1)
        ->and($examples[0]['title'])->toBe('Invoice')
        ->and($examples[0]['template'])->toBe($fixtures[0]->template)
        ->and($examples[0]['data'])->toBe($fixtures[0]->data);
});
