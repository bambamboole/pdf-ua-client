import type { ReactNode } from "react";
import type { DataValue, TemplateDataLayers } from "../types";
import type { UpdateBlockData, UpdateDataField } from "./types";

interface FieldControlProps {
  blockId: string;
  field: string;
  label: string;
  value: unknown;
  data: TemplateDataLayers;
  onUpdateDataField: UpdateDataField;
  children: (props: { value: unknown; update: (value: unknown) => void }) => ReactNode;
}

export function FieldControl({
  blockId,
  field,
  label,
  value,
  data,
  onUpdateDataField,
  children,
}: FieldControlProps) {
  const example = Object.hasOwn(data.example[blockId] ?? {}, field);
  const locked = Object.hasOwn(data.constants[blockId] ?? {}, field);

  function update(nextValue: unknown): void {
    onUpdateDataField(blockId, field, nextValue, { example, locked });
  }

  return (
    <section className="min-w-0 overflow-hidden rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-3">
      <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
        <div className="min-w-0">
          <div className="text-xs font-medium text-[var(--builder-muted-strong)]">{label}</div>
          <div className="font-mono text-[10px] text-[var(--builder-muted)]">{field}</div>
        </div>
        <span className="flex items-center gap-3 text-xs text-[var(--builder-muted-strong)]">
          <label className="inline-flex items-center gap-1.5">
            <input
              type="checkbox"
              checked={example}
              onChange={(event) =>
                onUpdateDataField(blockId, field, value, {
                  example: event.currentTarget.checked,
                  locked,
                })
              }
            />
            Example
          </label>
          <label className="inline-flex items-center gap-1.5">
            <input
              type="checkbox"
              checked={locked}
              onChange={(event) =>
                onUpdateDataField(blockId, field, value, {
                  example,
                  locked: event.currentTarget.checked,
                })
              }
            />
            Lock
          </label>
        </span>
      </div>
      {children({ value, update })}
    </section>
  );
}

export function BlockDataControl({
  blockId,
  label,
  value,
  data,
  onUpdateBlockData,
  children,
}: {
  blockId: string;
  label: string;
  value: DataValue;
  data: TemplateDataLayers;
  onUpdateBlockData: UpdateBlockData;
  children: (props: { value: DataValue; update: (value: DataValue) => void }) => ReactNode;
}) {
  const example = Object.hasOwn(data.example, blockId);
  const locked = Object.hasOwn(data.constants, blockId);

  function update(nextValue: DataValue): void {
    onUpdateBlockData(blockId, nextValue, { example, locked });
  }

  return (
    <section className="min-w-0 overflow-hidden rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-3">
      <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
        <div className="min-w-0">
          <div className="text-xs font-medium text-[var(--builder-muted-strong)]">{label}</div>
          <div className="font-mono text-[10px] text-[var(--builder-muted)]">{blockId}</div>
        </div>
        <span className="flex items-center gap-3 text-xs text-[var(--builder-muted-strong)]">
          <label className="inline-flex items-center gap-1.5">
            <input
              type="checkbox"
              checked={example}
              onChange={(event) =>
                onUpdateBlockData(blockId, value, {
                  example: event.currentTarget.checked,
                  locked,
                })
              }
            />
            Example
          </label>
          <label className="inline-flex items-center gap-1.5">
            <input
              type="checkbox"
              checked={locked}
              onChange={(event) =>
                onUpdateBlockData(blockId, value, {
                  example,
                  locked: event.currentTarget.checked,
                })
              }
            />
            Lock
          </label>
        </span>
      </div>
      {children({ value, update })}
    </section>
  );
}
