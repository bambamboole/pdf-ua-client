import type { ChangeEvent, FocusEvent } from "react";
import {
  type BaseInputTemplateProps,
  type DescriptionFieldProps,
  type FieldTemplateProps,
  type ObjectFieldTemplateProps,
  type WidgetProps,
  getInputProps,
} from "@rjsf/utils";

export function BaseInputTemplate(props: BaseInputTemplateProps) {
  const {
    id,
    value,
    type,
    placeholder,
    required,
    disabled,
    readonly,
    autofocus,
    onChange,
    onChangeOverride,
    onBlur,
    onFocus,
    schema,
    options,
  } = props;
  const inputProps = getInputProps(schema, type, options);
  return (
    <input
      id={id}
      className="block w-full rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-field)] px-2 py-1 text-sm text-[var(--builder-field-ink)] focus:border-[var(--builder-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--builder-accent-soft)] disabled:bg-[var(--builder-surface)]"
      value={value ?? ""}
      placeholder={placeholder}
      required={required}
      disabled={disabled}
      readOnly={readonly}
      autoFocus={autofocus}
      {...inputProps}
      onChange={
        onChangeOverride ||
        ((e: ChangeEvent<HTMLInputElement>) =>
          onChange(e.target.value === "" ? options.emptyValue : e.target.value))
      }
      onBlur={(e: FocusEvent<HTMLInputElement>) => onBlur(id, e.target.value)}
      onFocus={(e: FocusEvent<HTMLInputElement>) => onFocus(id, e.target.value)}
    />
  );
}

export function FieldTemplate(props: FieldTemplateProps) {
  const {
    id,
    classNames,
    style,
    label,
    help,
    required,
    description,
    errors,
    children,
    hidden,
    schema,
  } = props;
  const rendersOwnLegend = schema.type === "object";
  if (hidden) {
    return <div className="hidden">{children}</div>;
  }
  return (
    <div className={`mb-3 ${classNames ?? ""}`} style={style}>
      {label && !rendersOwnLegend && (
        <label
          htmlFor={id}
          className="mb-1 block text-xs font-medium text-[var(--builder-muted-strong)]"
        >
          {label}
          {required ? <span className="text-[var(--builder-danger)]"> *</span> : null}
        </label>
      )}
      {children}
      {!rendersOwnLegend && description}
      {errors}
      {help}
    </div>
  );
}

export function DescriptionFieldTemplate({ id, description }: DescriptionFieldProps) {
  if (!description) {
    return null;
  }

  return (
    <p id={id} className="mt-1 text-[10px] leading-snug text-[var(--builder-muted)]">
      {description}
    </p>
  );
}

export function ObjectFieldTemplate(props: ObjectFieldTemplateProps) {
  const { title, description, properties } = props;
  return (
    <fieldset className="mb-3 rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-panel)] p-3">
      {title && (
        <legend className="px-1 text-xs font-semibold uppercase tracking-wide text-[var(--builder-muted)]">
          {title}
        </legend>
      )}
      {description && <div className="mb-2">{description}</div>}
      {properties.map((el) => (
        <div key={el.name}>{el.content}</div>
      ))}
    </fieldset>
  );
}

export function SelectWidget(props: WidgetProps) {
  const {
    id,
    options,
    value,
    required,
    disabled,
    readonly,
    multiple,
    placeholder,
    onChange,
    onBlur,
    onFocus,
  } = props;
  const enumOptions = options.enumOptions ?? [];
  return (
    <select
      id={id}
      className="block w-full rounded-[var(--builder-radius)] border border-[var(--builder-stroke)] bg-[var(--builder-field)] px-2 py-1 text-sm text-[var(--builder-field-ink)] focus:border-[var(--builder-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--builder-accent-soft)] disabled:bg-[var(--builder-surface)]"
      value={value ?? ""}
      required={required}
      disabled={disabled || readonly}
      multiple={multiple}
      onChange={(e) => onChange(e.target.value === "" ? options.emptyValue : e.target.value)}
      onBlur={(e) => onBlur(id, e.target.value)}
      onFocus={(e) => onFocus(id, e.target.value)}
    >
      {!multiple && <option value="">{placeholder ?? "—"}</option>}
      {enumOptions.map((opt) => (
        <option key={String(opt.value)} value={String(opt.value)}>
          {opt.label}
        </option>
      ))}
    </select>
  );
}

export const rjsfTemplates = {
  BaseInputTemplate,
  DescriptionFieldTemplate,
  FieldTemplate,
  ObjectFieldTemplate,
};
export const rjsfWidgets = { SelectWidget };
