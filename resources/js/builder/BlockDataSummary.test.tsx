import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import BlockDataSummary from "./BlockDataSummary";
import type { EditorBlock } from "./types";

function block(type: EditorBlock["type"], data: EditorBlock["data"]): EditorBlock {
  return {
    uid: `${type}-uid`,
    id: type,
    type,
    config: {},
    data,
  };
}

describe("BlockDataSummary", () => {
  it("renders image data directly when a source is available", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={block("image", {
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
        block={{
          ...block("key-value", {
            seller: "PDF UA Kit GmbH",
            vatId: "DE123456789",
          }),
          config: {
            fields: [
              { key: "seller", label: "Seller" },
              { key: "vatId", label: "VAT ID" },
            ],
          },
        }}
      />,
    );

    expect(html).toContain("<table");
    expect(html).toContain("Seller");
    expect(html).toContain("PDF UA Kit GmbH");
  });

  it("renders configured table object rows", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={{
          ...block("table", [{ description: "Implementation", total: "3.800,00 €" }]),
          config: {
            columns: [
              { key: "description", label: "Description" },
              { key: "total", label: "Total" },
            ],
          },
        }}
      />,
    );

    expect(html).toContain("<thead");
    expect(html).toContain("Description");
    expect(html).toContain("Implementation");
  });

  it("renders every example row", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={{
          ...block("table", [
            { item: "One" },
            { item: "Two" },
            { item: "Three" },
            { item: "Four" },
          ]),
          config: {
            columns: [{ key: "item", label: "Item" }],
          },
        }}
      />,
    );

    expect(html).toContain("One");
    expect(html).toContain("Four");
    expect(html).not.toContain("more rows");
  });

  it("renders an empty summary when table columns are missing", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={block("table", {
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
