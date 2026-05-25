import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import PageCanvas from "./PageCanvas";

describe("PageCanvas", () => {
  it("sandboxes preview HTML rendered through srcDoc", () => {
    const html = renderToStaticMarkup(
      <PageCanvas
        format="A4"
        html={'<script>window.top.location = "https://example.com"</script>'}
      />,
    );

    expect(html).toContain('sandbox=""');
    expect(html).toContain('srcDoc="&lt;script&gt;');
  });
});
