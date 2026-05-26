import { blockDefinition } from "./blocks/registry";
import type { BlockDataEditorProps } from "./blocks/types";

export default function BlockDataEditor(props: BlockDataEditorProps) {
  const DataEditor = blockDefinition(props.block.type).DataEditor;

  return DataEditor ? <DataEditor {...props} /> : null;
}
