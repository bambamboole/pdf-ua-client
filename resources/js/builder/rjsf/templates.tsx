import type { ChangeEvent, FocusEvent } from "react";
import {
  type BaseInputTemplateProps,
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
      className="block w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-gray-100"
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
  const { id, classNames, style, label, help, required, description, errors, children, hidden } =
    props;
  if (hidden) {
    return <div className="hidden">{children}</div>;
  }
  return (
    <div className={`mb-3 ${classNames ?? ""}`} style={style}>
      {label && (
        <label htmlFor={id} className="mb-1 block text-xs font-medium text-gray-600">
          {label}
          {required ? <span className="text-red-500"> *</span> : null}
        </label>
      )}
      {description}
      {children}
      {errors}
      {help}
    </div>
  );
}

export function ObjectFieldTemplate(props: ObjectFieldTemplateProps) {
  const { title, description, properties } = props;
  return (
    <fieldset className="mb-3 rounded border border-gray-200 p-3">
      {title && (
        <legend className="px-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
          {title}
        </legend>
      )}
      {description && <p className="mb-2 text-xs text-gray-500">{description}</p>}
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
      className="block w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
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

export const rjsfTemplates = { BaseInputTemplate, FieldTemplate, ObjectFieldTemplate };
export const rjsfWidgets = { SelectWidget };
