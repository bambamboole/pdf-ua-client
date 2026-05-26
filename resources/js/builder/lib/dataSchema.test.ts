import { describe, expect, it } from "vitest";
import { dataSchemaForTemplate } from "./dataSchema";
import type { JsonSchema, Template } from "../types";

const schema: JsonSchema = {
  $defs: {
    block: {
      oneOf: [
        { $ref: "#/$defs/headingBlock" },
        { $ref: "#/$defs/dividerBlock" },
        { $ref: "#/$defs/keyValueBlock" },
      ],
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
    keyValueBlock: {
      properties: {
        type: { const: "key-value" },
        config: { $ref: "#/$defs/keyValueConfig" },
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
    keyValueProps: {
      type: "object",
      properties: {
        entries: { type: "array" },
      },
    },
    headingConfig: {
      type: "object",
      properties: {},
    },
    dividerConfig: {
      type: "object",
      properties: {},
    },
    keyValueConfig: {
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

  it("adds defaults and omits constants from the runtime contract", () => {
    const template: Template = {
      version: 1,
      config: {},
      rows: [{ blocks: [{ type: "heading", id: "hero-title" }] }],
      data: {
        defaults: { "hero-title": { text: "Fallback title" } },
        constants: { "hero-title": { badge: "Locked title" } },
      },
    };

    const out = dataSchemaForTemplate(schema, template);

    expect(out.properties).toEqual({
      "hero-title": {
        type: "object",
        properties: {
          text: { type: ["string", "null"], default: "Fallback title" },
        },
      },
    });
    expect(out.required).toBeUndefined();
  });

  it("builds key-value data contracts from configured field keys", () => {
    const template: Template = {
      version: 1,
      config: {},
      rows: [
        {
          blocks: [
            {
              type: "key-value",
              id: "invoice-meta",
              config: {
                fields: [
                  { key: "invoiceNumber", label: "Invoice number" },
                  { key: "currency", label: "Currency" },
                  { key: "issueDate", label: "Issue date" },
                ],
              },
            },
          ],
        },
      ],
      data: {
        defaults: { "invoice-meta": { invoiceNumber: "RE-2026-001234" } },
        constants: { "invoice-meta": { currency: "EUR" } },
      },
    };

    const out = dataSchemaForTemplate(schema, template);
    const invoiceMeta = (out.properties as Record<string, any>)["invoice-meta"];

    expect(invoiceMeta.properties).toMatchObject({
      invoiceNumber: {
        type: ["string", "null"],
        default: "RE-2026-001234",
      },
      issueDate: {
        type: "string",
      },
    });
    expect(invoiceMeta.properties).not.toHaveProperty("entries");
    expect(invoiceMeta.properties).not.toHaveProperty("currency");
    expect(invoiceMeta.required).toEqual(["issueDate"]);
    expect(out.required).toEqual(["invoice-meta"]);
  });

  it("does not expose legacy key-value entries without configured fields", () => {
    const template: Template = {
      version: 1,
      config: {},
      rows: [{ blocks: [{ type: "key-value", id: "invoice-meta", config: {} }] }],
    };

    expect(dataSchemaForTemplate(schema, template)).toEqual({
      $schema: "https://json-schema.org/draft/2020-12/schema",
      $id: "https://pdfuakit.com/schemas/pdf-ua-client-template-data-v1.json",
      type: "object",
      properties: {},
      additionalProperties: false,
    });
  });
});
