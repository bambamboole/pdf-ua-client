# Two-Schema Template + Data Contract — Design

- **Date:** 2026-05-25
- **Repo:** `pdf-ua-client` (standalone)
- **Status:** Approved, ready for implementation planning
- **Supersedes (in part):** the single-schema model from `2026-05-25-schema-driven-frontend-design.md` — that work's `#[Title]`/`#[Description]`/`#[Example]` annotations, `PropsReflector`, and `ExampleRegistry` stay; the inline-`props`-in-templates model and the schema-root example shape change here.

## Context

Today `TemplateSchemaCompiler::compile()` emits **one** schema (`template.schema.json`) describing a *full template document*:

```
{ version, config: TemplateConfig, rows: [ { blocks: [ { type, id, props, config } ], gap, columnWidths } ] }
```

Each block carries both `props` (content) and `config` (styling), and `stripRequired()` forces **everything optional** — including genuinely-required content (`heading.text`, `table.headers`/`rows`). That single schema does double duty: it drives the React builder (palette, per-block forms, page settings) **and** validates a submitted document in `TemplateFactory::fromArray()`.

The structure/data split already exists, but only operationally — not in the schema layer:

- The builder's `EditorBlock` separates `config` (styling) from `data` (content), with a `DataMap` keyed by block `id`; `toTemplate()` exports a content-free template and `toDataMap()` the content.
- `/render` receives `template` and `data` separately; `TemplateRenderer::mergeProps()` merges data into props by `id`; `BlockHydrator` validates each block's merged props **ad hoc, per block, at hydration time**.

**The gap:** there is no schema describing the data a *given* template needs. Because `props` are forced optional, the one schema can't act as a data contract — a consumer filling a template has nothing to validate against, and bad data only surfaces as late per-block hydration exceptions.

## Goals

- **Two schemas, cleanly separated:**
  - **Schema #1 — static authoring schema** (`template.schema.json`): the grammar for building a template = *structure + config only*. Used by the builder and by `TemplateFactory` to validate a submitted template.
  - **Schema #2 — per-template data contract**: a standalone, serializable JSON Schema derived from a built `Template`, describing the data payload (keyed by block `id`). Used to validate data up front and (in the workbench) to drive data entry. Shippable to consumers.
- **All content is data.** A template instance carries no content `props` — every content-bearing block's data comes from the data payload.
- **Schema #2 is the single data-validation gate.** Validate the whole payload up front; remove `BlockHydrator`'s per-block validation.
- Provide production surfaces for schema #2: a compiler, a public convenience method, and an Artisan export command. Builder/Inertia wiring stays in the workbench only.

## Non-Goals

- No change to `PropsReflector`'s reflection rules or the `#[Title]`/`#[Description]`/`#[Example]` annotations.
- No per-block or per-prop "static vs dynamic" content marking — the boundary is absolute (all content is data).
- No persistence of schema #2 inside the package (consumers persist it if they want); the package only *derives* it.
- No new data-entry *form* generation in this iteration — exposing schema #2 to the builder is in scope; building RJSF data forms from it is a follow-up.

## Locked Decisions

1. **Two schemas** — static authoring (#1) + per-template data contract (#2).
2. **All content is data** — templates are structure + config; no inline `props`.
3. **Approach A** — two compilers; schema #1 keeps the `{type}Props` defs as a *catalog* in `$defs`; a new `DataSchemaCompiler` derives schema #2.
4. **Schema #2 is the single data gate** — up-front validation in the render path; `BlockHydrator::validate()` removed.
5. **`dataSchemaFor()` lives on `DataSchemaCompiler`**; the examples reshape is in scope here.
6. **Inertia/builder code stays in `workbench/`** — production `src/` ships only the compiler, the public method, and the command.

## Architecture (Approach A)

Two compilers share the existing `PropsReflector` + `SchemaRegistry` (`$defs`) machinery.

### Schema #1 — `TemplateSchemaCompiler` (modified)

Structure + config only. Concrete changes to `compile()`:

- **Block defs drop `props`.** A `{type}Block` def becomes:

```jsonc
"headingBlock": {
  "allOf": [{ "$ref": "#/$defs/blockBase" }],
  "properties": {
    "type":   { "const": "heading", "type": "string" },
    "config": { "$ref": "#/$defs/headingConfig" }
  },
  "unevaluatedProperties": false   // a template carrying `props` is now REJECTED
}
```

- **The `{type}Props` defs stay in `$defs` as a catalog**, registered with their **real `required` preserved** (the current `stripRequired($propsSchema)` call before `$defs->ref($propsDefName, …)` is removed). Nothing in schema #1 `$ref`s the catalog; it exists for the builder and for schema #2 to reuse. `stripRequired`/`stripRequiredFromConfigs` continue to apply to **config** defs and `templateConfig` (config fields legitimately default).
- **Root `examples`** become *structure-only* documents (see Examples reshape) — still valid instances of schema #1.

The committed `template.schema.json` shrinks (block defs lose `props`); the `SchemaFileTest` drift guard requires regeneration (`composer schema` or `UPDATE_SCHEMA=1 ./vendor/bin/pest`).

### Schema #2 — `DataSchemaCompiler` (new)

`src/Template/DataSchemaCompiler.php`. Dependencies: `PropsReflector`, `BlockRegistry`, `TemplateFactory` (the last only for the array convenience).

```php
public function compile(Template $template): array;          // core
public function dataSchemaFor(Template|array $template): array; // array → TemplateFactory::fromArray() then compile()
```

`compile(Template)` walks `template->rows[]->blocks[]`. For each `BlockInstance`:

1. Resolve the block class via `BlockRegistry::resolve($instance->type)`.
2. Get its data schema: `PropsReflector::reflectBlock($class)['data']`. This is **fully inlined** (the `data` reflection runs with no `SchemaRegistry`, so nested types are inlined, not `$ref`'d) and keeps `required` + `additionalProperties:false`.
3. If the data schema has **no properties** (empty object — `spacer`/`divider`), skip the block (it takes no data).
4. Otherwise set `properties[$instance->id]` = that data schema; if the data schema has a non-empty `required`, add `$instance->id` to the root `required`.

Result — a standalone schema (no `$defs` needed, every property fully inlined):

```jsonc
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://pdfuakit.com/schemas/pdf-ua-client-template-data-v1.json",
  "type": "object",
  "properties": {
    "company": { "type":"object","properties":{"text":{"type":"string"}},"required":["text"],"additionalProperties":false },
    "items":   { "type":"object","properties":{"headers":{ "type":"array","items":{"type":"string"} },"rows":{ … }},"required":["headers","rows"],"additionalProperties":false }
    // …one entry per content-bearing block id; `rule` (divider) omitted
  },
  "required": ["company", "items", …],   // ids whose block has any required prop
  "additionalProperties": false           // rejects data for unknown / content-free ids
}
```

**Block-id constraint:** keys are block `id`s, so ids must be unique and stable. The builder enforces uniqueness (`uniqueBlockId`); `TemplateFactory` auto-assigns `r{row}b{block}` when an id is missing. Content-bearing blocks should carry explicit ids (the fixtures/examples migration ensures this). `compile()` assumes uniqueness (last-wins if violated).

## Data flow

```
DESIGN TIME (workbench builder)
  build structure+config ─► template doc { version, config, rows:[{type,id,config}] }   (validated by schema #1)
                          └► DataSchemaCompiler.compile(Template) ─► schema #2 (data contract)

FILL TIME (production)
  data payload {id:{…}} ─► validate vs schema #2 ─► TemplateRenderer.render(template, data) ─► HTML
```

## Render path changes

- **`TemplateRenderer`** gains a `DataSchemaCompiler` dependency. At the top of `render(Template, data, options)`: compile schema #2 for the template, normalize the payload with `SchemaAwareNormalizer`, validate with opis; on failure throw `DataValidationException`. This is the single data gate, always enforced.
- **`mergeProps()` is removed.** Templates carry no inline props, so each block's props **are** `data[$id] ?? []`. The renderer builds the resolved `BlockInstance` with `props: $data[$instance->id] ?? []`.
- **`BlockHydrator::validate()` is removed** — data is covered up front by schema #2, config structurally by schema #1 at factory time. `BlockHydrator` becomes pure instantiation and **no longer needs `PropsReflector`** (constructor reduces to `BlockRegistry`; update the binding).
- **`TemplateFactory::buildRows()`** stops reading `$blockData['props']`; `BlockInstance.props` defaults to `[]` from the factory and is only populated by the renderer.

### `DataValidationException`

`src/Exceptions/DataValidationException.php`, mirroring `TemplateValidationException` (carries the opis `ValidationError`). Thrown by `TemplateRenderer` when the payload fails schema #2.

## Production surfaces (`src/`)

- `DataSchemaCompiler::compile(Template): array` + `dataSchemaFor(Template|array): array`.
- `ExportDataSchemaCommand` — `php artisan pdf-ua-client:data-schema-export {template} {path?}` (mirrors `ExportSchemaCommand`'s `pdf-ua-client:schema-export {path?}`). Reads a template JSON file, builds it via `TemplateFactory`, compiles schema #2, writes pretty JSON to `{path?}` (default `storage_path('app/pdf-ua-client/template-data.schema.json')`). Registered via `hasCommand()` in the service provider.
- Service provider: bind `DataSchemaCompiler` as a singleton (`PropsReflector`, `BlockRegistry`, `TemplateFactory`); add `DataSchemaCompiler` to `TemplateRenderer`'s binding; drop `PropsReflector` from `BlockHydrator`'s binding; register the new command.

## Workbench surfaces (`workbench/`)

- `TemplateBuilderController::render()` — the renderer now throws `DataValidationException`; catch it and return `422` with the contract error (alongside the existing `TemplateValidationException` catch).
- `TemplateBuilderController::index()` — pass an `examples` Inertia prop (`ExampleRegistry::all()`, each `{title, template, data}`) in addition to `schema`.

## Examples reshape

Because schema #1 now **rejects** inline `props`, the invoice example and root `examples` must be reshaped (not optional — `SchemaExamplesTest` would break otherwise).

- **`InvoiceExample`** splits:
  - `document(): array` — structure + config only (no `props`), explicit ids retained, plus the menu-only `title`.
  - `data(): array` — the content map keyed by id: `company`, `invoice-meta`, `from`, `to`, `items`, `totals`, `footer` (the `rule` divider omitted).
- **`ExampleRegistry`** entries become `{ title, template, data }`:
  - `register(string $title, array $template, array $data = []): self`
  - `all(): list<array{title: string, template: array, data: array}>`
  - The service provider registers the default invoice: `register('Invoice', InvoiceExample::document(), InvoiceExample::data())`, where `document()` is the structure doc (it carries the menu `title` at its root, which schema #1 permits). The existing single-arg `register(array $document)` is replaced — `ExampleRegistryTest` updates to the new signature.
- **`TemplateSchemaCompiler`** attaches root `examples` = the structure documents (with their `title`), still valid schema-#1 instances (the root permits the extra `title`).
- **Frontend** (`workbench` + shared builder lib): `examples.ts` reads the `examples` Inertia prop instead of `schema.examples`; `loadExample({template, data})` → `fromTemplate(template, data)`; `listExamples(examples)` maps the prop. `BlockPalette`/`TemplateBuilder`/`Builder.tsx` pass the `examples` prop through.

## Frontend: schema #1 no longer carries `props` on block defs

`getBlockSubschemas(schema, type)` currently reads `blockDef.properties.props.$ref`. Since block defs drop `props`, resolve the props catalog by name instead: compute the props def name from the type (`{camelCase(type)}Props`, mirroring the PHP `camelCase`) and look it up in `$defs`. `config` still comes from `blockDef.properties.config.$ref`. This keeps `exampleFromSchema(getBlockSubschemas(schema, type).props, schema)` (new-block dummy data) and the config forms working unchanged.

## Migration & impact

- **`template.schema.json`** — regenerate (drift guard). Block defs shrink; `{type}Props` catalog now carries real `required`; root `examples` are structure-only.
- **Render fixtures** — 7 of 8 carry inline `props` and must move content into the fixture's `data` map keyed by block id (`runtime-data-merge.php` is already pure-data and is the canonical example): `full-catalog`, `positioning`, `heading-simple`, `invoice-realistic`, `typography-cascade`, `two-column-row`, `runtime-data-rich`. Give content-bearing blocks explicit ids. Rendered HTML is unchanged (same content), so expected `html` stays; `runtime-data-rich` previously exercised inline+runtime *merge* — with merge removed it collapses to data-only (update its `html` only if output actually changes).
- **`BlockHydratorTest`** — drop assertions about hydrator-level validation failures (validation moved to the schema #2 gate).
- **`SchemaExamplesTest`** — `examples[0].title === 'Invoice'` still holds; the "invoice validates" case simplifies (no `props` to strip); add: invoice `data()` validates against `DataSchemaCompiler::compile(builtInvoiceTemplate)`.
- **`ServiceProviderBindingsTest` / `ServiceProviderTest`** — assert the new bindings (`DataSchemaCompiler`, command registration, `BlockHydrator` without `PropsReflector`).

## Testing

**PHP (Pest):**

- `DataSchemaCompilerTest` (unit): keys properties by block id; required prop → id in root `required`; all-optional block → id present but not required; `divider`/`spacer` omitted from `properties` and `required`; `additionalProperties:false` at root; output is standalone (no `$defs`, every property inlined); `dataSchemaFor()` accepts both `Template` and `array`.
- Schema #1 **rejects** a block carrying `props` (`unevaluatedProperties:false`) — `TemplateFactory::fromArray` throws.
- `TemplateRenderer`/`RenderEndpointTest`: missing/invalid data throws `DataValidationException` (controller → 422); valid data renders unchanged.
- `ExportDataSchemaCommandTest`: writes a file that is a valid 2020-12 schema and validates the invoice `data()`.
- `SchemaValidityTest`: schema #2 for the invoice is itself a valid 2020-12 schema (reuse the meta-schema fixtures).
- `SchemaFileTest`, `RenderFixtureTest`, `SchemaExamplesTest`, `ExampleRegistryTest`, `BlockHydratorTest` updated per Migration.

**Vitest:**

- `examples.test.ts`: `loadExample({template, data})` builds the editor model; `listExamples(examplesProp)` maps title/index fallback.
- `schema.test.ts`: `getBlockSubschemas`/`getBlockTitle` resolve props from the `{type}Props` catalog by name now that block defs omit `props`.

**Full gate:** `composer test && composer analyse && npm run typecheck && npm run test && npm run build`.

## File layout

```
# Production (src/)
src/Template/DataSchemaCompiler.php          # NEW — compile(Template) + dataSchemaFor(Template|array)
src/Exceptions/DataValidationException.php   # NEW — opis ValidationError carrier
src/Console/ExportDataSchemaCommand.php      # NEW — pdf-ua-client:data-schema-export {template} {path?}
src/Template/TemplateSchemaCompiler.php      # block defs drop props; props catalog keeps required; root examples structure-only
src/Rendering/TemplateRenderer.php           # + DataSchemaCompiler dep; up-front schema #2 gate; drop mergeProps
src/Block/BlockHydrator.php                  # drop validate(); drop PropsReflector dep
src/Template/TemplateFactory.php             # stop reading props in buildRows
src/Examples/InvoiceExample.php              # document() structure-only + data()
src/Template/ExampleRegistry.php             # {title, template, data} entries
src/PdfUaClientServiceProvider.php           # bind DataSchemaCompiler + command; update renderer/hydrator bindings; reshaped example

template.schema.json                         # regenerated (shrunk)

# Workbench (workbench/)
workbench/app/Http/Controllers/TemplateBuilderController.php  # 422 on DataValidationException; pass examples prop
workbench/resources/js/Pages/Builder.tsx                      # thread examples prop

# Shared builder lib (resources/js/builder/)
resources/js/builder/lib/examples.ts (+ examples.test.ts)     # consume examples prop; loadExample({template,data})
resources/js/builder/lib/schema.ts   (+ schema.test.ts)       # getBlockSubschemas resolves {type}Props by name
resources/js/builder/BlockPalette.tsx, TemplateBuilder.tsx    # examples from prop, not schema.examples
```

## Risks

- **Fixture migration volume** — 7 fixtures change shape. Mechanical (props→data), HTML output unchanged; verify with the existing `RenderFixtureTest` (and `UPDATE_FIXTURES=1` only if any output genuinely shifts).
- **Frontend props-lookup change** — `getBlockSubschemas` must resolve the catalog by name; a wrong `camelCase` mirror would silently return empty props (dummy data → `{}`). Covered by `schema.test.ts`.
- **Duplicate/auto ids** — schema #2 keys on id; non-unique ids collide. Builder + factory guarantee uniqueness; document the assumption and last-wins behavior.
- **`additionalProperties:false` strictness** — data for a content-free or nonexistent id is rejected. Intended, but consumers migrating loose payloads must clean them up; surfaced clearly via `DataValidationException`.
