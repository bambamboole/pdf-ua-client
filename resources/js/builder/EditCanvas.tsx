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
import { getBlockTitle } from "./lib/schema";
import BlockDataSummary from "./BlockDataSummary";
import ColumnResizer from "./ColumnResizer";
import InlineBlockEditor from "./InlineBlockEditor";
import type {
  DragData,
  EditorBlock,
  EditorRow,
  Json,
  JsonSchema,
  TemplateDataLayers,
} from "./types";

interface Props {
  model: { rows: EditorRow[]; data: TemplateDataLayers };
  schema: JsonSchema;
  format: string;
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

interface BlockBoxProps {
  block: EditorBlock;
  rowUid: string;
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
    data: { source: "block", rowUid } satisfies DragData,
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
    data: { source: "row", rowUid: row.uid } satisfies DragData,
  });
  const { setNodeRef: setDropRef } = useDroppable({
    id: `rowdrop-${row.uid}`,
    data: { source: "row", rowUid: row.uid } satisfies DragData,
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

function NewRowZone() {
  const { setNodeRef, isOver } = useDroppable({
    id: "new-row",
    data: { source: "newrow" } satisfies DragData,
  });
  return (
    <div
      ref={setNodeRef}
      className={`rounded-[var(--builder-radius)] border-2 border-dashed px-3 py-6 text-center text-sm font-medium transition ${isOver ? "border-[var(--builder-accent)] bg-[var(--builder-accent-soft)] text-[var(--builder-accent)]" : "border-[var(--builder-stroke-strong)] text-[var(--builder-muted)]"}`}
    >
      Drop a block here to add a new row
    </div>
  );
}

export default function EditCanvas({
  model,
  schema,
  format,
  selectedBlockUid,
  onSelectBlock,
  onRemoveBlock,
  onRemoveRow,
  onSetRowWidths,
  onUpdateBlockId,
  onUpdateBlockConfig,
  onUpdateDataField,
}: Props) {
  const [width] = pageSizeForFormat(format);

  return (
    <div
      data-edit-canvas
      className="mx-auto box-border w-full space-y-3 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-3 shadow-[var(--builder-shadow)]"
      style={{ maxWidth: `${width}mm` }}
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
  );
}
