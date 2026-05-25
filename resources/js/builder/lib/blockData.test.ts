import { describe, it, expect } from "vitest";
import { defaultData } from "./blockData";

describe("defaultData", () => {
  it("returns prepared data per type", () => {
    expect(defaultData("heading")).toEqual({ text: "Heading" });
    expect(defaultData("table")).toEqual({
      headers: ["Column A", "Column B"],
      rows: [
        ["A1", "B1"],
        ["A2", "B2"],
      ],
    });
    expect(defaultData("key-value")).toEqual({ entries: [{ label: "Label", value: "Value" }] });
    expect(defaultData("divider")).toEqual({});
  });

  it("returns a fresh object each call (no shared mutation)", () => {
    const a = defaultData("table") as { rows: unknown[] };
    const b = defaultData("table") as { rows: unknown[] };
    expect(a).not.toBe(b);
    expect(a.rows).not.toBe(b.rows);
  });
});
