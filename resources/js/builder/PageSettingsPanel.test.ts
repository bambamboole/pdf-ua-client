import { describe, expect, it } from "vitest";
import { pageSettingsSchema, preserveFooterRows } from "./PageSettingsPanel";
import type { JsonSchema } from "./types";

const schema: JsonSchema = {
  $defs: {
    templateConfig: {
      type: "object",
      properties: {
        page: { $ref: "#/$defs/pageConfig" },
      },
    },
    pageConfig: {
      type: "object",
      properties: {
        footer: { $ref: "#/$defs/pageFooterConfig" },
      },
    },
    pageFooterConfig: {
      type: "object",
      properties: {
        repeat: { type: "boolean" },
        rows: { type: "array" },
      },
    },
  },
};

describe("pageSettingsSchema", () => {
  it("removes footer rows from the sidebar page settings schema", () => {
    const result = pageSettingsSchema(schema);
    const defs = result.$defs as Record<string, JsonSchema>;
    const footer = defs.pageFooterConfig as { properties: Record<string, unknown> };

    expect(footer.properties).toHaveProperty("repeat");
    expect(footer.properties).not.toHaveProperty("rows");
  });
});

describe("preserveFooterRows", () => {
  it("keeps existing footer rows when page settings change", () => {
    expect(
      preserveFooterRows(
        {
          page: {
            format: "A4",
            footer: {
              repeat: true,
              rows: [{ blocks: [{ type: "text", id: "footer" }] }],
            },
          },
        },
        {
          page: {
            format: "A5",
            footer: {
              repeat: false,
            },
          },
        },
      ),
    ).toEqual({
      page: {
        format: "A5",
        footer: {
          repeat: false,
          rows: [{ blocks: [{ type: "text", id: "footer" }] }],
        },
      },
    });
  });
});
