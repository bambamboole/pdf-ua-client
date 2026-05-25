import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { DndContext, DragOverlay, PointerSensor, useSensor, useSensors } from "@dnd-kit/core";
import type { DragEndEvent, DragStartEvent } from "@dnd-kit/core";
import BlockPalette from "./BlockPalette";
import EditCanvas from "./EditCanvas";
import Inspector from "./Inspector";
import PageCanvas from "./PageCanvas";
import DataView from "./DataView";
import SchemaView from "./SchemaView";
import { getPageFormat, getBlockTitle, getBlockSubschemas } from "./lib/schema";
import { listExamples, loadExample } from "./lib/examples";
import { dataSchemaForTemplate } from "./lib/dataSchema";
import {
  fromTemplate,
  toTemplate,
  toDataMap,
  findBlock,
  addBlock,
  removeBlock,
  moveBlock,
  removeRow,
  moveRow,
  updateBlockConfig,
  updateBlockId,
  updateTemplateConfig,
  setRowWidths,
} from "./state/templateModel";
import { exampleFromSchema } from "./lib/exampleFromSchema";
import type { DataMap, Json, JsonSchema, Template } from "./types";

interface Props {
  schema: JsonSchema;
  examples?: unknown;
  initialTemplate: Template;
  initialData?: DataMap;
  renderTemplate: (t: unknown, d: unknown) => Promise<string>;
  onChange?: (t: unknown) => void;
}

function rowIndexById(model: ReturnType<typeof fromTemplate>, rowSortableId: string): number {
  const uid = rowSortableId.replace(/^row-/, "");
  return model.rows.findIndex((r) => r.uid === uid);
}

export default function TemplateBuilder({
  schema,
  examples,
  initialTemplate,
  initialData,
  renderTemplate,
  onChange,
}: Props) {
  const [model, setModel] = useState(() => fromTemplate(initialTemplate, initialData));
  const [selectedBlockUid, setSelectedBlockUid] = useState<string | null>(null);
  const [pageSelected, setPageSelected] = useState(false);
  const [tab, setTab] = useState<"build" | "schema" | "example-data" | "render">("build");
  const [activeLabel, setActiveLabel] = useState<string | null>(null);
  const [html, setHtml] = useState("");
  const [error, setError] = useState<string | null>(null);
  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 4 } }));
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const template = useMemo(() => toTemplate(model), [model]);
  const data = useMemo(() => toDataMap(model), [model]);
  const dataSchema = useMemo(() => dataSchemaForTemplate(schema, template), [schema, template]);

  useEffect(() => {
    if (onChange) {
      onChange(template);
    }
    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }
    debounceRef.current = setTimeout(() => {
      renderTemplate(template, data)
        .then((result) => {
          setHtml(result);
          setError(null);
        })
        .catch((cause: unknown) => setError(String((cause as Error)?.message ?? cause)));
    }, 300);
    return () => clearTimeout(debounceRef.current!);
  }, [template, data, renderTemplate, onChange]);

  const selectBlock = useCallback((uid: string) => {
    setSelectedBlockUid(uid);
    setPageSelected(false);
  }, []);
  const selectPage = useCallback(() => {
    setPageSelected(true);
    setSelectedBlockUid(null);
  }, []);

  const blockData = useCallback(
    (type: string): Json =>
      exampleFromSchema(getBlockSubschemas(schema, type).props, schema) as Json,
    [schema],
  );

  const handleDragEnd = useCallback(
    ({ active, over }: DragEndEvent) => {
      setActiveLabel(null);
      if (!over) {
        return;
      }
      const a = (active.data.current ?? {}) as { source?: string; type?: string; rowUid?: string };
      const o = (over.data.current ?? {}) as { source?: string; type?: string; rowUid?: string };

      if (a.source === "palette") {
        if (o.source === "newrow") {
          setModel((m) => addBlock(m, a.type!, { rowUid: null, data: blockData(a.type!) }));
        } else if (o.source === "block") {
          setModel((m) => {
            const found = findBlock(m, String(over.id));
            return addBlock(m, a.type!, {
              rowUid: o.rowUid,
              index: found ? found.blockIndex : null,
              data: blockData(a.type!),
            });
          });
        } else if (o.source === "row") {
          setModel((m) => addBlock(m, a.type!, { rowUid: o.rowUid, data: blockData(a.type!) }));
        }
        return;
      }

      if (a.source === "block") {
        if (o.source === "newrow") {
          setModel((m) => moveBlock(m, String(active.id), null, null));
        } else if (o.source === "block") {
          setModel((m) => {
            const found = findBlock(m, String(over.id));
            return moveBlock(
              m,
              String(active.id),
              o.rowUid ?? null,
              found ? found.blockIndex : null,
            );
          });
        } else if (o.source === "row") {
          setModel((m) => moveBlock(m, String(active.id), o.rowUid ?? null, null));
        }
        return;
      }

      if (a.source === "row" && o.source === "row" && active.id !== over.id) {
        setModel((m) => moveRow(m, a.rowUid!, rowIndexById(m, String(over.id))));
      }
    },
    [blockData],
  );

  const handleExport = useCallback(() => {
    const json = JSON.stringify(template, null, 2);
    navigator.clipboard?.writeText(json).catch(() => {});
    const blob = new Blob([json], { type: "application/json" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "template.json";
    link.click();
    URL.revokeObjectURL(url);
  }, [template]);

  const selection = useMemo(() => {
    if (pageSelected) {
      return { kind: "page" as const, config: model.config };
    }
    const found = selectedBlockUid ? findBlock(model, selectedBlockUid) : null;
    return found ? { kind: "block" as const, block: found.block } : null;
  }, [pageSelected, selectedBlockUid, model]);

  const format =
    (model.config as { page?: { format?: string } })?.page?.format ?? getPageFormat(schema);

  return (
    <DndContext
      sensors={sensors}
      onDragEnd={handleDragEnd}
      onDragStart={(e: DragStartEvent) => {
        const d = (e.active.data.current ?? {}) as { type?: string };
        setActiveLabel(d.type ? getBlockTitle(schema, d.type) : "Block");
      }}
    >
      <div className="flex h-screen">
        <aside className="w-64 shrink-0 overflow-y-auto border-r border-gray-200 bg-gray-50 p-4">
          <BlockPalette
            schema={schema}
            examples={listExamples(examples)}
            onSelectPage={selectPage}
            onLoadExample={(entry) => {
              setModel(() => loadExample(entry));
              setSelectedBlockUid(null);
              setPageSelected(false);
            }}
          />
        </aside>
        <main className="flex flex-1 flex-col overflow-hidden">
          <div className="flex gap-1 border-b border-gray-200 bg-white px-4 py-2">
            <button
              type="button"
              onClick={() => setTab("build")}
              className={`rounded px-3 py-1 text-sm ${tab === "build" ? "bg-gray-800 text-white" : "text-gray-600"}`}
            >
              Build
            </button>
            <button
              type="button"
              onClick={() => setTab("schema")}
              className={`rounded px-3 py-1 text-sm ${tab === "schema" ? "bg-gray-800 text-white" : "text-gray-600"}`}
            >
              Schema
            </button>
            <button
              type="button"
              onClick={() => setTab("example-data")}
              className={`rounded px-3 py-1 text-sm ${tab === "example-data" ? "bg-gray-800 text-white" : "text-gray-600"}`}
            >
              Example Data
            </button>
            <button
              type="button"
              onClick={() => setTab("render")}
              className={`rounded px-3 py-1 text-sm ${tab === "render" ? "bg-gray-800 text-white" : "text-gray-600"}`}
            >
              Render
            </button>
          </div>
          <div className="flex-1 overflow-auto bg-gray-100 p-6">
            {tab === "build" ? (
              <EditCanvas
                model={model}
                selectedBlockUid={selectedBlockUid}
                onSelectBlock={selectBlock}
                onRemoveBlock={(uid) => setModel((m) => removeBlock(m, uid))}
                onRemoveRow={(uid) => setModel((m) => removeRow(m, uid))}
                onSetRowWidths={(rowUid, widths) =>
                  setModel((m) => setRowWidths(m, rowUid, widths))
                }
              />
            ) : tab === "schema" ? (
              <SchemaView
                template={template}
                dataSchema={dataSchema}
                onExportTemplate={handleExport}
              />
            ) : tab === "example-data" ? (
              <DataView data={data} />
            ) : error ? (
              <div className="rounded border border-red-200 bg-red-50 p-4 text-red-700">
                {error}
              </div>
            ) : (
              <PageCanvas format={format} html={html} />
            )}
          </div>
        </main>
        <aside className="w-80 shrink-0 overflow-y-auto border-l border-gray-200 bg-white p-4">
          <Inspector
            schema={schema}
            selection={selection}
            onUpdateBlockId={(uid, id) => setModel((m) => updateBlockId(m, uid, id))}
            onUpdateBlockConfig={(uid, config) =>
              setModel((m) => updateBlockConfig(m, uid, config as Json))
            }
            onUpdateTemplateConfig={(config) =>
              setModel((m) => updateTemplateConfig(m, config as Json))
            }
          />
        </aside>
      </div>
      <DragOverlay>
        {activeLabel ? (
          <div className="rounded border border-blue-400 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-lg">
            {activeLabel}
          </div>
        ) : null}
      </DragOverlay>
    </DndContext>
  );
}
