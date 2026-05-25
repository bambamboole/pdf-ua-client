<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Examples\InvoiceExample;
use Illuminate\Support\Facades\File;

it('writes a standalone data schema for a given template file', function (): void {
    $dir = sys_get_temp_dir().'/pdf-ua-'.uniqid();
    File::ensureDirectoryExists($dir);
    $templatePath = $dir.'/template.json';
    $outPath = $dir.'/data.schema.json';
    File::put($templatePath, (string) json_encode(InvoiceExample::document()));

    $this->artisan('pdf-ua-client:data-schema-export', ['template' => $templatePath, 'path' => $outPath])
        ->assertSuccessful();

    $schema = json_decode((string) File::get($outPath), true, flags: JSON_THROW_ON_ERROR);
    expect($schema['type'])->toBe('object')
        ->and($schema['additionalProperties'])->toBeFalse()
        ->and($schema['properties'])->toHaveKey('items');

    File::deleteDirectory($dir);
});
