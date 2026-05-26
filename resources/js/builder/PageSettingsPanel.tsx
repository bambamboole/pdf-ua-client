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
            onUpdateTemplateConfig(preserveCanvasPageConfig(config ?? {}, nextConfig))
          }
        />
      </Suspense>
    </div>
  );
}

export function pageSettingsSchema(schema: JsonSchema): JsonSchema {
  const next = structuredClone(schema) as JsonSchema;
  const defs = objectValue(next.$defs);
  const pageConfig = objectValue(defs?.pageConfig);
  const pageProperties = objectValue(pageConfig?.properties);

  if (pageProperties) {
    delete pageProperties.footer;
    delete pageProperties.pageNumbers;
  }

  return getTemplateConfigSchema(next);
}

export function preserveCanvasPageConfig(currentConfig: Json, nextConfig: Json): Json {
  const currentPage = objectValue(currentConfig.page);
  const currentFooter = currentPage?.footer;
  const currentPageNumbers = currentPage?.pageNumbers;

  if (currentFooter === undefined && currentPageNumbers === undefined) {
    return nextConfig;
  }

  const nextPage = objectOrEmpty(nextConfig.page);

  return {
    ...nextConfig,
    page: {
      ...nextPage,
      ...(currentFooter !== undefined ? { footer: currentFooter } : {}),
      ...(currentPageNumbers !== undefined ? { pageNumbers: currentPageNumbers } : {}),
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
