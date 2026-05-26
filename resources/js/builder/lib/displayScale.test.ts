import { describe, expect, it } from "vitest";
import { estimatedPhysicalScale, mmToScaledPx } from "./displayScale";

describe("estimatedPhysicalScale", () => {
  it("estimates a larger physical scale for a 16 inch MacBook Pro retina panel", () => {
    expect(
      estimatedPhysicalScale({
        screenWidth: 1728,
        screenHeight: 1117,
        devicePixelRatio: 2,
      }),
    ).toBe(1.32);
  });

  it("falls back to a conservative retina estimate for unknown high-density displays", () => {
    expect(
      estimatedPhysicalScale({
        screenWidth: 1700,
        screenHeight: 1000,
        devicePixelRatio: 2,
      }),
    ).toBe(1.25);
  });
});

describe("mmToScaledPx", () => {
  it("converts A4 width at guessed physical scale to a larger css pixel width", () => {
    expect(mmToScaledPx(210, 1.32)).toBe(1048);
  });
});
