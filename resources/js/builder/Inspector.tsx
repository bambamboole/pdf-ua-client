import { useState, type ReactNode } from "react";
import Form from "@rjsf/core";
import { customizeValidator } from "@rjsf/validator-ajv8";
import Ajv2020 from "ajv/dist/2020";
import type { EditorBlock, Json, JsonSchema } from "./types";
import { getBlockConfigSchema, getTemplateConfigSchema, humanizeType } from "./lib/schema";
import { rjsfTemplates, rjsfWidgets } from "./rjsf/templates";

const validator = customizeValidator({ AjvClass: Ajv2020 });
const uiSchema = { "ui:submitButtonOptions": { norender: true } };

export type Selection =
  | { kind: "page"; config: Json }
  | { kind: "block"; block: EditorBlock }
  | null;

interface Props {
  schema: JsonSchema;
  selection: Selection;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
  onUpdateTemplateConfig: (config: Json) => void;
}

function Collapsible({
  title,
  children,
  defaultOpen = false,
}: {
  title: string;
  children: ReactNode;
  defaultOpen?: boolean;
}) {
  const [open, setOpen] = useState(defaultOpen);
  return (
    <div className="rounded border border-gray-200">
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="flex w-full items-center justify-between px-3 py-2 text-sm font-medium text-gray-700"
      >
        <span>{title}</span>
        <span className="text-gray-400">{open ? "▾" : "▸"}</span>
      </button>
      {open && <div className="border-t border-gray-100 p-3">{children}</div>}
    </div>
  );
}

export default function Inspector({
  schema,
  selection,
  onUpdateBlockId,
  onUpdateBlockConfig,
  onUpdateTemplateConfig,
}: Props) {
  if (!selection) {
    return <p className="text-sm text-gray-400">Select a block, or open Page settings.</p>;
  }
  if (selection.kind === "page") {
    return (
      <Form
        schema={getTemplateConfigSchema(schema)}
        validator={validator}
        uiSchema={uiSchema}
        templates={rjsfTemplates}
        widgets={rjsfWidgets}
        formData={selection.config ?? {}}
        onChange={(e) => onUpdateTemplateConfig((e.formData ?? {}) as Json)}
      />
    );
  }
  const { block } = selection;
  return (
    <div className="space-y-3">
      <div>
        <label className="mb-1 block text-xs font-medium text-gray-600">Block id</label>
        <input
          key={block.uid}
          className="block w-full rounded border border-gray-300 px-2 py-1 font-mono text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          defaultValue={block.id}
          onBlur={(e) => onUpdateBlockId(block.uid, e.target.value)}
        />
        <p className="mt-1 text-xs text-gray-400">
          {humanizeType(block.type)} — data key for injection
        </p>
      </div>
      <Collapsible title="Config">
        <Form
          key={`${block.uid}-config`}
          schema={getBlockConfigSchema(schema, block.type)}
          validator={validator}
          uiSchema={uiSchema}
          templates={rjsfTemplates}
          widgets={rjsfWidgets}
          formData={block.config ?? {}}
          onChange={(e) => onUpdateBlockConfig(block.uid, (e.formData ?? {}) as Json)}
        />
      </Collapsible>
    </div>
  );
}
