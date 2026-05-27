import { lazy, Suspense, useEffect, useState } from "react";
import type { EditorBlock, Json, JsonSchema, TemplateDataLayers } from "./types";
import { blockDefinition } from "./blocks/registry";
import {
  getBlockConfigGroupSchema,
  getBlockSubschemas,
  getNestedBlockConfigSchema,
} from "./lib/schema";
import { useBuilderActions } from "./state/builderActions";
import BlockDataEditor from "./BlockDataEditor";

const SettingsForm = lazy(() => import("./SettingsForm"));
type InlineEditorTab = "data" | "config" | "typography" | "spacing";

interface Props {
  block: EditorBlock;
  schema: JsonSchema;
  data: TemplateDataLayers;
  detailsOpen: boolean;
}

export default function InlineBlockEditor({ block, schema, data, detailsOpen }: Props) {
  const { onUpdateBlockId, onUpdateBlockConfig, onUpdateDataField, onUpdateBlockData } =
    useBuilderActions();
  const blockDataSchema = getBlockSubschemas(schema, block.type).props;
  const dataProperties = blockDataSchema.properties;
  const hasDataProperties =
    typeof dataProperties === "object" &&
    dataProperties !== null &&
    !Array.isArray(dataProperties) &&
    Object.keys(dataProperties).length > 0;
  const configSchema = getBlockConfigGroupSchema(schema, block.type);
  const definition = blockDefinition(block.type);
  const hasGenericConfig = hasSchemaProperties(configSchema);
  const hasConfigTab = hasGenericConfig || Boolean(definition.ConfigFields);
  const typographySchema = getNestedBlockConfigSchema(schema, block.type, "typography");
  const spacingSchema = getNestedBlockConfigSchema(schema, block.type, "spacing");
  const [tab, setTab] = useState<InlineEditorTab>(() => (hasDataProperties ? "data" : "config"));

  useEffect(() => {
    if (detailsOpen) {
      setTab(hasDataProperties ? "data" : "config");
    }
  }, [block.uid, detailsOpen, hasDataProperties]);

  return (
    <div
      data-inline-block-editor
      onClick={(event) => event.stopPropagation()}
      onPointerDown={(event) => event.stopPropagation()}
      className="mt-3 border-t border-[var(--builder-stroke)] pt-3"
    >
      <div className="mb-3 flex flex-wrap gap-1">
        {hasDataProperties ? (
          <TabButton tab="data" activeTab={tab} onChange={setTab}>
            Data
          </TabButton>
        ) : null}
        {hasConfigTab ? (
          <TabButton tab="config" activeTab={tab} onChange={setTab}>
            Config
          </TabButton>
        ) : null}
        {typographySchema ? (
          <TabButton tab="typography" activeTab={tab} onChange={setTab}>
            Typography
          </TabButton>
        ) : null}
        {spacingSchema ? (
          <TabButton tab="spacing" activeTab={tab} onChange={setTab}>
            Spacing
          </TabButton>
        ) : null}
      </div>
      {tab === "data" && hasDataProperties ? (
        <BlockDataEditor
          block={block}
          schema={schema}
          data={data}
          onUpdateDataField={onUpdateDataField}
          onUpdateBlockData={onUpdateBlockData}
        />
      ) : tab === "config" ? (
        <div className="space-y-3">
          <BlockIdControl block={block} onUpdateBlockId={onUpdateBlockId} />
          {definition.ConfigFields ? (
            <definition.ConfigFields
              config={block.config ?? {}}
              onChange={(config) => onUpdateBlockConfig(block.uid, config)}
            />
          ) : null}
          <ConfigSettingsForm
            schema={configSchema}
            config={block.config ?? {}}
            onChange={(config) => onUpdateBlockConfig(block.uid, config)}
          />
        </div>
      ) : tab === "typography" && typographySchema ? (
        <NestedConfigSettingsForm
          schema={typographySchema}
          config={block.config ?? {}}
          property="typography"
          onChange={(config) => onUpdateBlockConfig(block.uid, config)}
        />
      ) : tab === "spacing" && spacingSchema ? (
        <NestedConfigSettingsForm
          schema={spacingSchema}
          config={block.config ?? {}}
          property="spacing"
          onChange={(config) => onUpdateBlockConfig(block.uid, config)}
        />
      ) : null}
    </div>
  );
}

function BlockIdControl({
  block,
  onUpdateBlockId,
}: {
  block: EditorBlock;
  onUpdateBlockId: (uid: string, id: string) => void;
}) {
  return (
    <div>
      <label className="mb-1 block text-xs font-medium text-[var(--builder-muted-strong)]">
        Block id
      </label>
      <input
        key={block.uid}
        className="block w-full rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-field)] px-2 py-1 font-mono text-sm text-[var(--builder-ink)] focus:border-[var(--builder-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--builder-accent-soft)]"
        defaultValue={block.id}
        onBlur={(event) => onUpdateBlockId(block.uid, event.target.value)}
      />
    </div>
  );
}

function TabButton({
  tab,
  activeTab,
  onChange,
  children,
}: {
  tab: InlineEditorTab;
  activeTab: InlineEditorTab;
  onChange: (tab: InlineEditorTab) => void;
  children: string;
}) {
  return (
    <button
      type="button"
      data-inline-editor-tab={tab}
      onClick={() => onChange(tab)}
      className={`rounded-full px-2.5 py-1 text-xs font-medium transition ${activeTab === tab ? "bg-[var(--builder-ink)] text-white" : "text-[var(--builder-muted)] hover:bg-[var(--builder-surface)] hover:text-[var(--builder-ink)]"}`}
    >
      {children}
    </button>
  );
}

function ConfigSettingsForm({
  schema,
  config,
  onChange,
}: {
  schema: JsonSchema;
  config: Json;
  onChange: (config: Json) => void;
}) {
  if (!hasSchemaProperties(schema)) {
    return null;
  }

  const keys = Object.keys((schema.properties ?? {}) as Record<string, unknown>);

  return (
    <Suspense
      fallback={
        <div className="h-24 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-surface)]" />
      }
    >
      <SettingsForm
        schema={schema}
        formData={pickProperties(config, keys)}
        onChange={(nextConfig) => onChange({ ...config, ...nextConfig })}
      />
    </Suspense>
  );
}

function NestedConfigSettingsForm({
  schema,
  config,
  property,
  onChange,
}: {
  schema: JsonSchema;
  config: Json;
  property: string;
  onChange: (config: Json) => void;
}) {
  return (
    <Suspense
      fallback={
        <div className="h-24 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-surface)]" />
      }
    >
      <SettingsForm
        schema={schema}
        formData={objectValue(config[property])}
        onChange={(nextConfig) => onChange({ ...config, [property]: nextConfig })}
      />
    </Suspense>
  );
}

function hasSchemaProperties(schema: JsonSchema): boolean {
  return Object.keys((schema.properties ?? {}) as Record<string, unknown>).length > 0;
}

function pickProperties(config: Json, keys: string[]): Json {
  return Object.fromEntries(
    keys.map((key) => [key, config[key]]).filter(([, value]) => value !== undefined),
  );
}

function objectValue(value: unknown): Json {
  return isRecord(value) ? value : {};
}

function isRecord(value: unknown): value is Json {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}
