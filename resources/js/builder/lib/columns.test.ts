import { describe, it, expect } from "vitest";
import { parseWidths, formatWidths, setBoundary, gridTemplateForWidths } from "./columns";

describe("parseWidths", () => {
  it("returns equal integer percents when widths is null", () => {
    expect(parseWidths(null, 2)).toEqual([50, 50]);
    expect(parseWidths(null, 4)).toEqual([25, 25, 25, 25]);
  });
  it("parses percent strings", () => {
    expect(parseWidths(["30%", "70%"], 2)).toEqual([30, 70]);
  });
});

describe("formatWidths", () => {
  it("formats to percent strings", () => {
    expect(formatWidths([30, 70])).toEqual(["30%", "70%"]);
  });
});

describe("setBoundary", () => {
  it("transfers width between adjacent columns, clamped to >= 5", () => {
    expect(setBoundary([50, 50], 0, 70)).toEqual([70, 30]);
    expect(setBoundary([50, 50], 0, 2)).toEqual([5, 95]);
    expect(setBoundary([50, 50], 0, 99)).toEqual([95, 5]);
  });
  it("only affects the two adjacent columns", () => {
    expect(setBoundary([25, 25, 50], 1, 40)).toEqual([25, 40, 35]);
  });
});

describe("gridTemplateForWidths", () => {
  it("reserves fixed resizer tracks between weighted block columns", () => {
    expect(gridTemplateForWidths(["60%", "40%"], 2)).toBe(
      "minmax(0, 60fr) 0.375rem minmax(0, 40fr)",
    );
  });

  it("returns null when there is no fixed-width row", () => {
    expect(gridTemplateForWidths(null, 2)).toBeNull();
  });
});
