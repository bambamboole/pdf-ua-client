import type { DataMap } from "./types";

export default function DataView({ data }: { data: DataMap }) {
  const json = JSON.stringify(data, null, 2);
  return (
    <div className="mx-auto max-w-3xl">
      <div className="mb-2 flex justify-end">
        <button
          type="button"
          onClick={() => navigator.clipboard?.writeText(json).catch(() => {})}
          className="rounded border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50"
        >
          Copy
        </button>
      </div>
      <pre className="overflow-auto rounded border border-gray-200 bg-white p-4 text-xs text-gray-800">
        {json}
      </pre>
    </div>
  );
}
