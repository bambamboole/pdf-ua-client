<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Rendering\TemplateRenderer;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Tests\Fixtures\TestFixture;

function pdfUaExtractBody(string $html): string
{
    if (preg_match('/<body[^>]*>(.*)<\/body>/s', $html, $matches) !== 1) {
        throw new RuntimeException('Rendered HTML did not contain a <body> element.');
    }

    return trim($matches[1]);
}

function pdfUaWriteFixtureHtml(string $path, string $body): void
{
    $source = file_get_contents($path);
    if ($source === false) {
        throw new RuntimeException("Unable to read fixture {$path}");
    }

    $exported = var_export($body, true);
    $replaced = preg_replace(
        '/html:\s*(?:\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*")/s',
        'html: '.addcslashes($exported, '\\$'),
        $source,
        1,
        $count,
    );

    if ($replaced === null || $count !== 1) {
        throw new RuntimeException("Failed to substitute html value in fixture {$path}");
    }

    file_put_contents($path, $replaced);
}

dataset('renderFixtures', function () {
    $files = glob(__DIR__.'/../Fixtures/render/*.php') ?: [];
    sort($files);

    return array_map(
        fn (string $file): array => [basename($file), $file],
        $files,
    );
});

it('renders fixture to expected body HTML', function (string $name, string $path) {
    $fixture = require $path;
    expect($fixture)->toBeInstanceOf(TestFixture::class);

    $factory = app(TemplateFactory::class);
    $renderer = app(TemplateRenderer::class);

    $template = $factory->fromArray($fixture->spec);
    $html = $renderer->render($template, $fixture->data);
    $body = pdfUaExtractBody($html);

    if (getenv('UPDATE_FIXTURES') === '1') {
        pdfUaWriteFixtureHtml($path, $body);
        $this->markTestSkipped("Updated fixture: {$name}");
    }

    expect($body)->toBe($fixture->html);
})->with('renderFixtures');
