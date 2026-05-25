import type { JsonSchema } from "../types";

export function listBlockTypes(schema: JsonSchema): string[] {
  const defs = (schema as any)?.$defs ?? {};
  const variants: any[] = defs?.block?.oneOf ?? [];

  return variants
    .map((variant) => resolveRef(schema, variant?.$ref))
    .map((def) => extractDiscriminator(def))
    .filter((type): type is string => typeof type === "string");
}

export function humanizeType(type: string): string {
  return String(type)
    .split("-")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

export function getPageFormat(schema: JsonSchema): string {
  return (schema as any)?.$defs?.pageConfig?.properties?.format?.default ?? "A4";
}

function resolveRef(schema: JsonSchema, ref: unknown): Record<string, any> | null {
  if (typeof ref !== "string" || !ref.startsWith("#/$defs/")) {
    return null;
  }

  return (schema as any)?.$defs?.[ref.slice("#/$defs/".length)] ?? null;
}

function extractDiscriminator(def: Record<string, any> | null): string | null {
  const value = def?.properties?.type?.const;

  return typeof value === "string" ? value : null;
}

function findBlockDef(schema: JsonSchema, type: string): Record<string, any> | null {
  const variants: any[] = (schema as any)?.$defs?.block?.oneOf ?? [];

  for (const variant of variants) {
    const def = resolveRef(schema, variant?.$ref);
    if (def && extractDiscriminator(def) === type) {
      return def;
    }
  }

  return null;
}

export function getBlockSubschemas(
  schema: JsonSchema,
  type: string,
): { props: JsonSchema; config: JsonSchema } {
  const emptySchema = (): JsonSchema => ({
    type: "object",
    properties: {},
    $defs: (schema as any)?.$defs,
  });
  const blockDef = findBlockDef(schema, type);

  if (!blockDef) {
    return { props: emptySchema(), config: emptySchema() };
  }

  const props = resolveRef(schema, blockDef.properties?.props?.$ref);
  const config = resolveRef(schema, blockDef.properties?.config?.$ref);

  return {
    props: props ? { ...props, $defs: (schema as any).$defs } : emptySchema(),
    config: config ? { ...config, $defs: (schema as any).$defs } : emptySchema(),
  };
}

export function getBlockConfigSchema(schema: JsonSchema, type: string): JsonSchema {
  return getBlockSubschemas(schema, type).config;
}

export function getTemplateConfigSchema(schema: JsonSchema): JsonSchema {
  const templateConfig = (schema as any)?.$defs?.templateConfig ?? {
    type: "object",
    properties: {},
  };
  return { ...templateConfig, $defs: (schema as any)?.$defs };
}
