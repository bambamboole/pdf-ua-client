import { describe, it, expect } from "vitest";
import { exampleFromSchema } from "./exampleFromSchema";
import type { JsonSchema } from "../types";

const root: JsonSchema = { $defs: {} };

describe("exampleFromSchema", () => {
  it("uses examples[0] when present", () => {
    expect(exampleFromSchema({ type: "string", examples: ["Hi"] }, root)).toBe("Hi");
  });
  it("falls back to default", () => {
    expect(exampleFromSchema({ type: "integer", default: 2 }, root)).toBe(2);
  });
  it("builds an object from property examples", () => {
    const s: JsonSchema = {
      type: "object",
      properties: { text: { type: "string", examples: ["T"] } },
    };
    expect(exampleFromSchema(s, root)).toEqual({ text: "T" });
  });
  it("builds a one-item array from object item examples", () => {
    const s: JsonSchema = {
      type: "object",
      properties: {
        rows: {
          type: "array",
          items: {
            type: "object",
            properties: {
              label: { type: "string", examples: ["L"] },
              value: { type: "string", examples: ["V"] },
            },
          },
        },
      },
    };
    expect(exampleFromSchema(s, root)).toEqual({ rows: [{ label: "L", value: "V" }] });
  });
  it("uses an array-valued example directly (table headers)", () => {
    const s: JsonSchema = {
      type: "object",
      properties: { headers: { type: "array", examples: [["A", "B"]] } },
    };
    expect(exampleFromSchema(s, root)).toEqual({ headers: ["A", "B"] });
  });
  it("resolves a $ref against the root $defs", () => {
    const rootWithDefs: JsonSchema = {
      $defs: { pair: { type: "object", properties: { k: { type: "string", examples: ["x"] } } } },
    } as JsonSchema;
    expect(exampleFromSchema({ $ref: "#/$defs/pair" } as JsonSchema, rootWithDefs)).toEqual({
      k: "x",
    });
  });
  it("type-based fallbacks", () => {
    expect(exampleFromSchema({ type: "string" }, root)).toBe("");
    expect(exampleFromSchema({ type: ["integer", "null"] }, root)).toBe(0);
    expect(exampleFromSchema({ type: "array" }, root)).toEqual([]);
    expect(exampleFromSchema({ type: "boolean" }, root)).toBe(false);
    expect(exampleFromSchema(undefined, root)).toBe(null);
  });
});
