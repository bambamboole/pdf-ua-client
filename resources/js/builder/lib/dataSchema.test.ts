import { describe, expect, it } from "vitest";
import { dataSchemaForTemplate } from "./dataSchema";
import type { JsonSchema, Template } from "../types";

const schema: JsonSchema = {
  $defs: {
    block: {
      oneOf: [{ $ref: "#/$defs/headingBlock" }, { $ref: "#/$defs/dividerBlock" }],
    },
    headingBlock: {
      properties: {
        type: { const: "heading" },
        config: { $ref: "#/$defs/headingConfig" },
      },
    },
    dividerBlock: {
      properties: {
        type: { const: "divider" },
        config: { $ref: "#/$defs/dividerConfig" },
      },
    },
    headingProps: {
      type: "object",
      required: ["text"],
      properties: {
        text: { type: "string" },
      },
    },
    dividerProps: {
      type: "object",
      properties: {},
    },
    headingConfig: {
      type: "object",
      properties: {},
    },
    dividerConfig: {
      type: "object",
      properties: {},
    },
  },
};

describe("dataSchemaForTemplate", () => {
  it("builds a data contract from the block ids used by the template", () => {
    const template: Template = {
      version: 1,
      config: {},
      rows: [
        {
          blocks: [
            { type: "heading", id: "hero-title" },
            { type: "divider", id: "divider" },
          ],
        },
      ],
    };

    expect(dataSchemaForTemplate(schema, template)).toEqual({
      $schema: "https://json-schema.org/draft/2020-12/schema",
      $id: "https://pdfuakit.com/schemas/pdf-ua-client-template-data-v1.json",
      type: "object",
      properties: {
        "hero-title": {
          type: "object",
          required: ["text"],
          properties: {
            text: { type: "string" },
          },
        },
      },
      required: ["hero-title"],
      additionalProperties: false,
    });
  });

  it("adds defaults and constants from template data layers", () => {
    const template: Template = {
      version: 1,
      config: {},
      rows: [{ blocks: [{ type: "heading", id: "hero-title" }] }],
      data: {
        defaults: { "hero-title": { text: "Fallback title" } },
        constants: { "hero-title": { text: "Locked title" } },
      },
    };

    const out = dataSchemaForTemplate(schema, template);

    expect(out.properties).toEqual({
      "hero-title": {
        type: "object",
        required: ["text"],
        properties: {
          text: { type: "string", default: "Fallback title", const: "Locked title" },
        },
      },
    });
    expect(out.required).toBeUndefined();
  });
});
