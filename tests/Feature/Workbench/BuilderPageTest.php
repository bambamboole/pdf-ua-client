<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;

it('renders the builder page with the compiled schema prop', function (): void {
    $this->withoutVite();

    get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Builder')
            ->where('schema.examples', fn ($examples): bool => $examples->contains('title', 'Invoice'))
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

    expect(collect($response->json('examples'))->firstWhere('title', 'Invoice'))
        ->toHaveKeys(['title', 'template', 'data']);
});
