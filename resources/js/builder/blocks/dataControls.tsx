import type { ReactNode } from "react";
import type { DataValue, TemplateDataLayers } from "../types";
import type { UpdateBlockData, UpdateDataField } from "./types";

interface DataLayerSectionProps {
  label: string;
  subtitle: string;
  example: boolean;
  locked: boolean;
  onToggleExample: (checked: boolean) => void;
  onToggleLocked: (checked: boolean) => void;
  children: ReactNode;
}

function DataLayerSection({
  label,
  subtitle,
  example,
  locked,
  onToggleExample,
  onToggleLocked,
  children,
}: DataLayerSectionProps) {
  return (
    <section className="min-w-0 overflow-hidden rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-3">
      <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
        <div className="min-w-0">
          <div className="text-xs font-medium text-[var(--builder-muted-strong)]">{label}</div>
          <div className="font-mono text-[10px] text-[var(--builder-muted)]">{subtitle}</div>
        </div>
        <span className="flex items-center gap-3 text-xs text-[var(--builder-muted-strong)]">
          <label className="inline-flex items-center gap-1.5">
            <input
              type="checkbox"
              checked={example}
              onChange={(event) => onToggleExample(event.currentTarget.checked)}
            />
            Example
          </label>
          <label className="inline-flex items-center gap-1.5">
            <input
              type="checkbox"
              checked={locked}
              onChange={(event) => onToggleLocked(event.currentTarget.checked)}
            />
            Lock
          </label>
        </span>
      </div>
      {children}
    </section>
  );
}

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
    <DataLayerSection
      label={label}
      subtitle={field}
      example={example}
      locked={locked}
      onToggleExample={(checked) =>
        onUpdateDataField(blockId, field, value, { example: checked, locked })
      }
      onToggleLocked={(checked) =>
        onUpdateDataField(blockId, field, value, { example, locked: checked })
      }
    >
      {children({ value, update })}
    </DataLayerSection>
  );
}

interface BlockDataControlProps {
  blockId: string;
  label: string;
  value: DataValue;
  data: TemplateDataLayers;
  onUpdateBlockData: UpdateBlockData;
  children: (props: { value: DataValue; update: (value: DataValue) => void }) => ReactNode;
}

export function BlockDataControl({
  blockId,
  label,
  value,
  data,
  onUpdateBlockData,
  children,
}: BlockDataControlProps) {
  const example = Object.hasOwn(data.example, blockId);
  const locked = Object.hasOwn(data.constants, blockId);

  function update(nextValue: DataValue): void {
    onUpdateBlockData(blockId, nextValue, { example, locked });
  }

  return (
    <DataLayerSection
      label={label}
      subtitle={blockId}
      example={example}
      locked={locked}
      onToggleExample={(checked) => onUpdateBlockData(blockId, value, { example: checked, locked })}
      onToggleLocked={(checked) => onUpdateBlockData(blockId, value, { example, locked: checked })}
    >
      {children({ value, update })}
    </DataLayerSection>
  );
}
