export type Json = Record<string, unknown>;
export type DataValue = Record<string, unknown> | unknown[];
export type JsonSchema = Record<string, unknown>;

export interface TemplateBlock {
  type: string;
  id?: string;
  props?: Json;
  config?: Json;
}
export interface TemplateRow {
  blocks: TemplateBlock[];
  gap?: number;
}
export interface Template {
  version: number;
  config: Json;
  rows: TemplateRow[];
  data?: Partial<TemplateDataLayers>;
}

export interface EditorBlock {
  uid: string;
  id: string;
  type: string;
  config: Json;
  data: DataValue;
}
export interface EditorRow {
  uid: string;
  gap: number | null;
  blocks: EditorBlock[];
}
export interface EditorModel {
  version: number;
  config: Json;
  rows: EditorRow[];
  footerRows: EditorRow[];
  data: TemplateDataLayers;
}

export type DataMap = Record<string, DataValue>;
export interface TemplateDataLayers {
  example: DataMap;
  defaults: DataMap;
  constants: DataMap;
}

export type EditorArea = "body" | "footer";

export type DragData =
  | { source: "palette"; type: string }
  | { source: "block"; rowUid: string; area: EditorArea }
  | { source: "row"; rowUid: string; area: EditorArea }
  | { source: "newrow"; area: EditorArea };
