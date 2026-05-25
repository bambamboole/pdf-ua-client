import type { EditorBlock } from "./types";

function truncate(s: string, n = 40): string {
  return s.length > n ? `${s.slice(0, n)}…` : s;
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
    case "key-value":
      summary = `${Array.isArray(d.entries) ? d.entries.length : 0} entries`;
      break;
    case "table": {
      const headers = Array.isArray(d.headers) ? d.headers.length : 0;
      const rows = Array.isArray(d.rows) ? d.rows.length : 0;
      summary = `${headers} cols × ${rows} rows`;
      break;
    }
    default:
      summary = "";
  }
  return summary ? (
    <span className="truncate text-xs text-[var(--builder-muted)]">{summary}</span>
  ) : null;
}
