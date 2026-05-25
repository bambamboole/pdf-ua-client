import type { Template, DataMap } from '../../../resources/js/builder/types';

export const sampleTemplate: Template = {
    version: 1,
    config: { page: { format: 'A4' } },
    rows: [
        { blocks: [{ type: 'heading', id: 'title', config: { level: 1 } }] },
        { blocks: [{ type: 'text', id: 'intro', config: {} }] },
        { blocks: [
            { type: 'key-value', id: 'meta-left', config: { width: '50%' } },
            { type: 'key-value', id: 'meta-right', config: { width: '50%' } },
        ] },
        { blocks: [{ type: 'divider', id: 'rule', config: {} }] },
        { blocks: [{ type: 'table', id: 'items', config: {} }] },
    ],
};

export const sampleData: DataMap = {
    title: { text: 'Invoice 2026-001' },
    intro: { text: 'Thank you for your business.' },
    'meta-left': { entries: [{ label: 'Date', value: '2026-05-24' }, { label: 'Due', value: '2026-06-07' }] },
    'meta-right': { entries: [{ label: 'Invoice', value: '2026-001' }] },
    items: { headers: ['Item', 'Qty', 'Price'], rows: [['Widget', '2', '€10.00'], ['Gadget', '1', '€25.00']] },
};
