<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Http\Attachment;
use Bambamboole\PdfUaClient\Http\Exceptions\PdfApiClientException;
use Bambamboole\PdfUaClient\Http\Exceptions\PdfApiServerException;
use Bambamboole\PdfUaClient\Http\PdfApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    /** @var Factory $factory */
    $factory = Http::getFacadeRoot();
    $this->factory = $factory;
    Http::preventStrayRequests();
});

it('posts html to /convert and returns the PDF body', function () {
    Http::fake([
        'http://api.test/convert' => Http::response('PDF-BYTES', 200, ['Content-Type' => 'application/pdf']),
    ]);

    $client = new PdfApiClient(http: $this->factory, baseUrl: 'http://api.test');

    $result = $client->convert('<html>hi</html>');

    expect($result)->toBe('PDF-BYTES');

    Http::assertSent(fn ($request) => $request->url() === 'http://api.test/convert'
        && $request->method() === 'POST'
        && $request['html'] === '<html>hi</html>'
        && ! isset($request['attachments']));
});

it('sends attachments when provided', function () {
    Http::fake([
        'http://api.test/convert' => Http::response('PDF', 200),
    ]);

    $client = new PdfApiClient(http: $this->factory, baseUrl: 'http://api.test');

    $client->convert('<html/>', [
        new Attachment(name: 'factur-x.xml', contentBase64: 'BASE64', mimeType: 'text/xml', relationship: 'Alternative'),
    ]);

    Http::assertSent(function ($request) {
        $payload = $request->data();

        return $payload['attachments'][0]['name'] === 'factur-x.xml'
            && $payload['attachments'][0]['content'] === 'BASE64'
            && $payload['attachments'][0]['mimeType'] === 'text/xml'
            && $payload['attachments'][0]['relationship'] === 'Alternative';
    });
});

it('maps 4xx responses to PdfApiClientException', function () {
    Http::fake([
        'http://api.test/convert' => Http::response('bad html', 422),
    ]);

    $client = new PdfApiClient(http: $this->factory, baseUrl: 'http://api.test', retryAttempts: 1);

    expect(fn () => $client->convert('<bad>'))
        ->toThrow(PdfApiClientException::class, 'PDF API convert failed (4xx)');
});

it('maps 5xx responses to PdfApiServerException', function () {
    Http::fake([
        'http://api.test/convert' => Http::response('upstream broke', 502),
    ]);

    $client = new PdfApiClient(http: $this->factory, baseUrl: 'http://api.test', retryAttempts: 1);

    expect(fn () => $client->convert('<html/>'))
        ->toThrow(PdfApiServerException::class, 'PDF API convert failed (5xx)');
});

it('retries transient server errors before returning a successful PDF response', function () {
    Http::fakeSequence('http://api.test/convert')
        ->push('upstream broke', 502)
        ->push('PDF-BYTES', 200);

    $client = new PdfApiClient(http: $this->factory, baseUrl: 'http://api.test', retryAttempts: 2, retrySleepMs: 1);

    $result = $client->convert('<html/>');

    expect($result)->toBe('PDF-BYTES');
    Http::assertSentCount(2);
});

it('maps connection failures to server exceptions', function () {
    Http::fake(fn () => throw new ConnectionException('connection refused'));

    $client = new PdfApiClient(http: $this->factory, baseUrl: 'http://api.test', retryAttempts: 1);

    expect(fn () => $client->convert('<html/>'))
        ->toThrow(PdfApiServerException::class, 'PDF API connection failed');
});

it('sends bearer token when configured', function () {
    Http::fake([
        'http://api.test/convert' => Http::response('PDF', 200),
    ]);

    $client = new PdfApiClient(http: $this->factory, baseUrl: 'http://api.test', bearerToken: 'sk_test_xyz');

    $client->convert('<html/>');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk_test_xyz'));
});

it('posts raw PDF body to /validate and returns parsed JSON', function () {
    Http::fake([
        'http://api.test/validate' => Http::response(['compliant' => true, 'issues' => []], 200),
    ]);

    $client = new PdfApiClient(http: $this->factory, baseUrl: 'http://api.test');

    $result = $client->validate('PDF-BYTES');

    expect($result)->toBe(['compliant' => true, 'issues' => []]);

    Http::assertSent(fn ($request) => $request->url() === 'http://api.test/validate'
        && $request->method() === 'POST'
        && $request->hasHeader('Content-Type', 'application/pdf')
        && $request->body() === 'PDF-BYTES');
});
