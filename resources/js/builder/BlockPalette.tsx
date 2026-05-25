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
      className={`w-full cursor-grab rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] px-3 py-2 text-left text-sm font-medium text-[var(--builder-ink)] transition hover:border-[var(--builder-muted)] hover:bg-[var(--builder-raised)] ${isDragging ? "opacity-50" : ""}`}
    >
      <span className="mr-2 text-[var(--builder-muted)]">+</span>
      {getBlockTitle(schema, type)}
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
    <div className="flex flex-col gap-4">
      <div>
        <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--builder-muted)]">
          Blocks
        </h2>
        <div className="space-y-1.5">
          {listBlockTypes(schema).map((type) => (
            <PaletteItem key={type} schema={schema} type={type} />
          ))}
        </div>
      </div>
      {examples.length > 0 && (
        <div className="space-y-1.5 border-t border-[var(--builder-stroke)] pt-4">
          <h2 className="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--builder-muted)]">
            Examples
          </h2>
          {examples.map((ex) => (
            <button
              key={ex.title}
              type="button"
              onClick={() => onLoadExample(ex)}
              className="w-full rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] px-3 py-2 text-left text-sm font-medium text-[var(--builder-ink)] transition hover:border-[var(--builder-muted)] hover:bg-[var(--builder-raised)]"
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
