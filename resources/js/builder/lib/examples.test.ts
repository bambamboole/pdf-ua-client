import { describe, it, expect } from "vitest";
import { listExamples, loadExample } from "./examples";
import { toTemplate, toDataMap } from "../state/templateModel";
import type { JsonSchema } from "../types";

const doc = {
  title: "Invoice",
  version: 1,
  config: { page: { format: "A4" } },
  rows: [{ blocks: [{ type: "heading", id: "title", config: { level: 1 }, props: { text: "Hi" } }] }],
};

describe("listExamples", () => {
  it("lists titled examples, with an index fallback", () => {
    expect(
      listExamples({ examples: [doc, { version: 1, config: {}, rows: [] }] } as JsonSchema),
    ).toEqual([
      { title: "Invoice", document: doc },
      { title: "Example 2", document: { version: 1, config: {}, rows: [] } },
    ]);
    expect(listExamples({} as JsonSchema)).toEqual([]);
  });
});

describe("loadExample", () => {
  it("splits inline props into the data map and strips title/props", () => {
    const model = loadExample(doc as Record<string, unknown>);
    expect(toDataMap(model)).toEqual({ title: { text: "Hi" } });
    const out = toTemplate(model);
    expect(out.rows[0].blocks[0]).toEqual({ type: "heading", id: "title", config: { level: 1 } });
    expect(JSON.stringify(out)).not.toContain("Hi");
  });
});
