import TemplateBuilder from '@builder/TemplateBuilder';
import type { JsonSchema, Template } from '@builder/types';

function xsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function renderTemplate(template: unknown, data: unknown): Promise<string> {
    const response = await fetch('/render', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify({ template, data }),
    });

    if (!response.ok) {
        throw new Error(`Render request failed (${response.status})`);
    }

    const payload = await response.json() as { html: string };

    return payload.html;
}

const emptyTemplate: Template = { version: 1, config: {}, rows: [] };

export default function Builder({ schema, examples }: { schema: JsonSchema; examples?: unknown }) {
    return (
        <TemplateBuilder
            schema={schema}
            examples={examples}
            initialTemplate={emptyTemplate}
            initialData={{}}
            renderTemplate={renderTemplate}
        />
    );
}
