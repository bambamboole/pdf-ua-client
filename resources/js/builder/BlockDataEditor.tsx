import { useEffect, useState, type ChangeEvent, type ReactNode } from "react";
import { getBlockSubschemas } from "./lib/schema";
import { IMAGE_ACCEPT, imageFileError, imageFileToDataUrl } from "./lib/imageUpload";
import type { DataValue, EditorBlock, JsonSchema, TemplateDataLayers } from "./types";

type UpdateDataField = (
  blockId: string,
  field: string,
  value: unknown,
  options: { example: boolean; locked: boolean },
) => void;
type UpdateBlockData = (
  blockId: string,
  value: DataValue,
  options: { example: boolean; locked: boolean },
) => void;

interface Props {
  block: EditorBlock;
  schema: JsonSchema;
  data: TemplateDataLayers;
  onUpdateDataField: UpdateDataField;
  onUpdateBlockData: UpdateBlockData;
}

interface FieldProps {
  blockId: string;
  field: string;
  label: string;
  value: unknown;
  data: TemplateDataLayers;
  onUpdateDataField: UpdateDataField;
  children: (props: { value: unknown; update: (value: unknown) => void }) => ReactNode;
}

interface KeyValueField {
  key: string;
  label: string;
}

interface TableColumn {
  key: string;
  label: string;
}

export default function BlockDataEditor({
  block,
  schema,
  data,
  onUpdateDataField,
  onUpdateBlockData,
}: Props) {
  if (block.type === "image") {
    return <ImageDataEditor block={block} data={data} onUpdateDataField={onUpdateDataField} />;
  }

  if (block.type === "key-value") {
    return <KeyValueDataEditor block={block} data={data} onUpdateDataField={onUpdateDataField} />;
  }

  if (block.type === "table") {
    return <TableDataEditor block={block} data={data} onUpdateBlockData={onUpdateBlockData} />;
  }

  return (
    <GenericDataEditor
      block={block}
      schema={schema}
      data={data}
      onUpdateDataField={onUpdateDataField}
      onUpdateBlockData={onUpdateBlockData}
    />
  );
}

function ImageDataEditor({
  block,
  data,
  onUpdateDataField,
}: {
  block: EditorBlock;
  data: TemplateDataLayers;
  onUpdateDataField: UpdateDataField;
}) {
  return (
    <div data-inline-data-fields className="space-y-3">
      <FieldControl
        blockId={block.id}
        field="src"
        label="Image source"
        value={currentFieldValue(data, block.id, "src")}
        data={data}
        onUpdateDataField={onUpdateDataField}
      >
        {({ value, update }) => <ImageSourceInput value={stringValue(value)} onChange={update} />}
      </FieldControl>
      <FieldControl
        blockId={block.id}
        field="alt"
        label="Alt text"
        value={currentFieldValue(data, block.id, "alt")}
        data={data}
        onUpdateDataField={onUpdateDataField}
      >
        {({ value, update }) => (
          <input
            className={inputClass}
            value={stringValue(value)}
            onChange={(event) => update(event.currentTarget.value)}
          />
        )}
      </FieldControl>
    </div>
  );
}

function ImageSourceInput({
  value,
  onChange,
}: {
  value: string;
  onChange: (value: string) => void;
}) {
  const [message, setMessage] = useState<string | null>(null);

  async function handleFile(event: ChangeEvent<HTMLInputElement>): Promise<void> {
    const file = event.currentTarget.files?.[0];
    if (!file) {
      return;
    }

    const error = imageFileError(file);
    if (error) {
      setMessage(error);
      event.currentTarget.value = "";
      return;
    }

    onChange(await imageFileToDataUrl(file));
    setMessage(`${file.name} uploaded`);
    event.currentTarget.value = "";
  }

  return (
    <div data-image-source-input className="grid min-w-0 gap-2">
      {value ? (
        <div className="flex min-w-0 items-center gap-3 overflow-hidden rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-2">
          <img
            src={value}
            alt=""
            className="h-12 max-w-24 shrink-0 rounded border border-[var(--builder-stroke)] object-contain"
          />
          <span className="block min-w-0 flex-1 truncate font-mono text-[10px] text-[var(--builder-muted)]">
            {value}
          </span>
        </div>
      ) : null}
      <input
        className={inputClass}
        type="url"
        value={value}
        placeholder="https://example.com/logo.png or uploaded data URL"
        onChange={(event) => {
          setMessage(null);
          onChange(event.currentTarget.value);
        }}
      />
      <label className="inline-flex cursor-pointer items-center justify-center rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] px-2 py-1 text-xs font-medium text-[var(--builder-muted-strong)] transition hover:border-[var(--builder-accent)]">
        Upload
        <input
          className="sr-only"
          type="file"
          accept={IMAGE_ACCEPT}
          onChange={(event) => {
            void handleFile(event);
          }}
        />
      </label>
      <p className="text-[10px] leading-snug text-[var(--builder-muted)]">
        PNG, JPEG, WebP, GIF, or SVG. 200 KB max.
      </p>
      {message ? (
        <p className="text-[10px] leading-snug text-[var(--builder-muted-strong)]">{message}</p>
      ) : null}
    </div>
  );
}

function KeyValueDataEditor({
  block,
  data,
  onUpdateDataField,
}: {
  block: EditorBlock;
  data: TemplateDataLayers;
  onUpdateDataField: UpdateDataField;
}) {
  const fields = keyValueFields(block);

  if (fields.length === 0) {
    return (
      <div className="rounded-[var(--builder-radius)] border border-dashed border-[var(--builder-stroke)] p-3 text-xs text-[var(--builder-muted)]">
        Define key/value fields in Config.
      </div>
    );
  }

  return (
    <div data-inline-data-fields className="space-y-3">
      {fields.map((field) => (
        <FieldControl
          key={field.key}
          blockId={block.id}
          field={field.key}
          label={field.label}
          value={currentFieldValue(data, block.id, field.key)}
          data={data}
          onUpdateDataField={onUpdateDataField}
        >
          {({ value, update }) => (
            <input
              className={inputClass}
              value={stringValue(value)}
              onChange={(event) => update(event.currentTarget.value)}
            />
          )}
        </FieldControl>
      ))}
    </div>
  );
}

function TableDataEditor({
  block,
  data,
  onUpdateBlockData,
}: {
  block: EditorBlock;
  data: TemplateDataLayers;
  onUpdateBlockData: UpdateBlockData;
}) {
  const columns = tableColumns(block.config.columns);

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

function BlockDataControl({
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

function GenericDataEditor({ block, schema, data, onUpdateDataField }: Props) {
  const blockDataSchema = getBlockSubschemas(schema, block.type).props;
  const properties = blockDataSchema.properties;

  if (!properties || typeof properties !== "object" || Array.isArray(properties)) {
    return null;
  }

  return (
    <div data-inline-data-fields className="space-y-3">
      {Object.entries(properties as Record<string, JsonSchema>).map(([field, fieldSchema]) => (
        <FieldControl
          key={field}
          blockId={block.id}
          field={field}
          label={fieldTitle(field, fieldSchema)}
          value={currentFieldValue(data, block.id, field)}
          data={data}
          onUpdateDataField={onUpdateDataField}
        >
          {({ value, update }) =>
            field === "html" ? (
              <textarea
                className={`${inputClass} min-h-20 font-mono`}
                value={stringValue(value)}
                onChange={(event) => update(event.currentTarget.value)}
              />
            ) : (
              <input
                className={inputClass}
                value={stringValue(value)}
                onChange={(event) => update(event.currentTarget.value)}
              />
            )
          }
        </FieldControl>
      ))}
    </div>
  );
}

function FieldControl({
  blockId,
  field,
  label,
  value,
  data,
  onUpdateDataField,
  children,
}: FieldProps) {
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

function currentFieldValue(data: TemplateDataLayers, blockId: string, field: string): unknown {
  const constants = data.constants[blockId];
  const example = data.example[blockId];
  const defaults = data.defaults[blockId];

  if (isPlainObject(constants) && Object.hasOwn(constants, field)) {
    return constants[field];
  }

  if (isPlainObject(example) && Object.hasOwn(example, field)) {
    return example[field];
  }

  if (isPlainObject(defaults) && Object.hasOwn(defaults, field)) {
    return defaults[field];
  }

  return "";
}

function currentBlockValue(data: TemplateDataLayers, blockId: string): DataValue {
  if (Object.hasOwn(data.constants, blockId)) {
    return data.constants[blockId];
  }

  if (Object.hasOwn(data.example, blockId)) {
    return data.example[blockId];
  }

  if (Object.hasOwn(data.defaults, blockId)) {
    return data.defaults[blockId];
  }

  return [];
}

function keyValueFields(block: EditorBlock): KeyValueField[] {
  const fields = block.config.fields;
  if (!Array.isArray(fields)) {
    return [];
  }

  return fields
    .map((field) => {
      if (!field || typeof field !== "object") {
        return null;
      }

      const record = field as Record<string, unknown>;
      const key = stringValue(record.key);
      if (key === "") {
        return null;
      }

      return { key, label: stringValue(record.label) || key };
    })
    .filter((field): field is KeyValueField => field !== null);
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

function fieldTitle(field: string, schema: JsonSchema): string {
  return typeof schema.title === "string" ? schema.title : field;
}

function stringValue(value: unknown): string {
  return value == null ? "" : String(value);
}

function tableColumns(value: unknown): TableColumn[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((column) => {
      if (!column || typeof column !== "object") {
        return null;
      }

      const record = column as Record<string, unknown>;
      const key = stringValue(record.key);
      if (key === "") {
        return null;
      }

      return { key, label: stringValue(record.label) || key };
    })
    .filter((column): column is TableColumn => column !== null);
}

function rowsToJson(value: unknown): string {
  if (!Array.isArray(value)) {
    return "[]";
  }

  return JSON.stringify(value, null, 2);
}

const inputClass =
  "block min-w-0 w-full rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-field)] px-2 py-1 text-sm text-[var(--builder-field-ink)] focus:border-[var(--builder-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--builder-accent-soft)] disabled:bg-[var(--builder-surface)]";
