import type { BlockType, Json } from "../types";

const PLACEHOLDER_IMAGE =
  "data:image/svg+xml;utf8," +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="80"><rect width="200" height="80" fill="#e5e7eb"/><text x="100" y="44" font-family="sans-serif" font-size="12" fill="#6b7280" text-anchor="middle">image</text></svg>',
  );

export function defaultData(type: BlockType | string): Json {
  switch (type) {
    case "heading":
      return { text: "Heading" };
    case "text":
      return { text: "Lorem ipsum dolor sit amet, consectetur adipiscing elit." };
    case "html":
      return { html: "<p>Custom HTML</p>" };
    case "image":
      return { src: PLACEHOLDER_IMAGE, alt: "Image" };
    case "key-value":
      return { entries: [{ label: "Label", value: "Value" }] };
    case "table":
      return {
        headers: ["Column A", "Column B"],
        rows: [
          ["A1", "B1"],
          ["A2", "B2"],
        ],
      };
    case "spacer":
    case "divider":
    default:
      return {};
  }
}
