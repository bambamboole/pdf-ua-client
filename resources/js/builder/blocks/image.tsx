import { useState, type ChangeEvent } from "react";
import { IMAGE_ACCEPT, imageFileError, imageFileToDataUrl } from "../lib/imageUpload";
import { FieldControl } from "./dataControls";
import { ImagePreview, MutedSummary } from "./preview";
import {
  currentFieldValue,
  inputClass,
  isPlainObject,
  previewBlockValue,
  stringValue,
} from "./shared";
import type { BlockDataEditorProps, BlockDefinition, BlockSummaryProps } from "./types";

function ImageDataEditor({ block, data, onUpdateDataField }: BlockDataEditorProps) {
  return (
    <div data-inline-data-fields className="space-y-3">
      <FieldControl
        blockId={block.id}
        field="src"
        label="Image source"
        value={currentFieldValue(data, block.id, "src")}
        data={data}
        onUpdateDataField={onUpdateDataField}
      >
        {({ value, update }) => <ImageSourceInput value={stringValue(value)} onChange={update} />}
      </FieldControl>
      <FieldControl
        blockId={block.id}
        field="alt"
        label="Alt text"
        value={currentFieldValue(data, block.id, "alt")}
        data={data}
        onUpdateDataField={onUpdateDataField}
      >
        {({ value, update }) => (
          <input
            className={inputClass}
            value={stringValue(value)}
            onChange={(event) => update(event.currentTarget.value)}
          />
        )}
      </FieldControl>
    </div>
  );
}

function ImageSourceInput({
  value,
  onChange,
}: {
  value: string;
  onChange: (value: string) => void;
}) {
  const [message, setMessage] = useState<string | null>(null);

  async function handleFile(event: ChangeEvent<HTMLInputElement>): Promise<void> {
    const file = event.currentTarget.files?.[0];
    if (!file) {
      return;
    }

    const error = imageFileError(file);
    if (error) {
      setMessage(error);
      event.currentTarget.value = "";
      return;
    }

    onChange(await imageFileToDataUrl(file));
    setMessage(`${file.name} uploaded`);
    event.currentTarget.value = "";
  }

  return (
    <div data-image-source-input className="grid min-w-0 gap-2">
      {value ? (
        <div className="flex min-w-0 items-center gap-3 overflow-hidden rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-2">
          <img
            src={value}
            alt=""
            className="h-12 max-w-24 shrink-0 rounded border border-[var(--builder-stroke)] object-contain"
          />
          <span className="block min-w-0 flex-1 truncate font-mono text-[10px] text-[var(--builder-muted)]">
            {value}
          </span>
        </div>
      ) : null}
      <input
        className={inputClass}
        type="url"
        value={value}
        placeholder="https://example.com/logo.png or uploaded data URL"
        onChange={(event) => {
          setMessage(null);
          onChange(event.currentTarget.value);
        }}
      />
      <label className="inline-flex cursor-pointer items-center justify-center rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] px-2 py-1 text-xs font-medium text-[var(--builder-muted-strong)] transition hover:border-[var(--builder-accent)]">
        Upload
        <input
          className="sr-only"
          type="file"
          accept={IMAGE_ACCEPT}
          onChange={(event) => {
            void handleFile(event);
          }}
        />
      </label>
      <p className="text-[10px] leading-snug text-[var(--builder-muted)]">
        PNG, JPEG, WebP, GIF, or SVG. 200 KB max.
      </p>
      {message ? (
        <p className="text-[10px] leading-snug text-[var(--builder-muted-strong)]">{message}</p>
      ) : null}
    </div>
  );
}

function ImageSummary({ block, data }: BlockSummaryProps) {
  const blockData = previewBlockValue(data, block.id);
  const record = isPlainObject(blockData) ? blockData : {};
  const src = stringValue(record.src);

  if (src !== "") {
    return <ImagePreview src={src} alt={stringValue(record.alt)} />;
  }

  return <MutedSummary>{stringValue(record.alt) || "image"}</MutedSummary>;
}

export const imageBlock: BlockDefinition = {
  DataEditor: ImageDataEditor,
  Summary: ImageSummary,
};
