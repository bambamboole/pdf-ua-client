import type {
  BlockType,
  DataMap,
  EditorBlock,
  EditorModel,
  EditorRow,
  Json,
  Template,
} from "../types";

function uid(): string {
  return crypto.randomUUID();
}

function slugify(value: string): string {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function makeUnique(base: string, taken: Set<string>): string {
  if (!taken.has(base)) {
    return base;
  }
  let n = 2;
  while (taken.has(`${base}-${n}`)) {
    n += 1;
  }
  return `${base}-${n}`;
}

export function uniqueBlockId(model: EditorModel, type: string): string {
  const taken = new Set(model.rows.flatMap((r) => r.blocks.map((b) => b.id)));
  let n = 1;
  while (taken.has(`${type}-${n}`)) {
    n += 1;
  }
  return `${type}-${n}`;
}

export function fromTemplate(template: Template, data: DataMap = {}): EditorModel {
  return {
    version: template.version,
    config: template.config ?? {},
    rows: (template.rows ?? []).map(
      (row): EditorRow => ({
        uid: uid(),
        gap: row.gap ?? null,
        blocks: (row.blocks ?? []).map((block): EditorBlock => {
          const id = block.id ?? block.type;
          return {
            uid: uid(),
            id,
            type: block.type as BlockType,
            config: (block.config ?? {}) as Json,
            data: (data[id] ?? {}) as Json,
          };
        }),
      }),
    ),
  };
}

export function toTemplate(model: EditorModel): Template {
  return {
    version: model.version,
    config: model.config ?? {},
    rows: model.rows
      .filter((row) => row.blocks.length > 0)
      .map((row) => {
        const out: Template["rows"][number] = {
          blocks: row.blocks.map((b) => ({ type: b.type, id: b.id, config: b.config ?? {} })),
        };
        if (typeof row.gap === "number") {
          out.gap = row.gap;
        }
        return out;
      }),
  };
}

export function toDataMap(model: EditorModel): DataMap {
  const map: DataMap = {};
  for (const row of model.rows) {
    for (const block of row.blocks) {
      if (Object.keys(block.data ?? {}).length === 0) {
        continue;
      }

      map[block.id] = block.data ?? {};
    }
  }
  return map;
}

function newRow(block: EditorBlock): EditorRow {
  return { uid: uid(), gap: null, blocks: [block] };
}

export function addBlock(
  model: EditorModel,
  type: BlockType | string,
  opts: { rowUid?: string | null; index?: number | null; data?: Json } = {},
): EditorModel {
  const { rowUid = null, index = null, data = {} } = opts;
  const block: EditorBlock = {
    uid: uid(),
    id: uniqueBlockId(model, type),
    type: type as BlockType,
    config: {},
    data,
  };
  if (rowUid === null) {
    return { ...model, rows: [...model.rows, newRow(block)] };
  }
  return {
    ...model,
    rows: model.rows.map((row) => {
      if (row.uid !== rowUid) {
        return row;
      }
      const blocks = [...row.blocks];
      const at = index === null || index > blocks.length ? blocks.length : index;
      blocks.splice(at, 0, block);
      return { ...row, blocks };
    }),
  };
}

export function removeBlock(model: EditorModel, blockUid: string): EditorModel {
  return {
    ...model,
    rows: model.rows
      .map((row) => ({ ...row, blocks: row.blocks.filter((b) => b.uid !== blockUid) }))
      .filter((row) => row.blocks.length > 0),
  };
}

export function removeRow(model: EditorModel, rowUid: string): EditorModel {
  return { ...model, rows: model.rows.filter((row) => row.uid !== rowUid) };
}

export function moveBlock(
  model: EditorModel,
  blockUid: string,
  toRowUid: string | null,
  toIndex: number | null,
): EditorModel {
  let moving: EditorBlock | null = null;
  let rows = model.rows.map((row) => {
    const idx = row.blocks.findIndex((b) => b.uid === blockUid);
    if (idx === -1) {
      return { ...row, blocks: [...row.blocks] };
    }
    moving = row.blocks[idx];
    return { ...row, blocks: row.blocks.filter((b) => b.uid !== blockUid) };
  });
  if (!moving) {
    return model;
  }
  if (toRowUid === null) {
    rows.push(newRow(moving));
  } else {
    rows = rows.map((row) => {
      if (row.uid !== toRowUid) {
        return row;
      }
      const blocks = [...row.blocks];
      const at = toIndex === null || toIndex > blocks.length ? blocks.length : toIndex;
      blocks.splice(at, 0, moving as EditorBlock);
      return { ...row, blocks };
    });
  }
  return { ...model, rows: rows.filter((row) => row.blocks.length > 0) };
}

export function moveRow(model: EditorModel, rowUid: string, toIndex: number): EditorModel {
  const from = model.rows.findIndex((row) => row.uid === rowUid);
  if (from === -1) {
    return model;
  }
  const rows = [...model.rows];
  const [row] = rows.splice(from, 1);
  const at = toIndex < 0 ? 0 : toIndex > rows.length ? rows.length : toIndex;
  rows.splice(at, 0, row);
  return { ...model, rows };
}

export function setRowWidths(model: EditorModel, rowUid: string, widths: string[]): EditorModel {
  return {
    ...model,
    rows: model.rows.map((row) => {
      if (row.uid !== rowUid) {
        return row;
      }
      return {
        ...row,
        blocks: row.blocks.map((b, i) =>
          i < widths.length ? { ...b, config: { ...b.config, width: widths[i] } } : b,
        ),
      };
    }),
  };
}

function mapBlock(
  model: EditorModel,
  blockUid: string,
  fn: (b: EditorBlock) => EditorBlock,
): EditorModel {
  return {
    ...model,
    rows: model.rows.map((row) => ({
      ...row,
      blocks: row.blocks.map((b) => (b.uid === blockUid ? fn(b) : b)),
    })),
  };
}

export function updateBlockConfig(model: EditorModel, blockUid: string, config: Json): EditorModel {
  return mapBlock(model, blockUid, (b) => ({ ...b, config }));
}

export function updateBlockId(model: EditorModel, blockUid: string, rawId: string): EditorModel {
  const taken = new Set(
    model.rows.flatMap((r) => r.blocks.filter((b) => b.uid !== blockUid).map((b) => b.id)),
  );
  const id = makeUnique(slugify(rawId) || "block", taken);
  return mapBlock(model, blockUid, (b) => ({ ...b, id }));
}

export function updateTemplateConfig(model: EditorModel, config: Json): EditorModel {
  return { ...model, config };
}

export interface FoundBlock {
  row: EditorRow;
  block: EditorBlock;
  rowIndex: number;
  blockIndex: number;
}

export function findBlock(model: EditorModel, blockUid: string): FoundBlock | null {
  for (let rowIndex = 0; rowIndex < model.rows.length; rowIndex += 1) {
    const row = model.rows[rowIndex];
    const blockIndex = row.blocks.findIndex((b) => b.uid === blockUid);
    if (blockIndex !== -1) {
      return { row, block: row.blocks[blockIndex], rowIndex, blockIndex };
    }
  }
  return null;
}
