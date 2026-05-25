import type { JsonSchema, Template } from "../types";
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

  for (const row of template.rows) {
    for (const block of row.blocks) {
      if (!block.id) {
        continue;
      }

      const blockSchema = getBlockSubschemas(schema, block.type).props;
      if (!blockHasData(blockSchema)) {
        continue;
      }

      properties[block.id] = standaloneSchema(blockSchema);

      if (Array.isArray(blockSchema.required) && blockSchema.required.length > 0) {
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
