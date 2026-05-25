import { describe, it, expect } from "vitest";
import { listExamples, loadExample } from "./examples";
import { toTemplate, toDataMap } from "../state/templateModel";

const entry = {
  title: "Invoice",
  template: {
    version: 1,
    config: { page: { format: "A4" } },
    rows: [{ blocks: [{ type: "heading", id: "title", config: { level: 1 } }] }],
  },
  data: { title: { text: "Hi" } },
};

describe("listExamples", () => {
  it("lists titled entries with an index fallback", () => {
    expect(listExamples([entry, { template: { version: 1, config: {}, rows: [] }, data: {} }])).toEqual([
      { title: "Invoice", template: entry.template, data: entry.data },
      { title: "Example 2", template: { version: 1, config: {}, rows: [] }, data: {} },
    ]);
    expect(listExamples(undefined)).toEqual([]);
  });
});

describe("loadExample", () => {
  it("builds an editor model from template + data", () => {
    const model = loadExample(entry);
    expect(toDataMap(model)).toEqual({ title: { text: "Hi" } });
    const out = toTemplate(model);
    expect(out.rows[0].blocks[0]).toEqual({ type: "heading", id: "title", config: { level: 1 } });
  });
});
