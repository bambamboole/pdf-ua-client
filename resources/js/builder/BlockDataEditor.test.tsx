import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import BlockDataEditor from "./BlockDataEditor";
import type { EditorBlock, JsonSchema, TemplateDataLayers } from "./types";

const schema: JsonSchema = {
  $defs: {
    imageProps: {
      type: "object",
      properties: {
        src: { type: "string", title: "Image source" },
        alt: { type: "string", title: "Alt text" },
      },
    },
    keyValueProps: {
      type: "object",
      properties: {
        values: { type: "array" },
      },
    },
  },
};

function data(example: Record<string, unknown>): TemplateDataLayers {
  return {
    example: { block: example },
    defaults: {},
    constants: {},
  };
}

function block(type: string, config: Record<string, unknown> = {}): EditorBlock {
  return {
    uid: `${type}-uid`,
    id: "block",
    type,
    config,
    data: {},
  };
}

describe("BlockDataEditor", () => {
  it("renders image data with preview, url input, and upload affordance", () => {
    const html = renderToStaticMarkup(
      <BlockDataEditor
        block={block("image")}
        schema={schema}
        data={data({ src: "https://example.test/logo.png", alt: "Logo" })}
        onUpdateDataField={() => undefined}
      />,
    );

    expect(html).toContain('src="https://example.test/logo.png"');
    expect(html).toContain('type="file"');
    expect(html).toContain("Upload");
    expect(html).toContain("Example");
    expect(html).toContain("Lock");
    expect(html).toContain("overflow-hidden");
    expect(html).toContain("truncate");
  });

  it("renders key value rows from config fields and flat data keys", () => {
    const html = renderToStaticMarkup(
      <BlockDataEditor
        block={block("key-value", {
          fields: [
            { key: "invoiceNumber", label: "Invoice number" },
            { key: "currency", label: "Currency" },
          ],
        })}
        schema={schema}
        data={data({ invoiceNumber: "RE-2026-001234", currency: "EUR" })}
        onUpdateDataField={() => undefined}
      />,
    );

    expect(html).toContain("Invoice number");
    expect(html).toContain("invoiceNumber");
    expect(html).toContain('value="RE-2026-001234"');
    expect(html).not.toContain("entries");
  });
});
