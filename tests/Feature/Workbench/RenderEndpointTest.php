<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Workbench\App\Support\TemplateFixtureRepository;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('renders a posted template to html', function (): void {
    $response = postJson('/html', [
        'template' => [
            'version' => 1,
            'config' => ['page' => ['format' => 'A4']],
            'rows' => [
                ['blocks' => [
                    ['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]],
                ]],
            ],
        ],
        'data' => ['title' => ['text' => 'Invoice 2026-001']],
    ]);

    $response->assertOk();
    expect($response->json('html'))->toContain('<h1>Invoice 2026-001</h1>');
});

it('returns 422 for an invalid template', function (): void {
    $response = postJson('/html', [
        'template' => ['version' => 1, 'config' => [], 'rows' => [['blocks' => []]]],
    ]);

    $response->assertStatus(422);
});

it('injects posted data as runtime data by block id', function (): void {
    $response = postJson('/html', [
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

it('returns 422 when posted data violates the template data contract', function (): void {
    $response = postJson('/html', [
        'template' => [
            'version' => 1,
            'config' => ['page' => ['format' => 'A4']],
            'rows' => [
                ['blocks' => [
                    ['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]],
                ]],
            ],
        ],
        'data' => [],
    ]);

    $response->assertStatus(422);
});

it('renders the registered invoice example payload', function (): void {
    $fixture = collect(app(TemplateFixtureRepository::class)->examples())->firstWhere('slug', 'invoice');

    $response = postJson('/html', [
        'template' => $fixture->template,
        'data' => $fixture->data,
    ]);

    $response->assertSuccessful();
    expect($response->json('html'))->toContain('PDF UA Kit GmbH')
        ->toContain('RE-2026-001234');
});

it('renders every public example after loading through the workbench payload shape', function (): void {
    $examples = getJson('/examples')
        ->assertSuccessful()
        ->json('examples');

    expect($examples)->toBeArray()->not->toBeEmpty();

    foreach ($examples as $example) {
        $title = (string) $example['title'];
        $slug = Str::slug($title);
        $template = (array) $example['template'];
        $embeddedExampleData = (array) ($template['data']['example'] ?? []);
        $standaloneData = (array) $example['data'];
        $runtimeData = [...$embeddedExampleData, ...$standaloneData];

        $response = postJson('/html', [
            'template' => $template,
            'data' => $runtimeData,
        ]);

        expect($response->isSuccessful())->toBeTrue(
            "Example {$title} ({$slug}) failed workbench render validation with status {$response->status()}: {$response->getContent()}"
        );
    }
});

it('converts a posted template to pdf bytes', function (): void {
    Http::fake([
        'http://pdf-ua-api:8888/convert' => Http::response('%PDF-FAKE', 200, ['Content-Type' => 'application/pdf']),
    ]);
    config()->set('pdf-ua-client.base_url', 'http://pdf-ua-api:8888');

    $response = postJson('/pdf', [
        'template' => [
            'version' => 1,
            'config' => ['page' => ['format' => 'A4']],
            'rows' => [
                ['blocks' => [
                    ['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]],
                ]],
            ],
        ],
        'data' => ['title' => ['text' => 'Invoice 2026-001']],
    ]);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect($response->getContent())->toBe('%PDF-FAKE');
    Http::assertSent(fn ($request): bool => $request->url() === 'http://pdf-ua-api:8888/convert'
        && str_contains((string) $request['html'], '<h1>Invoice 2026-001</h1>'));
});

it('returns 502 when pdf conversion fails upstream', function (): void {
    Http::fake([
        'http://pdf-ua-api:8888/convert' => Http::response('upstream unavailable', 503),
    ]);
    config()->set('pdf-ua-client.base_url', 'http://pdf-ua-api:8888');

    $response = postJson('/pdf', [
        'template' => [
            'version' => 1,
            'config' => ['page' => ['format' => 'A4']],
            'rows' => [
                ['blocks' => [
                    ['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]],
                ]],
            ],
        ],
        'data' => ['title' => ['text' => 'Invoice 2026-001']],
    ]);

    $response->assertStatus(502);
    expect($response->json('message'))->toContain('PDF API convert failed');
});

it('returns the data schema for a valid template', function (): void {
    $response = postJson('/schema', [
        'template' => [
            'version' => 1,
            'config' => ['page' => ['format' => 'A4']],
            'rows' => [
                ['blocks' => [
                    ['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]],
                ]],
            ],
        ],
    ]);

    $response->assertOk();
    expect($response->json('dataSchema'))->toBeArray()
        ->toHaveKey('$schema')
        ->toHaveKey('properties');
});

it('returns 422 from schema endpoint for an invalid template', function (): void {
    $response = postJson('/schema', [
        'template' => ['version' => 1, 'config' => [], 'rows' => [['blocks' => []]]],
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toBeString();
});
