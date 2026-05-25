import type { DataMap } from "./types";

export default function DataView({ data }: { data: DataMap }) {
  const json = JSON.stringify(data, null, 2);
  return (
    <div className="min-w-0">
      <div className="mb-2 flex justify-end">
        <button
          type="button"
          onClick={() => navigator.clipboard?.writeText(json).catch(() => {})}
          className="rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] px-2 py-1 text-xs font-medium text-[var(--builder-muted-strong)] transition hover:bg-[var(--builder-surface)] hover:text-[var(--builder-ink)]"
        >
          Copy
        </button>
      </div>
      <pre className="max-h-[calc(100vh-12rem)] overflow-auto rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-4 text-xs text-[var(--builder-ink)]">
        {json}
      </pre>
    </div>
  );
}
