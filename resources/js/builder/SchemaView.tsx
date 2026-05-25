import type { JsonSchema, Template } from "./types";

interface JsonPanelProps {
  title: string;
  value: unknown;
  actions?: React.ReactNode;
}

function JsonPanel({ title, value, actions }: JsonPanelProps) {
  const json = JSON.stringify(value, null, 2);

  return (
    <section className="min-w-0 rounded border border-gray-200 bg-white p-3">
      <div className="mb-2 flex items-center justify-between gap-2">
        <h2 className="text-sm font-semibold text-gray-800">{title}</h2>
        <div className="flex shrink-0 gap-2">
          <button
            type="button"
            onClick={() => navigator.clipboard?.writeText(json).catch(() => {})}
            className="rounded border border-gray-200 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50"
          >
            Copy
          </button>
          {actions}
        </div>
      </div>
      <pre className="max-h-[calc(100vh-13rem)] overflow-auto rounded border border-gray-100 bg-gray-50 p-3 text-xs text-gray-800">
        {json}
      </pre>
    </section>
  );
}

interface Props {
  template: Template;
  dataSchema: JsonSchema;
  onExportTemplate: () => void;
}

export default function SchemaView({ template, dataSchema, onExportTemplate }: Props) {
  return (
    <div className="grid gap-4 xl:grid-cols-2">
      <JsonPanel
        title="Template"
        value={template}
        actions={
          <button
            type="button"
            onClick={onExportTemplate}
            className="rounded bg-gray-800 px-2 py-1 text-xs font-medium text-white hover:bg-gray-700"
          >
            Export JSON
          </button>
        }
      />
      <JsonPanel title="Data Contract" value={dataSchema} />
    </div>
  );
}
