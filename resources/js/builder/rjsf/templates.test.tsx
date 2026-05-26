import { renderToStaticMarkup } from "react-dom/server";
import type { FieldTemplateProps, WidgetProps } from "@rjsf/utils";
import { describe, expect, it } from "vitest";
import { DescriptionFieldTemplate, FieldTemplate, ImageUploadWidget } from "./templates";

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

  it("renders scalar descriptions as compact help text below the input", () => {
    const props = {
      id: "root_width",
      classNames: "",
      style: {},
      label: "Width",
      required: false,
      description: (
        <DescriptionFieldTemplate
          id="root_width__description"
          description="CSS width for this block."
          registry={{} as never}
          schema={{}}
          uiSchema={{}}
        />
      ),
      errors: undefined,
      help: undefined,
      hidden: false,
      schema: { type: "string" },
      registry: {},
      children: <input id="root_width" />,
    } as FieldTemplateProps;

    const html = renderToStaticMarkup(<FieldTemplate {...props} />);

    expect(html.indexOf('<input id="root_width"/>')).toBeLessThan(
      html.indexOf('id="root_width__description"'),
    );
    expect(html).toContain("text-[10px]");
    expect(html).toContain("CSS width for this block.");
  });
});

describe("ImageUploadWidget", () => {
  it("renders a url field and an image file picker", () => {
    const props = {
      id: "root_src",
      name: "src",
      schema: { type: "string" },
      uiSchema: {},
      value: "https://example.test/logo.png",
      required: true,
      disabled: false,
      readonly: false,
      autofocus: false,
      label: "Image source",
      hideLabel: false,
      multiple: false,
      options: { accept: "image/*,.svg" },
      onChange: () => undefined,
      onBlur: () => undefined,
      onFocus: () => undefined,
      registry: {} as never,
      formContext: {},
      rawErrors: [],
    } as WidgetProps;

    const html = renderToStaticMarkup(<ImageUploadWidget {...props} />);

    expect(html).toContain('type="url"');
    expect(html).toContain('type="file"');
    expect(html).toContain('accept="image/*,.svg"');
    expect(html).toContain("Upload image");
  });
});
