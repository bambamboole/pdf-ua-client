interface Props {
  url: string;
}

export default function PdfCanvas({ url }: Props) {
  return (
    <div className="mx-auto h-[calc(100vh-9rem)] min-h-[34rem] max-w-5xl overflow-hidden rounded-[var(--builder-radius)] bg-white shadow-lg">
      <object title="PDF preview" data={url} type="application/pdf" className="block size-full">
        <p className="p-4 text-sm text-[var(--builder-muted)]">PDF preview is unavailable.</p>
      </object>
    </div>
  );
}
