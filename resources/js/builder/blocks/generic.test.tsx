import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import { headingBlock, textBlock } from "./generic";
import type { BlockDefinition } from "./types";
import type { EditorBlock, TemplateDataLayers } from "../types";

const longText =
  "This is a deliberately long paragraph of text that runs well beyond forty characters so we can prove the builder preview shows it in full.";

function summaryHtml(definition: BlockDefinition, type: string, text: string): string {
  const Summary = definition.Summary;
  if (!Summary) {
    throw new Error(`${type} block has no Summary component`);
  }

  const block: EditorBlock = { uid: `${type}-uid`, id: type, type, config: {} };
  const data: TemplateDataLayers = { example: { [type]: { text } }, defaults: {}, constants: {} };

  return renderToStaticMarkup(<Summary block={block} data={data} />);
}

describe("block summaries", () => {
  it("shows the full text block preview without shortening or clipping", () => {
    const html = summaryHtml(textBlock, "text", longText);

    expect(html).toContain(longText);
    expect(html).not.toContain("truncate");
    expect(html).toContain("whitespace-pre-wrap");
  });

  it("keeps the heading preview on a single truncated line", () => {
    const html = summaryHtml(headingBlock, "heading", longText);

    expect(html).toContain("truncate");
    expect(html).not.toContain(longText);
  });
});
