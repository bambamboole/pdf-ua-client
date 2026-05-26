import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import KeyValueConfigFields from "./KeyValueConfigFields";

describe("KeyValueConfigFields", () => {
  it("renders configured fields with add and remove controls", () => {
    const html = renderToStaticMarkup(
      <KeyValueConfigFields
        config={{
          fields: [
            { key: "invoiceNumber", label: "Invoice number" },
            { key: "currency", label: "Currency" },
          ],
        }}
        onChange={() => undefined}
      />,
    );

    expect(html).toContain('value="invoiceNumber"');
    expect(html).toContain('value="Invoice number"');
    expect(html).toContain("Remove");
    expect(html).toContain("Add field");
  });
});
