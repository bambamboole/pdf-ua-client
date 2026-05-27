import { GenericDataEditor, headingBlock, htmlBlock, textBlock } from "./generic";
import { imageBlock } from "./image";
import { keyValueBlock } from "./keyValue";
import { tableBlock } from "./table";
import type { BlockDefinition } from "./types";

const definitions: Record<string, BlockDefinition> = {
  image: imageBlock,
  "key-value": keyValueBlock,
  table: tableBlock,
  text: textBlock,
  heading: headingBlock,
  html: htmlBlock,
};

const genericDefinition: BlockDefinition = {
  DataEditor: GenericDataEditor,
};

export function blockDefinition(type: string): BlockDefinition {
  return definitions[type] ?? genericDefinition;
}
