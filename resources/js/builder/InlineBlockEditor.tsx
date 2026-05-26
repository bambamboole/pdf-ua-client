import { lazy, Suspense, useEffect, useState } from "react";
import type { EditorBlock, Json, JsonSchema, TemplateDataLayers } from "./types";
import { getBlockConfigSchema, getBlockSubschemas } from "./lib/schema";

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
        <DataFields
          blockId={block.id}
          schema={blockDataSchema}
          data={data}
          onUpdateDataField={onUpdateDataField}
        />
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

function DataFields({
  blockId,
  schema,
  data,
  onUpdateDataField,
}: {
  blockId: string;
  schema: JsonSchema;
  data: TemplateDataLayers;
  onUpdateDataField: (
    blockId: string,
    field: string,
    value: unknown,
    options: { example: boolean; locked: boolean },
  ) => void;
}) {
  const properties = schema.properties as Record<string, JsonSchema>;

  return (
    <div data-inline-data-fields className="space-y-3">
      {Object.entries(properties).map(([field, fieldSchema]) => {
        const example = Object.hasOwn(data.example[blockId] ?? {}, field);
        const locked = Object.hasOwn(data.constants[blockId] ?? {}, field);
        const value = currentFieldValue(data, blockId, field);

        return (
          <section
            key={field}
            className="rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-3"
          >
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
              <span className="font-mono text-xs font-medium text-[var(--builder-muted-strong)]">
                {field}
              </span>
              <span className="flex items-center gap-3 text-xs text-[var(--builder-muted-strong)]">
                <label className="inline-flex items-center gap-1.5">
                  <input
                    type="checkbox"
                    checked={example}
                    onChange={(event) =>
                      onUpdateDataField(blockId, field, value, {
                        example: event.currentTarget.checked,
                        locked,
                      })
                    }
                  />
                  Example
                </label>
                <label className="inline-flex items-center gap-1.5">
                  <input
                    type="checkbox"
                    checked={locked}
                    onChange={(event) =>
                      onUpdateDataField(blockId, field, value, {
                        example,
                        locked: event.currentTarget.checked,
                      })
                    }
                  />
                  Lock
                </label>
              </span>
            </div>
            <Suspense
              fallback={
                <div className="h-16 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-panel)]" />
              }
            >
              <SettingsForm
                schema={{
                  type: "object",
                  properties: { [field]: fieldSchema },
                }}
                formData={{ [field]: value } as Json}
                onChange={(formData) =>
                  onUpdateDataField(blockId, field, formData[field], { example, locked })
                }
              />
            </Suspense>
          </section>
        );
      })}
    </div>
  );
}

function currentFieldValue(data: TemplateDataLayers, blockId: string, field: string): unknown {
  if (Object.hasOwn(data.constants[blockId] ?? {}, field)) {
    return data.constants[blockId][field];
  }

  if (Object.hasOwn(data.example[blockId] ?? {}, field)) {
    return data.example[blockId][field];
  }

  if (Object.hasOwn(data.defaults[blockId] ?? {}, field)) {
    return data.defaults[blockId][field];
  }

  return undefined;
}
