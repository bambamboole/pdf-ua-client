import { useCallback, type PointerEvent as ReactPointerEvent, type RefObject } from 'react';
import { parseWidths, formatWidths, setBoundary } from './lib/columns';

interface Props {
    widths: (string | number)[] | null;
    count: number;
    leftIndex: number;
    containerRef: RefObject<HTMLDivElement | null>;
    onResize: (widths: string[]) => void;
}

export default function ColumnResizer({ widths, count, leftIndex, containerRef, onResize }: Props) {
    const onPointerDown = useCallback((e: ReactPointerEvent) => {
        e.preventDefault();
        e.stopPropagation();
        const el = containerRef.current;
        if (!el) {
            return;
        }
        const rect = el.getBoundingClientRect();
        const base = parseWidths(widths, count);
        const leftStart = base.slice(0, leftIndex).reduce((a, b) => a + b, 0);
        const move = (ev: PointerEvent) => {
            const pct = ((ev.clientX - rect.left) / rect.width) * 100;
            onResize(formatWidths(setBoundary(base, leftIndex, pct - leftStart)));
        };
        const up = () => {
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', up);
        };
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', up);
    }, [widths, count, leftIndex, containerRef, onResize]);

    return (
        <div
            onPointerDown={onPointerDown}
            className="w-1.5 shrink-0 cursor-col-resize self-stretch rounded bg-gray-200 hover:bg-blue-400"
            title="Drag to resize columns"
        />
    );
}
