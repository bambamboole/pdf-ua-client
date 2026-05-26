import { describe, expect, it } from "vitest";
import { imageFileError, imageFileToDataUrl } from "./imageUpload";

describe("image upload helpers", () => {
  it("accepts image files at or below 200 KB", () => {
    expect(imageFileError({ size: 200 * 1024, type: "image/png", name: "logo.png" })).toBeNull();
  });

  it("rejects images larger than 200 KB", () => {
    expect(imageFileError({ size: 200 * 1024 + 1, type: "image/png", name: "logo.png" })).toBe(
      "Images must be 200 KB or smaller.",
    );
  });

  it("rejects non-image files", () => {
    expect(imageFileError({ size: 120, type: "application/pdf", name: "invoice.pdf" })).toBe(
      "Choose an image file.",
    );
  });

  it("allows svg files even when the browser omits the mime type", () => {
    expect(imageFileError({ size: 120, type: "", name: "logo.svg" })).toBeNull();
  });

  it("converts files to data urls", async () => {
    const file = new File(["logo"], "logo.svg", { type: "image/svg+xml" });

    await expect(imageFileToDataUrl(file)).resolves.toBe("data:image/svg+xml;base64,bG9nbw==");
  });
});
