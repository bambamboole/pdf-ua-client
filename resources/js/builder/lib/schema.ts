import type { JsonSchema } from "../types";
import { managedConfigKeys } from "../blocks/config";

type JsonRecord = Record<string, unknown>;

function record(value: unknown): JsonRecord | null {
  return typeof value === "object" && value !== null && !Array.isArray(value)
    ? (value as JsonRecord)
    : null;
}

function defs(schema: JsonSchema): Record<string, JsonRecord> {
  return (record(schema.$defs) ?? {}) as Record<string, JsonRecord>;
}

export function listBlockTypes(schema: JsonSchema): string[] {
  const blockDef = record(defs(schema).block);
  const variants = Array.isArray(blockDef?.oneOf) ? blockDef.oneOf : [];

  return variants
    .map((variant) => resolveRef(schema, record(variant)?.$ref))
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
  const pageConfig = record(defs(schema).pageConfig);
  const properties = record(pageConfig?.properties);
  const format = record(properties?.format);

  return typeof format?.default === "string" ? format.default : "A4";
}

function resolveRef(schema: JsonSchema, ref: unknown): JsonRecord | null {
  if (typeof ref !== "string" || !ref.startsWith("#/$defs/")) {
    return null;
  }

  return defs(schema)[ref.slice("#/$defs/".length)] ?? null;
}

function extractDiscriminator(def: JsonRecord | null): string | null {
  const properties = record(def?.properties);
  const type = record(properties?.type);
  const value = type?.const;

  return typeof value === "string" ? value : null;
}

function findBlockDef(schema: JsonSchema, type: string): JsonRecord | null {
  const blockDef = record(defs(schema).block);
  const variants = Array.isArray(blockDef?.oneOf) ? blockDef.oneOf : [];

  for (const variant of variants) {
    const def = resolveRef(schema, record(variant)?.$ref);
    if (def && extractDiscriminator(def) === type) {
      return def;
    }
  }

  return null;
}

function propsDefName(type: string): string {
  const [head = "", ...tail] = type.split(/[-_]/);
  const camel =
    head.charAt(0).toLowerCase() +
    head.slice(1) +
    tail.map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join("");

  return `${camel}Props`;
}

export function getBlockSubschemas(
  schema: JsonSchema,
  type: string,
): { props: JsonSchema; config: JsonSchema } {
  const emptySchema = (): JsonSchema => ({
    type: "object",
    properties: {},
    $defs: schema.$defs,
  });
  const blockDef = findBlockDef(schema, type);

  if (!blockDef) {
    return { props: emptySchema(), config: emptySchema() };
  }

  const props = defs(schema)[propsDefName(type)] ?? null;
  const properties = record(blockDef.properties);
  const configProperty = record(properties?.config);
  const config = resolveRef(schema, configProperty?.$ref);

  return {
    props: props ? { ...props, $defs: schema.$defs } : emptySchema(),
    config: config ? { ...config, $defs: schema.$defs } : emptySchema(),
  };
}

export function getBlockTitle(schema: JsonSchema, type: string): string {
  const props = getBlockSubschemas(schema, type).props as { title?: unknown };
  return typeof props.title === "string" ? props.title : humanizeType(type);
}

export function getBlockConfigSchema(schema: JsonSchema, type: string): JsonSchema {
  return getBlockSubschemas(schema, type).config;
}

export function getBlockConfigGroupSchema(schema: JsonSchema, type: string): JsonSchema {
  const omitted = new Set(["typography", "spacing", ...managedConfigKeys(type)]);

  return objectSchema(
    Object.fromEntries(
      Object.entries(configProperties(schema, type)).filter(([property]) => !omitted.has(property)),
    ),
    schema,
  );
}

export function getNestedBlockConfigSchema(
  schema: JsonSchema,
  type: string,
  property: string,
): JsonSchema | null {
  const configProperty = configProperties(schema, type)[property];
  const ref = record(configProperty)?.$ref;

  if (typeof ref !== "string") {
    return null;
  }

  const resolved = resolveRef(schema, ref);

  return resolved ? { ...resolved, $defs: schema.$defs } : null;
}

export function getTemplateConfigSchema(schema: JsonSchema): JsonSchema {
  const templateConfig = defs(schema).templateConfig ?? {
    type: "object",
    properties: {},
  };
  return { ...templateConfig, $defs: schema.$defs };
}

function configProperties(schema: JsonSchema, type: string): Record<string, unknown> {
  const configSchema = getBlockConfigSchema(schema, type);
  const properties = { ...propertiesFromAllOf(schema, configSchema) };
  Object.assign(properties, record(configSchema.properties) ?? {});

  return properties;
}

function propertiesFromAllOf(
  schema: JsonSchema,
  configSchema: JsonSchema,
): Record<string, unknown> {
  const allOf = Array.isArray(configSchema.allOf) ? configSchema.allOf : [];

  return allOf.reduce<Record<string, unknown>>((properties, item) => {
    const ref = record(item)?.$ref;
    if (typeof ref !== "string") {
      return properties;
    }

    const resolved = resolveRef(schema, ref);
    if (!resolved) {
      return properties;
    }

    return {
      ...properties,
      ...propertiesFromAllOf(schema, resolved),
      ...record(resolved.properties),
    };
  }, {});
}

function objectSchema(properties: Record<string, unknown>, schema: JsonSchema): JsonSchema {
  return {
    type: "object",
    properties,
    $defs: schema.$defs,
  };
}
