import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import TableConfigColumns from "./TableConfigColumns";

describe("TableConfigColumns", () => {
  it("renders table options and configured column controls", () => {
    const html = renderToStaticMarkup(
      <TableConfigColumns
        config={{
          numberRows: true,
          style: "striped",
          columns: [
            { key: "sku", label: "SKU", align: "center", width: "7%" },
            { key: "quantity", label: "Qty", align: "right", width: "12%" },
          ],
        }}
        onChange={() => undefined}
      />,
    );

    expect(html).toContain('value="sku"');
    expect(html).toContain('value="SKU"');
    expect(html).toContain('value="7%"');
    expect(html).toContain("Number rows");
    expect(html).toContain("Striped");
    expect(html).toContain("Minimal");
    expect(html).toContain("Remove");
    expect(html).toContain("Add column");
  });
});
