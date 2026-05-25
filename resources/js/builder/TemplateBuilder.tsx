import { lazy, Suspense, useCallback, useEffect, useMemo, useRef, useState } from "react";
import { DndContext, DragOverlay, PointerSensor, useSensor, useSensors } from "@dnd-kit/core";
import type { DragEndEvent, DragStartEvent } from "@dnd-kit/core";
import BlockPalette from "./BlockPalette";
import EditCanvas from "./EditCanvas";
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

const PageCanvas = lazy(() => import("./PageCanvas"));
const PdfCanvas = lazy(() => import("./PdfCanvas"));
const DataView = lazy(() => import("./DataView"));
const SchemaView = lazy(() => import("./SchemaView"));

type BuilderTab = "build" | "schema" | "html" | "pdf";

const tabs: Array<{ key: BuilderTab; label: string }> = [
  { key: "build", label: "Build" },
  { key: "schema", label: "Schema" },
  { key: "html", label: "HTML" },
  { key: "pdf", label: "PDF" },
];

interface Props {
  schema: JsonSchema;
  examples?: unknown;
  initialTemplate: Template;
  initialData?: DataMap;
  renderTemplate: (t: unknown, d: unknown) => Promise<string>;
  renderPdf: (t: unknown, d: unknown) => Promise<Blob>;
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
  renderPdf,
  onChange,
}: Props) {
  const [model, setModel] = useState(() => fromTemplate(initialTemplate, initialData));
  const [selectedBlockUid, setSelectedBlockUid] = useState<string | null>(null);
  const [tab, setTab] = useState<BuilderTab>("build");
  const [activeLabel, setActiveLabel] = useState<string | null>(null);
  const [html, setHtml] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [pdfUrl, setPdfUrl] = useState<string | null>(null);
  const [pdfLoading, setPdfLoading] = useState(false);
  const [pdfError, setPdfError] = useState<string | null>(null);
  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 4 } }));
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pdfDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pdfUrlRef = useRef<string | null>(null);
  const template = useMemo(() => toTemplate(model), [model]);
  const data = useMemo(() => toDataMap(model), [model]);
  const dataSchema = useMemo(() => dataSchemaForTemplate(schema, template), [schema, template]);

  const replacePdfUrl = useCallback((blob: Blob) => {
    if (pdfUrlRef.current) {
      URL.revokeObjectURL(pdfUrlRef.current);
    }

    const url = URL.createObjectURL(blob);
    pdfUrlRef.current = url;
    setPdfUrl(url);
  }, []);

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

  useEffect(
    () => () => {
      if (pdfUrlRef.current) {
        URL.revokeObjectURL(pdfUrlRef.current);
      }
    },
    [],
  );

  useEffect(() => {
    if (tab !== "pdf") {
      return;
    }

    let cancelled = false;
    setPdfLoading(true);
    setPdfError(null);

    if (pdfDebounceRef.current) {
      clearTimeout(pdfDebounceRef.current);
    }

    pdfDebounceRef.current = setTimeout(() => {
      renderPdf(template, data)
        .then((blob) => {
          if (cancelled) {
            return;
          }

          replacePdfUrl(blob);
          setPdfError(null);
        })
        .catch((cause: unknown) => {
          if (!cancelled) {
            setPdfError(String((cause as Error)?.message ?? cause));
          }
        })
        .finally(() => {
          if (!cancelled) {
            setPdfLoading(false);
          }
        });
    }, 300);

    return () => {
      cancelled = true;
      if (pdfDebounceRef.current) {
        clearTimeout(pdfDebounceRef.current);
      }
    };
  }, [tab, template, data, renderPdf, replacePdfUrl]);

  const selectBlock = useCallback((uid: string) => setSelectedBlockUid(uid), []);

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
      <div className="template-builder flex h-screen bg-[var(--builder-bg)] text-[var(--builder-ink)]">
        <aside className="template-builder__sidebar w-72 shrink-0 overflow-y-auto border-r border-[var(--builder-sidebar-border)] bg-[var(--builder-sidebar)] p-4 text-[var(--builder-ink)]">
          <BlockPalette
            schema={schema}
            examples={listExamples(examples)}
            pageConfig={model.config}
            onLoadExample={(entry) => {
              setModel(() => loadExample(entry));
              setSelectedBlockUid(null);
            }}
            onUpdateTemplateConfig={(config) =>
              setModel((m) => updateTemplateConfig(m, config as Json))
            }
          />
        </aside>
        <main className="flex flex-1 flex-col overflow-hidden">
          <div className="flex items-center justify-between gap-4 border-b border-[var(--builder-stroke)] bg-[var(--builder-panel)] px-5 py-3 shadow-sm">
            <div>
              <h1 className="text-sm font-semibold text-[var(--builder-ink)]">Template builder</h1>
              <p className="text-xs text-[var(--builder-muted)]">
                Compose blocks, inspect data, and preview HTML or PDF output.
              </p>
            </div>
            <div
              data-builder-tabs
              aria-label="Builder views"
              className="flex gap-1 rounded-full border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-1"
            >
              {tabs.map((item) => (
                <button
                  key={item.key}
                  type="button"
                  aria-pressed={tab === item.key}
                  onClick={() => setTab(item.key)}
                  className={`rounded-full px-3 py-1 text-sm font-medium transition ${tab === item.key ? "bg-[var(--builder-ink)] text-white shadow-sm" : "text-[var(--builder-muted-strong)] hover:bg-[var(--builder-panel)] hover:text-[var(--builder-ink)]"}`}
                >
                  {item.label}
                </button>
              ))}
            </div>
          </div>
          <div className="flex-1 overflow-auto bg-[var(--builder-bg)] p-5">
            {tab === "build" ? (
              <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <EditCanvas
                  model={model}
                  schema={schema}
                  format={format}
                  selectedBlockUid={selectedBlockUid}
                  onSelectBlock={selectBlock}
                  onRemoveBlock={(uid) => setModel((m) => removeBlock(m, uid))}
                  onRemoveRow={(uid) => setModel((m) => removeRow(m, uid))}
                  onSetRowWidths={(rowUid, widths) =>
                    setModel((m) => setRowWidths(m, rowUid, widths))
                  }
                  onUpdateBlockId={(uid, id) => setModel((m) => updateBlockId(m, uid, id))}
                  onUpdateBlockConfig={(uid, config) =>
                    setModel((m) => updateBlockConfig(m, uid, config as Json))
                  }
                />
                <section className="sticky top-0 min-w-0 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-3 shadow-[var(--builder-shadow)]">
                  <div className="mb-2 flex items-center justify-between">
                    <h2 className="text-xs font-semibold uppercase tracking-wide text-[var(--builder-muted)]">
                      Example data
                    </h2>
                    <span className="rounded-full bg-[var(--builder-surface)] px-2 py-0.5 text-[0.6875rem] font-medium text-[var(--builder-muted)]">
                      Live
                    </span>
                  </div>
                  <Suspense
                    fallback={
                      <div className="h-24 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-surface)]" />
                    }
                  >
                    <DataView data={data} />
                  </Suspense>
                </section>
              </div>
            ) : tab === "schema" ? (
              <Suspense
                fallback={
                  <div className="h-24 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-panel)]" />
                }
              >
                <SchemaView
                  template={template}
                  dataSchema={dataSchema}
                  onExportTemplate={handleExport}
                />
              </Suspense>
            ) : tab === "html" && error ? (
              <div className="rounded-[var(--builder-radius)] border border-red-200 bg-red-50 p-4 text-red-700">
                {error}
              </div>
            ) : tab === "html" ? (
              <Suspense
                fallback={
                  <div className="h-96 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-panel)]" />
                }
              >
                <PageCanvas format={format} html={html} />
              </Suspense>
            ) : pdfError ? (
              <div className="rounded-[var(--builder-radius)] border border-red-200 bg-red-50 p-4 text-red-700">
                {pdfError}
              </div>
            ) : pdfLoading && pdfUrl === null ? (
              <div className="h-96 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-panel)]" />
            ) : pdfUrl ? (
              <Suspense
                fallback={
                  <div className="h-96 animate-pulse rounded-[var(--builder-radius)] bg-[var(--builder-panel)]" />
                }
              >
                <PdfCanvas url={pdfUrl} />
              </Suspense>
            ) : (
              <div className="rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-4 text-sm text-[var(--builder-muted)]">
                PDF preview is waiting for generated output.
              </div>
            )}
          </div>
        </main>
      </div>
      <DragOverlay>
        {activeLabel ? (
          <div className="rounded-[var(--builder-radius)] border border-[var(--builder-accent)] bg-[var(--builder-panel)] px-3 py-2 text-sm font-medium text-[var(--builder-ink)] shadow-[var(--builder-shadow)]">
            {activeLabel}
          </div>
        ) : null}
      </DragOverlay>
    </DndContext>
  );
}
