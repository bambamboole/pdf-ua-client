import type { Json } from "./types";

interface KeyValueField {
  key: string;
  label: string;
}

interface Props {
  config: Json;
  onChange: (config: Json) => void;
}

const inputClass =
  "block min-w-0 w-full rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-field)] px-2 py-1 text-sm text-[var(--builder-field-ink)] focus:border-[var(--builder-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--builder-accent-soft)]";

export default function KeyValueConfigFields({ config, onChange }: Props) {
  const fields = keyValueFields(config.fields);

  function updateFields(nextFields: KeyValueField[]): void {
    onChange({ ...config, fields: nextFields });
  }

  function updateField(index: number, field: Partial<KeyValueField>): void {
    updateFields(
      fields.map((current, currentIndex) =>
        currentIndex === index ? { ...current, ...field } : current,
      ),
    );
  }

  return (
    <section
      data-key-value-config-fields
      className="mb-3 min-w-0 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-3"
    >
      <div className="mb-3 flex items-center justify-between gap-3">
        <div className="min-w-0">
          <div className="text-xs font-semibold uppercase tracking-wide text-[var(--builder-muted)]">
            Fields
          </div>
          <p className="mt-1 text-[10px] leading-snug text-[var(--builder-muted)]">
            Fixed rows for this key/value block. Runtime data uses these keys directly.
          </p>
        </div>
        <button
          type="button"
          className="shrink-0 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] px-2 py-1 text-xs font-medium text-[var(--builder-muted-strong)] transition hover:border-[var(--builder-accent)]"
          onClick={() =>
            updateFields([...fields, { key: nextFieldKey(fields), label: "New field" }])
          }
        >
          Add field
        </button>
      </div>
      <div className="space-y-2">
        {fields.map((field, index) => (
          <div
            key={field.key}
            className="grid min-w-0 grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] items-end gap-2 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] p-2"
          >
            <label className="min-w-0">
              <span className="mb-1 block text-[10px] font-medium text-[var(--builder-muted)]">
                Key
              </span>
              <input
                className={inputClass}
                value={field.key}
                pattern="[A-Za-z][A-Za-z0-9_]*"
                onChange={(event) =>
                  updateField(index, { key: normalizeKey(event.currentTarget.value) })
                }
              />
            </label>
            <label className="min-w-0">
              <span className="mb-1 block text-[10px] font-medium text-[var(--builder-muted)]">
                Label
              </span>
              <input
                className={inputClass}
                value={field.label}
                onChange={(event) => updateField(index, { label: event.currentTarget.value })}
              />
            </label>
            <button
              type="button"
              className="rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-surface)] px-2 py-1 text-xs font-medium text-[var(--builder-muted-strong)] transition hover:border-[var(--builder-danger)] hover:text-[var(--builder-danger)]"
              onClick={() =>
                updateFields(fields.filter((_, currentIndex) => currentIndex !== index))
              }
            >
              Remove
            </button>
          </div>
        ))}
      </div>
    </section>
  );
}

function keyValueFields(value: unknown): KeyValueField[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((field) => {
      if (!field || typeof field !== "object") {
        return null;
      }

      const record = field as Record<string, unknown>;
      const key = stringValue(record.key);
      if (key === "") {
        return null;
      }

      return { key, label: stringValue(record.label) || key };
    })
    .filter((field): field is KeyValueField => field !== null);
}

function nextFieldKey(fields: KeyValueField[]): string {
  const keys = new Set(fields.map((field) => field.key));
  let index = fields.length + 1;
  let key = `field${index}`;

  while (keys.has(key)) {
    index += 1;
    key = `field${index}`;
  }

  return key;
}

function normalizeKey(value: string): string {
  return value.replace(/[^A-Za-z0-9_]/g, "").replace(/^[^A-Za-z]+/, "");
}

function stringValue(value: unknown): string {
  return value == null ? "" : String(value);
}
