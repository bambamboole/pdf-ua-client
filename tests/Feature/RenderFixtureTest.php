<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Http\PdfApiClient;
use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Tests\Support\ComparisonResult;
use Bambamboole\PdfUaClient\Tests\Support\PdfPageComparator;
use Illuminate\Support\Facades\Http;
use Workbench\App\Support\TemplateFixture;
use Workbench\App\Support\TemplateFixtureRepository;

function pdfUaExtractBody(string $html): string
{
    if (preg_match('/<body[^>]*>(.*)<\/body>/s', $html, $matches) !== 1) {
        throw new RuntimeException('Rendered HTML did not contain a <body> element.');
    }

    return trim($matches[1]);
}

function pdfUaWriteFixtureHtml(TemplateFixture $fixture, string $body): void
{
    file_put_contents($fixture->htmlPath ?? $fixture->path.'/expected.html', $body);
}

function pdfUaReachableApiBaseUrl(): ?string
{
    $baseUrl = (string) (getenv('PDF_UA_API_URL') ?: '');
    if ($baseUrl === '') {
        return null;
    }

    Http::clearResolvedInstances();

    try {
        return Http::timeout(2)->get(rtrim($baseUrl, '/').'/health')->successful() ? $baseUrl : null;
    } catch (Throwable) {
        return null;
    }
}

function pdfUaWriteDiffArtifacts(string $name, string $actualPdf, ComparisonResult $result): string
{
    $directory = __DIR__.'/../.pdf-diff';
    if (! is_dir($directory)) {
        mkdir($directory, 0o755, true);
    }

    $base = $directory.'/'.pathinfo($name, PATHINFO_FILENAME);
    file_put_contents($base.'.actual.pdf', $actualPdf);

    foreach ($result->pages as $page) {
        if ($page->distance <= PdfPageComparator::THRESHOLD) {
            continue;
        }

        $page->diffImage->setImageFormat('png');
        $page->diffImage->writeImage($base.'-page-'.$page->pageIndex.'-diff.png');
    }

    return $directory;
}

dataset('renderFixtures', function () {
    $fixtures = (new TemplateFixtureRepository(__DIR__.'/../Fixtures'))->all();

    return array_reduce(
        $fixtures,
        function (array $cases, TemplateFixture $fixture): array {
            $cases[$fixture->group.'/'.$fixture->slug] = [$fixture->group.'/'.$fixture->slug, $fixture];

            return $cases;
        },
        [],
    );
});

dataset('pdfFixtures', function () {
    $cases = [];
    foreach ((new TemplateFixtureRepository(__DIR__.'/../Fixtures'))->all() as $fixture) {
        if ($fixture->pdfPath !== null) {
            $cases[$fixture->group.'/'.$fixture->slug] = [$fixture->group.'/'.$fixture->slug, $fixture];
        }
    }

    return $cases;
});

it('renders fixture to expected body HTML', function (string $name, TemplateFixture $fixture) {
    $factory = app(TemplateFactory::class);
    $renderer = app(TemplateRenderer::class);

    $template = $factory->fromArray($fixture->template);
    $html = $renderer->render($template, $fixture->data);
    $body = pdfUaExtractBody($html);

    if (getenv('UPDATE_FIXTURES') === '1') {
        pdfUaWriteFixtureHtml($fixture, $body);
        $this->markTestSkipped("Updated fixture: {$name}");
    }

    if ($fixture->html === null) {
        $this->markTestSkipped("Fixture {$name} has no expected body HTML.");
    }

    expect($body)->toBe($fixture->html);
})->with('renderFixtures');

it('matches the committed golden PDF', function (string $name, TemplateFixture $fixture) {
    $goldenPath = $fixture->pdfPath ?? $fixture->path.'/expected.pdf';
    $baseUrl = pdfUaReachableApiBaseUrl();

    $factory = app(TemplateFactory::class);
    $renderer = app(TemplateRenderer::class);
    $html = $renderer->render($factory->fromArray($fixture->template), $fixture->data);

    if (getenv('UPDATE_PDF_FIXTURES') === '1') {
        if ($baseUrl === null) {
            $this->markTestSkipped('PDF_UA_API_URL is not reachable — cannot regenerate golden PDF.');
        }

        config()->set('pdf-ua-client.base_url', $baseUrl);
        if (! is_dir(dirname($goldenPath))) {
            mkdir(dirname($goldenPath), 0o755, true);
        }
        file_put_contents($goldenPath, app(PdfApiClient::class)->convert($html));

        $this->markTestSkipped("Updated golden PDF: {$name}");
    }

    if (! PdfPageComparator::isSupported()) {
        $this->markTestSkipped('ext-imagick and Ghostscript are required for PDF comparison.');
    }

    if ($baseUrl === null) {
        $this->markTestSkipped('PDF_UA_API_URL is not reachable — skipping PDF comparison.');
    }

    if (! is_file($goldenPath)) {
        $this->markTestSkipped("Golden PDF for {$name} is missing — run UPDATE_PDF_FIXTURES=1 to generate it.");
    }

    config()->set('pdf-ua-client.base_url', $baseUrl);
    $actualPdf = app(PdfApiClient::class)->convert($html);

    $comparator = new PdfPageComparator;
    $result = $comparator->compare(
        $comparator->rasterize((string) file_get_contents($goldenPath)),
        $comparator->rasterize($actualPdf),
    );

    if (! $result->matches(PdfPageComparator::THRESHOLD)) {
        $directory = pdfUaWriteDiffArtifacts($name, $actualPdf, $result);

        $this->fail(sprintf(
            'Rendered PDF for %s differs from golden (pages %d vs %d, worst RMSE %.4f > %.4f). Artifacts in %s',
            $name,
            $result->expectedPages,
            $result->actualPages,
            $result->worstDistance(),
            PdfPageComparator::THRESHOLD,
            $directory,
        ));
    }

    expect($result->matches(PdfPageComparator::THRESHOLD))->toBeTrue();
})->with('pdfFixtures');

it('keeps the committed shipping label golden PDF to one page', function () {
    if (! PdfPageComparator::isSupported()) {
        $this->markTestSkipped('ext-imagick and Ghostscript are required for PDF comparison.');
    }

    $fixture = collect((new TemplateFixtureRepository(__DIR__.'/../Fixtures'))->examples())
        ->firstWhere('slug', 'shipping-label');

    if (! $fixture instanceof TemplateFixture || $fixture->pdfPath === null) {
        $this->markTestSkipped('Shipping label golden PDF is missing — run UPDATE_PDF_FIXTURES=1 to generate it.');
    }

    $pages = (new PdfPageComparator)->rasterize((string) file_get_contents($fixture->pdfPath));

    expect($pages->getNumberImages())->toBe(1);
});
