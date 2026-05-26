const CSS_PX_PER_INCH = 96;

interface DisplayMetrics {
  screenWidth: number;
  screenHeight: number;
  devicePixelRatio: number;
}

interface KnownDisplay {
  width: number;
  height: number;
  diagonalInches: number;
}

const KNOWN_DISPLAYS: KnownDisplay[] = [
  { width: 3456, height: 2234, diagonalInches: 16.2 },
  { width: 3072, height: 1920, diagonalInches: 16 },
  { width: 3024, height: 1964, diagonalInches: 14.2 },
  { width: 2880, height: 1864, diagonalInches: 15.3 },
  { width: 2880, height: 1800, diagonalInches: 15.4 },
  { width: 2560, height: 1664, diagonalInches: 13.6 },
  { width: 2560, height: 1600, diagonalInches: 13.3 },
];

export function estimatedPhysicalScale(metrics = browserDisplayMetrics()): number {
  if (!metrics) {
    return 1;
  }

  const physicalWidth = Math.round(metrics.screenWidth * metrics.devicePixelRatio);
  const physicalHeight = Math.round(metrics.screenHeight * metrics.devicePixelRatio);
  const display = matchingKnownDisplay(physicalWidth, physicalHeight);

  if (!display) {
    return metrics.devicePixelRatio >= 2 ? 1.25 : 1;
  }

  const physicalDiagonalPx = Math.hypot(display.width, display.height);
  const physicalPpi = physicalDiagonalPx / display.diagonalInches;
  const cssPpi = physicalPpi / metrics.devicePixelRatio;

  return clampScale(cssPpi / CSS_PX_PER_INCH);
}

export function mmToScaledPx(mm: number, scale: number): number {
  return Math.round((mm / 25.4) * CSS_PX_PER_INCH * scale);
}

function browserDisplayMetrics(): DisplayMetrics | null {
  if (typeof window === "undefined") {
    return null;
  }

  return {
    screenWidth: window.screen.width,
    screenHeight: window.screen.height,
    devicePixelRatio: window.devicePixelRatio || 1,
  };
}

function matchingKnownDisplay(width: number, height: number): KnownDisplay | null {
  return (
    KNOWN_DISPLAYS.find(
      (display) =>
        dimensionsMatch(width, height, display.width, display.height) ||
        dimensionsMatch(width, height, display.height, display.width),
    ) ?? null
  );
}

function dimensionsMatch(
  width: number,
  height: number,
  targetWidth: number,
  targetHeight: number,
): boolean {
  return Math.abs(width - targetWidth) <= 96 && Math.abs(height - targetHeight) <= 96;
}

function clampScale(scale: number): number {
  return Math.min(1.6, Math.max(0.75, Number(scale.toFixed(2))));
}
