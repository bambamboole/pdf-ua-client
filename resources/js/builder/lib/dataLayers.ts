import type { DataMap, DataValue } from "../types";

export function isPlainObject(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

function mergeJson(base: unknown, override: unknown): unknown {
  if (!isPlainObject(base) || !isPlainObject(override)) {
    return override;
  }

  const merged: Record<string, unknown> = { ...base };
  for (const [key, value] of Object.entries(override)) {
    merged[key] = mergeJson(merged[key], value);
  }

  return merged;
}

export function cleanDataMap(data: DataMap): DataMap {
  const map: DataMap = {};

  for (const [id, value] of Object.entries(data)) {
    if (Object.keys(value ?? {}).length > 0) {
      map[id] = value;
    }
  }

  return map;
}

export function mergeDataMaps(...maps: DataMap[]): DataMap {
  const merged: DataMap = {};

  for (const map of maps) {
    for (const [id, value] of Object.entries(map)) {
      merged[id] = mergeJson(merged[id] ?? {}, value) as DataValue;
    }
  }

  return cleanDataMap(merged);
}

export function omitDataId(data: DataMap, id: string): DataMap {
  if (!(id in data)) {
    return data;
  }

  const next = { ...data };
  delete next[id];

  return next;
}
