import { describe, it, expect } from "vitest";
import { getBlockSubschemas, getBlockTitle, getTemplateConfigSchema } from "./schema";
import type { JsonSchema } from "../types";

// Minimal schema shaped like the real compiled one (camelCase def names).
const schema = {
  $defs: {
    block: {
      oneOf: [{ $ref: "#/$defs/keyValueBlock" }],
    },
    blockBase: {
      type: "object",
      required: ["type"],
      properties: { type: { type: "string" }, id: { type: "string" } },
    },
    keyValueBlock: {
      allOf: [{ $ref: "#/$defs/blockBase" }],
      properties: {
        type: { const: "key-value", type: "string" },
        props: { $ref: "#/$defs/keyValueProps" },
        config: { $ref: "#/$defs/keyValueConfig" },
      },
      unevaluatedProperties: false,
    },
    keyValueProps: { type: "object", properties: { entries: { type: "array" } } },
    keyValueConfig: {
      type: "object",
      properties: { labelWidth: { type: "string", default: "30mm" } },
    },
    templateConfig: { type: "object", properties: { page: { $ref: "#/$defs/pageConfig" } } },
    pageConfig: { type: "object", properties: { format: { type: "string", default: "A4" } } },
  },
};

describe("getBlockSubschemas", () => {
  it("resolves the camelCase block def via oneOf and attaches $defs", () => {
    const { props, config } = getBlockSubschemas(schema, "key-value");
    expect((props.properties as any).entries).toBeTruthy();
    expect((config.properties as any).labelWidth.default).toBe("30mm");
    expect(props.$defs).toBe(schema.$defs);
    expect(config.$defs).toBe(schema.$defs);
  });

  it("returns empty object schemas for an unknown type", () => {
    const { props, config } = getBlockSubschemas(schema, "nope");
    expect(props).toEqual({ type: "object", properties: {}, $defs: schema.$defs });
    expect(config).toEqual({ type: "object", properties: {}, $defs: schema.$defs });
  });
});

describe("getTemplateConfigSchema", () => {
  it("returns templateConfig with $defs attached", () => {
    const result = getTemplateConfigSchema(schema);
    expect((result.properties as any).page).toBeTruthy();
    expect(result.$defs).toBe(schema.$defs);
  });
});

describe("getBlockTitle", () => {
  it("reads the props def title, falling back to humanizeType", () => {
    const titleSchema = {
      $defs: {
        block: { oneOf: [{ $ref: "#/$defs/keyValueBlock" }, { $ref: "#/$defs/tableBlock" }] },
        keyValueBlock: {
          properties: { type: { const: "key-value" }, props: { $ref: "#/$defs/keyValueProps" } },
        },
        keyValueProps: { type: "object", title: "Key / Value", properties: {} },
        tableBlock: {
          properties: { type: { const: "table" }, props: { $ref: "#/$defs/tableProps" } },
        },
        tableProps: { type: "object", properties: {} },
      },
    } as JsonSchema;
    expect(getBlockTitle(titleSchema, "key-value")).toBe("Key / Value");
    expect(getBlockTitle(titleSchema, "table")).toBe("Table");
  });
});
