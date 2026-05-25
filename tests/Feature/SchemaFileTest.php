<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;

it('keeps template.schema.json in sync with the compiler output', function () {
    $registry = $this->app->make(BlockRegistry::class);
    $compiler = $this->app->make(TemplateSchemaCompiler::class);

    $expected = json_encode(
        $compiler->compile($registry),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
    )."\n";

    $path = __DIR__.'/../../template.schema.json';

    if (getenv('UPDATE_SCHEMA') === '1') {
        file_put_contents($path, $expected);
        $this->markTestSkipped("Updated: {$path}");

        return;
    }

    expect(file_exists($path))
        ->toBeTrue('template.schema.json is missing — regenerate via UPDATE_SCHEMA=1 ./vendor/bin/pest')
        ->and(file_get_contents($path))
        ->toBe($expected, 'template.schema.json is out of date — regenerate via UPDATE_SCHEMA=1 ./vendor/bin/pest');
});
