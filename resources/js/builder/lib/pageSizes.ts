const PAGE_SIZES_MM: Record<string, [number, number]> = {
  A4: [210, 297],
  A5: [148, 210],
  Letter: [215.9, 279.4],
  Legal: [215.9, 355.6],
};

export function pageSizeForFormat(format: string): [number, number] {
  return PAGE_SIZES_MM[format] ?? PAGE_SIZES_MM.A4;
}
