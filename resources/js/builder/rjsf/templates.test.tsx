import { renderToStaticMarkup } from "react-dom/server";
import type { FieldTemplateProps } from "@rjsf/utils";
import { describe, expect, it } from "vitest";
import { FieldTemplate } from "./templates";

describe("FieldTemplate", () => {
  it("does not render a separate label for object fields", () => {
    const props = {
      id: "root_typography",
      classNames: "",
      style: {},
      label: "Typography",
      required: false,
      description: undefined,
      errors: undefined,
      help: undefined,
      hidden: false,
      schema: { type: "object" },
      registry: {},
      children: (
        <fieldset>
          <legend>Typography</legend>
        </fieldset>
      ),
    } as FieldTemplateProps;

    const html = renderToStaticMarkup(<FieldTemplate {...props} />);

    expect(html).not.toContain("<label");
    expect(html).toContain("<legend>Typography</legend>");
  });
});
