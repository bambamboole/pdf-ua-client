import type { Json, TemplateDataLayers } from "../types";
import { keyedFieldKeys, pruneDataFieldsForId, pruneDataRowsForId } from "./shared";

export interface BlockMeta {
  managedConfigKeys: string[];
  pruneDataForConfig: (
    layers: TemplateDataLayers,
    blockId: string,
    config: Json,
  ) => TemplateDataLayers;
}

const metas: Record<string, BlockMeta> = {
  "key-value": {
    managedConfigKeys: ["fields"],
    pruneDataForConfig: (layers, blockId, config) =>
      pruneDataFieldsForId(layers, blockId, keyedFieldKeys(config.fields)),
  },
  table: {
    managedConfigKeys: ["columns", "numberRows", "style"],
    pruneDataForConfig: (layers, blockId, config) =>
      pruneDataRowsForId(layers, blockId, keyedFieldKeys(config.columns)),
  },
};

const fallback: BlockMeta = {
  managedConfigKeys: [],
  pruneDataForConfig: (layers) => layers,
};

export function blockMeta(type: string): BlockMeta {
  return metas[type] ?? fallback;
}
