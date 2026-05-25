import { useDraggable } from "@dnd-kit/core";
import type { JsonSchema } from "./types";
import { listBlockTypes, getBlockTitle } from "./lib/schema";
import { listExamples } from "./lib/examples";

interface PaletteItemProps {
  schema: JsonSchema;
  type: string;
}

function PaletteItem({ schema, type }: PaletteItemProps) {
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `palette-${type}`,
    data: { source: "palette", type },
  });

  return (
    <button
      type="button"
      ref={setNodeRef}
      {...listeners}
      {...attributes}
      className={`w-full cursor-grab rounded border border-gray-200 bg-white px-3 py-2 text-left text-sm font-medium text-gray-800 hover:bg-gray-50 ${isDragging ? "opacity-50" : ""}`}
    >
      + {getBlockTitle(schema, type)}
    </button>
  );
}

interface Props {
  schema: JsonSchema;
  onSelectPage: () => void;
  onExport: () => void;
  onLoadExample: (document: Record<string, unknown>) => void;
}

export default function BlockPalette({ schema, onSelectPage, onExport, onLoadExample }: Props) {
  const examples = listExamples(schema);

  return (
    <div className="flex flex-col gap-3">
      <div>
        <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Blocks</h2>
        <div className="space-y-1">
          {listBlockTypes(schema).map((type) => (
            <PaletteItem key={type} schema={schema} type={type} />
          ))}
        </div>
      </div>
      {examples.length > 0 && (
        <div className="space-y-1 border-t border-gray-200 pt-3">
          <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
            Examples
          </h2>
          {examples.map((ex) => (
            <button
              key={ex.title}
              type="button"
              onClick={() => onLoadExample(ex.document)}
              className="w-full rounded border border-gray-200 bg-white px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
            >
              {ex.title}
            </button>
          ))}
        </div>
      )}
      <div className="space-y-2 border-t border-gray-200 pt-3">
        <button
          type="button"
          onClick={onSelectPage}
          className="w-full rounded border border-gray-200 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
        >
          ⚙ Page settings
        </button>
        <button
          type="button"
          onClick={onExport}
          className="w-full rounded bg-gray-800 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700"
        >
          Export JSON
        </button>
      </div>
    </div>
  );
}
