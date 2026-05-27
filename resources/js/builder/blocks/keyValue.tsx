import KeyValueConfigFields from "../KeyValueConfigFields";
import { FieldControl } from "./dataControls";
import { keyPart, MutedSummary } from "./preview";
import {
  currentFieldValue,
  inputClass,
  isPlainObject,
  keyedFields,
  previewBlockValue,
  stringValue,
} from "./shared";
import type {
  BlockConfigEditorProps,
  BlockDataEditorProps,
  BlockDefinition,
  BlockSummaryProps,
} from "./types";

function KeyValueDataEditor({ block, data, onUpdateDataField }: BlockDataEditorProps) {
  const fields = keyedFields(block.config.fields);

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

function KeyValuePreview({ entries }: { entries: Array<{ label: string; value: string }> }) {
  return (
    <div className="mt-2 overflow-hidden rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] text-xs">
      <table className="w-full table-fixed border-collapse">
        <tbody>
          {entries.map((entry) => (
            <tr
              key={keyPart([entry.label, entry.value])}
              className="border-b border-[var(--builder-stroke)] last:border-0"
            >
              <th className="w-2/5 bg-[var(--builder-surface)] px-2 py-1 text-left font-medium text-[var(--builder-muted-strong)]">
                {entry.label}
              </th>
              <td className="px-2 py-1 text-[var(--builder-ink)]">{entry.value}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function KeyValueSummary({ block, data }: BlockSummaryProps) {
  const blockData = previewBlockValue(data, block.id);
  const record = isPlainObject(blockData) ? blockData : {};
  const entries = keyedFields(block.config.fields).map((field) => ({
    label: field.label,
    value: stringValue(record[field.key]),
  }));

  return entries.length > 0 ? (
    <KeyValuePreview entries={entries} />
  ) : (
    <MutedSummary>0 entries</MutedSummary>
  );
}

function KeyValueConfigEditor({
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
      <KeyValueConfigFields
        config={block.config ?? {}}
        onChange={(config) => onUpdateBlockConfig(block.uid, config)}
      />
      <ConfigSettingsForm
        schema={configSchema}
        config={block.config ?? {}}
        onChange={(config) =>
          onUpdateBlockConfig(block.uid, { ...config, fields: block.config.fields })
        }
      />
    </div>
  );
}

export const keyValueBlock: BlockDefinition = {
  DataEditor: KeyValueDataEditor,
  Summary: KeyValueSummary,
  ConfigEditor: KeyValueConfigEditor,
};
