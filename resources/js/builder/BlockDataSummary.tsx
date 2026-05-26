import { blockDefinition } from "./blocks/registry";
import type { EditorBlock, TemplateDataLayers } from "./types";

export default function BlockDataSummary({
  block,
  data,
}: {
  block: EditorBlock;
  data: TemplateDataLayers;
}) {
  const Summary = blockDefinition(block.type).Summary;

  return Summary ? <Summary block={block} data={data} /> : null;
}
