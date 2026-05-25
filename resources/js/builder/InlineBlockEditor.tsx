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
  const [tab, setTab] = useState<"content" | "config">("content");

  return (
    <div data-inline-block-editor className="mt-3 max-w-[13rem] border-t border-gray-100 pt-3">
      <div className="mb-3 flex gap-1">
        <button
          type="button"
          onClick={() => setTab("content")}
          className={`rounded px-2 py-1 text-xs font-medium ${tab === "content" ? "bg-gray-800 text-white" : "text-gray-500 hover:bg-gray-100"}`}
        >
          Content
        </button>
        <button
          type="button"
          onClick={() => setTab("config")}
          className={`rounded px-2 py-1 text-xs font-medium ${tab === "config" ? "bg-gray-800 text-white" : "text-gray-500 hover:bg-gray-100"}`}
        >
          Config
        </button>
      </div>
      {tab === "content" ? (
        <div>
          <label className="mb-1 block text-xs font-medium text-gray-600">Block id</label>
          <input
            key={block.uid}
            className="block w-full rounded border border-gray-300 px-2 py-1 font-mono text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            defaultValue={block.id}
            onBlur={(event) => onUpdateBlockId(block.uid, event.target.value)}
          />
          <p className="mt-1 text-xs text-gray-400">
            {getBlockTitle(schema, block.type)} data key for injection
          </p>
        </div>
      ) : (
        <Suspense fallback={<div className="h-24 animate-pulse rounded bg-gray-100" />}>
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
