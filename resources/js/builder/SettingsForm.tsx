import Form from "@rjsf/core";
import { customizeValidator } from "@rjsf/validator-ajv8";
import Ajv2020 from "ajv/dist/2020";
import type { Json, JsonSchema } from "./types";
import { rjsfTemplates, rjsfWidgets } from "./rjsf/templates";
import { imageUploadUiSchema } from "./lib/imageUpload";

const validator = customizeValidator({ AjvClass: Ajv2020 });
const baseUiSchema = { "ui:submitButtonOptions": { norender: true } };

interface Props {
  schema: JsonSchema;
  formData: Json;
  onChange: (data: Json) => void;
}

export default function SettingsForm({ schema, formData, onChange }: Props) {
  const uiSchema = { ...baseUiSchema, ...imageUploadUiSchema(schema) };

  return (
    <Form
      schema={schema}
      validator={validator}
      uiSchema={uiSchema}
      templates={rjsfTemplates}
      widgets={rjsfWidgets}
      formData={formData}
      onChange={(event) => onChange((event.formData ?? {}) as Json)}
    />
  );
}
