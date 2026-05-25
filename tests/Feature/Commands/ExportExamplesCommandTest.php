<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->path = sys_get_temp_dir().'/pdf-ua-client-examples-'.uniqid().'.json';
});

afterEach(function () {
    @unlink($this->path);
});

it('writes the registered examples to the specified path', function () {
    $this->artisan('pdf-ua-client:examples-export', ['path' => $this->path])
        ->assertSuccessful();

    expect(File::exists($this->path))->toBeTrue();

    $examples = json_decode((string) File::get($this->path), true);

    expect($examples)->toBeArray();
    expect($examples)->not->toBeEmpty();
    expect($examples[0])->toHaveKeys(['title', 'template', 'data']);
    expect($examples[0]['title'])->toBe('Invoice');
});

it('uses the default path when none given', function () {
    $defaultPath = storage_path('app/pdf-ua-client/examples.json');
    @unlink($defaultPath);

    $this->artisan('pdf-ua-client:examples-export')->assertSuccessful();

    expect(File::exists($defaultPath))->toBeTrue();

    @unlink($defaultPath);
});
