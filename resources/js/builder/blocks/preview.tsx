import type { ReactNode } from "react";

export function truncate(value: string, length = 40): string {
  return value.length > length ? `${value.slice(0, length)}...` : value;
}

export function keyPart(parts: string[]): string {
  return parts.join("\u001f");
}

export function MutedSummary({ children }: { children: ReactNode }) {
  return <span className="truncate text-xs text-[var(--builder-muted)]">{children}</span>;
}

export function ImagePreview({ src, alt }: { src: string; alt: string }) {
  return (
    <div className="mt-2 overflow-hidden rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-2">
      <img src={src} alt={alt} className="max-h-28 w-full object-contain" />
    </div>
  );
}
