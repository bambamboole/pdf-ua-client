<?php

declare(strict_types=1);

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

beforeEach(function () {
    $this->schemaPath = __DIR__.'/../../template.schema.json';
    $this->schema = json_decode((string) file_get_contents($this->schemaPath), false, flags: JSON_THROW_ON_ERROR);
});

it('validates against the JSON Schema 2020-12 meta-schema', function () {
    $validator = new Validator;
    $metaDir = __DIR__.'/../Fixtures/json-schema-2020-12';

    $metaSchemas = [
        'https://json-schema.org/draft/2020-12/schema' => 'schema.json',
        'https://json-schema.org/draft/2020-12/meta/core' => 'core.json',
        'https://json-schema.org/draft/2020-12/meta/applicator' => 'applicator.json',
        'https://json-schema.org/draft/2020-12/meta/validation' => 'validation.json',
        'https://json-schema.org/draft/2020-12/meta/unevaluated' => 'unevaluated.json',
        'https://json-schema.org/draft/2020-12/meta/meta-data' => 'meta-data.json',
        'https://json-schema.org/draft/2020-12/meta/format-annotation' => 'format-annotation.json',
        'https://json-schema.org/draft/2020-12/meta/content' => 'content.json',
    ];

    $resolver = $validator->resolver();
    foreach ($metaSchemas as $uri => $filename) {
        $resolver?->registerRaw((string) file_get_contents($metaDir.'/'.$filename), $uri);
    }

    $result = $validator->validate($this->schema, 'https://json-schema.org/draft/2020-12/schema');

    if (! $result->isValid()) {
        $formatter = new ErrorFormatter;
        $this->fail("Schema fails 2020-12 meta-schema validation:\n".json_encode($formatter->formatOutput($result->error(), 'verbose'), JSON_PRETTY_PRINT));
    }

    expect($result->isValid())->toBeTrue();
});

it('resolves every internal $ref to a defined $defs entry', function () {
    $schemaArr = json_decode((string) file_get_contents($this->schemaPath), true, flags: JSON_THROW_ON_ERROR);

    $refs = $this->collectRefs($schemaArr);
    $defs = array_keys($schemaArr['$defs'] ?? []);

    foreach ($refs as $ref) {
        if (! str_starts_with($ref, '#/$defs/')) {
            continue;
        }
        $first = explode('/', substr($ref, strlen('#/$defs/')))[0];
        expect(in_array($first, $defs, true))->toBeTrue("Unresolved \$ref: {$ref}");
    }
});

it('has no orphan $defs entries', function () {
    $schemaArr = json_decode((string) file_get_contents($this->schemaPath), true, flags: JSON_THROW_ON_ERROR);

    $refs = $this->collectRefs($schemaArr);
    $referenced = [];
    foreach ($refs as $ref) {
        if (str_starts_with($ref, '#/$defs/')) {
            $referenced[] = explode('/', substr($ref, strlen('#/$defs/')))[0];
        }
    }
    $referenced = array_unique($referenced);

    foreach (array_keys($schemaArr['$defs'] ?? []) as $defName) {
        if (str_ends_with((string) $defName, 'Props')) {
            continue;
        }
        expect(in_array($defName, $referenced, true))->toBeTrue("Orphan \$def: {$defName}");
    }
});
