<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

it('renders the builder page with the compiled schema prop', function (): void {
    $this->withoutVite();

    $publicExampleTitles = ['Dunning Notice', 'Invoice', 'Packing Slip', 'Shipping Label'];

    get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Builder')
            ->where('schema.examples', fn ($examples): bool => collect($examples)
                ->pluck('title')
                ->intersect($publicExampleTitles)
                ->count() === count($publicExampleTitles))
            ->where('schema.$defs.block.oneOf', [
                ['$ref' => '#/$defs/headingBlock'],
                ['$ref' => '#/$defs/textBlock'],
                ['$ref' => '#/$defs/htmlBlock'],
                ['$ref' => '#/$defs/imageBlock'],
                ['$ref' => '#/$defs/spacerBlock'],
                ['$ref' => '#/$defs/dividerBlock'],
                ['$ref' => '#/$defs/keyValueBlock'],
                ['$ref' => '#/$defs/tableBlock'],
            ])
        );
});

it('returns user-facing examples from the workbench endpoint', function (): void {
    $response = get('/examples')
        ->assertOk()
        ->assertJsonStructure([
            'examples' => [
                [
                    'title',
                    'template',
                    'data',
                ],
            ],
        ]);

    $examples = collect($response->json('examples'));

    expect($examples->pluck('title')->all())
        ->toContain('Dunning Notice', 'Invoice', 'Packing Slip', 'Shipping Label');

    $examples->each(fn (array $example) => expect($example)->toHaveKeys(['title', 'template', 'data']));
});
