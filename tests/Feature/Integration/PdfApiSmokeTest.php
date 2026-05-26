<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Http\PdfApiClient;
use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Illuminate\Support\Facades\Http;
use Workbench\App\Support\TemplateFixture;
use Workbench\App\Support\TemplateFixtureRepository;

beforeEach(function () {
    $baseUrl = (string) (getenv('PDF_UA_API_URL') ?: '');

    if ($baseUrl === '') {
        $this->markTestSkipped('PDF_UA_API_URL is not set — skipping real-API integration test.');
    }

    // Real HTTP only — make sure no global fake leaks from earlier tests.
    Http::clearResolvedInstances();

    $reachable = false;
    try {
        $response = Http::timeout(2)->get(rtrim($baseUrl, '/').'/health');
        $reachable = $response->successful();
    } catch (Throwable) {
        $reachable = false;
    }

    if (! $reachable) {
        $this->markTestSkipped("pdf-ua-api at {$baseUrl} is not reachable — skipping real-API integration test.");
    }

    config()->set('pdf-ua-client.base_url', $baseUrl);

    $this->factory = $this->app->make(TemplateFactory::class);
    $this->renderer = $this->app->make(TemplateRenderer::class);
    $this->client = $this->app->make(PdfApiClient::class);
});

it('renders the invoice fixture and converts it to a real PDF via pdf-ua-api', function () {
    $fixtures = new TemplateFixtureRepository(__DIR__.'/../../Fixtures');
    $fixture = null;

    foreach ($fixtures->regressions() as $candidate) {
        if ($candidate->slug === 'invoice-realistic') {
            $fixture = $candidate;
            break;
        }
    }

    if (! $fixture instanceof TemplateFixture) {
        $this->fail('Missing invoice-realistic regression fixture.');
    }

    $template = $this->factory->fromArray($fixture->template);
    $html = $this->renderer->render($template, $fixture->data);

    $pdf = $this->client->convert($html);

    // PDFs start with the "%PDF-" magic and end with "%%EOF".
    expect(substr((string) $pdf, 0, 5))->toBe('%PDF-');
    expect(strlen((string) $pdf))->toBeGreaterThan(1024);
    expect(str_contains((string) $pdf, '%%EOF'))->toBeTrue();
});
