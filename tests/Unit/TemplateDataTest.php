<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\PdfTemplate;
use Bambamboole\PdfUaClient\TemplateData;

it('rejects templates from the legacy contract', function (): void {
    expect(fn (): PdfTemplate => new PdfTemplate(['version' => 1, 'rows' => []]))
        ->toThrow(InvalidArgumentException::class, 'Only template version 2 is supported.');
});

it('interpolates placeholders throughout a v2 template and preserves unknown values', function (): void {
    $template = [
        'config' => ['title' => 'Quote {{ quote.number }}'],
        'rows' => [['blocks' => [['type' => 'text', 'text' => '{{ customer.name }} / {{ unknown }}']]]],
    ];

    $result = TemplateData::interpolate($template, [
        'quote.number' => 'Q-1000',
        'customer.name' => 'Acme',
    ]);

    expect($result)->toBe([
        'config' => ['title' => 'Quote Q-1000'],
        'rows' => [['blocks' => [['type' => 'text', 'text' => 'Acme / {{ unknown }}']]]],
    ]);
});

it('lists body and footer blocks in document order', function (): void {
    $template = [
        'rows' => [['blocks' => [['type' => 'heading', 'id' => 'title']]]],
        'config' => ['page' => ['footer' => ['rows' => [
            ['blocks' => [['type' => 'text', 'id' => 'footer']]],
        ]]]],
    ];

    expect(TemplateData::blocks($template))->toBe([
        ['type' => 'heading', 'id' => 'title'],
        ['type' => 'text', 'id' => 'footer'],
    ]);
});

it('builds an override that matches each supported v2 block contract', function (): void {
    expect(TemplateData::contentOverride(['type' => 'text'], "First\nSecond"))->toBe(['text' => "First\nSecond"])
        ->and(TemplateData::contentOverride(['type' => 'html'], '<unsafe>'))->toBe(['html' => '&lt;unsafe&gt;'])
        ->and(TemplateData::keyValueOverride([
            'type' => 'key-value',
            'fields' => [['key' => 'number'], ['key' => 'date']],
        ], [
            'number' => 'Q-1000',
            'date' => null,
            'undeclared' => 'ignored',
        ]))->toBe([
            'number' => 'Q-1000',
            'date' => '',
        ])
        ->and(TemplateData::tableOverride([
            'type' => 'table',
            'columns' => [['key' => 'name'], ['key' => 'amount']],
        ], [[
            'name' => 'Item',
            'amount' => '10.00',
            'undeclared' => 'ignored',
        ]]))->toBe([[
            'name' => 'Item',
            'amount' => '10.00',
        ]]);
});

it('returns no override for a mismatched block type', function (): void {
    expect(TemplateData::contentOverride(['type' => 'table'], 'content'))->toBeNull()
        ->and(TemplateData::keyValueOverride(['type' => 'text'], []))->toBeNull()
        ->and(TemplateData::tableOverride(['type' => 'heading'], []))->toBeNull();
});

it('tidies blank lines without removing intentional paragraph breaks', function (): void {
    expect(TemplateData::tidyText("\nFirst  \n\n\n\nSecond  \n"))->toBe("First\n\nSecond");
});
