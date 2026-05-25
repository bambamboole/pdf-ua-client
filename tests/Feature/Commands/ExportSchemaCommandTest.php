<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->path = sys_get_temp_dir().'/pdf-ua-client-schema-'.uniqid().'.json';
});

afterEach(function () {
    @unlink($this->path);
});

it('writes the compiled schema to the specified path', function () {
    $this->artisan('pdf-ua-client:schema-export', ['path' => $this->path])
        ->assertSuccessful();

    expect(File::exists($this->path))->toBeTrue();

    $schema = json_decode((string) File::get($this->path), true);

    expect($schema)->toBeArray();
    expect($schema['$id'])->toBe('https://pdfuakit.com/schemas/pdf-ua-client-template-v1.json');
    expect($schema['$defs']['block']['oneOf'])->not->toBeEmpty();
});

it('uses the default path when none given', function () {
    $defaultPath = storage_path('app/pdf-ua-client/template.schema.json');
    @unlink($defaultPath);
    @rmdir(dirname($defaultPath));

    $this->artisan('pdf-ua-client:schema-export')->assertSuccessful();

    expect(File::exists($defaultPath))->toBeTrue();

    @unlink($defaultPath);
});
