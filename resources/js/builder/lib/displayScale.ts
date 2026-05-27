const CSS_PX_PER_INCH = 96;

export function mmToPx(mm: number): number {
  return Math.round((mm / 25.4) * CSS_PX_PER_INCH);
}
