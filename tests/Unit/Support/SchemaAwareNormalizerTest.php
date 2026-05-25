<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Support\SchemaAwareNormalizer;

it('coerces an empty PHP array to stdClass when the schema declares object type', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'config' => ['type' => 'object'],
        ],
    ];

    $result = SchemaAwareNormalizer::normalize(['config' => []], $schema);

    expect($result)->toBeInstanceOf(stdClass::class);
    expect($result->config)->toBeInstanceOf(stdClass::class);
});

it('leaves array-typed empty arrays as arrays', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'rows' => ['type' => 'array', 'items' => ['type' => 'object']],
        ],
    ];

    $result = SchemaAwareNormalizer::normalize(['rows' => []], $schema);

    expect($result->rows)->toBe([]);
});

it('resolves $ref into $defs and recurses', function () {
    $schema = [
        'type' => 'object',
        'properties' => ['config' => ['$ref' => '#/$defs/cfg']],
        '$defs' => ['cfg' => ['type' => 'object', 'properties' => ['nested' => ['type' => 'object']]]],
    ];

    $result = SchemaAwareNormalizer::normalize(['config' => ['nested' => []]], $schema);

    expect($result->config)->toBeInstanceOf(stdClass::class);
    expect($result->config->nested)->toBeInstanceOf(stdClass::class);
});

it('dispatches into oneOf branches via if/then discriminator', function () {
    $schema = [
        'type' => 'object',
        'oneOf' => [
            [
                'if' => ['properties' => ['type' => ['const' => 'foo']]],
                'then' => ['type' => 'object', 'properties' => ['extra' => ['type' => 'object']]],
            ],
        ],
    ];

    $result = SchemaAwareNormalizer::normalize(['type' => 'foo', 'extra' => []], $schema);

    expect($result)->toBeInstanceOf(stdClass::class);
    expect($result->extra)->toBeInstanceOf(stdClass::class);
});

it('passes nested objects in arrays through correctly', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'rows' => [
                'type' => 'array',
                'items' => ['type' => 'object', 'properties' => ['config' => ['type' => 'object']]],
            ],
        ],
    ];

    $result = SchemaAwareNormalizer::normalize([
        'rows' => [
            ['config' => []],
            ['config' => []],
        ],
    ], $schema);

    expect($result->rows[0]->config)->toBeInstanceOf(stdClass::class);
    expect($result->rows[1]->config)->toBeInstanceOf(stdClass::class);
});

it('leaves non-array values unchanged', function () {
    $schema = ['type' => 'object', 'properties' => ['x' => ['type' => 'string']]];

    $result = SchemaAwareNormalizer::normalize(['x' => 'hello'], $schema);

    expect($result->x)->toBe('hello');
});
