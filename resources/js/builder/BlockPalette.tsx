import { useDraggable } from "@dnd-kit/core";
import type { EditorRow, JsonSchema } from "./types";
import { listBlockTypes, humanizeType } from "./lib/schema";
import { presets } from "./presets";

interface PaletteItemProps {
  type: string;
}

function PaletteItem({ type }: PaletteItemProps) {
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
      + {humanizeType(type)}
    </button>
  );
}

interface Props {
  schema: JsonSchema;
  onSelectPage: () => void;
  onExport: () => void;
  onInsertPreset: (rows: EditorRow[]) => void;
  onLoadInvoice: () => void;
}

export default function BlockPalette({
  schema,
  onSelectPage,
  onExport,
  onInsertPreset,
  onLoadInvoice,
}: Props) {
  return (
    <div className="flex flex-col gap-3">
      <div>
        <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Blocks</h2>
        <div className="space-y-1">
          {listBlockTypes(schema).map((type) => (
            <PaletteItem key={type} type={type} />
          ))}
        </div>
      </div>
      <div className="space-y-1 border-t border-gray-200 pt-3">
        <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
          Presets
        </h2>
        {presets.map((preset) => (
          <button
            key={preset.name}
            type="button"
            onClick={() => onInsertPreset(preset.build())}
            className="w-full rounded border border-gray-200 bg-white px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
          >
            + {preset.name}
          </button>
        ))}
        <button
          type="button"
          onClick={onLoadInvoice}
          className="mt-2 w-full rounded bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-500"
        >
          Load invoice example
        </button>
      </div>
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
