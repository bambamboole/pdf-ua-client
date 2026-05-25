import type { DataMap, EditorModel, Json, JsonSchema, Template } from "../types";
import { fromTemplate } from "../state/templateModel";

export interface ExampleEntry {
  title: string;
  document: Record<string, unknown>;
}

export function listExamples(schema: JsonSchema): ExampleEntry[] {
  const examples = (schema as { examples?: unknown }).examples;
  if (!Array.isArray(examples)) {
    return [];
  }
  return examples.map((document, i) => ({
    title:
      typeof (document as { title?: unknown })?.title === "string"
        ? (document as { title: string }).title
        : `Example ${i + 1}`,
    document: document as Record<string, unknown>,
  }));
}

export function loadExample(document: Record<string, unknown>): EditorModel {
  const data: DataMap = {};
  const rowsIn = Array.isArray(document.rows)
    ? (document.rows as Array<Record<string, unknown>>)
    : [];
  const rows = rowsIn.map((row) => {
    const blocksIn = Array.isArray(row.blocks)
      ? (row.blocks as Array<Record<string, unknown>>)
      : [];
    const blocks = blocksIn.map((b) => {
      if (b.props && typeof b.id === "string") {
        data[b.id] = b.props as Json;
      }
      const { props: _props, ...rest } = b;
      return rest;
    });
    return { ...row, blocks };
  });
  const template = {
    version: document.version as number,
    config: (document.config ?? {}) as Json,
    rows,
  } as unknown as Template;
  return fromTemplate(template, data);
}
