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
import BlockDataSummary from "./BlockDataSummary";
import ColumnResizer from "./ColumnResizer";
import InlineBlockEditor from "./InlineBlockEditor";
import type { EditorBlock, Json, JsonSchema } from "./types";

interface CanvasBlock {
  uid: string;
  id: string;
  type: string;
  config: Record<string, unknown>;
  data: Record<string, unknown>;
}

interface CanvasRow {
  uid: string;
  gap: number | null;
  blocks: CanvasBlock[];
}

interface Props {
  model: { rows: CanvasRow[] };
  schema: JsonSchema;
  format: string;
  selectedBlockUid: string | null;
  onSelectBlock: (uid: string) => void;
  onRemoveBlock: (uid: string) => void;
  onRemoveRow: (uid: string) => void;
  onSetRowWidths: (rowUid: string, widths: string[]) => void;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
}

interface BlockBoxProps {
  block: CanvasBlock;
  rowUid: string;
  selected: boolean;
  initiallyOpen: boolean;
  style?: CSSProperties;
  onSelect: (uid: string) => void;
  onRemove: (uid: string) => void;
  schema: JsonSchema;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
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
  onUpdateBlockId,
  onUpdateBlockConfig,
}: BlockBoxProps) {
  const [settingsOpen, setSettingsOpen] = useState(initiallyOpen);
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: block.uid,
    data: { source: "block", rowUid },
  });
  const style = { transform: CSS.Transform.toString(transform), transition, ...layoutStyle };

  return (
    <div
      ref={setNodeRef}
      style={style}
      onClick={() => onSelect(block.uid)}
      onPointerDown={() => onSelect(block.uid)}
      onPointerDownCapture={() => onSelect(block.uid)}
      data-builder-block
      className={`${layoutStyle ? "" : "flex-1"} rounded border bg-white px-3 py-2 text-sm ${selected ? "border-blue-500 ring-1 ring-blue-300" : "border-gray-200"} ${isDragging ? "opacity-50" : ""}`}
    >
      <div className="flex items-center justify-between gap-2">
        <span
          {...listeners}
          {...attributes}
          aria-label="Move block"
          title="Move block"
          className="cursor-grab text-gray-400"
        >
          ⠿
        </span>
        <span className="flex items-center gap-2">
          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              onRemove(block.uid);
            }}
            onPointerDown={(e) => e.stopPropagation()}
            className="text-gray-400 hover:text-red-600"
          >
            ✕
          </button>
        </span>
      </div>
      <BlockDataSummary block={block as unknown as EditorBlock} />
      <details
        data-inline-block-details
        open={settingsOpen}
        className="mt-3 border-t border-gray-100 pt-2"
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
          className="cursor-pointer text-xs font-medium text-gray-500 hover:text-gray-800"
        >
          More
        </summary>
        <InlineBlockEditor
          block={block as unknown as EditorBlock}
          schema={schema}
          onUpdateBlockId={onUpdateBlockId}
          onUpdateBlockConfig={onUpdateBlockConfig}
        />
      </details>
    </div>
  );
}

interface RowProps {
  row: CanvasRow;
  rowIndex: number;
  schema: JsonSchema;
  selectedBlockUid: string | null;
  onSelectBlock: (uid: string) => void;
  onRemoveBlock: (uid: string) => void;
  onRemoveRow: (uid: string) => void;
  onSetRowWidths: (rowUid: string, widths: string[]) => void;
  onUpdateBlockId: (uid: string, id: string) => void;
  onUpdateBlockConfig: (uid: string, config: Json) => void;
}

function Row({
  row,
  rowIndex,
  schema,
  selectedBlockUid,
  onSelectBlock,
  onRemoveBlock,
  onRemoveRow,
  onSetRowWidths,
  onUpdateBlockId,
  onUpdateBlockConfig,
}: RowProps) {
  const { attributes, listeners, setNodeRef, transform, transition } = useSortable({
    id: `row-${row.uid}`,
    data: { source: "row", rowUid: row.uid },
  });
  const { setNodeRef: setDropRef } = useDroppable({
    id: `rowdrop-${row.uid}`,
    data: { source: "row", rowUid: row.uid },
  });
  const style = { transform: CSS.Transform.toString(transform), transition };
  const containerRef = useRef<HTMLDivElement>(null);

  const rawWidths = row.blocks.map((b) => b.config.width as string | number | undefined);
  const widths = rawWidths.every((w) => w != null) ? (rawWidths as (string | number)[]) : null;
  const gridTemplateColumns = gridTemplateForWidths(widths, row.blocks.length);

  return (
    <div ref={setNodeRef} style={style} className="rounded border border-gray-200 bg-gray-50 p-2">
      <div className="mb-1 flex items-center justify-between text-xs text-gray-400">
        <span {...listeners} {...attributes} className="cursor-grab">
          ⠿ row
        </span>
        <span className="flex gap-2">
          <button type="button" onClick={() => onRemoveRow(row.uid)} className="hover:text-red-600">
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
                onUpdateBlockId={onUpdateBlockId}
                onUpdateBlockConfig={onUpdateBlockConfig}
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
  const { setNodeRef, isOver } = useDroppable({ id: "new-row", data: { source: "newrow" } });
  return (
    <div
      ref={setNodeRef}
      className={`rounded border-2 border-dashed px-3 py-6 text-center text-sm ${isOver ? "border-blue-400 text-blue-500" : "border-gray-300 text-gray-400"}`}
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
}: Props) {
  const [width] = pageSizeForFormat(format);

  return (
    <div data-edit-canvas className="mx-auto w-full space-y-3" style={{ maxWidth: `${width}mm` }}>
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
            selectedBlockUid={selectedBlockUid}
            onSelectBlock={onSelectBlock}
            onRemoveBlock={onRemoveBlock}
            onRemoveRow={onRemoveRow}
            onSetRowWidths={onSetRowWidths}
            onUpdateBlockId={onUpdateBlockId}
            onUpdateBlockConfig={onUpdateBlockConfig}
          />
        ))}
      </SortableContext>
      <NewRowZone />
    </div>
  );
}
