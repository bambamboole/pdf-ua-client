import { describe, it, expect } from "vitest";
import { pageSizeForFormat } from "./pageSizes";

describe("pageSizeForFormat", () => {
  it("returns parcel label dimensions", () => {
    expect(pageSizeForFormat("ParcelLabel4x6")).toEqual([101.6, 152.4]);
  });
});
