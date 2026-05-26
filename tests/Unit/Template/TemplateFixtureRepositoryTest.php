<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Workbench\App\Support\TemplateFixtureRepository;

it('loads example fixtures from directory names and standard files', function (): void {
    $root = sys_get_temp_dir().'/pdf-ua-fixtures-'.uniqid();

    File::ensureDirectoryExists($root.'/examples/invoice');
    File::put($root.'/examples/invoice/template.json', json_encode([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [['blocks' => [['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]]]]],
    ]));
    File::put($root.'/examples/invoice/data.json', json_encode(['title' => ['text' => 'Invoice']]));
    File::put($root.'/examples/invoice/contract.json', json_encode(['type' => 'object']));
    File::put($root.'/examples/invoice/expected.html', '<p>Invoice</p>');

    $fixtures = (new TemplateFixtureRepository($root))->examples();

    expect($fixtures)->toHaveCount(1)
        ->and($fixtures[0]->group)->toBe('examples')
        ->and($fixtures[0]->slug)->toBe('invoice')
        ->and($fixtures[0]->title)->toBe('Invoice')
        ->and($fixtures[0]->template['version'])->toBe(1)
        ->and($fixtures[0]->data)->toBe(['title' => ['text' => 'Invoice']])
        ->and($fixtures[0]->contract)->toBe(['type' => 'object'])
        ->and($fixtures[0]->html)->toBe('<p>Invoice</p>')
        ->and($fixtures[0]->htmlPath)->toBe($root.'/examples/invoice/expected.html')
        ->and($fixtures[0]->pdfPath)->toBeNull();

    File::deleteDirectory($root);
});

it('loads regression fixtures separately from examples', function (): void {
    $root = sys_get_temp_dir().'/pdf-ua-fixtures-'.uniqid();

    File::ensureDirectoryExists($root.'/regressions/heading-simple');
    File::put($root.'/regressions/heading-simple/template.json', json_encode([
        'version' => 1,
        'config' => ['page' => ['format' => 'A4']],
        'rows' => [['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]]],
    ]));
    File::put($root.'/regressions/heading-simple/data.json', json_encode(['h' => ['text' => 'Hello']]));

    $repository = new TemplateFixtureRepository($root);

    expect($repository->examples())->toBe([])
        ->and($repository->regressions())->toHaveCount(1)
        ->and($repository->all())->toHaveCount(1)
        ->and($repository->regressions()[0]->group)->toBe('regressions')
        ->and($repository->regressions()[0]->title)->toBe('Heading Simple');

    File::deleteDirectory($root);
});
