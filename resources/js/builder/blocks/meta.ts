import type { Json, TemplateDataLayers } from "../types";
import { keyedFieldKeys, pruneDataFieldsForId, pruneDataRowsForId } from "./shared";

export interface BlockMeta {
  type: string;
  managedConfigKeys: string[];
  pruneDataForConfig: (
    layers: TemplateDataLayers,
    blockId: string,
    config: Json,
  ) => TemplateDataLayers;
}

const metas = [
  {
    type: "key-value",
    managedConfigKeys: ["fields"],
    pruneDataForConfig: (layers, blockId, config) =>
      pruneDataFieldsForId(layers, blockId, keyedFieldKeys(config.fields)),
  },
  {
    type: "table",
    managedConfigKeys: ["columns", "numberRows", "style"],
    pruneDataForConfig: (layers, blockId, config) =>
      pruneDataRowsForId(layers, blockId, keyedFieldKeys(config.columns)),
  },
] satisfies BlockMeta[];

const fallback: BlockMeta = {
  type: "*",
  managedConfigKeys: [],
  pruneDataForConfig: (layers) => layers,
};

const metasByType = new Map(metas.map((meta) => [meta.type, meta]));

export function blockMeta(type: string): BlockMeta {
  return metasByType.get(type) ?? fallback;
}
