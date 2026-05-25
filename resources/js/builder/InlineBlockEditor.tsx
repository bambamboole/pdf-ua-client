import { lazy, Suspense, useState } from "react";
import type { EditorBlock, Json, JsonSchema } from "./types";
import { getBlockConfigSchema, getBlockTitle } from "./lib/schema";

const SettingsForm = lazy(() => import("./SettingsForm"));

interface Props {
  block: EditorBlock;
  schema: JsonSchema;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
}

export default function InlineBlockEditor({
  block,
  schema,
  onUpdateBlockId,
  onUpdateBlockConfig,
}: Props) {
  const [tab, setTab] = useState<"settings" | "config">("settings");

  return (
    <div data-inline-block-editor className="mt-3 border-t border-[var(--builder-stroke)] pt-3">
      <div className="mb-3 flex gap-1">
        <button
          type="button"
          onClick={() => setTab("settings")}
          className={`rounded-full px-2.5 py-1 text-xs font-medium transition ${tab === "settings" ? "bg-[var(--builder-ink)] text-white" : "text-[var(--builder-muted)] hover:bg-[var(--builder-surface)] hover:text-[var(--builder-ink)]"}`}
        >
          Settings
        </button>
        <button
          type="button"
          onClick={() => setTab("config")}
          className={`rounded-full px-2.5 py-1 text-xs font-medium transition ${tab === "config" ? "bg-[var(--builder-ink)] text-white" : "text-[var(--builder-muted)] hover:bg-[var(--builder-surface)] hover:text-[var(--builder-ink)]"}`}
        >
          Config
        </button>
      </div>
      {tab === "settings" ? (
        <div className="space-y-3">
          <div>
            <label className="mb-1 block text-xs font-medium text-[var(--builder-muted-strong)]">
              Type
            </label>
            <input
              className="block w-full rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] px-2 py-1 text-sm text-[var(--builder-muted-strong)]"
              value={getBlockTitle(schema, block.type)}
              readOnly
            />
          </div>
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
