import type { JsonSchema, Template } from "./types";

interface JsonPanelProps {
  title: string;
  value: unknown;
  actions?: React.ReactNode;
}

function JsonPanel({ title, value, actions }: JsonPanelProps) {
  const json = JSON.stringify(value, null, 2);

  return (
    <section className="min-w-0 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-3 shadow-[var(--builder-shadow)]">
      <div className="mb-2 flex items-center justify-between gap-2">
        <h2 className="text-sm font-semibold text-[var(--builder-ink)]">{title}</h2>
        <div className="flex shrink-0 gap-2">
          <button
            type="button"
            onClick={() => navigator.clipboard?.writeText(json).catch(() => {})}
            className="rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] px-2 py-1 text-xs font-medium text-[var(--builder-muted-strong)] transition hover:bg-[var(--builder-surface)] hover:text-[var(--builder-ink)]"
          >
            Copy
          </button>
          {actions}
        </div>
      </div>
      <pre className="max-h-[calc(100vh-13rem)] overflow-auto rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-3 text-xs text-[var(--builder-ink)]">
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
            className="rounded-[var(--builder-radius)] bg-[var(--builder-ink)] px-2 py-1 text-xs font-medium text-white transition hover:opacity-90"
          >
            Export JSON
          </button>
        }
      />
      <JsonPanel title="Data Contract" value={dataSchema} />
    </div>
  );
}
