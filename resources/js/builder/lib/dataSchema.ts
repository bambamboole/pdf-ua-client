import type { Json, JsonSchema, Template } from "../types";
import { getBlockSubschemas } from "./schema";

const dataSchemaId = "https://pdfuakit.com/schemas/pdf-ua-client-template-data-v1.json";

function blockHasData(schema: JsonSchema): boolean {
  const properties = schema.properties;

  return (
    typeof properties === "object" &&
    properties !== null &&
    !Array.isArray(properties) &&
    Object.keys(properties).length > 0
  );
}

function standaloneSchema(schema: JsonSchema): JsonSchema {
  const { $defs: _defs, ...rest } = schema;

  return rest;
}

export function dataSchemaForTemplate(schema: JsonSchema, template: Template): JsonSchema {
  const properties: Record<string, JsonSchema> = {};
  const required: string[] = [];

  for (const row of dataRows(template)) {
    for (const block of row.blocks) {
      if (!block.id) {
        continue;
      }

      const blockSchema = getBlockSubschemas(schema, block.type).props;
      if (!blockHasData(blockSchema)) {
        continue;
      }

      const defaults = template.data?.defaults?.[block.id] ?? {};
      const constants = template.data?.constants?.[block.id] ?? {};
      properties[block.id] = withDataLayerAnnotations(
        standaloneSchema(blockSchema),
        defaults,
        constants,
      );

      if (
        Array.isArray(blockSchema.required) &&
        uncoveredRequired(blockSchema, defaults, constants).length > 0
      ) {
        required.push(block.id);
      }
    }
  }

  return {
    $schema: "https://json-schema.org/draft/2020-12/schema",
    $id: dataSchemaId,
    type: "object",
    properties,
    ...(required.length > 0 ? { required: [...new Set(required)] } : {}),
    additionalProperties: false,
  };
}

function dataRows(template: Template): Template["rows"] {
  const footerRows = ((template.config as { page?: { footer?: { rows?: Template["rows"] } } }).page
    ?.footer?.rows ?? []) as Template["rows"];

  return [...template.rows, ...footerRows];
}

function withDataLayerAnnotations(schema: JsonSchema, defaults: Json, constants: Json): JsonSchema {
  const properties = schema.properties;
  if (!isPlainObject(properties)) {
    return schema;
  }

  const nextProperties: Record<string, unknown> = { ...properties };
  for (const [key, value] of Object.entries(defaults)) {
    if (isPlainObject(nextProperties[key])) {
      nextProperties[key] = { ...nextProperties[key], default: value };
    }
  }
  for (const [key, value] of Object.entries(constants)) {
    if (isPlainObject(nextProperties[key])) {
      nextProperties[key] = { ...nextProperties[key], const: value };
    }
  }

  return { ...schema, properties: nextProperties };
}

function uncoveredRequired(schema: JsonSchema, defaults: Json, constants: Json): string[] {
  if (!Array.isArray(schema.required)) {
    return [];
  }

  return schema.required.filter(
    (field): field is string =>
      typeof field === "string" && !(field in defaults) && !(field in constants),
  );
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}
