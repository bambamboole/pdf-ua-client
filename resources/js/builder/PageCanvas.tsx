const PAGE_SIZES_MM: Record<string, [number, number]> = {
  A4: [210, 297],
  A5: [148, 210],
  Letter: [215.9, 279.4],
  Legal: [215.9, 355.6],
};

interface Props {
  format: string;
  html: string;
}

export default function PageCanvas({ format, html }: Props) {
  const [width, height] = PAGE_SIZES_MM[format] ?? PAGE_SIZES_MM.A4;

  return (
    <div className="mx-auto bg-white shadow-lg" style={{ width: `${width}mm` }}>
      <iframe
        title="Page preview"
        srcDoc={html}
        className="block border-0"
        style={{ width: `${width}mm`, height: `${height}mm` }}
      />
    </div>
  );
}
