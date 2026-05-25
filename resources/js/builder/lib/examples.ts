import type { DataMap, EditorModel, Json, Template } from "../types";
import { fromTemplate } from "../state/templateModel";

export interface ExampleEntry {
  title: string;
  template: Record<string, unknown>;
  data: Record<string, unknown>;
}

export function listExamples(examples: unknown): ExampleEntry[] {
  if (!Array.isArray(examples)) {
    return [];
  }

  return examples.map((raw, index) => {
    const entry = (raw ?? {}) as { title?: unknown; template?: unknown; data?: unknown };

    return {
      title: typeof entry.title === "string" ? entry.title : `Example ${index + 1}`,
      template: (entry.template ?? {}) as Record<string, unknown>,
      data: (entry.data ?? {}) as Record<string, unknown>,
    };
  });
}

export function loadExample(entry: {
  template: Record<string, unknown>;
  data?: Record<string, unknown>;
}): EditorModel {
  const template = {
    version: entry.template.version as number,
    config: (entry.template.config ?? {}) as Json,
    rows: (entry.template.rows ?? []) as Template["rows"],
  } as Template;

  return fromTemplate(template, (entry.data ?? {}) as DataMap);
}
