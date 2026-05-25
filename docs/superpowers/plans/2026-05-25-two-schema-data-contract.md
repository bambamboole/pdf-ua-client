# Two-Schema Template + Data Contract Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split the single template schema into a static authoring schema (#1: structure + config) and a per-template, standalone data contract (#2), making "all content is data" with schema #2 as the single up-front data-validation gate.

**Architecture:** `TemplateSchemaCompiler` keeps emitting schema #1 but block defs drop `props` (the `{type}Props` defs stay in `$defs` as an unreferenced catalog with `required` preserved). A new `DataSchemaCompiler` derives schema #2 from a built `Template`, keyed by block `id`. `TemplateRenderer` validates the data payload against schema #2 up front; `BlockHydrator` and `TemplateFactory` stop dealing with content props.

**Tech Stack:** PHP 8.4 / Laravel (spatie package-tools, opis/json-schema, reflection-driven schema), Pest 4; TypeScript / React 19 / Vitest. Repo: standalone `pdf-ua-client`, branch `feat/two-schema-data-contract`.

**Spec:** `docs/superpowers/specs/2026-05-25-two-schema-data-contract-design.md`

**All commands run from the repo root `/Users/bambamboole/Projects/pdf-ua-client`.** PHP: `composer test` (Pest), `composer analyse` (PHPStan), `composer lint` (Pint via `vendor/bin/pint`). Schema regen: `UPDATE_SCHEMA=1 ./vendor/bin/pest tests/Feature/SchemaFileTest.php` (or `composer schema`). Fixtures regen: `UPDATE_FIXTURES=1 ./vendor/bin/pest tests/Feature/RenderFixtureTest.php`. Frontend: `npm run typecheck`, `npm run test` (Vitest), `npm run build`.

**Sequencing rationale (read first):** The behavior flip ("content comes only from data, validated by schema #2") breaks every render/factory test that uses inline `props`. To keep each commit green, Phase 2 first migrates **all consumers** (fixtures + tests) to pass content as runtime data while the source is unchanged (the current renderer's `mergeProps([], data)` yields the same HTML). Only then (Phase 3) does the source flip. Phase 4 tightens schema #1 to forbid `props` once no test sends `props` anymore.

---

## File Structure

```
# Production (src/) — new
src/Exceptions/DataValidationException.php   # opis ValidationError carrier (mirrors TemplateValidationException)
src/Template/DataSchemaCompiler.php          # compile(Template) + dataSchemaFor(Template|array)
src/Console/ExportDataSchemaCommand.php      # pdf-ua-client:data-schema-export {template} {path?}

# Production (src/) — modified
src/Template/TemplateSchemaCompiler.php      # block defs drop props; props catalog keeps required; root examples structure-only
src/Rendering/TemplateRenderer.php           # + DataSchemaCompiler dep; up-front schema #2 gate; drop mergeProps
src/Block/BlockHydrator.php                  # drop validate(); drop PropsReflector dep
src/Template/TemplateFactory.php             # stop reading props in buildRows
src/Examples/InvoiceExample.php              # document() (structure) + data() (content)
src/Template/ExampleRegistry.php             # {title, template, data} entries
src/PdfUaClientServiceProvider.php           # bind DataSchemaCompiler + command; update renderer/hydrator bindings; reshaped example
src/Exceptions/BlockDataValidationException.php  # DELETE if unused after hydrator change
template.schema.json                         # regenerated (block defs shrink; root examples structure-only)

# Workbench (workbench/)
workbench/app/Http/Controllers/TemplateBuilderController.php  # 422 on DataValidationException; pass examples Inertia prop

# Shared builder lib (resources/js/builder/) + workbench JS
resources/js/builder/lib/schema.ts (+ schema.test.ts)         # getBlockSubschemas resolves {type}Props by name
resources/js/builder/lib/examples.ts (+ examples.test.ts)     # listExamples(entries) + loadExample({template,data})
resources/js/builder/BlockPalette.tsx, TemplateBuilder.tsx    # examples from prop, not schema.examples
workbench/resources/js/Pages/Builder.tsx                      # thread examples prop

# Tests migrated (Phase 2/3/4)
tests/Fixtures/render/*.php (7 files)        # inline props -> data map
tests/Feature/RenderingTest.php, EndToEndTest.php, Rendering/ColumnWidthFromConfigTest.php
tests/Feature/Workbench/RenderEndpointTest.php
tests/Unit/Template/TemplateFactoryTest.php, TemplateSchemaCompilerTest.php
tests/Unit/Block/BlockHydratorTest.php
tests/Feature/SchemaExamplesTest.php, SchemaValidityTest.php
tests/Unit/Template/ExampleRegistryTest.php
```

---

# PHASE 1 — Schema #2 foundation (additive, fully green)

## Task 1: `DataValidationException`

**Files:** Create `src/Exceptions/DataValidationException.php`; test `tests/Unit/Exceptions/DataValidationExceptionTest.php`.

- [ ] **Step 1: Write the failing test** — create `tests/Unit/Exceptions/DataValidationExceptionTest.php`:

```php
<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Exceptions\DataValidationException;

it('is a runtime exception carrying an optional validation error', function (): void {
    $exception = new DataValidationException('Data failed schema validation');

    expect($exception)->toBeInstanceOf(RuntimeException::class)
        ->and($exception->getMessage())->toBe('Data failed schema validation')
        ->and($exception->error)->toBeNull();
});
```

- [ ] **Step 2: Run, verify FAIL** — `./vendor/bin/pest tests/Unit/Exceptions/DataValidationExceptionTest.php` → FAIL (class missing).

- [ ] **Step 3: Implement** — create `src/Exceptions/DataValidationException.php`:

```php
<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Exceptions;

use Opis\JsonSchema\Errors\ValidationError;
use RuntimeException;

final class DataValidationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?ValidationError $error = null,
    ) {
        parent::__construct($message);
    }
}
```

- [ ] **Step 4: Run, verify PASS** — `./vendor/bin/pest tests/Unit/Exceptions/DataValidationExceptionTest.php` → PASS.

- [ ] **Step 5: Lint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add src/Exceptions/DataValidationException.php tests/Unit/Exceptions/DataValidationExceptionTest.php
git commit -m "feat: add DataValidationException"
```

---

## Task 2: `DataSchemaCompiler` (compile + dataSchemaFor)

**Files:** Create `src/Template/DataSchemaCompiler.php`; modify `src/PdfUaClientServiceProvider.php`; test `tests/Unit/Template/DataSchemaCompilerTest.php`.

- [ ] **Step 1: Write the failing test** — create `tests/Unit/Template/DataSchemaCompilerTest.php`:

```php
<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use Bambamboole\PdfUaClient\Blocks\DividerBlock;
use Bambamboole\PdfUaClient\Blocks\HeadingBlock;
use Bambamboole\PdfUaClient\Blocks\TableBlock;
use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Bambamboole\PdfUaClient\Template\ExampleRegistry;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;

beforeEach(function (): void {
    $registry = new BlockRegistry;
    $registry->register(HeadingBlock::class);
    $registry->register(TableBlock::class);
    $registry->register(DividerBlock::class);
    $reflector = new PropsReflector;
    $this->factory = new TemplateFactory($registry, new TemplateSchemaCompiler($reflector, new ExampleRegistry));
    $this->compiler = new DataSchemaCompiler($reflector, $registry, $this->factory);
});

it('keys content-bearing blocks by id and requires those with required props', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [
            ['blocks' => [['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]]]],
            ['blocks' => [['type' => 'divider', 'id' => 'rule']]],
            ['blocks' => [['type' => 'table', 'id' => 'items']]],
        ],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema')
        ->and($schema['type'])->toBe('object')
        ->and($schema['additionalProperties'])->toBeFalse()
        ->and(array_keys($schema['properties']))->toBe(['title', 'items']) // divider omitted (no props)
        ->and($schema['properties']['title']['properties'])->toHaveKey('text')
        ->and($schema['properties']['title']['additionalProperties'])->toBeFalse()
        ->and($schema['required'])->toBe(['title', 'items']); // heading.text + table.headers/rows are required
});

it('emits an empty-object schema with no required when no block needs data', function (): void {
    $template = $this->factory->fromArray([
        'version' => 1,
        'config' => [],
        'rows' => [['blocks' => [['type' => 'divider', 'id' => 'rule']]]],
    ]);

    $schema = $this->compiler->compile($template);

    expect($schema['properties'])->toBeInstanceOf(stdClass::class)
        ->and($schema)->not->toHaveKey('required')
        ->and($schema)->not->toHaveKey('$defs'); // standalone: props are inlined, no $defs needed
});

it('dataSchemaFor accepts a raw array by building it through the factory', function (): void {
    $schema = $this->compiler->dataSchemaFor([
        'version' => 1,
        'config' => [],
        'rows' => [['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]]],
    ]);

    expect(array_keys($schema['properties']))->toBe(['h']);
});
```

- [ ] **Step 2: Run, verify FAIL** — `./vendor/bin/pest tests/Unit/Template/DataSchemaCompilerTest.php` → FAIL (class missing).

- [ ] **Step 3: Implement** — create `src/Template/DataSchemaCompiler.php`:

```php
<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Block\PropsReflector;
use stdClass;

final class DataSchemaCompiler
{
    public function __construct(
        private readonly PropsReflector $reflector,
        private readonly BlockRegistry $registry,
        private readonly TemplateFactory $factory,
    ) {}

    /** @param Template|array<string, mixed> $template */
    public function dataSchemaFor(Template|array $template): array
    {
        $built = is_array($template) ? $this->factory->fromArray($template) : $template;

        return $this->compile($built);
    }

    /** @return array<string, mixed> */
    public function compile(Template $template): array
    {
        $properties = [];
        $required = [];

        foreach ($template->rows as $row) {
            foreach ($row->blocks as $block) {
                $dataSchema = $this->reflector->reflectBlock($this->registry->resolve($block->type))['data'];

                if ($this->hasNoProperties($dataSchema)) {
                    continue;
                }

                $id = (string) $block->id;
                $properties[$id] = $dataSchema;

                if (isset($dataSchema['required']) && $dataSchema['required'] !== []) {
                    $required[] = $id;
                }
            }
        }

        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            '$id' => 'https://pdfuakit.com/schemas/pdf-ua-client-template-data-v1.json',
            'type' => 'object',
            'properties' => $properties === [] ? new stdClass : $properties,
            'additionalProperties' => false,
        ];

        if ($required !== []) {
            $schema['required'] = array_values(array_unique($required));
        }

        return $schema;
    }

    /** @param array<string, mixed> $schema */
    private function hasNoProperties(array $schema): bool
    {
        $properties = $schema['properties'] ?? null;

        return $properties === null || $properties instanceof stdClass || $properties === [];
    }
}
```

- [ ] **Step 4: Bind it** — in `src/PdfUaClientServiceProvider.php`, add `use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;` and inside `packageRegistered()` (after the `TemplateFactory` binding):

```php
        $this->app->singleton(DataSchemaCompiler::class, fn (Container $app): DataSchemaCompiler => new DataSchemaCompiler(
            $app->make(PropsReflector::class),
            $app->make(BlockRegistry::class),
            $app->make(TemplateFactory::class),
        ));
```

- [ ] **Step 5: Run, verify PASS** — `./vendor/bin/pest tests/Unit/Template/DataSchemaCompilerTest.php` → PASS. Then `composer test` (full suite still green — additive) and `composer analyse`.

- [ ] **Step 6: Lint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add src/Template/DataSchemaCompiler.php src/PdfUaClientServiceProvider.php tests/Unit/Template/DataSchemaCompilerTest.php
git commit -m "feat: add DataSchemaCompiler deriving per-template data contract"
```

---

# PHASE 2 — Migrate all consumers to the data model (source unchanged, stays green)

## Task 3: Move inline `props` into runtime data across fixtures and tests

The current renderer still merges (`mergeProps([], data) === data`) and the hydrator still validates per-block, so moving content to the data argument produces identical HTML and stays green. **Transformation rule** for every render-style template:

1. Ensure each content-bearing block has an explicit `'id'`.
2. Delete the block's `'props' => [...]` key.
3. Pass those props as the renderer's data argument keyed by id: `render($template, [<id> => [...props...], ...])`. The `block-N` CSS classes come from a sequential counter, not the id, so expected HTML is unchanged.

**Files:** modify the 7 render fixtures and the listed test files.

- [ ] **Step 1: Migrate the 7 render fixtures** — `tests/Fixtures/render/{full-catalog,positioning,heading-simple,invoice-realistic,typography-cascade,two-column-row,runtime-data-rich}.php`. In each `TestFixture`, give content blocks ids, remove `props` from `spec`, and merge those props into the `data` array keyed by id. Example (`heading-simple.php`) before:

```php
spec: [ /* ... */ 'rows' => [['blocks' => [['type' => 'heading', 'props' => ['text' => 'Hello'], 'config' => ['level' => 1]]]]] ],
data: [],
```

after:

```php
spec: [ /* ... */ 'rows' => [['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]]] ],
data: ['h' => ['text' => 'Hello']],
```

For `runtime-data-rich.php`, fold any inline props into `data` (merge happened at render before; now `data` is the sole source) — keep existing `data` keys, add the former inline props under the same block ids.

- [ ] **Step 2: Run the fixtures** — `./vendor/bin/pest tests/Feature/RenderFixtureTest.php`. Expected: PASS (HTML unchanged). If any fixture's HTML legitimately shifts, regenerate once: `UPDATE_FIXTURES=1 ./vendor/bin/pest tests/Feature/RenderFixtureTest.php`, then re-run to confirm green.

- [ ] **Step 3: Migrate `tests/Feature/RenderingTest.php`** — apply the rule to all cases that build a template with inline `props` then `render($template)`. Representative before (the first `it`):

```php
$template = $this->factory->fromArray([
    'version' => 1, 'config' => ['page' => ['format' => 'A4']],
    'rows' => [['blocks' => [['type' => 'heading', 'props' => ['text' => 'Invoice 001'], 'config' => ['level' => 1]]]]],
]);
$html = $this->renderer->render($template);
```

after:

```php
$template = $this->factory->fromArray([
    'version' => 1, 'config' => ['page' => ['format' => 'A4']],
    'rows' => [['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]]],
]);
$html = $this->renderer->render($template, ['h' => ['text' => 'Invoice 001']]);
```

For the multi-block "Left column"/"Right column" case, give ids `l`/`r` and pass `['l' => ['text' => 'Left column'], 'r' => ['text' => 'Right column']]`. The case currently named "merges runtime data over inline props by block id" already passes data only — rename it to "supplies block content via runtime data by block id" and leave its body. Apply to every case in the file (heading/text/html blocks). Expected HTML assertions stay identical.

- [ ] **Step 4: Migrate `tests/Feature/EndToEndTest.php` and `tests/Feature/Rendering/ColumnWidthFromConfigTest.php`** — same rule. For `ColumnWidthFromConfigTest`, the second block in the legacy-path case has no id — give it one (e.g. `r`) and move its text to data. Keep all `config` (e.g. `width`) inline; only `props` move.

- [ ] **Step 5: Migrate `tests/Feature/Workbench/RenderEndpointTest.php`** — the first case posts a template with inline `props`. Change it to give the heading an `id` and post a sibling `data` key instead:

```php
$response = postJson('/render', [
    'template' => [
        'version' => 1, 'config' => ['page' => ['format' => 'A4']],
        'rows' => [['blocks' => [['type' => 'heading', 'id' => 'title', 'config' => ['level' => 1]]]]],
    ],
    'data' => ['title' => ['text' => 'Invoice 2026-001']],
]);
```

Leave the "returns 422 for an invalid template" and "injects posted data" cases unchanged.

- [ ] **Step 6: Migrate `tests/Unit/Template/TemplateFactoryTest.php`** — this exercises the factory only (no render), so just **delete** the `'props' => [...]` entries from each block in the specs (the `test-fixture` block builds fine without props). All assertions (version, format, config, id `r0b0`) are unaffected.

- [ ] **Step 7: Run, verify PASS** — `composer test` (full suite green — source unchanged) and `composer analyse`.

- [ ] **Step 8: Lint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Fixtures/render tests/Feature/RenderingTest.php tests/Feature/EndToEndTest.php tests/Feature/Rendering/ColumnWidthFromConfigTest.php tests/Feature/Workbench/RenderEndpointTest.php tests/Unit/Template/TemplateFactoryTest.php
git commit -m "test: move inline block props into runtime data (all-content-is-data)"
```

---

# PHASE 3 — Flip the source to the clean model (single data gate)

## Task 4: Up-front schema #2 gate; drop `mergeProps` and `BlockHydrator::validate()`

**Files:** modify `src/Rendering/TemplateRenderer.php`, `src/Block/BlockHydrator.php`, `src/Template/TemplateFactory.php`, `src/PdfUaClientServiceProvider.php`, `tests/Unit/Block/BlockHydratorTest.php`; add a case to `tests/Feature/RenderingTest.php`; possibly delete `src/Exceptions/BlockDataValidationException.php`.

- [ ] **Step 1: Write the failing test** — append to `tests/Feature/RenderingTest.php`:

```php
it('rejects a data payload that violates the template data contract', function () {
    $template = $this->factory->fromArray([
        'version' => 1, 'config' => ['page' => ['format' => 'A4']],
        'rows' => [['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]]],
    ]);

    expect(fn () => $this->renderer->render($template, []))
        ->toThrow(\Bambamboole\PdfUaClient\Exceptions\DataValidationException::class);
});
```

Update this file's `beforeEach`: the renderer now needs the `DataSchemaCompiler`. Replace the renderer construction with:

```php
    $this->renderer = new TemplateRenderer(
        new BlockHydrator($registry),
        new DataSchemaCompiler($reflector, $registry, $this->factory),
    );
```

and add `use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;`.

- [ ] **Step 2: Run, verify FAIL** — `./vendor/bin/pest tests/Feature/RenderingTest.php` → FAIL (renderer ctor arity / no exception thrown yet).

- [ ] **Step 3: Implement the renderer** — `src/Rendering/TemplateRenderer.php`:
  - Add imports: `use Bambamboole\PdfUaClient\Exceptions\DataValidationException;`, `use Bambamboole\PdfUaClient\Support\SchemaAwareNormalizer;`, `use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;`, `use Opis\JsonSchema\Validator;`.
  - Constructor:

```php
    public function __construct(
        private readonly BlockHydrator $hydrator,
        private readonly DataSchemaCompiler $dataSchemaCompiler,
    ) {}
```

  - At the top of `render()`, before the rows loop, add the gate:

```php
        $this->validateData($template, $runtimeData);
```

  - Add the method:

```php
    /** @param array<string, array<string, mixed>> $runtimeData */
    private function validateData(Template $template, array $runtimeData): void
    {
        $schema = $this->dataSchemaCompiler->compile($template);
        $normalized = SchemaAwareNormalizer::normalize($runtimeData, $schema);
        $validator = new Validator;
        $result = $validator->validate($normalized, json_decode((string) json_encode($schema)));

        if (! $result->isValid()) {
            throw new DataValidationException('Data failed schema validation', $result->error());
        }
    }
```

  - Replace `mergeProps()` usage: in `renderBlock()`, change `$mergedProps = $this->mergeProps($instance->props, $runtimeData[$instance->id] ?? []);` to `$mergedProps = $runtimeData[$instance->id] ?? [];` and delete the now-unused `mergeProps()` method.

- [ ] **Step 4: Implement the hydrator** — `src/Block/BlockHydrator.php`:
  - Constructor becomes `public function __construct(private readonly BlockRegistry $registry) {}`.
  - Delete the `validate()` method and its call in `hydrate()`. Remove now-unused imports (`PropsReflector`, `BlockDataValidationException`, `SchemaAwareNormalizer`, `Validator`). `hydrate()` keeps resolving the block + config classes and instantiating.

- [ ] **Step 5: Implement the factory** — `src/Template/TemplateFactory.php`, in `buildRows()`, drop reading props so blocks carry no inline content:

```php
                $blocks[] = new BlockInstance(
                    type: (string) $blockData['type'],
                    id: $blockData['id'] ?? "r{$rowIndex}b{$blockIndex}",
                    config: (array) ($blockData['config'] ?? []),
                );
```

(`BlockInstance::$props` keeps its `[]` default.)

- [ ] **Step 6: Update bindings** — `src/PdfUaClientServiceProvider.php`:
  - `BlockHydrator` binding → `new BlockHydrator($app->make(BlockRegistry::class))` (drop the `PropsReflector` arg).
  - `TemplateRenderer` binding → `new TemplateRenderer($app->make(BlockHydrator::class), $app->make(DataSchemaCompiler::class))`.

- [ ] **Step 7: Update `tests/Unit/Block/BlockHydratorTest.php`**:
  - `beforeEach`: `$this->hydrator = new BlockHydrator($registry);` (drop `new PropsReflector`).
  - **Delete** the case "throws BlockDataValidationException when config violates a constraint" (constraint enforcement now lives in schema #1, not the hydrator).
  - Change the case "throws BlockDataValidationException when required props are missing" to expect the defensive hydration error and rename it:

```php
it('throws when a required prop is missing at hydration', function () {
    expect(fn () => $this->hydrator->hydrate(new BlockInstance(
        type: 'hydration-block',
        props: [],
    )))->toThrow(\Bambamboole\PdfUaClient\Exceptions\BlockHydrationException::class);
});
```

  - Remove the now-unused `BlockDataValidationException`, `Min`, `Max` imports if they become unused.

- [ ] **Step 8: Delete `BlockDataValidationException` if unused** — run `grep -rn "BlockDataValidationException" src tests`. If the only hits are its own file, delete it: `git rm src/Exceptions/BlockDataValidationException.php`. Otherwise leave it.

- [ ] **Step 9: Run, verify PASS** — `composer test` (full suite: the new gate case passes; migrated Phase-2 tests still pass because they pass complete data) and `composer analyse`.

- [ ] **Step 10: Lint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add src tests/Feature/RenderingTest.php tests/Unit/Block/BlockHydratorTest.php
git commit -m "feat: schema #2 is the single data gate; drop mergeProps + hydrator validation"
```

---

# PHASE 4 — Tighten schema #1 + reshape examples

## Task 5: Schema #1 drops `props` from block defs (catalog kept)

**Files:** modify `src/Template/TemplateSchemaCompiler.php`, `tests/Unit/Template/TemplateSchemaCompilerTest.php`, `tests/Feature/SchemaValidityTest.php`; regenerate `template.schema.json`; add a rejection case to `tests/Unit/Template/TemplateFactoryTest.php`.

- [ ] **Step 1: Write the failing tests.**

In `tests/Unit/Template/TemplateSchemaCompilerTest.php`, replace the `props` assertion in "composes per-block defs..." with:

```php
    expect($blockDef['properties'])->not->toHaveKey('props');
    expect($blockDef['properties']['config'])->toBe(['$ref' => '#/$defs/testFixtureBlockConfig']);
    expect($schema['$defs'])->toHaveKey('testFixtureProps'); // catalog retained
    expect($schema['$defs']['testFixtureProps']['required'])->toBe(['text']); // required preserved (not stripped)
```

In `tests/Unit/Template/TemplateFactoryTest.php`, add:

```php
it('rejects a block that carries inline props', function () {
    expect(fn () => $this->factory->fromArray([
        'version' => 1, 'config' => [],
        'rows' => [['blocks' => [['type' => 'test-fixture', 'props' => ['text' => 'x']]]]],
    ]))->toThrow(\Bambamboole\PdfUaClient\Exceptions\TemplateValidationException::class);
});
```

In `tests/Feature/SchemaValidityTest.php`, update the "has no orphan $defs entries" test so the `{type}Props` catalog (intentionally unreferenced within schema #1) is allowed:

```php
    foreach (array_keys($schemaArr['$defs'] ?? []) as $defName) {
        if (str_ends_with($defName, 'Props')) {
            continue; // block data-shape catalog: referenced by the builder + schema #2, not by schema #1
        }
        expect(in_array($defName, $referenced, true))->toBeTrue("Orphan \$def: {$defName}");
    }
```

- [ ] **Step 2: Run, verify FAIL** — `./vendor/bin/pest tests/Unit/Template/TemplateSchemaCompilerTest.php tests/Unit/Template/TemplateFactoryTest.php` → FAIL.

- [ ] **Step 3: Implement** — `src/Template/TemplateSchemaCompiler.php`, in the `foreach ($registry->all() ...)` loop:
  - Remove the `$propsSchema = $this->stripRequired($propsSchema);` line so the catalog keeps `required`.
  - In the `$defs->ref($defName, [...])` block-def array, **remove** the `'props' => ['$ref' => "#/\$defs/{$propsDefName}"],` line from `properties`. Keep registering the catalog: `$defs->ref($propsDefName, $propsSchema);` stays (above the block-def registration).

The block def now exposes only `type` + `config`; `unevaluatedProperties: false` rejects any `props` key.

- [ ] **Step 4: Regenerate the schema + run** — `UPDATE_SCHEMA=1 ./vendor/bin/pest tests/Feature/SchemaFileTest.php`, then `composer test` (drift guard, validity incl. the updated orphan rule, annotations test — `headingProps` etc. still carry their `title`/`examples` in the catalog) and `composer analyse`.

  Note: the schema-root `examples` still embed the old prop-bearing invoice at this point; that's annotation-only and `SchemaExamplesTest` strips props before validating, so it stays green. Task 6 reshapes it.

- [ ] **Step 5: Lint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add src/Template/TemplateSchemaCompiler.php tests/Unit/Template/TemplateSchemaCompilerTest.php tests/Unit/Template/TemplateFactoryTest.php tests/Feature/SchemaValidityTest.php template.schema.json
git commit -m "feat: schema #1 drops block props (kept as catalog); templates reject props"
```

---

## Task 6: Reshape `InvoiceExample` + `ExampleRegistry`; structure-only root examples

**Files:** modify `src/Examples/InvoiceExample.php`, `src/Template/ExampleRegistry.php`, `src/Template/TemplateSchemaCompiler.php`, `src/PdfUaClientServiceProvider.php`, `tests/Unit/Template/ExampleRegistryTest.php`, `tests/Feature/SchemaExamplesTest.php`; regenerate `template.schema.json`.

- [ ] **Step 1: Write the failing tests.**

Rewrite `tests/Unit/Template/ExampleRegistryTest.php`:

```php
<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Template\ExampleRegistry;

it('registers titled template+data entries, lists, and flushes', function (): void {
    $registry = new ExampleRegistry;
    $registry->register('A', ['version' => 1, 'config' => [], 'rows' => []], ['x' => ['text' => 'hi']]);
    expect($registry->all())->toHaveCount(1)
        ->and($registry->all()[0]['title'])->toBe('A')
        ->and($registry->all()[0]['data'])->toBe(['x' => ['text' => 'hi']]);

    $registry->flush()->register('B', ['version' => 1, 'config' => [], 'rows' => []]);
    expect($registry->all())->toHaveCount(1)
        ->and($registry->all()[0]['title'])->toBe('B')
        ->and($registry->all()[0]['data'])->toBe([]);
});
```

Rewrite `tests/Feature/SchemaExamplesTest.php`:

```php
<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Examples\InvoiceExample;
use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;
use Opis\JsonSchema\Validator;

it('attaches structure-only example documents to the compiled schema root', function (): void {
    $schema = app(TemplateSchemaCompiler::class)->compile(app(BlockRegistry::class));

    expect($schema['examples'])->toBeArray()
        ->and($schema['examples'][0]['title'])->toBe('Invoice');

    // structure-only: no block in any example carries props
    foreach ($schema['examples'][0]['rows'] as $row) {
        foreach ($row['blocks'] as $block) {
            expect($block)->not->toHaveKey('props');
        }
    }
});

it('the invoice structure validates against schema #1 and its data against schema #2', function (): void {
    $template = app(TemplateFactory::class)->fromArray(InvoiceExample::document());

    $dataSchema = app(DataSchemaCompiler::class)->compile($template);
    $result = (new Validator)->validate(
        json_decode((string) json_encode(InvoiceExample::data())),
        json_decode((string) json_encode($dataSchema)),
    );

    expect($result->isValid())->toBeTrue();
});
```

(Note: `TemplateFactory::fromArray(InvoiceExample::document())` ignores the extra root `title` key — the root permits additional properties.)

- [ ] **Step 2: Run, verify FAIL** — `./vendor/bin/pest tests/Unit/Template/ExampleRegistryTest.php tests/Feature/SchemaExamplesTest.php` → FAIL.

- [ ] **Step 3: Reshape `ExampleRegistry`** — `src/Template/ExampleRegistry.php`:

```php
<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

final class ExampleRegistry
{
    /** @var list<array{title: string, template: array<string, mixed>, data: array<string, mixed>}> */
    private array $examples = [];

    /**
     * @param  array<string, mixed>  $template
     * @param  array<string, mixed>  $data
     */
    public function register(string $title, array $template, array $data = []): self
    {
        $this->examples[] = ['title' => $title, 'template' => $template, 'data' => $data];

        return $this;
    }

    public function flush(): self
    {
        $this->examples = [];

        return $this;
    }

    /** @return list<array{title: string, template: array<string, mixed>, data: array<string, mixed>}> */
    public function all(): array
    {
        return $this->examples;
    }
}
```

- [ ] **Step 4: Split `InvoiceExample`** — `src/Examples/InvoiceExample.php`: `document()` returns structure + config (with the root `title`), each block `{type, id, config}` and **no `props`**; add `data()` returning the content keyed by id:

```php
<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Examples;

final class InvoiceExample
{
    /** @return array<string, mixed> */
    public static function document(): array
    {
        return [
            'title' => 'Invoice',
            'version' => 1,
            'config' => ['page' => ['format' => 'A4']],
            'rows' => [
                ['blocks' => [
                    ['type' => 'heading', 'id' => 'company', 'config' => ['level' => 1, 'width' => '60%']],
                    ['type' => 'key-value', 'id' => 'invoice-meta', 'config' => ['align' => 'right', 'width' => '40%']],
                ]],
                ['blocks' => [
                    ['type' => 'key-value', 'id' => 'from', 'config' => ['width' => '50%']],
                    ['type' => 'key-value', 'id' => 'to', 'config' => ['width' => '50%']],
                ]],
                ['blocks' => [['type' => 'divider', 'id' => 'rule']]],
                ['blocks' => [['type' => 'table', 'id' => 'items']]],
                ['blocks' => [['type' => 'key-value', 'id' => 'totals', 'config' => ['align' => 'right']]]],
                ['blocks' => [['type' => 'text', 'id' => 'footer']]],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function data(): array
    {
        return [
            'company' => ['text' => 'ACME GmbH'],
            'invoice-meta' => ['entries' => [
                ['label' => 'Invoice', 'value' => '2026-001'],
                ['label' => 'Date', 'value' => '2026-05-25'],
                ['label' => 'Due', 'value' => '2026-06-08'],
            ]],
            'from' => ['entries' => [['label' => 'From', 'value' => 'ACME GmbH, Main St 1']]],
            'to' => ['entries' => [['label' => 'Bill to', 'value' => 'Beta Ltd, 2nd Ave']]],
            'items' => [
                'headers' => ['Description', 'Qty', 'Unit', 'Amount'],
                'rows' => [['Consulting', '10', '€100', '€1000'], ['License', '1', '€250', '€250']],
            ],
            'totals' => ['entries' => [
                ['label' => 'Subtotal', 'value' => '€1250'],
                ['label' => 'Tax (19%)', 'value' => '€237.50'],
                ['label' => 'Total', 'value' => '€1487.50'],
            ]],
            'footer' => ['text' => 'Payment due within 14 days. Thank you for your business.'],
        ];
    }
}
```

- [ ] **Step 5: Update the compiler's root examples** — `src/Template/TemplateSchemaCompiler.php`, where it reads `$this->examples->all()`, map registry entries to their structure document (which already carries `title`):

```php
        $examples = array_map(static fn (array $entry): array => $entry['template'], $this->examples->all());
        if ($examples !== []) {
            $schema['examples'] = $examples;
        }
```

- [ ] **Step 6: Update the service-provider registration** — `src/PdfUaClientServiceProvider.php`, the `ExampleRegistry` singleton:

```php
        $this->app->singleton(ExampleRegistry::class, function (): ExampleRegistry {
            return (new ExampleRegistry)->register('Invoice', InvoiceExample::document(), InvoiceExample::data());
        });
```

- [ ] **Step 7: Regenerate + run** — `UPDATE_SCHEMA=1 ./vendor/bin/pest tests/Feature/SchemaFileTest.php`, then `./vendor/bin/pest tests/Unit/Template/ExampleRegistryTest.php tests/Feature/SchemaExamplesTest.php` → PASS, then `composer test` + `composer analyse`.

- [ ] **Step 8: Lint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add src/Examples/InvoiceExample.php src/Template/ExampleRegistry.php src/Template/TemplateSchemaCompiler.php src/PdfUaClientServiceProvider.php tests/Unit/Template/ExampleRegistryTest.php tests/Feature/SchemaExamplesTest.php template.schema.json
git commit -m "feat: structure-only examples with separate data fixture"
```

---

# PHASE 5 — Production export command

## Task 7: `pdf-ua-client:data-schema-export`

**Files:** create `src/Console/ExportDataSchemaCommand.php`; modify `src/PdfUaClientServiceProvider.php`; test `tests/Feature/Commands/ExportDataSchemaCommandTest.php`.

- [ ] **Step 1: Write the failing test** — create `tests/Feature/Commands/ExportDataSchemaCommandTest.php`:

```php
<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Examples\InvoiceExample;
use Illuminate\Support\Facades\File;

it('writes a standalone data schema for a given template file', function (): void {
    $dir = sys_get_temp_dir().'/pdf-ua-'.uniqid();
    File::ensureDirectoryExists($dir);
    $templatePath = $dir.'/template.json';
    $outPath = $dir.'/data.schema.json';
    File::put($templatePath, (string) json_encode(InvoiceExample::document()));

    $this->artisan('pdf-ua-client:data-schema-export', ['template' => $templatePath, 'path' => $outPath])
        ->assertSuccessful();

    $schema = json_decode((string) File::get($outPath), true, flags: JSON_THROW_ON_ERROR);
    expect($schema['type'])->toBe('object')
        ->and($schema['additionalProperties'])->toBeFalse()
        ->and($schema['properties'])->toHaveKey('items');

    File::deleteDirectory($dir);
});
```

- [ ] **Step 2: Run, verify FAIL** — `./vendor/bin/pest tests/Feature/Commands/ExportDataSchemaCommandTest.php` → FAIL (command missing).

- [ ] **Step 3: Implement** — create `src/Console/ExportDataSchemaCommand.php`:

```php
<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Console;

use Bambamboole\PdfUaClient\Template\DataSchemaCompiler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ExportDataSchemaCommand extends Command
{
    /** @var string */
    protected $signature = 'pdf-ua-client:data-schema-export {template} {path?}';

    /** @var string */
    protected $description = 'Export the data-payload JSON Schema for a given pdf-ua-client template.';

    public function handle(DataSchemaCompiler $compiler): int
    {
        /** @var string $templatePath */
        $templatePath = $this->argument('template');

        if (! File::exists($templatePath)) {
            $this->error("Template file not found: {$templatePath}");

            return self::FAILURE;
        }

        /** @var array<string, mixed> $template */
        $template = json_decode((string) File::get($templatePath), true, flags: JSON_THROW_ON_ERROR);

        /** @var string|null $argPath */
        $argPath = $this->argument('path');
        $path = $argPath ?? storage_path('app/pdf-ua-client/template-data.schema.json');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($compiler->dataSchemaFor($template), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Data schema written to: {$path}");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Register the command** — `src/PdfUaClientServiceProvider.php`, add `use Bambamboole\PdfUaClient\Console\ExportDataSchemaCommand;` and chain it in `configurePackage()`:

```php
            ->hasCommand(ExportSchemaCommand::class)
            ->hasCommand(ExportDataSchemaCommand::class);
```

- [ ] **Step 5: Run, verify PASS** — `./vendor/bin/pest tests/Feature/Commands/ExportDataSchemaCommandTest.php` → PASS, then `composer test` + `composer analyse`.

- [ ] **Step 6: Lint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add src/Console/ExportDataSchemaCommand.php src/PdfUaClientServiceProvider.php tests/Feature/Commands/ExportDataSchemaCommandTest.php
git commit -m "feat: add pdf-ua-client:data-schema-export command"
```

---

# PHASE 6 — Frontend: resolve props catalog by name; examples from a prop

## Task 8: `getBlockSubschemas` resolves `{type}Props` by name

**Files:** modify `resources/js/builder/lib/schema.ts`, `resources/js/builder/lib/schema.test.ts`.

- [ ] **Step 1: Update the test** — in `resources/js/builder/lib/schema.test.ts`, change the inline `keyValueBlock` and `tableBlock` defs to omit `props` (matching the new schema #1), keeping the `{type}Props` defs in `$defs`. For example `keyValueBlock` becomes:

```ts
    keyValueBlock: {
      allOf: [{ $ref: "#/$defs/blockBase" }],
      properties: {
        type: { const: "key-value", type: "string" },
        config: { $ref: "#/$defs/keyValueConfig" },
      },
      unevaluatedProperties: false,
    },
```

The `getBlockSubschemas` and `getBlockTitle` expectations stay the same (props resolved from `keyValueProps`/`tableProps` by name). Leave `keyValueProps`/`tableProps` defs as-is.

- [ ] **Step 2: Run, verify FAIL** — `npm run test -- schema` → FAIL (props resolved via the now-absent `blockDef.properties.props.$ref`).

- [ ] **Step 3: Implement** — in `resources/js/builder/lib/schema.ts`:
  - Add a `propsDefName` helper mirroring the PHP `camelCase(type) + "Props"`:

```ts
function propsDefName(type: string): string {
  const [head, ...tail] = type.split(/[-_]/);
  const camel =
    head.charAt(0).toLowerCase() +
    head.slice(1) +
    tail.map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join("");
  return `${camel}Props`;
}
```

  - In `getBlockSubschemas`, resolve `props` from the catalog by name instead of from the block def:

```ts
  const props = ((schema as any)?.$defs?.[propsDefName(type)] ?? null) as Record<string, any> | null;
  const config = resolveRef(schema, blockDef.properties?.config?.$ref);
```

  (Leave the `config` resolution and the `emptySchema()` fallbacks unchanged.)

- [ ] **Step 4: Run, verify PASS** — `npm run test -- schema` → PASS; `npm run typecheck`.

- [ ] **Step 5: Commit**

```bash
git add resources/js/builder/lib/schema.ts resources/js/builder/lib/schema.test.ts
git commit -m "feat: resolve block props schema from the {type}Props catalog by name"
```

---

## Task 9: Examples come from a prop ({title, template, data}); workbench wiring

**Files:** modify `resources/js/builder/lib/examples.ts`, `resources/js/builder/lib/examples.test.ts`, `resources/js/builder/BlockPalette.tsx`, `resources/js/builder/TemplateBuilder.tsx`, `workbench/resources/js/Pages/Builder.tsx`, `workbench/app/Http/Controllers/TemplateBuilderController.php`, `tests/Feature/Workbench/RenderEndpointTest.php`.

- [ ] **Step 1: Update the Vitest spec** — rewrite `resources/js/builder/lib/examples.test.ts`:

```ts
import { describe, it, expect } from "vitest";
import { listExamples, loadExample } from "./examples";
import { toTemplate, toDataMap } from "../state/templateModel";

const entry = {
  title: "Invoice",
  template: { version: 1, config: { page: { format: "A4" } }, rows: [{ blocks: [{ type: "heading", id: "title", config: { level: 1 } }] }] },
  data: { title: { text: "Hi" } },
};

describe("listExamples", () => {
  it("lists titled entries with an index fallback", () => {
    expect(listExamples([entry, { template: { version: 1, config: {}, rows: [] }, data: {} }])).toEqual([
      { title: "Invoice", template: entry.template, data: entry.data },
      { title: "Example 2", template: { version: 1, config: {}, rows: [] }, data: {} },
    ]);
    expect(listExamples(undefined)).toEqual([]);
  });
});

describe("loadExample", () => {
  it("builds an editor model from template + data", () => {
    const model = loadExample(entry);
    expect(toDataMap(model)).toEqual({ title: { text: "Hi" } });
    const out = toTemplate(model);
    expect(out.rows[0].blocks[0]).toEqual({ type: "heading", id: "title", config: { level: 1 } });
  });
});
```

- [ ] **Step 2: Run, verify FAIL** — `npm run test -- examples` → FAIL.

- [ ] **Step 3: Implement `examples.ts`** — rewrite `resources/js/builder/lib/examples.ts`:

```ts
import type { DataMap, EditorModel, Json, Template } from "../types";
import { fromTemplate } from "../state/templateModel";

export interface ExampleEntry {
  title: string;
  template: Record<string, unknown>;
  data: Record<string, unknown>;
}

export function listExamples(examples: unknown): ExampleEntry[] {
  if (!Array.isArray(examples)) {
    return [];
  }
  return examples.map((raw, i) => {
    const entry = (raw ?? {}) as { title?: unknown; template?: unknown; data?: unknown };
    return {
      title: typeof entry.title === "string" ? entry.title : `Example ${i + 1}`,
      template: (entry.template ?? {}) as Record<string, unknown>,
      data: (entry.data ?? {}) as Record<string, unknown>,
    };
  });
}

export function loadExample(entry: { template: Record<string, unknown>; data?: Record<string, unknown> }): EditorModel {
  const template = {
    version: entry.template.version as number,
    config: (entry.template.config ?? {}) as Json,
    rows: (entry.template.rows ?? []) as Template["rows"],
  } as Template;
  return fromTemplate(template, (entry.data ?? {}) as DataMap);
}
```

- [ ] **Step 4: Run, verify PASS** — `npm run test -- examples` → PASS.

- [ ] **Step 5: Thread the `examples` prop** — minimal type-level changes:
  - `workbench/resources/js/Pages/Builder.tsx`: accept `examples` and pass it through (no new import needed — it's threaded as `unknown`):

```tsx
export default function Builder({ schema, examples }: { schema: JsonSchema; examples?: unknown }) {
    return (
        <TemplateBuilder
            schema={schema}
            examples={examples}
            initialTemplate={emptyTemplate}
            initialData={{}}
            renderTemplate={renderTemplate}
        />
    );
}
```

  - `resources/js/builder/TemplateBuilder.tsx`: add `examples?: unknown` to `Props`; pass it to `BlockPalette`; change the `onLoadExample` handler to take an entry (its type is inferred from `BlockPalette`'s prop):

```tsx
import { listExamples, loadExample } from "./lib/examples";
// in Props: examples?: unknown;
// in the BlockPalette usage:
            <BlockPalette
              schema={schema}
              examples={listExamples(examples)}
              onSelectPage={selectPage}
              onExport={handleExport}
              onLoadExample={(entry) => {
                setModel(() => loadExample(entry));
                setSelectedBlockUid(null);
                setPageSelected(false);
              }}
            />
```

  - `resources/js/builder/BlockPalette.tsx`: take `examples: ExampleEntry[]` as a prop instead of calling `listExamples(schema)`; render `ex.title` and call `onLoadExample(ex)`:

```tsx
import type { ExampleEntry } from "./lib/examples";
// Props: add `examples: ExampleEntry[];`, change `onLoadExample: (entry: ExampleEntry) => void;`
// remove `import { listExamples } from "./lib/examples";` and the `const examples = listExamples(schema);` line
// in the Examples list: onClick={() => onLoadExample(ex)}
```

- [ ] **Step 6: Pass the prop from the controller** — `workbench/app/Http/Controllers/TemplateBuilderController.php`:
  - `index()`: inject `ExampleRegistry` and pass it:

```php
    public function index(TemplateSchemaCompiler $compiler, BlockRegistry $registry, ExampleRegistry $examples): Response
    {
        return Inertia::render('Builder', [
            'schema' => $compiler->compile($registry),
            'examples' => $examples->all(),
        ]);
    }
```

  - `render()`: catch the new exception (add `use Bambamboole\PdfUaClient\Exceptions\DataValidationException;`):

```php
        try {
            $built = $factory->fromArray($template);
            $html = $renderer->render($built, $data, new RenderOptions(mode: 'preview', title: 'Preview'));
        } catch (TemplateValidationException|DataValidationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['html' => $html]);
```

  Add `use Bambamboole\PdfUaClient\Template\ExampleRegistry;`.

- [ ] **Step 7: Add a controller data-gate test** — append to `tests/Feature/Workbench/RenderEndpointTest.php`:

```php
it('returns 422 when posted data violates the template data contract', function (): void {
    $response = postJson('/render', [
        'template' => [
            'version' => 1, 'config' => ['page' => ['format' => 'A4']],
            'rows' => [['blocks' => [['type' => 'heading', 'id' => 'h', 'config' => ['level' => 1]]]]],
        ],
        'data' => [],
    ]);

    $response->assertStatus(422);
});
```

- [ ] **Step 8: Full verification** — `npm run typecheck` (0), `npm run test` (green), `npm run build` (ok), and `composer test` (the new 422 case passes). Grep to confirm no stragglers reference `schema.examples` or the old `loadExample(document)` shape: `grep -rn "listExamples(schema)\|loadExample(document" resources/js workbench/resources/js | grep -v node_modules` → no hits.

- [ ] **Step 9: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/js workbench/resources/js workbench/app/Http/Controllers/TemplateBuilderController.php tests/Feature/Workbench/RenderEndpointTest.php
git commit -m "feat: builder loads examples from a prop; controller enforces the data contract"
```

---

# PHASE 7 — Final gate

## Task 10: Full suite + manual builder pass

- [ ] **Step 1: Full gate**

```bash
composer test && composer analyse && composer lint -- --test && npm run typecheck && npm run test && npm run build
```

Expected: PHP green (DataSchemaCompiler + gate + command + reshaped examples + drift guard + validity incl. updated orphan rule), PHPStan clean, Pint clean, typecheck 0, Vitest green, build ok.

- [ ] **Step 2: Manual builder pass (controller).** `composer serve`, open the builder: it starts empty; the palette labels and add-block dummy data still come from the schema (`{type}Props` catalog); the **Examples** section lists "Invoice"; clicking it loads the structure into Edit + the content into Data + renders in Preview; editing the Data tab content re-renders; an empty/invalid data payload surfaces the 422 contract error in the preview pane. (Ignore the favicon 404.)

- [ ] **Step 3: Commit any fix-forward**

```bash
git add -A && git commit -m "fix: finalize two-schema data-contract verification"
```

---

## Gotchas

1. **Green-on-commit ordering is load-bearing.** Phase 2 migrates consumers to data while the source still merges; Phase 3 flips the source; Phase 4 forbids `props`. Don't reorder — flipping the source before migrating consumers, or forbidding `props` before removing them from tests, breaks the suite.
2. **`new Validator->validate(...)`** uses PHP 8.4 "new without parentheses". If the project's PHP is 8.3 in CI, write `(new Validator)->validate(...)`. Match the existing style in `TemplateFactory` (`new Validator;`).
3. **Schema #2 is standalone** — block `data` reflection runs without the ref-registry, so props are fully inlined; schema #2 has no `$defs`. Don't add one.
4. **Orphan-`$defs` test** must exempt `*Props` (the catalog is intentionally unreferenced within schema #1). This is the one place the catalog approach touches an existing invariant.
5. **Root `examples` keep `title`** — valid because the schema root permits additional properties; `SchemaExamplesTest` guards this.
6. **`block-N` CSS classes** come from a sequential counter, not the block `id`, so giving blocks ids during fixture/test migration never changes expected HTML.
7. **Two schema regenerations** happen (Task 5 and Task 6). Always regenerate via `UPDATE_SCHEMA=1 ./vendor/bin/pest tests/Feature/SchemaFileTest.php` and commit the updated `template.schema.json`.
8. **`composer lint`** runs Pint without `--test` (fixes in place); for the read-only gate in Task 10 use `vendor/bin/pint --test`. Per project rules, run `vendor/bin/pint --dirty --format agent` before each commit.
