function escapeHtml(value: string): string {
  return value.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

export function renderStub(template: unknown, _data: unknown): Promise<string> {
  const json = escapeHtml(JSON.stringify(template, null, 2));

  return Promise.resolve(
    `<div style="font-family: ui-sans-serif, system-ui; padding: 1rem; color: #111827;">
      <p style="margin: 0 0 0.5rem; font-weight: 600;">Live preview requires the PHP backend.</p>
      <p style="margin: 0 0 1rem; color: #6b7280;">This is a static demo. The generated template is shown below.</p>
      <pre style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 1rem; overflow: auto; font-size: 0.75rem;">${json}</pre>
    </div>`,
  );
}

export function renderPdfStub(_template: unknown, _data: unknown): Promise<Blob> {
  return Promise.reject(new Error("PDF preview requires the PHP backend. This is a static demo."));
}
