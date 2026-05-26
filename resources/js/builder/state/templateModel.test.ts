import { describe, it, expect } from "vitest";
import {
  fromTemplate,
  toTemplate,
  toDataMap,
  previewDataMap,
  findBlock,
  addBlock,
  removeBlock,
  moveBlock,
  removeRow,
  moveRow,
  setRowWidths,
  updateBlockConfig,
  updateTemplateConfig,
  updateBlockId,
  updateDataField,
} from "./templateModel";
import type { Template, DataMap } from "../types";

const template: Template = {
  version: 1,
  config: { page: { format: "A4" } },
  rows: [
    { blocks: [{ type: "heading", id: "title", config: { level: 1 } }] },
    {
      blocks: [
        { type: "text", id: "a", config: { width: "50%" } },
        { type: "text", id: "b", config: { width: "50%" } },
      ],
    },
  ],
};
const data: DataMap = { title: { text: "Hi" }, a: { text: "A" }, b: { text: "B" } };

describe("fromTemplate", () => {
  it("assigns uids, keeps ids + config, seeds data", () => {
    const m = fromTemplate(template, data);
    expect(m.rows[0].blocks[0].id).toBe("title");
    expect(m.rows[0].blocks[0].config).toEqual({ level: 1 });
    expect(m.data.example.title).toEqual({ text: "Hi" });
    expect(m.rows[0].blocks[0].uid).toBeTruthy();
  });
  it("promotes footer rows into editable footer state", () => {
    const m = fromTemplate(
      {
        ...template,
        config: {
          page: {
            footer: {
              repeat: true,
              rows: [{ blocks: [{ type: "text", id: "footer-note" }] }],
            },
          },
        },
      },
      { "footer-note": { text: "Footer" } },
    );

    expect(m.footerRows[0].blocks[0].id).toBe("footer-note");
    expect(m.footerRows[0].blocks[0].data).toEqual({ text: "Footer" });
  });
  it("leaves block data empty when the data map lacks an id", () => {
    expect(fromTemplate(template).data.example.title).toBeUndefined();
  });
  it("loads template-level data layers", () => {
    const m = fromTemplate({
      ...template,
      data: {
        example: { title: { text: "Example" } },
        defaults: { title: { text: "Fallback" } },
        constants: { title: { locked: true } },
      },
    });

    expect(m.data.example.title).toEqual({ text: "Example" });
    expect(m.data.defaults.title).toEqual({ text: "Fallback" });
    expect(m.data.constants.title).toEqual({ locked: true });
    expect(m.rows[0].blocks[0].data).toEqual({ text: "Example", locked: true });
  });
});

describe("toTemplate", () => {
  it("emits type/id/config only (no data/props, no row columnWidths) and drops empty rows", () => {
    const out = toTemplate(fromTemplate(template, data));
    expect(out.rows[0].blocks[0]).toEqual({ type: "heading", id: "title", config: { level: 1 } });
    expect(out.rows[1].blocks[0].config).toEqual({ width: "50%" });
    expect((out.rows[1] as unknown as Record<string, unknown>).columnWidths).toBeUndefined();
    expect(out.data?.example).toEqual(data);
  });
  it("serializes editable footer rows back into page footer config", () => {
    const m = fromTemplate({
      ...template,
      config: {
        page: {
          footer: {
            repeat: true,
            rows: [{ blocks: [{ type: "text", id: "footer-note", config: { width: "100%" } }] }],
          },
        },
      },
    });

    expect(
      (
        ((toTemplate(m).config.page as Record<string, unknown>).footer as Record<string, unknown>)
          .rows as Template["rows"]
      )[0].blocks[0],
    ).toEqual({ type: "text", id: "footer-note", config: { width: "100%" } });
  });
});

describe("toDataMap", () => {
  it("maps block id to its data", () => {
    expect(toDataMap(fromTemplate(template, data))).toEqual(data);
  });

  it("omits empty block data", () => {
    const model = fromTemplate(
      {
        version: 1,
        config: {},
        rows: [{ blocks: [{ type: "divider", id: "rule" }] }],
      },
      {},
    );

    expect(toDataMap(model)).toEqual({});
  });

  it("returns runtime example data without fallback defaults or locked constants", () => {
    const model = fromTemplate({
      ...template,
      data: {
        example: { title: { text: "Example" } },
        defaults: { title: { fallback: "Fallback" } },
        constants: { title: { locked: true } },
      },
    });

    expect(toDataMap(model)).toEqual({ title: { text: "Example" } });
  });
});

describe("addBlock", () => {
  it("adds a block with a unique id and the provided data", () => {
    const m = addBlock(fromTemplate(template, data), "table", {
      rowUid: null,
      data: { headers: ["X"], rows: [["1"]] },
    });
    const added = m.rows[m.rows.length - 1].blocks[0];
    expect(added.type).toBe("table");
    expect(added.id).toBe("table-1");
    expect(m.data.example["table-1"]).toEqual({ headers: ["X"], rows: [["1"]] });
  });
  it("defaults to empty data when none is provided", () => {
    const m = addBlock(fromTemplate(template, data), "table", { rowUid: null });
    const added = m.rows[m.rows.length - 1].blocks[0];
    expect(added.id).toBe("table-1");
    expect(m.data.example["table-1"]).toBeUndefined();
  });
  it("makes ids unique across the model", () => {
    let m = addBlock(
      fromTemplate({
        ...template,
        config: {
          page: { footer: { rows: [{ blocks: [{ type: "heading", id: "heading-1" }] }] } },
        },
      }),
      "heading",
      { rowUid: null },
    );
    m = addBlock(m, "heading", { rowUid: null });
    const ids = m.rows.flatMap((r) => r.blocks.map((b) => b.id));
    const allIds = [...ids, ...m.footerRows.flatMap((r) => r.blocks.map((b) => b.id))];
    expect(new Set(allIds).size).toBe(allIds.length);
  });
  it("can add blocks to footer rows", () => {
    const m = addBlock(fromTemplate(template, data), "text", {
      area: "footer",
      data: { text: "F" },
    });

    expect(m.footerRows[0].blocks[0].id).toBe("text-1");
    expect(m.data.example["text-1"]).toEqual({ text: "F" });
  });
});

describe("moveBlock", () => {
  it("moves body blocks into footer rows", () => {
    const m = fromTemplate(template, data);
    const moved = moveBlock(m, m.rows[0].blocks[0].uid, null, null, "footer");

    expect(moved.rows[0].blocks[0].id).toBe("a");
    expect(moved.footerRows[0].blocks[0].id).toBe("title");
  });

  it("moves footer blocks back into body rows", () => {
    const m = fromTemplate({
      ...template,
      config: {
        page: {
          footer: { rows: [{ blocks: [{ type: "text", id: "footer-note" }] }] },
        },
      },
    });
    const moved = moveBlock(m, m.footerRows[0].blocks[0].uid, m.rows[0].uid, 0, "body");

    expect(moved.rows[0].blocks[0].id).toBe("footer-note");
    expect(moved.footerRows).toEqual([]);
  });
});

describe("moveRow", () => {
  it("reorders footer rows independently from body rows", () => {
    const m = fromTemplate({
      ...template,
      config: {
        page: {
          footer: {
            rows: [
              { blocks: [{ type: "text", id: "footer-a" }] },
              { blocks: [{ type: "text", id: "footer-b" }] },
            ],
          },
        },
      },
    });
    const moved = moveRow(m, m.footerRows[0].uid, 1, "footer");

    expect(moved.footerRows.map((row) => row.blocks[0].id)).toEqual(["footer-b", "footer-a"]);
    expect(moved.rows.map((row) => row.blocks[0].id)).toEqual(["title", "a"]);
  });
});

describe("updateBlockId", () => {
  it("slugifies", () => {
    const m = fromTemplate(template, data);
    expect(updateBlockId(m, m.rows[0].blocks[0].uid, "My Title").rows[0].blocks[0].id).toBe(
      "my-title",
    );
  });
  it("suffixes a colliding id", () => {
    const m = fromTemplate(template, data); // ids: title, a, b
    expect(updateBlockId(m, m.rows[0].blocks[0].uid, "a").rows[0].blocks[0].id).toBe("a-2");
  });
  it("renames all data layers with the block id", () => {
    const m = fromTemplate({
      ...template,
      data: {
        example: { title: { text: "Example" } },
        defaults: { title: { text: "Fallback" } },
        constants: { title: { text: "Locked" } },
      },
    });

    const out = updateBlockId(m, m.rows[0].blocks[0].uid, "headline");

    expect(out.data.example.headline).toEqual({ text: "Example" });
    expect(out.data.defaults.headline).toEqual({ text: "Fallback" });
    expect(out.data.constants.headline).toEqual({ text: "Locked" });
    expect(out.data.example.title).toBeUndefined();
  });
});

describe("previewDataMap", () => {
  it("merges fallback, example, and locked data in preview order", () => {
    const m = fromTemplate({
      ...template,
      data: {
        example: { title: { text: "Example" } },
        defaults: { title: { text: "Fallback", subtitle: "Fallback subtitle" } },
        constants: { title: { text: "Locked" } },
      },
    });

    expect(previewDataMap(m)).toEqual({
      title: { text: "Locked", subtitle: "Fallback subtitle" },
    });
  });
});

describe("updateDataField", () => {
  it("writes unchecked values to fallback defaults", () => {
    const m = updateDataField(fromTemplate(template), "title", "text", "Fallback", {
      example: false,
      locked: false,
    });

    expect(m.data.defaults.title).toEqual({ text: "Fallback" });
    expect(m.data.example.title).toBeUndefined();
    expect(m.data.constants.title).toBeUndefined();
  });

  it("moves checked values to example data", () => {
    const m = updateDataField(fromTemplate(template), "title", "text", "Example", {
      example: true,
      locked: false,
    });

    expect(m.data.example.title).toEqual({ text: "Example" });
    expect(m.data.defaults.title).toBeUndefined();
    expect(m.data.constants.title).toBeUndefined();
  });

  it("moves locked values to constants and updates block summary data", () => {
    const m = updateDataField(fromTemplate(template), "title", "text", "Locked", {
      example: true,
      locked: true,
    });

    expect(m.data.example.title).toEqual({ text: "Locked" });
    expect(m.data.constants.title).toEqual({ text: "Locked" });
    expect(m.rows[0].blocks[0].data).toEqual({ text: "Locked" });
    expect(previewDataMap(m)).toEqual({ title: { text: "Locked" } });
  });
});

describe("setRowWidths", () => {
  it("writes each block config.width in the row", () => {
    const m = fromTemplate(template, data);
    const rowUid = m.rows[1].uid;
    const out = setRowWidths(m, rowUid, ["30%", "70%"]);
    expect(out.rows[1].blocks[0].config.width).toBe("30%");
    expect(out.rows[1].blocks[1].config.width).toBe("70%");
  });
});

describe("config / moves / remove", () => {
  it("updates block config", () => {
    const m = fromTemplate(template, data);
    expect(
      updateBlockConfig(m, m.rows[0].blocks[0].uid, { level: 3 }).rows[0].blocks[0].config,
    ).toEqual({ level: 3 });
  });
  it("removeBlock prunes empty rows", () => {
    const m = fromTemplate(template, data);
    expect(removeBlock(m, m.rows[0].blocks[0].uid).rows).toHaveLength(1);
  });
  it("moveBlock moves into another row", () => {
    const m = fromTemplate(template, data);
    const blockUid = m.rows[0].blocks[0].uid;
    const moved = moveBlock(m, blockUid, m.rows[1].uid, 0);
    expect(moved.rows).toHaveLength(1);
    expect(moved.rows[0].blocks.map((b) => b.type)).toEqual(["heading", "text", "text"]);
  });
  it("moveRow reorders", () => {
    const m = fromTemplate(template, data);
    const moved = moveRow(m, m.rows[0].uid, 1);
    expect(moved.rows[1].blocks[0].id).toBe("title");
  });
  it("removeRow removes by uid", () => {
    const m = fromTemplate(template, data);
    const out = removeRow(m, m.rows[0].uid);
    expect(out.rows).toHaveLength(1);
    expect(out.data.example.title).toBeUndefined();
  });
  it("updateTemplateConfig replaces config", () => {
    expect(
      updateTemplateConfig(fromTemplate(template, data), { page: { format: "A5" } }).config,
    ).toEqual({ page: { format: "A5" } });
  });
  it("findBlock locates a block", () => {
    const m = fromTemplate(template, data);
    expect(findBlock(m, m.rows[0].blocks[0].uid)?.block.id).toBe("title");
    expect(findBlock(m, "nope")).toBeNull();
  });
});
