<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Http;

use Bambamboole\PdfUaClient\Http\Exceptions\PdfApiClientException;
use Bambamboole\PdfUaClient\Http\Exceptions\PdfApiServerException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class PdfApiClient
{
    public function __construct(
        private readonly Factory $http,
        private readonly string $baseUrl,
        private readonly ?string $bearerToken = null,
        private readonly int $timeoutSeconds = 30,
        private readonly int $retryAttempts = 2,
        private readonly int $retrySleepMs = 100,
    ) {}

    /** @param list<Attachment> $attachments */
    public function convert(string $html, array $attachments = []): string
    {
        $payload = ['html' => $html];

        if ($attachments !== []) {
            $payload['attachments'] = array_map(fn (Attachment $a): array => $a->toPayload(), $attachments);
        }

        $response = $this->send('convert', fn () => $this->request()->post($this->url('/convert'), $payload));

        return $response->body();
    }

    /** @return array<string, mixed> */
    public function validate(string $pdfBinary): array
    {
        $response = $this->send('validate', fn () => $this->request()
            ->withBody($pdfBinary, 'application/pdf')
            ->post($this->url('/validate'))
        );

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }

    private function request(): PendingRequest
    {
        $request = $this->http
            ->timeout($this->timeoutSeconds)
            ->retry($this->retryAttempts, $this->retrySleepMs, throw: false);

        if ($this->bearerToken !== null) {
            $request = $request->withToken($this->bearerToken);
        }

        return $request;
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/').$path;
    }

    private function send(string $endpoint, callable $send): Response
    {
        try {
            $response = $send();
        } catch (ConnectionException $e) {
            throw new PdfApiServerException(
                message: "PDF API connection failed: {$e->getMessage()}",
                statusCode: 0,
                responseBody: '',
                endpoint: $endpoint,
                previous: $e,
            );
        }

        if ($response->clientError()) {
            throw new PdfApiClientException(
                message: "PDF API {$endpoint} failed (4xx): ".$response->body(),
                statusCode: $response->status(),
                responseBody: $response->body(),
                endpoint: $endpoint,
            );
        }

        if ($response->serverError() || ! $response->successful()) {
            throw new PdfApiServerException(
                message: "PDF API {$endpoint} failed (5xx): ".$response->body(),
                statusCode: $response->status(),
                responseBody: $response->body(),
                endpoint: $endpoint,
            );
        }

        return $response;
    }
}
