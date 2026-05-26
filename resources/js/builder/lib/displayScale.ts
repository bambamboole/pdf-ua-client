const CSS_PX_PER_INCH = 96;

export function mmToScaledPx(mm: number, scale: number): number {
  return Math.round((mm / 25.4) * CSS_PX_PER_INCH * scale);
}
