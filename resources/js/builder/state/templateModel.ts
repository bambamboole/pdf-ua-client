import type {
  DataMap,
  DataValue,
  EditorBlock,
  EditorArea,
  EditorModel,
  EditorRow,
  Json,
  Template,
  TemplateDataLayers,
} from "../types";
import { blockMeta } from "../blocks/meta";
import { cleanDataMap, isPlainObject, mergeDataMaps, omitDataId } from "../lib/dataLayers";

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
  const taken = new Set(allRows(model).flatMap((r) => r.blocks.map((b) => b.id)));
  let n = 1;
  while (taken.has(`${type}-${n}`)) {
    n += 1;
  }
  return `${type}-${n}`;
}

export function fromTemplate(template: Template, data: DataMap = {}): EditorModel {
  const layers = normalizeDataLayers(template.data, data);
  const footerRows = footerRowsFromConfig(template.config);

  return {
    version: template.version,
    config: template.config ?? {},
    data: layers,
    rows: rowsFromTemplate(template.rows ?? []),
    footerRows: rowsFromTemplate(footerRows),
  };
}

export function toTemplate(model: EditorModel): Template {
  const data = compactDataLayers(model.data);

  return {
    version: model.version,
    config: configWithFooterRows(model.config ?? {}, rowsToTemplate(model.footerRows)),
    rows: rowsToTemplate(model.rows),
    ...(Object.keys(data).length > 0 ? { data } : {}),
  };
}

export function toDataMap(model: EditorModel): DataMap {
  return cleanDataMap(model.data.example);
}

export function previewDataMap(model: EditorModel): DataMap {
  return mergeDataMaps(model.data.defaults, model.data.example, model.data.constants);
}

function normalizeDataLayers(
  layers: Partial<TemplateDataLayers> | undefined,
  legacyExampleData: DataMap,
): TemplateDataLayers {
  return {
    example: cleanDataMap({ ...layers?.example, ...legacyExampleData }),
    defaults: cleanDataMap(layers?.defaults ?? {}),
    constants: cleanDataMap(layers?.constants ?? {}),
  };
}

function compactDataLayers(layers: TemplateDataLayers): Partial<TemplateDataLayers> {
  return Object.fromEntries(
    Object.entries(layers)
      .map(([key, value]) => [key, cleanDataMap(value)])
      .filter(([, value]) => Object.keys(value as DataMap).length > 0),
  ) as Partial<TemplateDataLayers>;
}

function newRow(block: EditorBlock): EditorRow {
  return { uid: uid(), gap: null, blocks: [block] };
}

export function addBlock(
  model: EditorModel,
  type: string,
  opts: {
    rowUid?: string | null;
    index?: number | null;
    data?: DataValue;
    area?: "body" | "footer";
  } = {},
): EditorModel {
  const { rowUid = null, index = null, data = {}, area = "body" } = opts;
  const block: EditorBlock = {
    uid: uid(),
    id: uniqueBlockId(model, type),
    type,
    config: {},
  };
  const dataLayers =
    Object.keys(data).length > 0
      ? { ...model.data, example: { ...model.data.example, [block.id]: data } }
      : model.data;

  if (rowUid === null) {
    return area === "footer"
      ? { ...model, data: dataLayers, footerRows: [...model.footerRows, newRow(block)] }
      : { ...model, data: dataLayers, rows: [...model.rows, newRow(block)] };
  }
  const targetRows = area === "footer" ? model.footerRows : model.rows;
  const nextRows = targetRows.map((row) => {
    if (row.uid !== rowUid) {
      return row;
    }
    const blocks = [...row.blocks];
    const at = index === null || index > blocks.length ? blocks.length : index;
    blocks.splice(at, 0, block);
    return { ...row, blocks };
  });

  return {
    ...model,
    data: dataLayers,
    ...(area === "footer" ? { footerRows: nextRows } : { rows: nextRows }),
  };
}

export function removeBlock(model: EditorModel, blockUid: string): EditorModel {
  const block = findBlock(model, blockUid)?.block;

  return {
    ...model,
    data: block ? removeDataForId(model.data, block.id) : model.data,
    rows: removeBlockFromRows(model.rows, blockUid),
    footerRows: removeBlockFromRows(model.footerRows, blockUid),
  };
}

export function removeRow(model: EditorModel, rowUid: string): EditorModel {
  const removedRow = allRows(model).find((candidate) => candidate.uid === rowUid);
  const data = removedRow
    ? removedRow.blocks.reduce((layers, block) => removeDataForId(layers, block.id), model.data)
    : model.data;

  return {
    ...model,
    data,
    rows: model.rows.filter((candidate) => candidate.uid !== rowUid),
    footerRows: model.footerRows.filter((candidate) => candidate.uid !== rowUid),
  };
}

export function moveBlock(
  model: EditorModel,
  blockUid: string,
  toRowUid: string | null,
  toIndex: number | null,
  toArea: EditorArea = "body",
): EditorModel {
  let moving: EditorBlock | null = null;
  const takeMovingBlock = (rows: EditorRow[]): EditorRow[] =>
    rows.map((row) => {
      const idx = row.blocks.findIndex((b) => b.uid === blockUid);
      if (idx === -1) {
        return { ...row, blocks: [...row.blocks] };
      }
      moving = row.blocks[idx];
      return { ...row, blocks: row.blocks.filter((b) => b.uid !== blockUid) };
    });

  const placeMovingBlock = (rows: EditorRow[]): EditorRow[] => {
    if (!moving) {
      return rows;
    }

    if (toRowUid === null) {
      return [...rows, newRow(moving)];
    }

    return rows.map((row) => {
      if (row.uid !== toRowUid) {
        return row;
      }
      const blocks = [...row.blocks];
      const at = toIndex === null || toIndex > blocks.length ? blocks.length : toIndex;
      blocks.splice(at, 0, moving as EditorBlock);
      return { ...row, blocks };
    });
  };

  let rows = takeMovingBlock(model.rows);
  let footerRows = takeMovingBlock(model.footerRows);

  if (!moving) {
    return model;
  }

  if (toArea === "footer") {
    footerRows = placeMovingBlock(footerRows);
  } else {
    rows = placeMovingBlock(rows);
  }

  return {
    ...model,
    rows: rows.filter((row) => row.blocks.length > 0),
    footerRows: footerRows.filter((row) => row.blocks.length > 0),
  };
}

export function moveRow(
  model: EditorModel,
  rowUid: string,
  toIndex: number,
  area: EditorArea = "body",
): EditorModel {
  const targetRows = area === "footer" ? model.footerRows : model.rows;
  const from = targetRows.findIndex((row) => row.uid === rowUid);
  if (from === -1) {
    return model;
  }
  const rows = [...targetRows];
  const [row] = rows.splice(from, 1);
  const at = toIndex < 0 ? 0 : toIndex > rows.length ? rows.length : toIndex;
  rows.splice(at, 0, row);

  return area === "footer" ? { ...model, footerRows: rows } : { ...model, rows };
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
    footerRows: model.footerRows.map((row) => {
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
    footerRows: model.footerRows.map((row) => ({
      ...row,
      blocks: row.blocks.map((b) => (b.uid === blockUid ? fn(b) : b)),
    })),
  };
}

export function updateBlockConfig(model: EditorModel, blockUid: string, config: Json): EditorModel {
  const block = findBlock(model, blockUid)?.block;
  const updated = mapBlock(model, blockUid, (b) => ({ ...b, config }));

  return block
    ? { ...updated, data: blockMeta(block.type).pruneDataForConfig(updated.data, block.id, config) }
    : updated;
}

export function updateBlockId(model: EditorModel, blockUid: string, rawId: string): EditorModel {
  const taken = new Set(
    allRows(model).flatMap((r) => r.blocks.filter((b) => b.uid !== blockUid).map((b) => b.id)),
  );
  const id = makeUnique(slugify(rawId) || "block", taken);
  const block = findBlock(model, blockUid)?.block;

  return {
    ...mapBlock(model, blockUid, (b) => ({ ...b, id })),
    data: block ? renameDataId(model.data, block.id, id) : model.data,
  };
}

export function updateDataField(
  model: EditorModel,
  blockId: string,
  field: string,
  value: unknown,
  options: { example: boolean; locked: boolean },
): EditorModel {
  let data = removeDataField(model.data, blockId, field);

  if (options.locked) {
    data = writeDataField(data, "constants", blockId, field, value);
  } else if (options.example) {
    data = writeDataField(data, "example", blockId, field, value);
  } else {
    data = writeDataField(data, "defaults", blockId, field, value);
  }

  return {
    ...model,
    data,
  };
}

export function updateBlockData(
  model: EditorModel,
  blockId: string,
  value: DataValue,
  options: { example: boolean; locked: boolean },
): EditorModel {
  let data = removeDataForId(model.data, blockId);

  if (options.locked) {
    data = writeDataBlock(data, "constants", blockId, value);
  } else if (options.example) {
    data = writeDataBlock(data, "example", blockId, value);
  } else {
    data = writeDataBlock(data, "defaults", blockId, value);
  }

  return {
    ...model,
    data,
  };
}

function renameDataId(layers: TemplateDataLayers, from: string, to: string): TemplateDataLayers {
  return {
    example: renameInDataMap(layers.example, from, to),
    defaults: renameInDataMap(layers.defaults, from, to),
    constants: renameInDataMap(layers.constants, from, to),
  };
}

function renameInDataMap(data: DataMap, from: string, to: string): DataMap {
  if (!(from in data)) {
    return data;
  }

  const next = { ...data, [to]: data[from] };
  delete next[from];

  return next;
}

function removeDataForId(layers: TemplateDataLayers, id: string): TemplateDataLayers {
  return {
    example: omitDataId(layers.example, id),
    defaults: omitDataId(layers.defaults, id),
    constants: omitDataId(layers.constants, id),
  };
}

function removeDataField(
  layers: TemplateDataLayers,
  blockId: string,
  field: string,
): TemplateDataLayers {
  return {
    example: omitDataField(layers.example, blockId, field),
    defaults: omitDataField(layers.defaults, blockId, field),
    constants: omitDataField(layers.constants, blockId, field),
  };
}

function omitDataField(data: DataMap, blockId: string, field: string): DataMap {
  const blockData = data[blockId];
  if (!isPlainObject(blockData) || !(field in blockData)) {
    return data;
  }

  const nextBlockData = { ...blockData };
  delete nextBlockData[field];

  if (Object.keys(nextBlockData).length === 0) {
    return omitDataId(data, blockId);
  }

  return { ...data, [blockId]: nextBlockData };
}

function writeDataField(
  layers: TemplateDataLayers,
  layer: keyof TemplateDataLayers,
  blockId: string,
  field: string,
  value: unknown,
): TemplateDataLayers {
  const blockData = { ...layers[layer][blockId], [field]: value };

  return {
    ...layers,
    [layer]: {
      ...layers[layer],
      [blockId]: blockData,
    },
  };
}

function writeDataBlock(
  layers: TemplateDataLayers,
  layer: keyof TemplateDataLayers,
  blockId: string,
  value: DataValue,
): TemplateDataLayers {
  return {
    ...layers,
    [layer]: {
      ...layers[layer],
      [blockId]: value,
    },
  };
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
  const rows = allRows(model);
  for (let rowIndex = 0; rowIndex < rows.length; rowIndex += 1) {
    const row = rows[rowIndex];
    const blockIndex = row.blocks.findIndex((b) => b.uid === blockUid);
    if (blockIndex !== -1) {
      return { row, block: row.blocks[blockIndex], rowIndex, blockIndex };
    }
  }
  return null;
}

function allRows(model: EditorModel): EditorRow[] {
  return [...model.rows, ...model.footerRows];
}

function rowsFromTemplate(rows: Template["rows"]): EditorRow[] {
  return rows.map(
    (row): EditorRow => ({
      uid: uid(),
      gap: row.gap ?? null,
      blocks: (row.blocks ?? []).map((block): EditorBlock => {
        const id = block.id ?? block.type;
        return {
          uid: uid(),
          id,
          type: block.type,
          config: (block.config ?? {}) as Json,
        };
      }),
    }),
  );
}

function rowsToTemplate(rows: EditorRow[]): Template["rows"] {
  return rows
    .filter((row) => row.blocks.length > 0)
    .map((row) => {
      const out: Template["rows"][number] = {
        blocks: row.blocks.map((b) => ({ type: b.type, id: b.id, config: b.config ?? {} })),
      };
      if (typeof row.gap === "number") {
        out.gap = row.gap;
      }
      return out;
    });
}

function footerRowsFromConfig(config: Json): Template["rows"] {
  const page = isPlainObject(config.page) ? config.page : {};
  const footer = isPlainObject(page.footer) ? page.footer : {};
  return Array.isArray(footer.rows) ? (footer.rows as Template["rows"]) : [];
}

function configWithFooterRows(config: Json, footerRows: Template["rows"]): Json {
  const page = isPlainObject(config.page) ? config.page : {};
  const footer = isPlainObject(page.footer) ? page.footer : {};

  return {
    ...config,
    page: {
      ...page,
      footer: {
        ...footer,
        rows: footerRows,
      },
    },
  };
}

function removeBlockFromRows(rows: EditorRow[], blockUid: string): EditorRow[] {
  return rows
    .map((row) => ({ ...row, blocks: row.blocks.filter((b) => b.uid !== blockUid) }))
    .filter((row) => row.blocks.length > 0);
}
