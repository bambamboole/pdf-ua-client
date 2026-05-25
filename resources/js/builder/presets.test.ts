import { describe, it, expect } from "vitest";
import { presets, invoiceExample } from "./presets";
import { fromTemplate, toDataMap } from "./state/templateModel";

describe("presets", () => {
  it("each preset yields rows with blocks carrying id + data", () => {
    for (const preset of presets) {
      const rows = preset.build();
      expect(rows.length).toBeGreaterThan(0);
      expect(rows[0].blocks[0].data).toBeTruthy();
      expect(rows[0].blocks[0].id).toBeTruthy();
    }
  });
});

describe("invoiceExample", () => {
  it("produces a template + data with unique ids and width in config", () => {
    const { template, data } = invoiceExample();
    const model = fromTemplate(template, data);
    const ids = model.rows.flatMap((r) => r.blocks.map((b) => b.id));
    expect(new Set(ids).size).toBe(ids.length);
    expect(Object.keys(toDataMap(model)).length).toBe(ids.length);
    // multi-column header row carries width in block config (not a row columnWidths)
    const header = template.rows[0];
    expect((header as unknown as Record<string, unknown>).columnWidths).toBeUndefined();
    expect(header.blocks[0].config).toMatchObject({ width: "60%" });
    expect(header.blocks[1].config).toMatchObject({ width: "40%" });
  });
});
