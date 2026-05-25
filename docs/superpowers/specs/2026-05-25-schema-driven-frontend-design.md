# Schema-Driven Frontend (title / description / examples) — Design

- **Date:** 2026-05-25
- **Repo:** `pdf-ua-client` (standalone)
- **Status:** Approved, ready for implementation planning
- **Builds on:** the template-designer builder (template = structure + config; content = dummy data injected at render)

## Context

The builder is already mostly schema-driven (the block list comes from the compiled
schema's `block.oneOf`). Three things are still hardcoded in the frontend and should be
**derived from the schema** instead:

- block **labels** (`humanizeType` turns the type string into a label, ignoring any
  schema `title`),
- per-block **dummy data** (`blockData.ts` hardcodes example content per type),
- the **presets + full invoice example** (`presets.ts` hardcodes them).

JSON Schema already has the right vocabulary: **`title`** (label), **`description`**
(help/tooltip), and **`examples`** (an array of sample values — annotation-only, since
draft-06 through 2020-12; distinct from the single-value `default`). The package's
`PropsReflector` already emits `title`/`description` from PHP attributes but they're
applied to no classes; it has no `examples` support yet. This iteration drives the
frontend entirely from the schema and adds the missing annotations, with the full
example template(s) provided by a **swappable** PHP registry.

## Goals

- **Nothing block/example-related is hardcoded in the frontend** — labels, help text,
  per-block dummy data, and the example template(s) all come from the schema.
- Add `#[Example]` and apply `#[Title]`/`#[Description]` so the schema carries
  `title`/`description`/`examples`.
- A new block self-fills realistic dummy data (no typing) from its props schema.
- One curated **invoice** example lives in the schema's root `examples`, **swappable**
  by package consumers via a registry (default invoice shipped; consumers register/replace).
- Drop `blockData.ts` and `presets.ts`.

## Non-Goals

- No per-props-object `#[Example]` — **per-prop only** (a prop's example may be scalar
  or array-valued).
- No presets (block-group snippets) — removed in favour of self-filling blocks + the
  example template(s).
- No change to the data-separation model or the `/render` injection path.
- The Data tab stays read-only; config editing stays RJSF.

## PHP — schema annotations

### Attributes
- Apply existing `#[Title]` / `#[Description]` to:
  - **block classes** (`HeadingBlock`, … ) → emitted on the `{type}Block` def (palette
    label + tooltip),
  - **config classes / promoted config params** → RJSF field labels/help,
  - **props params** (the block constructors' promoted props).
- Add `#[Example(mixed $value)]` (target: parameter). `PropsReflector::reflectParam`
  gains an `examples` branch (next to the `default`/`title`/`description`/`min`/… ones)
  that emits `examples: [$value]`. The value may be scalar **or array** — e.g.
  `#[Example(['Item','Qty','Price'])] array $headers`, `#[Example([['…']])] array $rows`,
  and on `KeyValuePair`: `#[Example('Label')] string $label`, `#[Example('Value')] string $value`.

### What gets annotated (the convention)
Each of the 8 blocks: a `#[Title]` (+ optional `#[Description]`) on the block class, and
`#[Example]` on each content prop. `spacer`/`divider` have no props. `key-value` annotates
`KeyValuePair`'s `label`/`value`; `table` annotates `headers`/`rows` with array examples.
Config classes get `#[Title]`/`#[Description]` on the fields worth labelling (level, align,
typography, spacing, etc.) — incrementally; not every field is mandatory.

These are pure annotations: opis validation is unaffected, and the `composer schema`
drift-guard + `SchemaValidityTest` continue to pass (after regenerating the committed
`template.schema.json`).

## PHP — swappable example templates

A new **`ExampleRegistry`** (mirrors `BlockRegistry`), bound as a singleton in
`PdfUaClientServiceProvider`:

```php
final class ExampleRegistry
{
    /** @var list<array<string, mixed>> */
    private array $examples = [];

    /** @param array<string, mixed> $document a full, schema-valid template document */
    public function register(array $document): self { $this->examples[] = $document; return $this; }
    public function flush(): self { $this->examples = []; return $this; }
    /** @return list<array<string, mixed>> */
    public function all(): array { return $this->examples; }
}
```

- The package ships a **default invoice** example — a curated template document
  (structure + config + inline `props`) authored as a PHP fixture/provider and registered
  in the service provider's boot. Consumers swap it by resolving the registry and calling
  `->flush()->register([...])` (or just `->register([...])` to add).
- Each example document carries a **`title`** key (e.g. `'Invoice'`) for the menu label.
  This is valid: the template schema's **root permits additional properties** (no root
  `additionalProperties`/`unevaluatedProperties: false`), so a document with an extra
  `title` still validates. (Conditional: keep the `title` only while the root stays
  permissive — the schema-validity test guards this; if the root is ever tightened, drop
  the `title` key and fall back to an index label.)
- `TemplateSchemaCompiler::compile()` takes the `ExampleRegistry` (resolved via the
  container) and attaches `examples => $registry->all()` to the **root** schema (omit the
  key when empty). Standard JSON Schema root `examples` — an array of example instances.

The example document carries inline `props` (content) so the example renders realistically;
it remains a valid instance of the template schema (props are optional on blocks; the root
allows the extra `title`).

## Frontend — fully schema-driven

`resources/js/builder/lib/`:

- **Labels:** a `getBlockTitle(schema, type)` reads the `{type}Block` def's `title`,
  falling back to `humanizeType(type)`. `humanizeType` stays only as the fallback.
- **Help/tooltips:** RJSF already renders `title`→label and `description`→help, so once
  the schema carries them the config forms improve with no frontend change.
- **Dummy data — `exampleFromSchema(schema, root)`** (pure, recursive, replaces
  `blockData.ts`):
  - if `schema.examples?.length` → clone `examples[0]`;
  - else if `'default' in schema` → clone `default`;
  - else by `type`: `object` → `{ [prop]: exampleFromSchema(propSchema) }` for each
    property (resolving `$ref` against the root `$defs`); `array` → `[exampleFromSchema(items)]`
    if `items` present else `[]`; `string` → `''`; `integer`/`number` → `0`; `boolean` →
    `false`; else `null`.
  - A new block's data = `exampleFromSchema(getBlockSubschemas(schema, type).props, schema)`
    (this revives the previously-dead `props` subschema).
- **Examples menu — `loadExample(doc)`** (pure): given an example template document with
  inline `props`, split each block's `props` into a dummy-data map keyed by `id`, strip
  `props` (and the menu-only `title`) to leave `{ version, config, rows:[{type,id,config}] }`
  structure, then `fromTemplate(structure, dataMap)`. The palette's old "Presets" section is
  replaced by an **Examples** list built from `schema.examples` (resolved via an Inertia
  prop, same as `schema`); each item's label is `example.title` (fallback `Example {n}`).

### Removals / changes
- Delete `resources/js/builder/lib/blockData.ts` and `resources/js/builder/presets.ts`.
- `BlockPalette.tsx`: drop the hardcoded presets/`Load invoice`; render the schema-driven
  Examples list; use `getBlockTitle` for block labels.
- `TemplateBuilder.tsx`: wire add-block → `exampleFromSchema`-derived data; wire the
  Examples list → `loadExample`.
- The workbench `Pages/Builder.tsx` starts the builder from an **empty template**
  (`{ version: 1, config: {}, rows: [] }`, empty data) — blocks self-fill when added, and
  the curated invoice is one click away via the Examples menu. **`sampleTemplate.ts` and its
  `sampleData` are removed** (no hand-authored dummy data anywhere in the frontend).

## Testing

- **PHP:** the regenerated `template.schema.json` carries `title`/`description`/`examples`
  on the right defs and a root `examples` array; the example invoice is itself schema-valid
  (assert by validating `schema.examples[0]` against the schema); `ExampleRegistry`
  register/flush/all + a swap test (register replaces); existing render fixtures + drift
  guard pass.
- **Vitest:** `exampleFromSchema` (scalar/array/object, `$ref` resolution, key-value via
  item examples, table via array examples, `examples`-over-`default` precedence);
  `getBlockTitle` (title vs fallback); `loadExample` (props→data split, ids preserved).
- **Browser:** adding any block fills realistic dummy data with no typing; the Examples
  menu loads the curated invoice; swapping the registry (a workbench test provider) changes
  the menu.

## Risks

- **Naming examples in the menu** — resolved by an extra `title` key on each example
  document (valid because the template root permits additional properties). The
  schema-validity test guards this; if the root is ever tightened to forbid extra
  properties, drop the `title` and fall back to an index label.
- **`exampleFromSchema` recursion** on `$ref` cycles — the template `$defs` are acyclic
  (config refs don't recurse into blocks), but guard with a visited-set or depth cap to be safe.
- **Octane safety** — `ExampleRegistry` is a mutable singleton like `BlockRegistry`;
  registration happens at boot, so it's process-stable (no per-request mutation).
