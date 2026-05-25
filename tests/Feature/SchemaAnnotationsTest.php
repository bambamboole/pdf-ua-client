<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;

it('emits block titles and prop examples into the schema', function (): void {
    $schema = app(TemplateSchemaCompiler::class)->compile(app(BlockRegistry::class));
    $defs = $schema['$defs'];

    expect($defs['headingProps']['title'])->toBe('Heading')
        ->and($defs['headingProps']['properties']['text']['examples'])->toBe(['Invoice 2026-001'])
        ->and($defs['tableProps']['properties']['headers']['examples'][0])->toBeArray()
        ->and($defs['keyValueProps']['properties']['entries']['items']['properties']['label']['examples'])->toBe(['Label']);
});

it('keeps required intact on the props catalog (not stripped like config)', function (): void {
    $schema = app(TemplateSchemaCompiler::class)->compile(app(BlockRegistry::class));
    $defs = $schema['$defs'];

    expect($defs['headingProps']['required'])->toContain('text')
        ->and($defs['tableProps']['required'])->toContain('headers', 'rows');
});
