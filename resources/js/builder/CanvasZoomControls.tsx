interface Props {
  onDecrease: () => void;
  onIncrease: () => void;
  onReset: () => void;
}

export default function CanvasZoomControls({ onDecrease, onIncrease, onReset }: Props) {
  return (
    <div className="mx-auto mb-3 flex max-w-[calc(100vw-24rem)] flex-wrap items-center justify-center gap-2 text-xs text-[var(--builder-muted-strong)]">
      <span className="font-medium">Page scale</span>
      <div className="flex items-center rounded-full border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-1 shadow-sm">
        <button
          type="button"
          className="rounded-full px-2 py-1 font-medium transition hover:bg-[var(--builder-surface)] hover:text-[var(--builder-ink)]"
          aria-label="Decrease page scale"
          onClick={onDecrease}
        >
          -
        </button>
        <span data-canvas-scale-value className="px-2 text-center font-medium">
          Scale
        </span>
        <button
          type="button"
          className="rounded-full px-2 py-1 font-medium transition hover:bg-[var(--builder-surface)] hover:text-[var(--builder-ink)]"
          aria-label="Increase page scale"
          onClick={onIncrease}
        >
          +
        </button>
      </div>
      <button
        type="button"
        className="rounded-full border border-[var(--builder-stroke)] bg-[var(--builder-panel)] px-2.5 py-1 font-medium shadow-sm transition hover:border-[var(--builder-accent)] hover:text-[var(--builder-ink)]"
        onClick={onReset}
      >
        Default
      </button>
    </div>
  );
}
