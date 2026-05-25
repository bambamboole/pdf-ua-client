import type { JsonSchema } from "../types";

function resolveRef(root: JsonSchema, node: Record<string, unknown>): Record<string, unknown> {
  const ref = node.$ref;
  if (typeof ref === "string" && ref.startsWith("#/$defs/")) {
    const defs = root.$defs as Record<string, Record<string, unknown>> | undefined;
    return defs?.[ref.slice("#/$defs/".length)] ?? node;
  }
  return node;
}

export function exampleFromSchema(schema: JsonSchema | undefined, root: JsonSchema): unknown {
  if (!schema) {
    return null;
  }
  const node = resolveRef(root, schema as Record<string, unknown>);

  const examples = node.examples;
  if (Array.isArray(examples) && examples.length > 0) {
    return structuredClone(examples[0]);
  }
  if ("default" in node) {
    return structuredClone(node.default);
  }

  const rawType = node.type;
  const type = Array.isArray(rawType) ? rawType.find((t) => t !== "null") : rawType;

  switch (type) {
    case "object": {
      const out: Record<string, unknown> = {};
      const props = (node.properties ?? {}) as Record<string, JsonSchema>;
      for (const key of Object.keys(props)) {
        out[key] = exampleFromSchema(props[key], root);
      }
      return out;
    }
    case "array":
      return node.items ? [exampleFromSchema(node.items as JsonSchema, root)] : [];
    case "string":
      return "";
    case "integer":
    case "number":
      return 0;
    case "boolean":
      return false;
    default:
      return null;
  }
}
