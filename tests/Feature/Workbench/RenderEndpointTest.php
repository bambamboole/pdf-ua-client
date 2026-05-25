<?php

declare(strict_types=1);

use function Pest\Laravel\postJson;

it('renders a posted template to html', function (): void {
    $response = postJson('/render', [
        'template' => [
            'version' => 1,
            'config' => ['page' => ['format' => 'A4']],
            'rows' => [
                ['blocks' => [
                    ['type' => 'heading', 'props' => ['text' => 'Invoice 2026-001'], 'config' => ['level' => 1]],
                ]],
            ],
        ],
    ]);

    $response->assertOk();
    expect($response->json('html'))->toContain('<h1>Invoice 2026-001</h1>');
});

it('returns 422 for an invalid template', function (): void {
    $response = postJson('/render', [
        'template' => ['version' => 1, 'config' => [], 'rows' => [['blocks' => []]]],
    ]);

    $response->assertStatus(422);
});

it('injects posted data as runtime data by block id', function (): void {
    $response = postJson('/render', [
        'template' => [
            'version' => 1,
            'config' => ['page' => ['format' => 'A4']],
            'rows' => [
                ['blocks' => [
                    ['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]],
                ]],
            ],
        ],
        'data' => [
            'title' => ['text' => 'Injected Heading'],
        ],
    ]);

    $response->assertOk();
    expect($response->json('html'))->toContain('<h1>Injected Heading</h1>');
});
