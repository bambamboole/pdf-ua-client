import type { ComponentType } from "react";
import type { DataValue, EditorBlock, Json, JsonSchema, TemplateDataLayers } from "../types";

export type UpdateDataField = (
  blockId: string,
  field: string,
  value: unknown,
  options: { example: boolean; locked: boolean },
) => void;

export type UpdateBlockData = (
  blockId: string,
  value: DataValue,
  options: { example: boolean; locked: boolean },
) => void;

export type ConfigFieldsComponent = ComponentType<{
  config: Json;
  onChange: (config: Json) => void;
}>;

export interface BlockDataEditorProps {
  block: EditorBlock;
  schema: JsonSchema;
  data: TemplateDataLayers;
  onUpdateDataField: UpdateDataField;
  onUpdateBlockData: UpdateBlockData;
}

export interface BlockSummaryProps {
  block: EditorBlock;
  data: TemplateDataLayers;
}

export interface BlockDefinition {
  DataEditor?: ComponentType<BlockDataEditorProps>;
  Summary?: ComponentType<BlockSummaryProps>;
  ConfigFields?: ConfigFieldsComponent;
}
