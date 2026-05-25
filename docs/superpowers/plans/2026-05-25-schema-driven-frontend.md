# Schema-Driven Frontend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the builder frontend fully schema-driven — block labels, per-block dummy data, and the example template(s) all come from the compiled JSON Schema — by adding `title`/`description`/`examples` annotations (PHP attributes) and a swappable `ExampleRegistry`, then deleting the hardcoded `blockData.ts` and `presets.ts`.

**Architecture:** PHP attributes emit `title`/`description`/`examples` into the schema; a swappable `ExampleRegistry` (mirrors `BlockRegistry`) is injected into the compiler and attaches example template documents to the schema's root `examples` (default = a curated invoice). The frontend derives a new block's dummy data with `exampleFromSchema()` (props-schema `examples`→`default`→type-recursion), labels via `getBlockTitle()`, and loads examples from `schema.examples`.

**Tech Stack:** PHP 8.4 / Laravel (spatie package-tools, opis/json-schema, reflection-driven schema), TypeScript / React 19 / Vitest. Repo: standalone `pdf-ua-client`, branch `feat/schema-driven-frontend`.

**Spec:** `docs/superpowers/specs/2026-05-25-schema-driven-frontend-design.md`

**All commands run from the repo root `/Users/bambamboole/Projects/pdf-ua-client` (NOT the monorepo).** `composer test` / `composer analyse` / `composer lint`; `composer schema` regenerates `template.schema.json`; `npm run typecheck` / `npm run test` / `npm run build`.

**Note:** another agent may be committing CI to `main`; this work is on `feat/schema-driven-frontend`. Only touch the files named per task.

---

## File Structure

```
src/Attributes/Example.php                 # NEW — #[Example(mixed $value)]
src/Block/PropsReflector.php               # + examples branch in reflectParam
src/Template/ExampleRegistry.php           # NEW — swappable example documents
src/Examples/InvoiceExample.php            # NEW — curated invoice document fixture
src/Template/TemplateSchemaCompiler.php    # ctor takes ExampleRegistry; attaches root examples
src/PdfUaClientServiceProvider.php         # bind ExampleRegistry (+ default invoice); wire into compiler
src/Blocks/*.php, src/Config/*.php         # apply #[Title]/#[Description]/#[Example]
template.schema.json                       # regenerated

resources/js/builder/lib/exampleFromSchema.ts   # NEW — derive a value from a schema
resources/js/builder/lib/examples.ts            # NEW — listExamples + loadExample
resources/js/builder/lib/schema.ts              # + getBlockTitle
resources/js/builder/state/templateModel.ts     # addBlock takes data; drop insertRows/replaceModel
resources/js/builder/BlockPalette.tsx           # schema labels + Examples list (no presets)
resources/js/builder/TemplateBuilder.tsx        # wire exampleFromSchema + loadExample
workbench/resources/js/Pages/Builder.tsx        # empty initial template; no sampleTemplate
DELETE: resources/js/builder/lib/blockData.ts, resources/js/builder/presets.ts,
        workbench/resources/js/sampleTemplate.ts
```

---

# PHASE 1 — PHP: schema annotations + swappable examples

## Task 1: `#[Example]` attribute + reflector support (TDD)

**Files:** Create `src/Attributes/Example.php`; modify `src/Block/PropsReflector.php`; test `tests/Unit/Block/PropsReflectorTest.php` (append).

- [ ] **Step 1: Create the attribute** `src/Attributes/Example.php`:
```php
<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Example
{
    public function __construct(public readonly mixed $value) {}
}
```

- [ ] **Step 2: Write a failing test** — append to `tests/Unit/Block/PropsReflectorTest.php`:
```php
it('emits examples from the Example attribute', function (): void {
    $class = new class('x') {
        public function __construct(
            #[\Bambamboole\PdfUaClient\Attributes\Example('Sample heading')]
            public readonly string $text,
        ) {}
    };

    $schema = app(\Bambamboole\PdfUaClient\Block\PropsReflector::class)->reflect($class::class);

    expect($schema['properties']['text']['examples'])->toBe(['Sample heading']);
});
```

- [ ] **Step 3: Run, verify FAIL** — `composer test -- --filter=PropsReflectorTest` → FAIL (no `examples` key).

- [ ] **Step 4: Implement** — in `src/Block/PropsReflector.php`: add `use Bambamboole\PdfUaClient\Attributes\Example;` and, inside `reflectParam()`, after the `Format::class` loop (right before the `if ($param->isDefaultValueAvailable() ...` block), add:
```php
        foreach ($param->getAttributes(Example::class) as $attr) {
            $schema['examples'] = [$attr->newInstance()->value];
        }
```

- [ ] **Step 5: Run, verify PASS** — `composer test -- --filter=PropsReflectorTest` → PASS. Then `composer test` (full) green.

- [ ] **Step 6: Lint + commit**
```bash
composer lint
git add src/Attributes/Example.php src/Block/PropsReflector.php tests/Unit/Block/PropsReflectorTest.php
git commit -m "feat: add #[Example] attribute emitting schema examples"
```

---

## Task 2: `ExampleRegistry` + invoice fixture + compiler root `examples` (TDD)

**Files:** Create `src/Template/ExampleRegistry.php`, `src/Examples/InvoiceExample.php`; modify `src/Template/TemplateSchemaCompiler.php`, `src/PdfUaClientServiceProvider.php`; tests `tests/Unit/Template/ExampleRegistryTest.php`, `tests/Feature/SchemaExamplesTest.php`.

- [ ] **Step 1: Create `src/Template/ExampleRegistry.php`**:
```php
<?php

declare(strict_types=1);
namespace Bambamboole\PdfUaClient\Template;

final class ExampleRegistry
{
    /** @var list<array<string, mixed>> */
    private array $examples = [];

    /** @param array<string, mixed> $document */
    public function register(array $document): self
    {
        $this->examples[] = $document;

        return $this;
    }

    public function flush(): self
    {
        $this->examples = [];

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->examples;
    }
}
```

- [ ] **Step 2: Create the invoice fixture** `src/Examples/InvoiceExample.php`:
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
                    ['type' => 'heading', 'id' => 'company', 'config' => ['level' => 1, 'width' => '60%'], 'props' => ['text' => 'ACME GmbH']],
                    ['type' => 'key-value', 'id' => 'invoice-meta', 'config' => ['align' => 'right', 'width' => '40%'], 'props' => ['entries' => [
                        ['label' => 'Invoice', 'value' => '2026-001'],
                        ['label' => 'Date', 'value' => '2026-05-25'],
                        ['label' => 'Due', 'value' => '2026-06-08'],
                    ]]],
                ]],
                ['blocks' => [
                    ['type' => 'key-value', 'id' => 'from', 'config' => ['width' => '50%'], 'props' => ['entries' => [['label' => 'From', 'value' => 'ACME GmbH, Main St 1']]]],
                    ['type' => 'key-value', 'id' => 'to', 'config' => ['width' => '50%'], 'props' => ['entries' => [['label' => 'Bill to', 'value' => 'Beta Ltd, 2nd Ave']]]],
                ]],
                ['blocks' => [['type' => 'divider', 'id' => 'rule']]],
                ['blocks' => [['type' => 'table', 'id' => 'items', 'props' => [
                    'headers' => ['Description', 'Qty', 'Unit', 'Amount'],
                    'rows' => [['Consulting', '10', '€100', '€1000'], ['License', '1', '€250', '€250']],
                ]]]],
                ['blocks' => [['type' => 'key-value', 'id' => 'totals', 'config' => ['align' => 'right'], 'props' => ['entries' => [
                    ['label' => 'Subtotal', 'value' => '€1250'],
                    ['label' => 'Tax (19%)', 'value' => '€237.50'],
                    ['label' => 'Total', 'value' => '€1487.50'],
                ]]]]],
                ['blocks' => [['type' => 'text', 'id' => 'footer', 'props' => ['text' => 'Payment due within 14 days. Thank you for your business.']]]],
            ],
        ];
    }
}
```
(Empty `config`/`props` are OMITTED so each present object serializes as a JSON object, not `[]`.)

- [ ] **Step 3: Write failing tests.**
Create `tests/Unit/Template/ExampleRegistryTest.php`:
```php
<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Template\ExampleRegistry;

it('registers, lists, and flushes example documents', function (): void {
    $registry = new ExampleRegistry;
    $registry->register(['title' => 'A', 'version' => 1, 'config' => [], 'rows' => []]);
    expect($registry->all())->toHaveCount(1);
    $registry->flush()->register(['title' => 'B', 'version' => 1, 'config' => [], 'rows' => []]);
    expect($registry->all())->toHaveCount(1)
        ->and($registry->all()[0]['title'])->toBe('B');
});
```
Create `tests/Feature/SchemaExamplesTest.php`:
```php
<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Examples\InvoiceExample;
use Bambamboole\PdfUaClient\Template\TemplateFactory;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;

it('attaches the registered example documents to the compiled schema root', function (): void {
    $schema = app(TemplateSchemaCompiler::class)->compile(app(BlockRegistry::class));

    expect($schema['examples'])->toBeArray()
        ->and($schema['examples'][0]['title'])->toBe('Invoice');
});

it('the invoice example validates against the schema', function (): void {
    // strip the menu-only title + inline props->runtime data, then build (validates)
    $doc = InvoiceExample::document();
    $data = [];
    $rows = array_map(function (array $row): array {
        $row['blocks'] = array_map(function (array $b) use (&$data): array {
            if (isset($b['props'], $b['id'])) {
                $data[$b['id']] = $b['props'];
            }
            unset($b['props']);

            return $b;
        }, $row['blocks']);

        return $row;
    }, $doc['rows']);

    $built = app(TemplateFactory::class)->fromArray([
        'version' => $doc['version'],
        'config' => $doc['config'],
        'rows' => $rows,
    ]);

    expect($built)->not->toBeNull();
});
```

- [ ] **Step 4: Run, verify FAIL** — `composer test -- --filter='ExampleRegistryTest|SchemaExamplesTest'` → FAIL (registry missing / no `examples` on schema).

- [ ] **Step 5: Implement the compiler change** — `src/Template/TemplateSchemaCompiler.php`:
  - constructor: add the registry:
    ```php
    public function __construct(
        private readonly PropsReflector $reflector,
        private readonly ExampleRegistry $examples,
    ) {}
    ```
    (add `use Bambamboole\PdfUaClient\Template\ExampleRegistry;` — same namespace, so no import needed.)
  - In `compile()`, build the result array, then attach examples before returning. Replace the final `return [ ... ];` so that after constructing `$schema = [ ... ]` you do:
    ```php
        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            '$id' => 'https://pdfuakit.com/schemas/pdf-ua-client-template-v1.json',
            'type' => 'object',
            'required' => ['version', 'config', 'rows'],
            'properties' => [
                'version' => ['const' => 1, 'type' => 'integer'],
                'config' => $templateConfigRef,
                'rows' => ['type' => 'array', 'items' => ['$ref' => '#/$defs/row']],
            ],
            '$defs' => $allDefs === [] ? new stdClass : $allDefs,
        ];

        $examples = $this->examples->all();
        if ($examples !== []) {
            $schema['examples'] = $examples;
        }

        return $schema;
    ```

- [ ] **Step 6: Wire the binding** — `src/PdfUaClientServiceProvider.php`, in `packageRegistered()`:
  - add `use Bambamboole\PdfUaClient\Examples\InvoiceExample;` and `use Bambamboole\PdfUaClient\Template\ExampleRegistry;`
  - register the registry singleton with the default invoice:
    ```php
        $this->app->singleton(ExampleRegistry::class, function (): ExampleRegistry {
            return (new ExampleRegistry)->register(InvoiceExample::document());
        });
    ```
  - pass it into the compiler binding:
    ```php
        $this->app->singleton(TemplateSchemaCompiler::class, fn (Container $app): TemplateSchemaCompiler => new TemplateSchemaCompiler(
            $app->make(PropsReflector::class),
            $app->make(ExampleRegistry::class),
        ));
    ```

- [ ] **Step 7: Run, verify PASS** — `composer test -- --filter='ExampleRegistryTest|SchemaExamplesTest'` → PASS. Regenerate the committed schema (it now includes root `examples`): `composer schema`. Then `composer test` (the `SchemaFileTest` drift guard + `SchemaValidityTest` must pass with the regenerated file) and `composer analyse`.

- [ ] **Step 8: Lint + commit**
```bash
composer lint
git add src/Template/ExampleRegistry.php src/Examples/InvoiceExample.php src/Template/TemplateSchemaCompiler.php src/PdfUaClientServiceProvider.php template.schema.json tests/Unit/Template/ExampleRegistryTest.php tests/Feature/SchemaExamplesTest.php
git commit -m "feat: swappable ExampleRegistry attaches example docs to schema root"
```

---

## Task 3: Apply `#[Title]`/`#[Description]`/`#[Example]` across blocks & configs

**Files:** modify `src/Blocks/*.php`, `src/Blocks/KeyValuePair.php`, select `src/Config/*.php`; regenerate `template.schema.json`; test `tests/Feature/SchemaAnnotationsTest.php`.

- [ ] **Step 1: Write a failing test** `tests/Feature/SchemaAnnotationsTest.php`:
```php
<?php

declare(strict_types=1);

use Bambamboole\PdfUaClient\Block\BlockRegistry;
use Bambamboole\PdfUaClient\Template\TemplateSchemaCompiler;

it('emits block titles and prop examples into the schema', function (): void {
    $schema = app(TemplateSchemaCompiler::class)->compile(app(BlockRegistry::class));
    $defs = $schema['$defs'];

    // block title lands on the props def (via class metadata)
    expect($defs['headingProps']['title'])->toBe('Heading')
        ->and($defs['headingProps']['properties']['text']['examples'])->toBe(['Invoice 2026-001'])
        ->and($defs['tableProps']['properties']['headers']['examples'][0])->toBeArray()
        ->and($defs['keyValueProps']['properties']['entries']['items']['properties']['label']['examples'])->toBe(['Label']);
});
```

- [ ] **Step 2: Run, verify FAIL** — `composer test -- --filter=SchemaAnnotationsTest`.

- [ ] **Step 3: Apply the attributes.** Add `use Bambamboole\PdfUaClient\Attributes\{Title, Description, Example};` to each file as needed. Apply exactly:

  - `src/Blocks/HeadingBlock.php`: `#[Title('Heading')]` + `#[Description('A section heading (h1–h6).')]` on the class; `#[Example('Invoice 2026-001')]` on `$text`.
  - `src/Blocks/TextBlock.php`: `#[Title('Text')]` + `#[Description('A paragraph of text.')]` on the class; `#[Example('Thank you for your business.')]` on `$text`.
  - `src/Blocks/HtmlBlock.php`: `#[Title('HTML')]` + `#[Description('Raw HTML escape hatch.')]` on the class; `#[Example('<p>Custom HTML</p>')]` on `$html`.
  - `src/Blocks/ImageBlock.php`: `#[Title('Image')]` on the class; `#[Example('https://placehold.co/200x80')]` on `$src`; `#[Example('Logo')]` on `$alt`.
  - `src/Blocks/SpacerBlock.php`: `#[Title('Spacer')]` + `#[Description('Vertical spacing.')]` on the class.
  - `src/Blocks/DividerBlock.php`: `#[Title('Divider')]` + `#[Description('A horizontal rule.')]` on the class.
  - `src/Blocks/KeyValueBlock.php`: `#[Title('Key / Value')]` on the class; `#[Example([['label' => 'Label', 'value' => 'Value']])]` on `$entries` (keep the existing `#[ArrayOf(KeyValuePair::class)]`).
  - `src/Blocks/TableBlock.php`: `#[Title('Table')]` on the class; `#[Example(['Column A', 'Column B'])]` on `$headers`; `#[Example([['A1', 'B1'], ['A2', 'B2']])]` on `$rows`.
  - `src/Blocks/KeyValuePair.php`: `#[Example('Label')]` on `$label`; `#[Example('Value')]` on `$value`.
  - Config labels (a few high-value ones): `src/Config/HeadingConfig.php` `#[Title('Level')]` on `$level`; `src/Config/BlockConfig.php` `#[Title('Width')]` on `$width`, `#[Title('Alignment')]` on `$align`. (Keep it light — these are nice-to-have RJSF labels.)

  Note: block-class `#[Title]`/`#[Description]` are emitted onto the `{type}Props` def via the existing `applyClassMetadata` (this is what the frontend reads for the palette label). `#[Example]` on a prop emits `examples` on that prop. `KeyValuePair` is reflected inline as the array `items`, so its `#[Example]`s land on `entries.items.properties.{label,value}`.

- [ ] **Step 4: Regenerate + run** — `composer schema`, then `composer test -- --filter=SchemaAnnotationsTest` → PASS, then `composer test` (drift guard + validity green) and `composer analyse`.

- [ ] **Step 5: Lint + commit**
```bash
composer lint
git add src/Blocks src/Config template.schema.json tests/Feature/SchemaAnnotationsTest.php
git commit -m "feat: annotate blocks/props with title/description/examples"
```

---

# PHASE 2 — Frontend: consume the schema, drop hardcoding

## Task 4: `exampleFromSchema` (TDD)

**Files:** Create `resources/js/builder/lib/exampleFromSchema.ts`, `resources/js/builder/lib/exampleFromSchema.test.ts`.

- [ ] **Step 1: Write failing tests** `resources/js/builder/lib/exampleFromSchema.test.ts`:
```ts
import { describe, it, expect } from "vitest";
import { exampleFromSchema } from "./exampleFromSchema";
import type { JsonSchema } from "../types";

const root: JsonSchema = { $defs: {} };

describe("exampleFromSchema", () => {
  it("uses examples[0] when present", () => {
    expect(exampleFromSchema({ type: "string", examples: ["Hi"] }, root)).toBe("Hi");
  });
  it("falls back to default", () => {
    expect(exampleFromSchema({ type: "integer", default: 2 }, root)).toBe(2);
  });
  it("builds an object from property examples", () => {
    const s = { type: "object", properties: { text: { type: "string", examples: ["T"] } } };
    expect(exampleFromSchema(s, root)).toEqual({ text: "T" });
  });
  it("builds a one-item array from item examples (key-value style)", () => {
    const s = {
      type: "object",
      properties: {
        entries: {
          type: "array",
          items: { type: "object", properties: { label: { type: "string", examples: ["L"] }, value: { type: "string", examples: ["V"] } } },
        },
      },
    };
    expect(exampleFromSchema(s, root)).toEqual({ entries: [{ label: "L", value: "V" }] });
  });
  it("uses an array-valued example directly (table headers)", () => {
    const s = { type: "object", properties: { headers: { type: "array", examples: [["A", "B"]] } } };
    expect(exampleFromSchema(s, root)).toEqual({ headers: ["A", "B"] });
  });
  it("type-based fallbacks", () => {
    expect(exampleFromSchema({ type: "string" }, root)).toBe("");
    expect(exampleFromSchema({ type: ["integer", "null"] }, root)).toBe(0);
    expect(exampleFromSchema({ type: "array" }, root)).toEqual([]);
  });
});
```

- [ ] **Step 2: Run, verify FAIL** — `npm run test`.

- [ ] **Step 3: Implement** `resources/js/builder/lib/exampleFromSchema.ts`:
```ts
import type { Json, JsonSchema } from "../types";

function resolveRef(root: JsonSchema, node: Record<string, unknown>): Record<string, unknown> {
  const ref = node.$ref;
  if (typeof ref === "string" && ref.startsWith("#/$defs/")) {
    const defs = (root as { $defs?: Record<string, Record<string, unknown>> }).$defs;
    return defs?.[ref.slice("#/$defs/".length)] ?? node;
  }
  return node;
}

export function exampleFromSchema(schema: JsonSchema | undefined, root: JsonSchema): unknown {
  if (!schema) {
    return null;
  }
  const node = resolveRef(root, schema as Record<string, unknown>);

  const examples = node.examples;
  if (Array.isArray(examples) && examples.length > 0) {
    return structuredClone(examples[0]);
  }
  if ("default" in node) {
    return structuredClone(node.default);
  }

  const rawType = node.type;
  const type = Array.isArray(rawType) ? rawType.find((t) => t !== "null") : rawType;

  switch (type) {
    case "object": {
      const out: Json = {};
      const props = (node.properties ?? {}) as Record<string, JsonSchema>;
      for (const key of Object.keys(props)) {
        out[key] = exampleFromSchema(props[key], root);
      }
      return out;
    }
    case "array":
      return node.items ? [exampleFromSchema(node.items as JsonSchema, root)] : [];
    case "string":
      return "";
    case "integer":
    case "number":
      return 0;
    case "boolean":
      return false;
    default:
      return null;
  }
}
```

- [ ] **Step 4: Run, verify PASS** — `npm run test`; `npm run typecheck`.

- [ ] **Step 5: Commit**
```bash
git add resources/js/builder/lib/exampleFromSchema.ts resources/js/builder/lib/exampleFromSchema.test.ts
git commit -m "feat: derive a value from a schema (exampleFromSchema)"
```

---

## Task 5: `getBlockTitle` + `examples.ts` (listExamples / loadExample) (TDD)

**Files:** modify `resources/js/builder/lib/schema.ts`; create `resources/js/builder/lib/examples.ts`, `resources/js/builder/lib/examples.test.ts`; append to `resources/js/builder/lib/schema.test.ts`.

- [ ] **Step 1: Write failing tests.**
Append to `resources/js/builder/lib/schema.test.ts`:
```ts
import { getBlockTitle } from "./schema";

it("getBlockTitle reads the props def title, falling back to humanizeType", () => {
  const schema = {
    $defs: {
      block: { oneOf: [{ $ref: "#/$defs/keyValueBlock" }, { $ref: "#/$defs/tableBlock" }] },
      keyValueBlock: { properties: { type: { const: "key-value" }, props: { $ref: "#/$defs/keyValueProps" } } },
      keyValueProps: { type: "object", title: "Key / Value", properties: {} },
      tableBlock: { properties: { type: { const: "table" }, props: { $ref: "#/$defs/tableProps" } } },
      tableProps: { type: "object", properties: {} },
    },
  };
  expect(getBlockTitle(schema, "key-value")).toBe("Key / Value");
  expect(getBlockTitle(schema, "table")).toBe("Table"); // fallback humanizeType
});
```
Create `resources/js/builder/lib/examples.test.ts`:
```ts
import { describe, it, expect } from "vitest";
import { listExamples, loadExample } from "./examples";
import { toTemplate, toDataMap } from "../state/templateModel";

const doc = {
  title: "Invoice",
  version: 1,
  config: { page: { format: "A4" } },
  rows: [{ blocks: [{ type: "heading", id: "title", config: { level: 1 }, props: { text: "Hi" } }] }],
};

describe("listExamples", () => {
  it("lists titled examples, index fallback", () => {
    expect(listExamples({ examples: [doc, { version: 1, config: {}, rows: [] }] })).toEqual([
      { title: "Invoice", document: doc },
      { title: "Example 2", document: { version: 1, config: {}, rows: [] } },
    ]);
    expect(listExamples({})).toEqual([]);
  });
});

describe("loadExample", () => {
  it("splits inline props into the data map and strips title/props", () => {
    const model = loadExample(doc);
    expect(toDataMap(model)).toEqual({ title: { text: "Hi" } });
    const out = toTemplate(model);
    expect(out.rows[0].blocks[0]).toEqual({ type: "heading", id: "title", config: { level: 1 } });
    expect(JSON.stringify(out)).not.toContain("Hi");
  });
});
```

- [ ] **Step 2: Run, verify FAIL** — `npm run test`.

- [ ] **Step 3: Implement `getBlockTitle`** — append to `resources/js/builder/lib/schema.ts`:
```ts
export function getBlockTitle(schema: JsonSchema, type: string): string {
  const props = getBlockSubschemas(schema, type).props as { title?: unknown };
  return typeof props.title === "string" ? props.title : humanizeType(type);
}
```

- [ ] **Step 4: Implement `examples.ts`** `resources/js/builder/lib/examples.ts`:
```ts
import type { DataMap, EditorModel, JsonSchema, Template } from "../types";
import { fromTemplate } from "../state/templateModel";

export interface ExampleEntry {
  title: string;
  document: Record<string, unknown>;
}

export function listExamples(schema: JsonSchema): ExampleEntry[] {
  const examples = (schema as { examples?: unknown }).examples;
  if (!Array.isArray(examples)) {
    return [];
  }
  return examples.map((document, i) => ({
    title: typeof (document as { title?: unknown })?.title === "string"
      ? (document as { title: string }).title
      : `Example ${i + 1}`,
    document: document as Record<string, unknown>,
  }));
}

export function loadExample(document: Record<string, unknown>): EditorModel {
  const data: DataMap = {};
  const rowsIn = Array.isArray(document.rows) ? (document.rows as Array<Record<string, unknown>>) : [];
  const rows = rowsIn.map((row) => {
    const blocksIn = Array.isArray(row.blocks) ? (row.blocks as Array<Record<string, unknown>>) : [];
    const blocks = blocksIn.map((b) => {
      if (b.props && typeof b.id === "string") {
        data[b.id] = b.props as Record<string, unknown>;
      }
      const { props, ...rest } = b;
      void props;
      return rest;
    });
    return { ...row, blocks };
  });
  const template = { version: document.version, config: document.config ?? {}, rows } as Template;
  return fromTemplate(template, data);
}
```

- [ ] **Step 5: Run, verify PASS** — `npm run test`; `npm run typecheck`.

- [ ] **Step 6: Commit**
```bash
git add resources/js/builder/lib/schema.ts resources/js/builder/lib/schema.test.ts resources/js/builder/lib/examples.ts resources/js/builder/lib/examples.test.ts
git commit -m "feat: getBlockTitle + schema-driven example list/loader"
```

---

## Task 6: `addBlock` takes data; delete `blockData.ts` (TDD)

**Files:** modify `resources/js/builder/state/templateModel.ts`, `resources/js/builder/state/templateModel.test.ts`; delete `resources/js/builder/lib/blockData.ts`.

- [ ] **Step 1: Update the failing test** — in `resources/js/builder/state/templateModel.test.ts`, change the `addBlock` "default data" expectation to pass explicit data (the model no longer invents data). Replace the existing `addBlock` data test body with:
```ts
  it("adds a block with a unique id and the provided data", () => {
    const m = addBlock(fromTemplate(template, data), "table", { rowUid: null, data: { headers: ["X"], rows: [] } });
    const added = m.rows[m.rows.length - 1].blocks[0];
    expect(added.type).toBe("table");
    expect(added.id).toBe("table-1");
    expect(added.data).toEqual({ headers: ["X"], rows: [] });
  });
  it("defaults new-block data to an empty object", () => {
    const m = addBlock(fromTemplate(template, data), "text", { rowUid: null });
    expect(m.rows[m.rows.length - 1].blocks[0].data).toEqual({});
  });
```

- [ ] **Step 2: Run, verify FAIL** — `npm run test` (the old test expected `defaultData`-derived data).

- [ ] **Step 3: Implement** — in `resources/js/builder/state/templateModel.ts`:
  - remove `import { defaultData } from "../lib/blockData";`
  - change `addBlock`:
    ```ts
    export function addBlock(
      model: EditorModel,
      type: BlockType | string,
      opts: { rowUid?: string | null; index?: number | null; data?: Json } = {},
    ): EditorModel {
      const { rowUid = null, index = null, data = {} } = opts;
      const block: EditorBlock = {
        uid: uid(),
        id: uniqueBlockId(model, type),
        type: type as BlockType,
        config: {},
        data,
      };
      // ... rest unchanged (insert into row / new row) ...
    }
    ```
    (Ensure `Json` is in the `import type { ... }` line.)
  - delete the file: `git rm resources/js/builder/lib/blockData.ts`.

- [ ] **Step 4: Run, verify PASS** — `npm run test`; `npm run typecheck` (note: `TemplateBuilder.tsx`'s existing `addBlock` calls still compile — `data` defaults to `{}`; T7 wires the real data); `npm run build`.

- [ ] **Step 5: Commit**
```bash
git add resources/js/builder/state/templateModel.ts resources/js/builder/state/templateModel.test.ts resources/js/builder/lib/blockData.ts
git commit -m "refactor: addBlock takes data; drop hardcoded blockData"
```

---

## Task 7: Wire the schema-driven frontend; remove presets/sampleTemplate

**Files:** modify `resources/js/builder/BlockPalette.tsx`, `resources/js/builder/TemplateBuilder.tsx`, `workbench/resources/js/Pages/Builder.tsx`, `resources/js/builder/state/templateModel.ts` (+ test); delete `resources/js/builder/presets.ts`, `workbench/resources/js/sampleTemplate.ts`.

- [ ] **Step 1: Rewrite `BlockPalette.tsx`** — schema-driven labels + Examples list (no presets):
```tsx
import { useDraggable } from "@dnd-kit/core";
import type { JsonSchema } from "./types";
import { listBlockTypes, getBlockTitle } from "./lib/schema";
import { listExamples } from "./lib/examples";

function PaletteItem({ schema, type }: { schema: JsonSchema; type: string }) {
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `palette-${type}`,
    data: { source: "palette", type },
  });
  return (
    <button
      type="button"
      ref={setNodeRef}
      {...listeners}
      {...attributes}
      className={`w-full cursor-grab rounded border border-gray-200 bg-white px-3 py-2 text-left text-sm font-medium text-gray-800 hover:bg-gray-50 ${isDragging ? "opacity-50" : ""}`}
    >
      + {getBlockTitle(schema, type)}
    </button>
  );
}

interface Props {
  schema: JsonSchema;
  onSelectPage: () => void;
  onExport: () => void;
  onLoadExample: (document: Record<string, unknown>) => void;
}

export default function BlockPalette({ schema, onSelectPage, onExport, onLoadExample }: Props) {
  const examples = listExamples(schema);
  return (
    <div className="flex flex-col gap-3">
      <div>
        <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Blocks</h2>
        <div className="space-y-1">
          {listBlockTypes(schema).map((type) => (
            <PaletteItem key={type} schema={schema} type={type} />
          ))}
        </div>
      </div>
      {examples.length > 0 && (
        <div className="space-y-1 border-t border-gray-200 pt-3">
          <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Examples</h2>
          {examples.map((ex) => (
            <button
              key={ex.title}
              type="button"
              onClick={() => onLoadExample(ex.document)}
              className="w-full rounded border border-gray-200 bg-white px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
            >
              {ex.title}
            </button>
          ))}
        </div>
      )}
      <div className="space-y-2 border-t border-gray-200 pt-3">
        <button type="button" onClick={onSelectPage} className="w-full rounded border border-gray-200 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">⚙ Page settings</button>
        <button type="button" onClick={onExport} className="w-full rounded bg-gray-800 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700">Export JSON</button>
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Update `TemplateBuilder.tsx`** — read it and:
  - Imports: remove `import { invoiceExample } from "./presets";`; remove `insertRows` from the `./state/templateModel` import; remove `humanizeType` from the `./lib/schema` import and add `getBlockTitle, getBlockSubschemas`; add `import { exampleFromSchema } from "./lib/exampleFromSchema";` and `import { loadExample } from "./lib/examples";`.
  - Add a helper inside the component: `const blockData = (type: string): Json => exampleFromSchema(getBlockSubschemas(schema, type).props, schema) as Json;`
  - In `handleDragEnd`, the three palette-add `addBlock(...)` calls gain `data: blockData(a.type!)`:
    - `addBlock(m, a.type!, { rowUid: null, data: blockData(a.type!) })`
    - `addBlock(m, a.type!, { rowUid: o.rowUid, index: found ? found.blockIndex : null, data: blockData(a.type!) })`
    - `addBlock(m, a.type!, { rowUid: o.rowUid, data: blockData(a.type!) })`
  - Drag-overlay label (was `humanizeType(d.type)`) → `getBlockTitle(schema, d.type)` (fallback `"Block"` when `d.type` missing).
  - `<BlockPalette>` props: remove `onInsertPreset` and `onLoadInvoice`; add `onLoadExample={(document) => { setModel(() => loadExample(document)); setSelectedBlockUid(null); setPageSelected(false); }}`. Keep `schema`, `onSelectPage`, `onExport`.
  - Remove the old `onLoadInvoice` handler that used `invoiceExample`.

- [ ] **Step 3: Remove the now-dead model ops** — in `resources/js/builder/state/templateModel.ts`, delete `insertRows` and `replaceModel` (only presets used them; `loadExample` uses `fromTemplate`). Remove their tests from `templateModel.test.ts` (the `insertRows`/`replaceModel` describe blocks). If `replaceModel` is referenced nowhere, delete it too. Re-run `npm run test` to confirm nothing else referenced them.

- [ ] **Step 4: Empty initial template in the workbench** — rewrite `workbench/resources/js/Pages/Builder.tsx`:
```tsx
import TemplateBuilder from '@builder/TemplateBuilder';
import type { JsonSchema, Template } from '@builder/types';

function xsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function renderTemplate(template: unknown, data: unknown): Promise<string> {
    const response = await fetch('/render', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
        body: JSON.stringify({ template, data }),
    });
    if (!response.ok) {
        throw new Error(`Render request failed (${response.status})`);
    }
    return (await response.json() as { html: string }).html;
}

const emptyTemplate: Template = { version: 1, config: {}, rows: [] };

export default function Builder({ schema }: { schema: JsonSchema }) {
    return (
        <TemplateBuilder
            schema={schema}
            initialTemplate={emptyTemplate}
            initialData={{}}
            renderTemplate={renderTemplate}
        />
    );
}
```

- [ ] **Step 5: Delete the dead files**
```bash
git rm resources/js/builder/presets.ts workbench/resources/js/sampleTemplate.ts
```
Also delete `resources/js/builder/presets.test.ts` if it exists (`git rm` it).

- [ ] **Step 6: Verify** — `npm run typecheck` (0), `npm run test` (green; presets/blockData tests gone, examples/exampleFromSchema/getBlockTitle present), `npm run build` (ok). Grep to confirm no stragglers: `grep -rn "blockData\|presets\|invoiceExample\|insertRows\|sampleTemplate\|humanizeType" resources/js workbench/resources/js | grep -v node_modules` — only `humanizeType`'s definition + `getBlockTitle`'s fallback use should remain.

- [ ] **Step 7: Commit**
```bash
git add resources/js workbench/resources/js
git commit -m "feat: schema-driven palette/labels/examples; remove presets + sampleTemplate"
```

---

## Task 8: Full verification + browser pass

- [ ] **Step 1: Full gate**
```bash
composer test && composer analyse && npm run typecheck && npm run test && npm run build
```
Expected: PHP green (incl. new annotation/example/registry tests + drift guard + validity), PHPStan clean, typecheck 0, Vitest green, build ok.

- [ ] **Step 2: Confirm no hardcoded block/example data remains** — `ls resources/js/builder/lib/blockData.ts resources/js/builder/presets.ts workbench/resources/js/sampleTemplate.ts 2>&1` → all "No such file".

- [ ] **Step 3: Browser pass (controller).** Serve (`npm run build` + `php vendor/bin/testbench serve`) and verify: the builder starts empty; the palette block labels come from the schema (`getBlockTitle`); adding any block fills realistic dummy data from the schema (no typing); the **Examples** section lists "Invoice"; clicking it loads the curated invoice (structure+config in Edit, content in Data, rendered in Preview); export produces a content-free template. (Ignore the favicon 404.)

- [ ] **Step 4: Commit (if any fix-forward needed)**
```bash
git add -A
git commit -m "fix: finalize schema-driven frontend verification"
```

---

## Gotchas

1. **Empty config/props in the invoice fixture are OMITTED** so each present object serializes as a JSON object (not `[]`). Don't add `'config' => []`.
2. **Block title → props def:** the block class `#[Title]` lands on `{type}Props` via the existing `applyClassMetadata`; `getBlockTitle` reads it there (no compiler change for titles). The only compiler change is attaching root `examples`.
3. **`examples` is annotation-only** — opis validation is unaffected; the drift guard just needs the regenerated `template.schema.json` committed.
4. **Root `examples` with a `title` key is valid** because the schema root has no `additionalProperties`/`unevaluatedProperties: false`. The `SchemaValidityTest`/`SchemaExamplesTest` guard this.
5. **`addBlock` no longer invents data** — the orchestrator passes `exampleFromSchema(...)`; the model stays schema-agnostic and pure.
6. **`loadExample` strips inline `props` → data map and drops the menu-only `title`** so the editor model and export stay content-free.
7. **`structuredClone`** is available in the browser and the Vitest node env (Node 17+).
