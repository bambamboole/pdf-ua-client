import { describe, expect, it } from "vitest";
import { mmToScaledPx } from "./displayScale";

describe("mmToScaledPx", () => {
  it("converts A4 width at scale 1.32 to the expected css pixel width", () => {
    expect(mmToScaledPx(210, 1.32)).toBe(1048);
  });
});
