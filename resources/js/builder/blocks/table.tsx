import { useEffect, useState } from "react";
import TableConfigColumns from "../TableConfigColumns";
import type { DataValue } from "../types";
import { BlockDataControl } from "./dataControls";
import { keyPart, MutedSummary } from "./preview";
import {
  currentBlockValue,
  inputClass,
  isPlainObject,
  keyedFields,
  previewBlockValue,
  rowsToJson,
  stringValue,
} from "./shared";
import type {
  BlockConfigEditorProps,
  BlockDataEditorProps,
  BlockDefinition,
  BlockSummaryProps,
} from "./types";

function TableDataEditor({ block, data, onUpdateBlockData }: BlockDataEditorProps) {
  const columns = keyedFields(block.config.columns);

  if (columns.length === 0) {
    return (
      <div className="rounded-[var(--builder-radius)] border border-dashed border-[var(--builder-stroke)] p-3 text-xs text-[var(--builder-muted)]">
        Define table columns in Config.
      </div>
    );
  }

  return (
    <div data-inline-data-fields className="space-y-3">
      <BlockDataControl
        blockId={block.id}
        label="Rows"
        value={currentBlockValue(data, block.id)}
        data={data}
        onUpdateBlockData={onUpdateBlockData}
      >
        {({ value, update }) => (
          <div className="grid gap-2">
            <div className="flex flex-wrap gap-1">
              {columns.map((column) => (
                <span
                  key={column.key}
                  className="rounded-[var(--builder-radius)] bg-[var(--builder-panel)] px-2 py-1 text-[10px] font-medium text-[var(--builder-muted-strong)]"
                >
                  {column.label}
                </span>
              ))}
            </div>
            <TableJsonInput value={value} onChange={update} />
          </div>
        )}
      </BlockDataControl>
    </div>
  );
}

function TableJsonInput({
  value,
  onChange,
}: {
  value: DataValue;
  onChange: (value: DataValue) => void;
}) {
  const [draft, setDraft] = useState(() => rowsToJson(value));

  useEffect(() => {
    setDraft(rowsToJson(value));
  }, [value]);

  return (
    <textarea
      className={`${inputClass} min-h-36 font-mono`}
      value={draft}
      onChange={(event) => {
        const nextDraft = event.currentTarget.value;
        setDraft(nextDraft);

        try {
          const parsed = JSON.parse(nextDraft);
          if (Array.isArray(parsed)) {
            onChange(parsed);
          }
        } catch {
          return;
        }
      }}
    />
  );
}

function objectTableRows(
  value: unknown,
  columns: Array<{ key: string; label: string }>,
): string[][] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((row) => {
      if (!isPlainObject(row)) {
        return [];
      }

      return columns.map((column) => stringValue(row[column.key]));
    })
    .filter((row) => row.length > 0);
}

function TablePreview({ headers, rows }: { headers: string[]; rows: string[][] }) {
  return (
    <div className="mt-2 overflow-auto rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] text-xs">
      <table className="w-full min-w-max border-collapse">
        {headers.length > 0 ? (
          <thead>
            <tr className="bg-[var(--builder-surface)]">
              {headers.map((header) => (
                <th
                  key={header}
                  className="border-b border-[var(--builder-stroke)] px-2 py-1 text-left font-medium text-[var(--builder-muted-strong)]"
                >
                  {header}
                </th>
              ))}
            </tr>
          </thead>
        ) : null}
        <tbody>
          {rows.map((row) => (
            <tr
              key={keyPart(row)}
              className="border-b border-[var(--builder-stroke)] last:border-0"
            >
              {row.map((cell, cellIndex) => (
                <td
                  key={keyPart([cell, headers[cellIndex] ?? ""])}
                  className="px-2 py-1 text-[var(--builder-ink)]"
                >
                  {cell}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function TableSummary({ block, data }: BlockSummaryProps) {
  const columns = keyedFields(block.config.columns);
  const headers = columns.map((column) => column.label);
  const rows = objectTableRows(previewBlockValue(data, block.id), columns);

  return headers.length > 0 || rows.length > 0 ? (
    <TablePreview headers={headers} rows={rows} />
  ) : (
    <MutedSummary>0 cols × 0 rows</MutedSummary>
  );
}

function TableConfigEditor({
  block,
  configSchema,
  onUpdateBlockId,
  onUpdateBlockConfig,
  BlockIdControl,
  ConfigSettingsForm,
}: BlockConfigEditorProps) {
  return (
    <div className="space-y-3">
      <BlockIdControl block={block} onUpdateBlockId={onUpdateBlockId} />
      <TableConfigColumns
        config={block.config ?? {}}
        onChange={(config) => onUpdateBlockConfig(block.uid, config)}
      />
      <ConfigSettingsForm
        schema={configSchema}
        config={block.config ?? {}}
        onChange={(config) =>
          onUpdateBlockConfig(block.uid, {
            ...config,
            columns: block.config.columns,
            numberRows: block.config.numberRows,
            style: block.config.style,
          })
        }
      />
    </div>
  );
}

export const tableBlock: BlockDefinition = {
  DataEditor: TableDataEditor,
  Summary: TableSummary,
  ConfigEditor: TableConfigEditor,
};
