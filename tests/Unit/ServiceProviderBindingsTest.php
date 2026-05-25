<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Http\PdfApiClient;

it('resolves PdfApiClient from the container with config values', function () {
    config()->set('pdf-ua-client.base_url', 'http://example.test');
    config()->set('pdf-ua-client.token', 'tok_abc');

    $client = $this->app->make(PdfApiClient::class);

    expect($client)->toBeInstanceOf(PdfApiClient::class);
});
