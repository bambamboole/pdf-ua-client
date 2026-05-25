import { pageSizeForFormat } from "./lib/pageSizes";

interface Props {
  format: string;
  html: string;
}

export default function PageCanvas({ format, html }: Props) {
  const [width, height] = pageSizeForFormat(format);

  return (
    <div className="mx-auto bg-white shadow-lg" style={{ width: `${width}mm` }}>
      <iframe
        title="Page preview"
        sandbox=""
        srcDoc={html}
        className="block border-0"
        style={{ width: `${width}mm`, height: `${height}mm` }}
      />
    </div>
  );
}
