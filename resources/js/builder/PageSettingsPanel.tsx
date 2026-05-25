import { lazy, Suspense } from "react";
import type { Json, JsonSchema } from "./types";
import { getTemplateConfigSchema } from "./lib/schema";

const SettingsForm = lazy(() => import("./SettingsForm"));

interface Props {
  schema: JsonSchema;
  config: Json;
  onUpdateTemplateConfig: (config: Json) => void;
}

export default function PageSettingsPanel({ schema, config, onUpdateTemplateConfig }: Props) {
  return (
    <div className="border-t border-gray-200 pt-3">
      <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
        Page settings
      </h2>
      <Suspense fallback={<div className="h-28 animate-pulse rounded bg-gray-200" />}>
        <SettingsForm
          schema={getTemplateConfigSchema(schema)}
          formData={config ?? {}}
          onChange={onUpdateTemplateConfig}
        />
      </Suspense>
    </div>
  );
}
