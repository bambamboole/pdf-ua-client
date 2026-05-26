import { lazy, Suspense, useEffect, useState } from "react";
import type { EditorBlock, Json, JsonSchema, TemplateDataLayers } from "./types";
import { getBlockConfigSchema, getBlockSubschemas } from "./lib/schema";
import BlockDataEditor from "./BlockDataEditor";
import KeyValueConfigFields from "./KeyValueConfigFields";

const SettingsForm = lazy(() => import("./SettingsForm"));

interface Props {
  block: EditorBlock;
  schema: JsonSchema;
  data: TemplateDataLayers;
  detailsOpen: boolean;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
  onUpdateDataField: (
    blockId: string,
    field: string,
    value: unknown,
    options: { example: boolean; locked: boolean },
  ) => void;
}

export default function InlineBlockEditor({
  block,
  schema,
  data,
  detailsOpen,
  onUpdateBlockId,
  onUpdateBlockConfig,
  onUpdateDataField,
}: Props) {
  const blockDataSchema = getBlockSubschemas(schema, block.type).props;
  const dataProperties = blockDataSchema.properties;
  const hasDataProperties =
    typeof dataProperties === "object" &&
    dataProperties !== null &&
    !Array.isArray(dataProperties) &&
    Object.keys(dataProperties).length > 0;
  const [tab, setTab] = useState<"settings" | "data" | "config">("settings");

  useEffect(() => {
    if (detailsOpen && hasDataProperties) {
      setTab("data");
    }
  }, [block.uid, detailsOpen, hasDataProperties]);

  return (
    <div
      data-inline-block-editor
      onClick={(event) => event.stopPropagation()}
      onPointerDown={(event) => event.stopPropagation()}
      className="mt-3 border-t border-[var(--builder-stroke)] pt-3"
    >
      <div className="mb-3 flex gap-1">
        <button
          type="button"
          data-inline-editor-tab="settings"
          onClick={() => setTab("settings")}
          className={`rounded-full px-2.5 py-1 text-xs font-medium transition ${tab === "settings" ? "bg-[var(--builder-ink)] text-white" : "text-[var(--builder-muted)] hover:bg-[var(--builder-surface)] hover:text-[var(--builder-ink)]"}`}
        >
          Settings
        </button>
        {hasDataProperties ? (
          <button
            type="button"
            data-inline-editor-tab="data"
            onClick={() => setTab("data")}
            className={`rounded-full px-2.5 py-1 text-xs font-medium transition ${tab === "data" ? "bg-[var(--builder-ink)] text-white" : "text-[var(--builder-muted)] hover:bg-[var(--builder-surface)] hover:text-[var(--builder-ink)]"}`}
          >
            Data
          </button>
        ) : null}
        <button
          type="button"
          data-inline-editor-tab="config"
          onClick={() => setTab("config")}
          className={`rounded-full px-2.5 py-1 text-xs font-medium transition ${tab === "config" ? "bg-[var(--builder-ink)] text-white" : "text-[var(--builder-muted)] hover:bg-[var(--builder-surface)] hover:text-[var(--builder-ink)]"}`}
        >
          Config
        </button>
      </div>
      {tab === "settings" ? (
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
      ) : tab === "data" && hasDataProperties ? (
        <BlockDataEditor
          block={block}
          schema={schema}
          data={data}
          onUpdateDataField={onUpdateDataField}
        />
      ) : block.type === "key-value" ? (
        <div>
          <KeyValueConfigFields
            config={block.config ?? {}}
            onChange={(config) => onUpdateBlockConfig(block.uid, config)}
          />
          <Suspense
            fallback={
              <div className="h-24 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-surface)]" />
            }
          >
            <SettingsForm
              schema={withoutProperty(getBlockConfigSchema(schema, block.type), "fields")}
              formData={withoutProperty(block.config ?? {}, "fields")}
              onChange={(config) =>
                onUpdateBlockConfig(block.uid, { ...config, fields: block.config.fields })
              }
            />
          </Suspense>
        </div>
      ) : (
        <Suspense
          fallback={
            <div className="h-24 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-surface)]" />
          }
        >
          <SettingsForm
            schema={getBlockConfigSchema(schema, block.type)}
            formData={block.config ?? {}}
            onChange={(config) => onUpdateBlockConfig(block.uid, config)}
          />
        </Suspense>
      )}
    </div>
  );
}

function withoutProperty<T extends Record<string, unknown>>(value: T, property: string): T {
  const next = { ...value };
  delete next[property];
  return next;
}
