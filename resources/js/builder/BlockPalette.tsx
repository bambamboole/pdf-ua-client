import { useDraggable } from "@dnd-kit/core";
import PageSettingsPanel from "./PageSettingsPanel";
import type { Json, JsonSchema } from "./types";
import { listBlockTypes, getBlockTitle } from "./lib/schema";
import type { ExampleEntry } from "./lib/examples";

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
  examples: ExampleEntry[];
  pageConfig: Json;
  onLoadExample: (entry: ExampleEntry) => void;
  onUpdateTemplateConfig: (config: Json) => void;
}

export default function BlockPalette({
  schema,
  examples,
  pageConfig,
  onLoadExample,
  onUpdateTemplateConfig,
}: Props) {
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
              onClick={() => onLoadExample(ex)}
              className="w-full rounded border border-gray-200 bg-white px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
            >
              {ex.title}
            </button>
          ))}
        </div>
      )}
      <PageSettingsPanel
        schema={schema}
        config={pageConfig}
        onUpdateTemplateConfig={onUpdateTemplateConfig}
      />
    </div>
  );
}
