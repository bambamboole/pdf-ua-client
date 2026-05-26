import { GenericDataEditor, GenericSummary } from "./generic";
import { imageBlock } from "./image";
import { keyValueBlock } from "./keyValue";
import { tableBlock } from "./table";
import type { BlockDefinition } from "./types";

const definitions = [imageBlock, keyValueBlock, tableBlock] satisfies BlockDefinition[];
const definitionsByType = new Map(definitions.map((definition) => [definition.type, definition]));

const genericDefinition: BlockDefinition = {
  type: "*",
  DataEditor: GenericDataEditor,
  Summary: GenericSummary,
};

export function blockDefinition(type: string): BlockDefinition {
  return definitionsByType.get(type) ?? genericDefinition;
}
