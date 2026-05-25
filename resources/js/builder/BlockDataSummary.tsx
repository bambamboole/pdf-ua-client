import type { EditorBlock } from "./types";

const previewLimit = 3;

function truncate(s: string, n = 40): string {
  return s.length > n ? `${s.slice(0, n)}…` : s;
}

function stringValue(value: unknown): string {
  return value == null ? "" : String(value);
}

function keyValueEntries(data: Record<string, unknown>): Array<{ label: string; value: string }> {
  if (!Array.isArray(data.entries)) {
    return [];
  }

  return data.entries
    .map((entry) => {
      if (!entry || typeof entry !== "object") {
        return null;
      }

      const pair = entry as Record<string, unknown>;
      return {
        label: stringValue(pair.label),
        value: stringValue(pair.value),
      };
    })
    .filter((entry): entry is { label: string; value: string } => entry !== null);
}

function stringList(value: unknown): string[] {
  return Array.isArray(value) ? value.map(stringValue) : [];
}

function tableRows(value: unknown): string[][] {
  return Array.isArray(value) ? value.map(stringList).filter((row) => row.length > 0) : [];
}

function keyPart(parts: string[]): string {
  return parts.join("\u001f");
}

function KeyValuePreview({ entries }: { entries: Array<{ label: string; value: string }> }) {
  const visible = entries.slice(0, previewLimit);
  const hidden = entries.length - visible.length;

  return (
    <div className="mt-2 overflow-hidden rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] text-xs">
      <table className="w-full table-fixed border-collapse">
        <tbody>
          {visible.map((entry) => (
            <tr
              key={keyPart([entry.label, entry.value])}
              className="border-b border-[var(--builder-stroke)] last:border-0"
            >
              <th className="w-2/5 bg-[var(--builder-surface)] px-2 py-1 text-left font-medium text-[var(--builder-muted-strong)]">
                {entry.label}
              </th>
              <td className="px-2 py-1 text-[var(--builder-ink)]">{entry.value}</td>
            </tr>
          ))}
        </tbody>
      </table>
      {hidden > 0 ? (
        <div className="border-t border-[var(--builder-stroke)] bg-[var(--builder-surface)] px-2 py-1 text-[var(--builder-muted)]">
          +{hidden} more
        </div>
      ) : null}
    </div>
  );
}

function TablePreview({ headers, rows }: { headers: string[]; rows: string[][] }) {
  const visibleRows = rows.slice(0, previewLimit);
  const hiddenRows = rows.length - visibleRows.length;

  return (
    <div className="mt-2 overflow-auto rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] text-xs">
      <table className="w-full min-w-max border-collapse">
        {headers.length > 0 ? (
          <thead>
            <tr className="bg-[var(--builder-surface)]">
              {headers.map((header) => (
                <th
                  key={header}
                  className="border-b border-[var(--builder-stroke)] px-2 py-1 text-left font-medium text-[var(--builder-muted-strong)]"
                >
                  {header}
                </th>
              ))}
            </tr>
          </thead>
        ) : null}
        <tbody>
          {visibleRows.map((row) => (
            <tr
              key={keyPart(row)}
              className="border-b border-[var(--builder-stroke)] last:border-0"
            >
              {row.map((cell, cellIndex) => (
                <td
                  key={keyPart([cell, headers[cellIndex] ?? ""])}
                  className="px-2 py-1 text-[var(--builder-ink)]"
                >
                  {cell}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
      {hiddenRows > 0 ? (
        <div className="border-t border-[var(--builder-stroke)] bg-[var(--builder-surface)] px-2 py-1 text-[var(--builder-muted)]">
          +{hiddenRows} more rows
        </div>
      ) : null}
    </div>
  );
}

export default function BlockDataSummary({ block }: { block: EditorBlock }) {
  const d = block.data as Record<string, unknown>;
  let summary = "";
  switch (block.type) {
    case "heading":
    case "text":
      summary = truncate(String(d.text ?? ""));
      break;
    case "html":
      summary = "HTML";
      break;
    case "image":
      summary = String(d.alt ?? "image");
      break;
    case "key-value": {
      const entries = keyValueEntries(d);
      return entries.length > 0 ? (
        <KeyValuePreview entries={entries} />
      ) : (
        <span className="truncate text-xs text-[var(--builder-muted)]">0 entries</span>
      );
    }
    case "table": {
      const headers = stringList(d.headers);
      const rows = tableRows(d.rows);
      return headers.length > 0 || rows.length > 0 ? (
        <TablePreview headers={headers} rows={rows} />
      ) : (
        <span className="truncate text-xs text-[var(--builder-muted)]">0 cols × 0 rows</span>
      );
    }
    default:
      break;
  }
  return summary ? (
    <span className="truncate text-xs text-[var(--builder-muted)]">{summary}</span>
  ) : null;
}
