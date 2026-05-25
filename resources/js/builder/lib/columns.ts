const MIN = 5;

export function parseWidths(widths: (string | number)[] | null, count: number): number[] {
    if (!widths || widths.length !== count) {
        const each = Math.round(100 / count);
        const arr = Array.from({ length: count }, () => each);
        arr[count - 1] = 100 - each * (count - 1);
        return arr;
    }
    return widths.map((w) => (typeof w === 'number' ? w : parseFloat(String(w))));
}

export function formatWidths(percents: number[]): string[] {
    return percents.map((p) => `${p}%`);
}

export function setBoundary(percents: number[], leftIndex: number, leftPercent: number): number[] {
    const next = [...percents];
    const pair = next[leftIndex] + next[leftIndex + 1];
    const left = Math.max(MIN, Math.min(pair - MIN, Math.round(leftPercent)));
    next[leftIndex] = left;
    next[leftIndex + 1] = pair - left;
    return next;
}
