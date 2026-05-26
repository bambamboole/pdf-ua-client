import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import BlockDataSummary from "./BlockDataSummary";
import type { DataValue, EditorBlock, TemplateDataLayers } from "./types";

function block(type: string, config: Record<string, unknown> = {}): EditorBlock {
  return {
    uid: `${type}-uid`,
    id: type,
    type,
    config,
  };
}

function data(value: DataValue): TemplateDataLayers {
  return {
    example: { image: value, "key-value": value, table: value },
    defaults: {},
    constants: {},
  };
}

describe("BlockDataSummary", () => {
  it("renders image data directly when a source is available", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={block("image")}
        data={data({
          src: "https://example.test/logo.png",
          alt: "Company logo",
        })}
      />,
    );

    expect(html).toContain("<img");
    expect(html).toContain('src="https://example.test/logo.png"');
    expect(html).toContain('alt="Company logo"');
  });

  it("renders key-value data as a compact table preview", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={block("key-value", {
          fields: [
            { key: "seller", label: "Seller" },
            { key: "vatId", label: "VAT ID" },
          ],
        })}
        data={data({
          seller: "PDF UA Kit GmbH",
          vatId: "DE123456789",
        })}
      />,
    );

    expect(html).toContain("<table");
    expect(html).toContain("Seller");
    expect(html).toContain("PDF UA Kit GmbH");
  });

  it("renders configured table object rows", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={block("table", {
          columns: [
            { key: "description", label: "Description" },
            { key: "total", label: "Total" },
          ],
        })}
        data={data([{ description: "Implementation", total: "3.800,00 €" }])}
      />,
    );

    expect(html).toContain("<thead");
    expect(html).toContain("Description");
    expect(html).toContain("Implementation");
  });

  it("renders every example row", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={block("table", {
          columns: [{ key: "item", label: "Item" }],
        })}
        data={data([{ item: "One" }, { item: "Two" }, { item: "Three" }, { item: "Four" }])}
      />,
    );

    expect(html).toContain("One");
    expect(html).toContain("Four");
    expect(html).not.toContain("more rows");
  });

  it("renders an empty summary when table columns are missing", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={block("table")}
        data={data({
          headers: ["Description", "Total"],
          rows: [["Implementation", "3.800,00 €"]],
        })}
      />,
    );

    expect(html).toContain("0 cols × 0 rows");
    expect(html).not.toContain("Description");
    expect(html).not.toContain("Implementation");
  });
});
