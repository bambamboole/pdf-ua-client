import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import PdfCanvas from "./PdfCanvas";

describe("PdfCanvas", () => {
  it("renders a PDF blob URL inside an object preview", () => {
    const html = renderToStaticMarkup(<PdfCanvas url="blob:http://localhost/preview" />);

    expect(html).toContain('title="PDF preview"');
    expect(html).toContain('data="blob:http://localhost/preview"');
    expect(html).toContain('type="application/pdf"');
  });
});
