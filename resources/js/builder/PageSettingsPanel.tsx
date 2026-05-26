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
  const settingsSchema = pageSettingsSchema(schema);

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
          schema={settingsSchema}
          formData={config ?? {}}
          onChange={(nextConfig) =>
            onUpdateTemplateConfig(preserveFooterRows(config ?? {}, nextConfig))
          }
        />
      </Suspense>
    </div>
  );
}

export function pageSettingsSchema(schema: JsonSchema): JsonSchema {
  const next = structuredClone(schema) as JsonSchema;
  const defs = objectValue(next.$defs);
  const pageFooterConfig = objectValue(defs?.pageFooterConfig);
  const footerProperties = objectValue(pageFooterConfig?.properties);

  if (footerProperties) {
    delete footerProperties.rows;
  }

  return getTemplateConfigSchema(next);
}

export function preserveFooterRows(currentConfig: Json, nextConfig: Json): Json {
  const currentPage = objectValue(currentConfig.page);
  const currentFooter = objectValue(currentPage?.footer);
  const currentRows = currentFooter?.rows;

  if (!Array.isArray(currentRows)) {
    return nextConfig;
  }

  const nextPage = objectOrEmpty(nextConfig.page);
  const nextFooter = { ...objectOrEmpty(nextPage.footer), rows: currentRows };

  return {
    ...nextConfig,
    page: {
      ...nextPage,
      footer: nextFooter,
    },
  };
}

function objectValue(value: unknown): Record<string, unknown> | null {
  return typeof value === "object" && value !== null && !Array.isArray(value)
    ? (value as Record<string, unknown>)
    : null;
}

function objectOrEmpty(value: unknown): Record<string, unknown> {
  return objectValue(value) ?? {};
}
