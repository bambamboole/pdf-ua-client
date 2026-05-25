export type BlockType =
  | "heading"
  | "text"
  | "html"
  | "image"
  | "spacer"
  | "divider"
  | "key-value"
  | "table";

export type Json = Record<string, unknown>;
export type JsonSchema = Record<string, unknown>;

export interface TemplateBlock {
  type: string;
  id?: string;
  props?: Json;
  config?: Json;
}
export interface TemplateRow {
  blocks: TemplateBlock[];
  columnWidths?: (string | number)[];
  gap?: number;
}
export interface Template {
  version: number;
  config: Json;
  rows: TemplateRow[];
}

export interface EditorBlock {
  uid: string;
  id: string;
  type: BlockType;
  config: Json;
  data: Json;
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
}

export type DataMap = Record<string, Json>;
