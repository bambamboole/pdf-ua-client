import { Fragment, useRef, type CSSProperties } from "react";
import { useDroppable } from "@dnd-kit/core";
import {
  SortableContext,
  useSortable,
  horizontalListSortingStrategy,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { humanizeType } from "./lib/schema";
import { parseWidths } from "./lib/columns";
import BlockDataSummary from "./BlockDataSummary";
import ColumnResizer from "./ColumnResizer";
import type { EditorBlock } from "./types";

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
  selectedBlockUid: string | null;
  onSelectBlock: (uid: string) => void;
  onRemoveBlock: (uid: string) => void;
  onRemoveRow: (uid: string) => void;
  onSetRowWidths: (rowUid: string, widths: string[]) => void;
}

interface BlockBoxProps {
  block: CanvasBlock;
  rowUid: string;
  selected: boolean;
  style?: CSSProperties;
  onSelect: (uid: string) => void;
  onRemove: (uid: string) => void;
}

function BlockBox({
  block,
  rowUid,
  selected,
  style: layoutStyle,
  onSelect,
  onRemove,
}: BlockBoxProps) {
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
      className={`${layoutStyle ? "" : "flex-1"} rounded border bg-white px-3 py-2 text-sm ${selected ? "border-blue-500 ring-1 ring-blue-300" : "border-gray-200"} ${isDragging ? "opacity-50" : ""}`}
    >
      <div className="flex items-center justify-between gap-2">
        <span {...listeners} {...attributes} className="cursor-grab font-medium text-gray-800">
          ⠿ {humanizeType(block.type)}
        </span>
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            onRemove(block.uid);
          }}
          className="text-gray-400 hover:text-red-600"
        >
          ✕
        </button>
      </div>
      <BlockDataSummary block={block as unknown as EditorBlock} />
    </div>
  );
}

interface RowProps {
  row: CanvasRow;
  selectedBlockUid: string | null;
  onSelectBlock: (uid: string) => void;
  onRemoveBlock: (uid: string) => void;
  onRemoveRow: (uid: string) => void;
  onSetRowWidths: (rowUid: string, widths: string[]) => void;
}

function Row({
  row,
  selectedBlockUid,
  onSelectBlock,
  onRemoveBlock,
  onRemoveRow,
  onSetRowWidths,
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
  const percents = parseWidths(widths, row.blocks.length);

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
        className="flex gap-2"
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
                style={
                  widths ? { flexBasis: `${percents[i]}%`, flexGrow: 0, flexShrink: 0 } : undefined
                }
                onSelect={onSelectBlock}
                onRemove={onRemoveBlock}
              />
              {i < row.blocks.length - 1 ? (
                <ColumnResizer
                  key={`rz-${i}`}
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
  selectedBlockUid,
  onSelectBlock,
  onRemoveBlock,
  onRemoveRow,
  onSetRowWidths,
}: Props) {
  return (
    <div className="space-y-3">
      <SortableContext
        items={model.rows.map((r) => `row-${r.uid}`)}
        strategy={verticalListSortingStrategy}
      >
        {model.rows.map((row) => (
          <Row
            key={row.uid}
            row={row}
            selectedBlockUid={selectedBlockUid}
            onSelectBlock={onSelectBlock}
            onRemoveBlock={onRemoveBlock}
            onRemoveRow={onRemoveRow}
            onSetRowWidths={onSetRowWidths}
          />
        ))}
      </SortableContext>
      <NewRowZone />
    </div>
  );
}
