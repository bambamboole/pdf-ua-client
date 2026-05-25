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
    <div className="border-t border-[var(--builder-stroke)] pt-4">
      <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--builder-muted)]">
        Page settings
      </h2>
      <Suspense
        fallback={
          <div className="h-28 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-raised)]" />
        }
      >
        <SettingsForm
          schema={getTemplateConfigSchema(schema)}
          formData={config ?? {}}
          onChange={onUpdateTemplateConfig}
        />
      </Suspense>
    </div>
  );
}
