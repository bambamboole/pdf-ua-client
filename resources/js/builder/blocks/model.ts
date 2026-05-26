import type { Json, TemplateDataLayers } from "../types";
import { keyedFieldKeys, pruneDataFieldsForId, pruneDataRowsForId } from "./shared";

export function pruneDataForBlockConfig(
  type: string,
  layers: TemplateDataLayers,
  blockId: string,
  config: Json,
): TemplateDataLayers {
  if (type === "key-value") {
    return pruneDataFieldsForId(layers, blockId, keyedFieldKeys(config.fields));
  }

  if (type === "table") {
    return pruneDataRowsForId(layers, blockId, keyedFieldKeys(config.columns));
  }

  return layers;
}
