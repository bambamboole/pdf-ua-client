import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import InlineBlockEditor from "./InlineBlockEditor";
import { getBlockConfigGroupSchema } from "./lib/schema";
import type { EditorBlock, JsonSchema, TemplateDataLayers } from "./types";

const schema: JsonSchema = {
  $defs: {
    blockConfig: {
      type: "object",
      properties: {
        typography: { $ref: "#/$defs/typographyConfig", title: "Typography" },
        spacing: { $ref: "#/$defs/spacingConfig", title: "Spacing" },
        width: { type: ["string", "null"], title: "Width" },
        align: { type: ["string", "null"], title: "Alignment" },
      },
    },
    typographyConfig: {
      type: "object",
      properties: {
        family: { type: ["string", "null"], title: "Font family" },
      },
    },
    spacingConfig: {
      type: "object",
      properties: {
        top: { type: ["integer", "null"], title: "Top" },
      },
    },
    tableProps: {
      type: "object",
      properties: {
        rows: { type: "array" },
      },
    },
    tableConfig: {
      allOf: [{ $ref: "#/$defs/blockConfig" }],
      properties: {
        numberRows: { type: "boolean", title: "Number rows" },
        columns: { type: "array", title: "Columns" },
        style: { type: "string", title: "Style" },
      },
    },
    block: {
      oneOf: [{ $ref: "#/$defs/tableBlock" }],
    },
    tableBlock: {
      properties: {
        type: { const: "table" },
        config: { $ref: "#/$defs/tableConfig" },
      },
    },
  },
};

const data: TemplateDataLayers = {
  example: {},
  defaults: {},
  constants: {},
};

const tableBlock: EditorBlock = {
  uid: "table-uid",
  id: "lineItems",
  type: "table",
  config: {
    columns: [{ key: "description", label: "Description" }],
    numberRows: true,
    style: "striped",
    typography: { family: "Inter" },
    spacing: { top: 2 },
    width: "100%",
  },
};

describe("InlineBlockEditor", () => {
  it("orders data first and merges settings into config", () => {
    const html = renderToStaticMarkup(
      <InlineBlockEditor
        block={tableBlock}
        schema={schema}
        data={data}
        detailsOpen
        onUpdateBlockId={() => undefined}
        onUpdateBlockConfig={() => undefined}
        onUpdateDataField={() => undefined}
        onUpdateBlockData={() => undefined}
      />,
    );

    expect(html.indexOf("Data")).toBeLessThan(html.indexOf("Config"));
    expect(html).not.toContain("Settings");
    expect(html).toContain("Typography");
    expect(html).toContain("Spacing");
  });

  it("keeps table-managed fields out of the generic config schema", () => {
    const configSchema = getBlockConfigGroupSchema(schema, "table");

    expect(configSchema.properties).toEqual({
      width: { type: ["string", "null"], title: "Width" },
      align: { type: ["string", "null"], title: "Alignment" },
    });
  });
});
