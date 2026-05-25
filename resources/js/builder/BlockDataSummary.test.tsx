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
  it("renders key-value data as a compact table preview", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={block("key-value", {
          entries: [
            { label: "Seller", value: "PDF UA Kit GmbH" },
            { label: "VAT ID", value: "DE123456789" },
          ],
        })}
      />,
    );

    expect(html).toContain("<table");
    expect(html).toContain("Seller");
    expect(html).toContain("PDF UA Kit GmbH");
  });

  it("renders table data with headers and rows", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={block("table", {
          headers: ["Description", "Total"],
          rows: [["Implementation", "3.800,00 €"]],
        })}
      />,
    );

    expect(html).toContain("<thead");
    expect(html).toContain("Description");
    expect(html).toContain("Implementation");
  });

  it("limits long previews", () => {
    const html = renderToStaticMarkup(
      <BlockDataSummary
        block={block("table", {
          headers: ["Item"],
          rows: [["One"], ["Two"], ["Three"], ["Four"]],
        })}
      />,
    );

    expect(html).toContain("+1 more rows");
    expect(html).not.toContain("Four");
  });
});
