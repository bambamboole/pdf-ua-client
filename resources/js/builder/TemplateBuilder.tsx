import { lazy, Suspense, useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  DndContext,
  DragOverlay,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from "@dnd-kit/core";
import type { DragEndEvent, DragStartEvent } from "@dnd-kit/core";
import { sortableKeyboardCoordinates } from "@dnd-kit/sortable";
import BlockPalette from "./BlockPalette";
import EditCanvas from "./EditCanvas";
import PageCanvas from "./PageCanvas";
import PdfCanvas from "./PdfCanvas";
import { getPageFormat, getBlockTitle, getBlockSubschemas } from "./lib/schema";
import { listExamples, loadExample } from "./lib/examples";
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
  updateDataField,
} from "./state/templateModel";
import { exampleFromSchema } from "./lib/exampleFromSchema";
import { useLatest } from "./useLatest";
import type {
  DataMap,
  DragData,
  EditorArea,
  EditorModel,
  Json,
  JsonSchema,
  Template,
} from "./types";
import CanvasZoomControls from "./CanvasZoomControls";

const SchemaView = lazy(() => import("./SchemaView"));

type BuilderTab = "build" | "schema" | "html" | "pdf";

const tabs: Array<{ key: BuilderTab; label: string }> = [
  { key: "build", label: "Build" },
  { key: "schema", label: "Schema" },
  { key: "html", label: "HTML" },
  { key: "pdf", label: "PDF" },
];

const ZOOM_STEP = 0.05;
const MIN_ZOOM = 0.75;
const MAX_ZOOM = 1.6;

export interface TemplateBuilderProps {
  schema: JsonSchema;
  examples?: unknown;
  initialTemplate: Template;
  initialData?: DataMap;
  renderTemplate: (template: Template, data: DataMap) => Promise<string>;
  renderPdf: (template: Template, data: DataMap) => Promise<Blob>;
  fetchSchema: (template: Template) => Promise<JsonSchema>;
  onChange?: (template: Template) => void;
}

function errorMessage(cause: unknown): string {
  return cause instanceof Error ? cause.message : String(cause);
}

function rowIndexById(model: EditorModel, rowSortableId: string, area: EditorArea): number {
  const uid = rowSortableId.replace(/^row-/, "");
  const rows = area === "footer" ? model.footerRows : model.rows;
  return rows.findIndex((r) => r.uid === uid);
}

export default function TemplateBuilder({
  schema,
  examples,
  initialTemplate,
  initialData,
  renderTemplate,
  renderPdf,
  fetchSchema,
  onChange,
}: TemplateBuilderProps) {
  const [model, setModel] = useState(() => fromTemplate(initialTemplate, initialData));
  const [selectedBlockUid, setSelectedBlockUid] = useState<string | null>(null);
  const [tab, setTab] = useState<BuilderTab>("build");
  const [activeLabel, setActiveLabel] = useState<string | null>(null);
  const [html, setHtml] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [pdfUrl, setPdfUrl] = useState<string | null>(null);
  const [pdfLoading, setPdfLoading] = useState(false);
  const [pdfError, setPdfError] = useState<string | null>(null);
  const [dataSchema, setDataSchema] = useState<JsonSchema>({});
  const [schemaError, setSchemaError] = useState<string | null>(null);
  const [canvasScale, setCanvasScale] = useState(1);
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
  );
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pdfDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const schemaDebounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pdfUrlRef = useRef<string | null>(null);
  const renderTemplateRef = useLatest(renderTemplate);
  const renderPdfRef = useLatest(renderPdf);
  const fetchSchemaRef = useLatest(fetchSchema);
  const onChangeRef = useLatest(onChange);
  const template = useMemo(() => toTemplate(model), [model]);
  const data = useMemo(() => toDataMap(model), [model]);

  const replacePdfUrl = useCallback((blob: Blob) => {
    if (pdfUrlRef.current) {
      URL.revokeObjectURL(pdfUrlRef.current);
    }

    const url = URL.createObjectURL(blob);
    pdfUrlRef.current = url;
    setPdfUrl(url);
  }, []);

  useEffect(() => {
    onChangeRef.current?.(template);
  }, [template, onChangeRef]);

  useEffect(() => {
    if (tab !== "html") {
      return;
    }

    let cancelled = false;
    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }

    debounceRef.current = setTimeout(() => {
      renderTemplateRef
        .current(template, data)
        .then((result) => {
          if (!cancelled) {
            setHtml(result);
            setError(null);
          }
        })
        .catch((cause: unknown) => {
          if (!cancelled) {
            setError(errorMessage(cause));
          }
        });
    }, 300);

    return () => {
      cancelled = true;
      if (debounceRef.current) {
        clearTimeout(debounceRef.current);
      }
    };
  }, [tab, template, data, renderTemplateRef]);

  useEffect(() => {
    if (tab !== "schema") {
      return;
    }

    let cancelled = false;
    if (schemaDebounceRef.current) {
      clearTimeout(schemaDebounceRef.current);
    }

    schemaDebounceRef.current = setTimeout(() => {
      fetchSchemaRef
        .current(template)
        .then((result) => {
          if (!cancelled) {
            setDataSchema(result);
            setSchemaError(null);
          }
        })
        .catch((cause: unknown) => {
          if (!cancelled) {
            setSchemaError(errorMessage(cause));
          }
        });
    }, 300);

    return () => {
      cancelled = true;
      if (schemaDebounceRef.current) {
        clearTimeout(schemaDebounceRef.current);
      }
    };
  }, [tab, template, fetchSchemaRef]);

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
      renderPdfRef
        .current(template, data)
        .then((blob) => {
          if (cancelled) {
            return;
          }

          replacePdfUrl(blob);
          setPdfError(null);
        })
        .catch((cause: unknown) => {
          if (!cancelled) {
            setPdfError(errorMessage(cause));
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
  }, [tab, template, data, renderPdfRef, replacePdfUrl]);

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
      const a = active.data.current as DragData | undefined;
      const o = over.data.current as DragData | undefined;
      if (!a || !o) {
        return;
      }

      if (a.source === "palette") {
        if (o.source === "newrow") {
          setModel((m) =>
            addBlock(m, a.type, { rowUid: null, area: o.area, data: blockData(a.type) }),
          );
        } else if (o.source === "block") {
          setModel((m) => {
            const found = findBlock(m, String(over.id));
            return addBlock(m, a.type, {
              rowUid: o.rowUid,
              area: o.area,
              index: found ? found.blockIndex : null,
              data: blockData(a.type),
            });
          });
        } else if (o.source === "row") {
          setModel((m) =>
            addBlock(m, a.type, { rowUid: o.rowUid, area: o.area, data: blockData(a.type) }),
          );
        }
        return;
      }

      if (a.source === "block") {
        if (o.source === "newrow") {
          setModel((m) => moveBlock(m, String(active.id), null, null, o.area));
        } else if (o.source === "block") {
          setModel((m) => {
            const found = findBlock(m, String(over.id));
            return moveBlock(
              m,
              String(active.id),
              o.rowUid,
              found ? found.blockIndex : null,
              o.area,
            );
          });
        } else if (o.source === "row") {
          setModel((m) => moveBlock(m, String(active.id), o.rowUid, null, o.area));
        }
        return;
      }

      if (a.source === "row" && o.source === "row" && active.id !== over.id && a.area === o.area) {
        setModel((m) => moveRow(m, a.rowUid, rowIndexById(m, String(over.id), o.area), o.area));
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
        const a = e.active.data.current as DragData | undefined;
        setActiveLabel(a?.source === "palette" ? getBlockTitle(schema, a.type) : "Block");
      }}
    >
      <div className="template-builder flex h-screen bg-[var(--builder-bg)] text-[var(--builder-ink)]">
        <aside className="template-builder__sidebar w-72 shrink-0 overflow-y-auto border-r border-[var(--builder-sidebar-border)] bg-[var(--builder-sidebar)] p-4 text-[var(--builder-ink)]">
          <BlockPalette
            schema={schema}
            examples={listExamples(examples)}
            pageConfig={model.config}
            onLoadExample={(entry) => {
              setModel(loadExample(entry));
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
              <div className="grid items-start gap-5">
                <CanvasZoomControls
                  onDecrease={() => setCanvasScale((scale) => clampZoom(scale - ZOOM_STEP))}
                  onIncrease={() => setCanvasScale((scale) => clampZoom(scale + ZOOM_STEP))}
                  onReset={() => setCanvasScale(1)}
                />
                <EditCanvas
                  model={model}
                  schema={schema}
                  format={format}
                  scale={canvasScale}
                  selectedBlockUid={selectedBlockUid}
                  onSelectBlock={selectBlock}
                  onRemoveBlock={(uid) => setModel((m) => removeBlock(m, uid))}
                  onRemoveRow={(uid) => setModel((m) => removeRow(m, uid))}
                  onUpdateFooterRepeat={(repeat) => setModel((m) => updateFooterRepeat(m, repeat))}
                  onUpdatePageNumbers={(position) =>
                    setModel((m) => updatePageNumbers(m, position))
                  }
                  onSetRowWidths={(rowUid, widths) =>
                    setModel((m) => setRowWidths(m, rowUid, widths))
                  }
                  onUpdateBlockId={(uid, id) => setModel((m) => updateBlockId(m, uid, id))}
                  onUpdateBlockConfig={(uid, config) =>
                    setModel((m) => updateBlockConfig(m, uid, config as Json))
                  }
                  onUpdateDataField={(blockId, field, value, options) =>
                    setModel((m) => updateDataField(m, blockId, field, value, options))
                  }
                />
              </div>
            ) : tab === "schema" && schemaError ? (
              <div className="rounded-[var(--builder-radius)] border border-red-200 bg-red-50 p-4 text-red-700">
                {schemaError}
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

function clampZoom(scale: number): number {
  return Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, Number(scale.toFixed(2))));
}

function updateFooterRepeat(model: EditorModel, repeat: boolean): EditorModel {
  const page = objectOrEmpty(model.config.page);
  const footer = objectOrEmpty(page.footer);

  return {
    ...model,
    config: {
      ...model.config,
      page: {
        ...page,
        footer: {
          ...footer,
          repeat,
        },
      },
    },
  };
}

function updatePageNumbers(
  model: EditorModel,
  position: "disabled" | "left" | "center" | "right",
): EditorModel {
  const page = objectOrEmpty(model.config.page);

  return {
    ...model,
    config: {
      ...model.config,
      page: {
        ...page,
        pageNumbers:
          position === "disabled"
            ? { ...objectOrEmpty(page.pageNumbers), enabled: false }
            : { ...objectOrEmpty(page.pageNumbers), enabled: true, position },
      },
    },
  };
}

function objectOrEmpty(value: unknown): Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value)
    ? (value as Record<string, unknown>)
    : {};
}
