import { describe, expect, it } from "vitest";
import { pageSettingsSchema, preserveCanvasPageConfig } from "./PageSettingsPanel";
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
        format: { type: "string" },
        pageNumbers: { $ref: "#/$defs/pageNumbersConfig" },
        footer: { $ref: "#/$defs/pageFooterConfig" },
      },
    },
    pageNumbersConfig: {
      type: "object",
      properties: {
        enabled: { type: "boolean" },
        position: { type: "string", enum: ["left", "center", "right"] },
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
  it("removes footer and page numbers from the sidebar page settings schema", () => {
    const result = pageSettingsSchema(schema);
    const defs = result.$defs as Record<string, JsonSchema>;
    const page = defs.pageConfig as { properties: Record<string, unknown> };

    expect(page.properties).toHaveProperty("format");
    expect(page.properties).not.toHaveProperty("footer");
    expect(page.properties).not.toHaveProperty("pageNumbers");
  });
});

describe("preserveCanvasPageConfig", () => {
  it("keeps existing footer and page numbers when page settings change", () => {
    expect(
      preserveCanvasPageConfig(
        {
          page: {
            format: "A4",
            footer: {
              repeat: true,
              rows: [{ blocks: [{ type: "text", id: "footer" }] }],
            },
            pageNumbers: {
              enabled: true,
              position: "right",
            },
          },
        },
        {
          page: {
            format: "A5",
          },
        },
      ),
    ).toEqual({
      page: {
        format: "A5",
        footer: {
          repeat: true,
          rows: [{ blocks: [{ type: "text", id: "footer" }] }],
        },
        pageNumbers: {
          enabled: true,
          position: "right",
        },
      },
    });
  });
});
