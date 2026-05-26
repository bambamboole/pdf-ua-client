import type { EditorBlock } from "./types";

function truncate(s: string, n = 40): string {
  return s.length > n ? `${s.slice(0, n)}…` : s;
}

function stringValue(value: unknown): string {
  return value == null ? "" : String(value);
}

function keyValueEntries(
  data: Record<string, unknown>,
  fields: unknown,
): Array<{ label: string; value: string }> {
  if (!Array.isArray(fields)) {
    return [];
  }

  return fields
    .map((field) => {
      if (!field || typeof field !== "object") {
        return null;
      }

      const record = field as Record<string, unknown>;
      const key = stringValue(record.key);
      if (key === "") {
        return null;
      }

      return {
        label: stringValue(record.label) || key,
        value: stringValue(data[key]),
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
  return (
    <div className="mt-2 overflow-hidden rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] text-xs">
      <table className="w-full table-fixed border-collapse">
        <tbody>
          {entries.map((entry) => (
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
    </div>
  );
}

function TablePreview({ headers, rows }: { headers: string[]; rows: string[][] }) {
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
          {rows.map((row) => (
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
      const entries = keyValueEntries(d, block.config.fields);
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
