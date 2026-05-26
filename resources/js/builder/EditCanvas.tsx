import { Fragment, useRef, useState, type CSSProperties } from "react";
import { useDroppable } from "@dnd-kit/core";
import {
  SortableContext,
  useSortable,
  horizontalListSortingStrategy,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { gridTemplateForWidths } from "./lib/columns";
import { pageSizeForFormat } from "./lib/pageSizes";
import { mmToScaledPx } from "./lib/displayScale";
import { getBlockTitle } from "./lib/schema";
import BlockDataSummary from "./BlockDataSummary";
import ColumnResizer from "./ColumnResizer";
import InlineBlockEditor from "./InlineBlockEditor";
import type {
  DragData,
  EditorBlock,
  EditorArea,
  EditorRow,
  Json,
  JsonSchema,
  TemplateDataLayers,
} from "./types";

interface Props {
  model: { rows: EditorRow[]; footerRows: EditorRow[]; data: TemplateDataLayers; config: Json };
  schema: JsonSchema;
  format: string;
  scale: number;
  selectedBlockUid: string | null;
  onSelectBlock: (uid: string) => void;
  onRemoveBlock: (uid: string) => void;
  onRemoveRow: (uid: string) => void;
  onUpdateFooterRepeat: (repeat: boolean) => void;
  onUpdatePageNumbers: (position: "disabled" | "left" | "center" | "right") => void;
  onSetRowWidths: (rowUid: string, widths: string[]) => void;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
  onUpdateDataField: (
    blockId: string,
    field: string,
    value: unknown,
    options: { example: boolean; locked: boolean },
  ) => void;
}

interface BlockBoxProps {
  block: EditorBlock;
  rowUid: string;
  area: EditorArea;
  selected: boolean;
  initiallyOpen: boolean;
  style?: CSSProperties;
  onSelect: (uid: string) => void;
  onRemove: (uid: string) => void;
  schema: JsonSchema;
  data: TemplateDataLayers;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
  onUpdateDataField: (
    blockId: string,
    field: string,
    value: unknown,
    options: { example: boolean; locked: boolean },
  ) => void;
}

function BlockBox({
  block,
  rowUid,
  area,
  selected,
  initiallyOpen,
  style: layoutStyle,
  onSelect,
  onRemove,
  schema,
  data,
  onUpdateBlockId,
  onUpdateBlockConfig,
  onUpdateDataField,
}: BlockBoxProps) {
  const [settingsOpen, setSettingsOpen] = useState(initiallyOpen);
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: block.uid,
    data: { source: "block", rowUid, area } satisfies DragData,
  });
  const style = { transform: CSS.Transform.toString(transform), transition, ...layoutStyle };

  return (
    <div
      ref={setNodeRef}
      style={style}
      onPointerDownCapture={() => onSelect(block.uid)}
      data-builder-block
      data-builder-block-type={block.type}
      className={`${layoutStyle ? "" : "flex-1"} rounded-[var(--builder-radius)] border bg-[var(--builder-panel)] px-3 py-2 text-sm shadow-sm transition ${selected ? "border-[var(--builder-accent)] ring-2 ring-[var(--builder-accent-soft)]" : "border-[var(--builder-stroke)] hover:border-[var(--builder-stroke-strong)]"} ${isDragging ? "opacity-50" : ""}`}
    >
      <div className="flex items-center justify-between gap-2">
        <div className="flex min-w-0 items-center gap-2">
          <span
            {...listeners}
            {...attributes}
            aria-label="Move block"
            title="Move block"
            className="shrink-0 cursor-grab text-[var(--builder-muted)]"
          >
            ⠿
          </span>
          <span className="truncate text-xs font-medium text-[var(--builder-muted)]">
            {getBlockTitle(schema, block.type)}
          </span>
        </div>
        <span className="flex items-center gap-2">
          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              onRemove(block.uid);
            }}
            onPointerDown={(e) => e.stopPropagation()}
            className="text-[var(--builder-muted)] transition hover:text-[var(--builder-danger)]"
          >
            ✕
          </button>
        </span>
      </div>
      <BlockDataSummary block={block} />
      <details
        data-inline-block-details
        open={settingsOpen}
        className="mt-3 border-t border-[var(--builder-stroke)] pt-2"
        onToggle={(event) => {
          if (event.currentTarget.open) {
            onSelect(block.uid);
          }
        }}
      >
        <summary
          onClick={(event) => {
            event.preventDefault();
            event.stopPropagation();
            setSettingsOpen((open) => !open);
            onSelect(block.uid);
          }}
          onPointerDown={(event) => event.stopPropagation()}
          className="cursor-pointer text-xs font-medium text-[var(--builder-muted)] transition hover:text-[var(--builder-ink)]"
        >
          More
        </summary>
        <InlineBlockEditor
          block={block}
          schema={schema}
          data={data}
          detailsOpen={settingsOpen}
          onUpdateBlockId={onUpdateBlockId}
          onUpdateBlockConfig={onUpdateBlockConfig}
          onUpdateDataField={onUpdateDataField}
        />
      </details>
    </div>
  );
}

interface RowProps {
  row: EditorRow;
  rowIndex: number;
  area: EditorArea;
  schema: JsonSchema;
  data: TemplateDataLayers;
  selectedBlockUid: string | null;
  onSelectBlock: (uid: string) => void;
  onRemoveBlock: (uid: string) => void;
  onRemoveRow: (uid: string) => void;
  onSetRowWidths: (rowUid: string, widths: string[]) => void;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
  onUpdateDataField: (
    blockId: string,
    field: string,
    value: unknown,
    options: { example: boolean; locked: boolean },
  ) => void;
}

function Row({
  row,
  rowIndex,
  area,
  schema,
  data,
  selectedBlockUid,
  onSelectBlock,
  onRemoveBlock,
  onRemoveRow,
  onSetRowWidths,
  onUpdateBlockId,
  onUpdateBlockConfig,
  onUpdateDataField,
}: RowProps) {
  const { attributes, listeners, setNodeRef, transform, transition } = useSortable({
    id: `row-${row.uid}`,
    data: { source: "row", rowUid: row.uid, area } satisfies DragData,
  });
  const { setNodeRef: setDropRef } = useDroppable({
    id: `rowdrop-${row.uid}`,
    data: { source: "row", rowUid: row.uid, area } satisfies DragData,
  });
  const style = { transform: CSS.Transform.toString(transform), transition };
  const containerRef = useRef<HTMLDivElement>(null);

  const rawWidths = row.blocks.map((b) => b.config.width as string | number | undefined);
  const widths = rawWidths.every((w) => w != null) ? (rawWidths as (string | number)[]) : null;
  const gridTemplateColumns = gridTemplateForWidths(widths, row.blocks.length);

  return (
    <div
      ref={setNodeRef}
      style={style}
      className="rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-2 shadow-sm"
    >
      <div className="mb-1 flex items-center justify-between text-xs text-[var(--builder-muted)]">
        <span {...listeners} {...attributes} className="cursor-grab">
          ⠿ row
        </span>
        <span className="flex gap-2">
          <button
            type="button"
            onClick={() => onRemoveRow(row.uid)}
            className="transition hover:text-[var(--builder-danger)]"
          >
            remove row
          </button>
        </span>
      </div>
      <div
        ref={(node) => {
          setDropRef(node);
          containerRef.current = node;
        }}
        className={gridTemplateColumns ? "grid gap-2" : "flex gap-2"}
        style={gridTemplateColumns ? { gridTemplateColumns } : undefined}
      >
        <SortableContext
          items={row.blocks.map((b) => b.uid)}
          strategy={horizontalListSortingStrategy}
        >
          {row.blocks.map((block, i) => (
            <Fragment key={block.uid}>
              <BlockBox
                block={block}
                rowUid={row.uid}
                area={area}
                selected={block.uid === selectedBlockUid}
                initiallyOpen={rowIndex === 0 && i === 0}
                style={widths ? { minWidth: 0 } : undefined}
                onSelect={onSelectBlock}
                onRemove={onRemoveBlock}
                schema={schema}
                data={data}
                onUpdateBlockId={onUpdateBlockId}
                onUpdateBlockConfig={onUpdateBlockConfig}
                onUpdateDataField={onUpdateDataField}
              />
              {i < row.blocks.length - 1 ? (
                <ColumnResizer
                  key={`rz-${block.uid}-${row.blocks[i + 1]?.uid ?? "end"}`}
                  widths={widths}
                  count={row.blocks.length}
                  leftIndex={i}
                  containerRef={containerRef}
                  onResize={(w) => onSetRowWidths(row.uid, w)}
                />
              ) : null}
            </Fragment>
          ))}
        </SortableContext>
      </div>
    </div>
  );
}

function NewRowZone({ area = "body" }: { area?: EditorArea }) {
  const { setNodeRef, isOver } = useDroppable({
    id: area === "footer" ? "footer-new-row" : "new-row",
    data: { source: "newrow", area } satisfies DragData,
  });
  return (
    <div
      ref={setNodeRef}
      data-new-row-zone={area}
      className={`rounded-[var(--builder-radius)] border-2 border-dashed px-3 py-6 text-center text-sm font-medium transition ${isOver ? "border-[var(--builder-accent)] bg-[var(--builder-accent-soft)] text-[var(--builder-accent)]" : "border-[var(--builder-stroke-strong)] text-[var(--builder-muted)]"}`}
    >
      Drop a block here to add a new row
    </div>
  );
}

function FooterCanvas({
  model,
  schema,
  selectedBlockUid,
  onSelectBlock,
  onRemoveBlock,
  onRemoveRow,
  onSetRowWidths,
  onUpdateBlockId,
  onUpdateBlockConfig,
  onUpdateDataField,
  onUpdateFooterRepeat,
  onUpdatePageNumbers,
}: {
  model: Props["model"];
  schema: JsonSchema;
  selectedBlockUid: string | null;
  onSelectBlock: (uid: string) => void;
  onRemoveBlock: (uid: string) => void;
  onRemoveRow: (uid: string) => void;
  onSetRowWidths: (rowUid: string, widths: string[]) => void;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
  onUpdateDataField: (
    blockId: string,
    field: string,
    value: unknown,
    options: { example: boolean; locked: boolean },
  ) => void;
  onUpdateFooterRepeat: (repeat: boolean) => void;
  onUpdatePageNumbers: (position: "disabled" | "left" | "center" | "right") => void;
}) {
  const page = model.config.page as Record<string, unknown> | undefined;
  const footer = page?.footer as Record<string, unknown> | undefined;
  const pageNumbers = page?.pageNumbers as Record<string, unknown> | undefined;
  const pageNumberPosition =
    pageNumbers?.enabled === true ? String(pageNumbers.position ?? "center") : "disabled";

  return (
    <section className="space-y-2">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div>
          <div className="text-xs font-semibold uppercase tracking-wide text-[var(--builder-muted)]">
            Footer
          </div>
          <div className="text-[10px] text-[var(--builder-muted)]">
            Repeated content rendered in the page footer area.
          </div>
        </div>
        <div className="flex flex-wrap items-center gap-2 text-xs">
          <label className="inline-flex items-center gap-1.5 text-[var(--builder-muted-strong)]">
            <input
              type="checkbox"
              checked={footer?.repeat !== false}
              onChange={(event) => onUpdateFooterRepeat(event.currentTarget.checked)}
            />
            Repeat
          </label>
        </div>
      </div>
      <div
        data-footer-canvas
        className="space-y-2 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-3 shadow-[var(--builder-shadow)]"
      >
        {model.footerRows.length > 0 ? (
          <SortableContext
            items={model.footerRows.map((candidate) => `row-${candidate.uid}`)}
            strategy={verticalListSortingStrategy}
          >
            {model.footerRows.map((row, rowIndex) => (
              <Row
                key={row.uid}
                row={row}
                rowIndex={rowIndex}
                area="footer"
                schema={schema}
                data={model.data}
                selectedBlockUid={selectedBlockUid}
                onSelectBlock={onSelectBlock}
                onRemoveBlock={onRemoveBlock}
                onRemoveRow={onRemoveRow}
                onSetRowWidths={onSetRowWidths}
                onUpdateBlockId={onUpdateBlockId}
                onUpdateBlockConfig={onUpdateBlockConfig}
                onUpdateDataField={onUpdateDataField}
              />
            ))}
          </SortableContext>
        ) : null}
        <NewRowZone area="footer" />
      </div>
      <div className="flex justify-center">
        <label className="inline-flex items-center gap-1.5 text-xs text-[var(--builder-muted-strong)]">
          Page numbers
          <select
            className="rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-field)] px-2 py-1 text-xs text-[var(--builder-field-ink)]"
            value={pageNumberPosition}
            onChange={(event) =>
              onUpdatePageNumbers(
                event.currentTarget.value as "disabled" | "left" | "center" | "right",
              )
            }
          >
            <option value="disabled">Disabled</option>
            <option value="left">Left</option>
            <option value="center">Center</option>
            <option value="right">Right</option>
          </select>
        </label>
      </div>
    </section>
  );
}

export default function EditCanvas({
  model,
  schema,
  format,
  scale,
  selectedBlockUid,
  onSelectBlock,
  onRemoveBlock,
  onRemoveRow,
  onUpdateFooterRepeat,
  onUpdatePageNumbers,
  onSetRowWidths,
  onUpdateBlockId,
  onUpdateBlockConfig,
  onUpdateDataField,
}: Props) {
  const [width] = pageSizeForFormat(format);
  const scaledWidth = mmToScaledPx(width, scale);

  return (
    <div
      data-edit-canvas
      className="mx-auto box-border w-full space-y-3"
      style={{ maxWidth: `${scaledWidth}px` }}
    >
      <div
        data-body-canvas
        className="space-y-3 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-3 shadow-[var(--builder-shadow)]"
      >
        <SortableContext
          items={model.rows.map((r) => `row-${r.uid}`)}
          strategy={verticalListSortingStrategy}
        >
          {model.rows.map((row, rowIndex) => (
            <Row
              key={row.uid}
              row={row}
              rowIndex={rowIndex}
              area="body"
              schema={schema}
              data={model.data}
              selectedBlockUid={selectedBlockUid}
              onSelectBlock={onSelectBlock}
              onRemoveBlock={onRemoveBlock}
              onRemoveRow={onRemoveRow}
              onSetRowWidths={onSetRowWidths}
              onUpdateBlockId={onUpdateBlockId}
              onUpdateBlockConfig={onUpdateBlockConfig}
              onUpdateDataField={onUpdateDataField}
            />
          ))}
        </SortableContext>
        <NewRowZone />
      </div>
      <FooterCanvas
        model={model}
        schema={schema}
        selectedBlockUid={selectedBlockUid}
        onSelectBlock={onSelectBlock}
        onRemoveBlock={onRemoveBlock}
        onRemoveRow={onRemoveRow}
        onSetRowWidths={onSetRowWidths}
        onUpdateBlockId={onUpdateBlockId}
        onUpdateBlockConfig={onUpdateBlockConfig}
        onUpdateDataField={onUpdateDataField}
        onUpdateFooterRepeat={onUpdateFooterRepeat}
        onUpdatePageNumbers={onUpdatePageNumbers}
      />
    </div>
  );
}
