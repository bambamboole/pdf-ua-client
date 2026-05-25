<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\PdfUaClientServiceProvider;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Orchestra\Testbench\Foundation\Application as TestbenchApplication;

require __DIR__.'/../vendor/autoload.php';

$path = $argv[1] ?? __DIR__.'/../template.schema.json';

$app = TestbenchApplication::create(
    options: [
        'extra' => [
            'providers' => [PdfUaClientServiceProvider::class],
            'dont-discover' => ['*'],
        ],
    ],
);

$registry = $app->make(BlockRegistry::class);
$compiler = $app->make(TemplateSchemaCompiler::class);

$json = json_encode(
    $compiler->compile($registry),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
)."\n";

if (file_put_contents($path, $json) === false) {
    fwrite(STDERR, "Failed to write schema to {$path}\n");
    exit(1);
}

$bytes = strlen($json);
$absolute = realpath($path) ?: $path;

fwrite(STDOUT, "Wrote {$bytes} bytes to {$absolute}\n");
