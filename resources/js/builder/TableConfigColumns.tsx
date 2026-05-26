import type { Json } from "./types";

interface TableColumn {
  key: string;
  label: string;
  align?: string;
  width?: string;
}

interface Props {
  config: Json;
  onChange: (config: Json) => void;
}

const inputClass =
  "block min-w-0 w-full rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-field)] px-2 py-1 text-sm text-[var(--builder-field-ink)] focus:border-[var(--builder-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--builder-accent-soft)]";
const buttonClass =
  "rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] px-2 py-1 text-xs font-medium text-[var(--builder-muted-strong)] transition hover:border-[var(--builder-accent)]";
const activeButtonClass =
  "border-[var(--builder-ink)] bg-[var(--builder-ink)] text-white hover:border-[var(--builder-ink)]";

export default function TableConfigColumns({ config, onChange }: Props) {
  const columns = tableColumns(config.columns);
  const style = stringValue(config.style) || "striped";
  const numberRows = config.numberRows === true;

  function updateColumns(nextColumns: TableColumn[]): void {
    onChange({ ...config, columns: nextColumns.map(serializeColumn) });
  }

  function updateColumn(index: number, column: Partial<TableColumn>): void {
    updateColumns(
      columns.map((current, currentIndex) =>
        currentIndex === index ? { ...current, ...column } : current,
      ),
    );
  }

  return (
    <section
      data-table-config-columns
      className="mb-3 min-w-0 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-3"
    >
      <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <div className="text-xs font-semibold uppercase tracking-wide text-[var(--builder-muted)]">
            Table
          </div>
          <p className="mt-1 text-[10px] leading-snug text-[var(--builder-muted)]">
            Configure numbering, style, and fixed runtime columns.
          </p>
        </div>
        <button
          type="button"
          className={`shrink-0 ${buttonClass}`}
          onClick={() =>
            updateColumns([
              ...columns,
              { key: nextColumnKey(columns), label: "New column", align: "left", width: "" },
            ])
          }
        >
          Add column
        </button>
      </div>
      <div className="mb-4 grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
        <label className="flex min-w-0 items-center gap-3 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] px-3 py-2">
          <input
            type="checkbox"
            checked={numberRows}
            onChange={(event) => onChange({ ...config, numberRows: event.currentTarget.checked })}
          />
          <span className="min-w-0">
            <span className="block text-xs font-medium text-[var(--builder-muted-strong)]">
              Number rows
            </span>
            <span className="block text-[10px] leading-snug text-[var(--builder-muted)]">
              Render a leading auto-incrementing column.
            </span>
          </span>
        </label>
        <div className="flex min-w-0 flex-wrap gap-1 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-1">
          {["striped", "bordered", "minimal"].map((option) => (
            <button
              key={option}
              type="button"
              className={`${buttonClass} ${style === option ? activeButtonClass : ""}`}
              onClick={() => onChange({ ...config, style: option })}
            >
              {titleCase(option)}
            </button>
          ))}
        </div>
      </div>
      <div className="space-y-2">
        {columns.map((column, index) => (
          <div
            key={columnKey(column)}
            className="grid min-w-0 grid-cols-1 gap-2 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-2 md:grid-cols-[minmax(8rem,1fr)_minmax(10rem,1fr)_8rem_7rem_auto] md:items-end"
          >
            <label className="min-w-0">
              <span className="mb-1 block text-[10px] font-medium text-[var(--builder-muted)]">
                Key
              </span>
              <input
                className={inputClass}
                value={column.key}
                pattern="[A-Za-z][A-Za-z0-9_]*"
                onChange={(event) =>
                  updateColumn(index, { key: normalizeKey(event.currentTarget.value) })
                }
              />
            </label>
            <label className="min-w-0">
              <span className="mb-1 block text-[10px] font-medium text-[var(--builder-muted)]">
                Label
              </span>
              <input
                className={inputClass}
                value={column.label}
                onChange={(event) => updateColumn(index, { label: event.currentTarget.value })}
              />
            </label>
            <label className="min-w-0">
              <span className="mb-1 block text-[10px] font-medium text-[var(--builder-muted)]">
                Align
              </span>
              <select
                className={inputClass}
                value={column.align ?? ""}
                onChange={(event) =>
                  updateColumn(index, { align: event.currentTarget.value || undefined })
                }
              >
                <option value="">Default</option>
                <option value="left">Left</option>
                <option value="center">Center</option>
                <option value="right">Right</option>
              </select>
            </label>
            <label className="min-w-0">
              <span className="mb-1 block text-[10px] font-medium text-[var(--builder-muted)]">
                Width
              </span>
              <input
                className={inputClass}
                value={column.width ?? ""}
                onChange={(event) => updateColumn(index, { width: event.currentTarget.value })}
              />
            </label>
            <button
              type="button"
              className="rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] px-2 py-1 text-xs font-medium text-[var(--builder-muted-strong)] transition hover:border-[var(--builder-danger)] hover:text-[var(--builder-danger)]"
              onClick={() =>
                updateColumns(columns.filter((_, currentIndex) => currentIndex !== index))
              }
            >
              Remove
            </button>
          </div>
        ))}
      </div>
    </section>
  );
}

function tableColumns(value: unknown): TableColumn[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((column): TableColumn | null => {
      if (!column || typeof column !== "object") {
        return null;
      }

      const record = column as Record<string, unknown>;
      const key = stringValue(record.key);
      if (key === "") {
        return null;
      }

      return {
        key,
        label: stringValue(record.label) || key,
        align: optionalStringValue(record.align),
        width: optionalStringValue(record.width),
      };
    })
    .filter((column): column is TableColumn => column !== null);
}

function nextColumnKey(columns: TableColumn[]): string {
  const keys = new Set(columns.map((column) => column.key));
  let index = columns.length + 1;
  let key = `column${index}`;

  while (keys.has(key)) {
    index += 1;
    key = `column${index}`;
  }

  return key;
}

function normalizeKey(value: string): string {
  return value.replace(/[^A-Za-z0-9_]/g, "").replace(/^[^A-Za-z]+/, "");
}

function stringValue(value: unknown): string {
  return value == null ? "" : String(value);
}

function optionalStringValue(value: unknown): string | undefined {
  const string = stringValue(value);

  return string === "" ? undefined : string;
}

function serializeColumn(column: TableColumn): TableColumn {
  return Object.fromEntries(
    Object.entries(column).filter(([, value]) => value !== undefined && value !== ""),
  ) as TableColumn;
}

function columnKey(column: TableColumn): string {
  return [column.key, column.label, column.align ?? "", column.width ?? ""].join(":");
}

function titleCase(value: string): string {
  return `${value.charAt(0).toUpperCase()}${value.slice(1)}`;
}
