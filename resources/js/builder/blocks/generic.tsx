import { getBlockSubschemas } from "../lib/schema";
import type { JsonSchema } from "../types";
import { FieldControl } from "./dataControls";
import { MutedSummary, truncate } from "./preview";
import {
  currentFieldValue,
  inputClass,
  isPlainObject,
  previewBlockValue,
  stringValue,
} from "./shared";
import type { BlockDataEditorProps, BlockDefinition, BlockSummaryProps } from "./types";

export function GenericDataEditor({
  block,
  schema,
  data,
  onUpdateDataField,
}: BlockDataEditorProps) {
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

function TextSummary({ block, data }: BlockSummaryProps) {
  const blockData = previewBlockValue(data, block.id);
  const record = isPlainObject(blockData) ? blockData : {};

  return <MutedSummary>{truncate(stringValue(record.text))}</MutedSummary>;
}

function HtmlSummary() {
  return <MutedSummary>HTML</MutedSummary>;
}

function fieldTitle(field: string, schema: JsonSchema): string {
  return typeof schema.title === "string" ? schema.title : field;
}

export const textBlock: BlockDefinition = {
  DataEditor: GenericDataEditor,
  Summary: TextSummary,
};

export const headingBlock: BlockDefinition = {
  DataEditor: GenericDataEditor,
  Summary: TextSummary,
};

export const htmlBlock: BlockDefinition = {
  DataEditor: GenericDataEditor,
  Summary: HtmlSummary,
};
