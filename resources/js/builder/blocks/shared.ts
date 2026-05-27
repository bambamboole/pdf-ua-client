import type { DataMap, DataValue, TemplateDataLayers } from "../types";
import { isPlainObject, mergeDataMaps, omitDataId } from "../lib/dataLayers";

export { isPlainObject };

export interface KeyedField {
  key: string;
  label: string;
}

export function stringValue(value: unknown): string {
  return value == null ? "" : String(value);
}

export function keyedFields(value: unknown): KeyedField[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((field) => {
      if (!isPlainObject(field)) {
        return null;
      }

      const key = stringValue(field.key);
      if (key === "") {
        return null;
      }

      return { key, label: stringValue(field.label) || key };
    })
    .filter((field): field is KeyedField => field !== null);
}

export function keyedFieldKeys(value: unknown): Set<string> {
  return new Set(keyedFields(value).map((field) => field.key));
}

export function currentFieldValue(
  data: TemplateDataLayers,
  blockId: string,
  field: string,
): unknown {
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

export function currentBlockValue(data: TemplateDataLayers, blockId: string): DataValue {
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

export function previewBlockValue(data: TemplateDataLayers, blockId: string): DataValue {
  return mergeDataMaps(data.defaults, data.example, data.constants)[blockId] ?? {};
}

export function pruneDataFieldsForId(
  layers: TemplateDataLayers,
  blockId: string,
  keys: Set<string>,
): TemplateDataLayers {
  return {
    example: pruneDataMapFields(layers.example, blockId, keys),
    defaults: pruneDataMapFields(layers.defaults, blockId, keys),
    constants: pruneDataMapFields(layers.constants, blockId, keys),
  };
}

function pruneDataMapFields(data: DataMap, blockId: string, keys: Set<string>): DataMap {
  const blockData = data[blockId];
  if (!isPlainObject(blockData)) {
    return data;
  }

  const nextBlockData = Object.fromEntries(
    Object.entries(blockData).filter(([field]) => keys.has(field)),
  );

  if (Object.keys(nextBlockData).length === Object.keys(blockData).length) {
    return data;
  }

  return Object.keys(nextBlockData).length > 0
    ? { ...data, [blockId]: nextBlockData }
    : omitDataId(data, blockId);
}

export function pruneDataRowsForId(
  layers: TemplateDataLayers,
  blockId: string,
  keys: Set<string>,
): TemplateDataLayers {
  return {
    example: pruneDataMapRows(layers.example, blockId, keys),
    defaults: pruneDataMapRows(layers.defaults, blockId, keys),
    constants: pruneDataMapRows(layers.constants, blockId, keys),
  };
}

function pruneDataMapRows(data: DataMap, blockId: string, keys: Set<string>): DataMap {
  const blockData = data[blockId];
  if (!Array.isArray(blockData)) {
    return data;
  }

  const rows = blockData.map((row) => {
    if (!isPlainObject(row)) {
      return row;
    }

    return Object.fromEntries(Object.entries(row).filter(([field]) => keys.has(field)));
  });

  return { ...data, [blockId]: rows };
}

export function rowsToJson(value: unknown): string {
  if (!Array.isArray(value)) {
    return "[]";
  }

  return JSON.stringify(value, null, 2);
}

export const inputClass =
  "block min-w-0 w-full rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-field)] px-2 py-1 text-sm text-[var(--builder-field-ink)] focus:border-[var(--builder-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--builder-accent-soft)] disabled:bg-[var(--builder-surface)]";
