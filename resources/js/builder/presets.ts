import type { DataMap, EditorBlock, EditorRow, Template } from "./types";

function block(
  id: string,
  type: EditorBlock["type"],
  config: Record<string, unknown>,
  data: Record<string, unknown>,
): EditorBlock {
  return { uid: crypto.randomUUID(), id, type, config, data };
}

function row(...blocks: EditorBlock[]): EditorRow {
  return { uid: crypto.randomUUID(), gap: null, blocks };
}

export interface Preset {
  name: string;
  build: () => EditorRow[];
}

export const presets: Preset[] = [
  {
    name: "Invoice header",
    build: () => [row(block("invoice-title", "heading", { level: 1 }, { text: "INVOICE" }))],
  },
  {
    name: "Bill to",
    build: () => [
      row(
        block(
          "bill-to",
          "key-value",
          {},
          {
            entries: [
              { label: "Customer", value: "ACME Corp" },
              { label: "Address", value: "Main St 1" },
              { label: "Email", value: "ap@acme.test" },
            ],
          },
        ),
      ),
    ],
  },
  {
    name: "Line items",
    build: () => [
      row(
        block(
          "line-items",
          "table",
          {},
          {
            headers: ["Item", "Qty", "Unit", "Total"],
            rows: [
              ["Consulting", "10", "€100", "€1000"],
              ["License", "1", "€250", "€250"],
            ],
          },
        ),
      ),
    ],
  },
  {
    name: "Totals",
    build: () => [
      row(
        block(
          "totals",
          "key-value",
          { align: "right" },
          {
            entries: [
              { label: "Subtotal", value: "€1250" },
              { label: "Tax (19%)", value: "€237.50" },
              { label: "Total", value: "€1487.50" },
            ],
          },
        ),
      ),
    ],
  },
];

export function invoiceExample(): { template: Template; data: DataMap } {
  const rows: EditorRow[] = [
    row(
      block("company", "heading", { level: 1, width: "60%" }, { text: "ACME GmbH" }),
      block(
        "invoice-meta",
        "key-value",
        { align: "right", width: "40%" },
        {
          entries: [
            { label: "Invoice", value: "2026-001" },
            { label: "Date", value: "2026-05-25" },
            { label: "Due", value: "2026-06-08" },
          ],
        },
      ),
    ),
    row(
      block(
        "from",
        "key-value",
        { width: "50%" },
        { entries: [{ label: "From", value: "ACME GmbH, Main St 1" }] },
      ),
      block(
        "to",
        "key-value",
        { width: "50%" },
        { entries: [{ label: "Bill to", value: "Beta Ltd, 2nd Ave" }] },
      ),
    ),
    row(block("rule", "divider", {}, {})),
    row(
      block(
        "items",
        "table",
        {},
        {
          headers: ["Description", "Qty", "Unit", "Amount"],
          rows: [
            ["Consulting", "10", "€100", "€1000"],
            ["License", "1", "€250", "€250"],
          ],
        },
      ),
    ),
    row(
      block(
        "totals",
        "key-value",
        { align: "right" },
        {
          entries: [
            { label: "Subtotal", value: "€1250" },
            { label: "Tax (19%)", value: "€237.50" },
            { label: "Total", value: "€1487.50" },
          ],
        },
      ),
    ),
    row(
      block(
        "footer",
        "text",
        {},
        { text: "Payment due within 14 days. Thank you for your business." },
      ),
    ),
  ];

  const template: Template = {
    version: 1,
    config: { page: { format: "A4" } },
    rows: rows.map((r) => ({
      blocks: r.blocks.map((b) => ({ type: b.type, id: b.id, config: b.config })),
    })),
  };

  const data: DataMap = {};
  for (const r of rows) {
    for (const b of r.blocks) {
      data[b.id] = b.data;
    }
  }

  return { template, data };
}
