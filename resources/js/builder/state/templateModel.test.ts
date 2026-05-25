import { describe, it, expect } from 'vitest';
import {
    fromTemplate, toTemplate, toDataMap, findBlock,
    addBlock, removeBlock, moveBlock, removeRow, moveRow, setRowWidths,
    updateBlockConfig, updateTemplateConfig, updateBlockId,
    insertRows, replaceModel,
} from './templateModel';
import type { Template, DataMap } from '../types';
import { presets, invoiceExample } from '../presets';

const template: Template = {
    version: 1,
    config: { page: { format: 'A4' } },
    rows: [
        { blocks: [{ type: 'heading', id: 'title', config: { level: 1 } }] },
        { blocks: [
            { type: 'text', id: 'a', config: { width: '50%' } },
            { type: 'text', id: 'b', config: { width: '50%' } },
        ] },
    ],
};
const data: DataMap = { title: { text: 'Hi' }, a: { text: 'A' }, b: { text: 'B' } };

describe('fromTemplate', () => {
    it('assigns uids, keeps ids + config, seeds data', () => {
        const m = fromTemplate(template, data);
        expect(m.rows[0].blocks[0].id).toBe('title');
        expect(m.rows[0].blocks[0].config).toEqual({ level: 1 });
        expect(m.rows[0].blocks[0].data).toEqual({ text: 'Hi' });
        expect(m.rows[0].blocks[0].uid).toBeTruthy();
    });
    it('seeds default data when the data map lacks an id', () => {
        expect(fromTemplate(template).rows[0].blocks[0].data).toEqual({ text: 'Heading' });
    });
});

describe('toTemplate', () => {
    it('emits type/id/config only (no data/props, no row columnWidths) and drops empty rows', () => {
        const out = toTemplate(fromTemplate(template, data));
        expect(out.rows[0].blocks[0]).toEqual({ type: 'heading', id: 'title', config: { level: 1 } });
        expect(out.rows[1].blocks[0].config).toEqual({ width: '50%' });
        expect((out.rows[1] as unknown as Record<string, unknown>).columnWidths).toBeUndefined();
        // No injected data content (e.g. "Hi"/"A"/"B") leaks into the exported template.
        expect(JSON.stringify(out)).not.toContain('"Hi"');
    });
});

describe('toDataMap', () => {
    it('maps block id to its data', () => {
        expect(toDataMap(fromTemplate(template, data))).toEqual(data);
    });
});

describe('addBlock', () => {
    it('adds a block with a unique id and default data', () => {
        const m = addBlock(fromTemplate(template, data), 'table', { rowUid: null });
        const added = m.rows[m.rows.length - 1].blocks[0];
        expect(added.type).toBe('table');
        expect(added.id).toBe('table-1');
        expect(added.data).toHaveProperty('headers');
    });
    it('makes ids unique across the model', () => {
        let m = addBlock(fromTemplate(template, data), 'heading', { rowUid: null });
        m = addBlock(m, 'heading', { rowUid: null });
        const ids = m.rows.flatMap((r) => r.blocks.map((b) => b.id));
        expect(new Set(ids).size).toBe(ids.length);
    });
});

describe('updateBlockId', () => {
    it('slugifies', () => {
        const m = fromTemplate(template, data);
        expect(updateBlockId(m, m.rows[0].blocks[0].uid, 'My Title').rows[0].blocks[0].id).toBe('my-title');
    });
    it('suffixes a colliding id', () => {
        const m = fromTemplate(template, data); // ids: title, a, b
        expect(updateBlockId(m, m.rows[0].blocks[0].uid, 'a').rows[0].blocks[0].id).toBe('a-2');
    });
});

describe('setRowWidths', () => {
    it('writes each block config.width in the row', () => {
        const m = fromTemplate(template, data);
        const rowUid = m.rows[1].uid;
        const out = setRowWidths(m, rowUid, ['30%', '70%']);
        expect(out.rows[1].blocks[0].config.width).toBe('30%');
        expect(out.rows[1].blocks[1].config.width).toBe('70%');
    });
});

describe('config / moves / remove', () => {
    it('updates block config', () => {
        const m = fromTemplate(template, data);
        expect(updateBlockConfig(m, m.rows[0].blocks[0].uid, { level: 3 }).rows[0].blocks[0].config).toEqual({ level: 3 });
    });
    it('removeBlock prunes empty rows', () => {
        const m = fromTemplate(template, data);
        expect(removeBlock(m, m.rows[0].blocks[0].uid).rows).toHaveLength(1);
    });
    it('moveBlock moves into another row', () => {
        const m = fromTemplate(template, data);
        const blockUid = m.rows[0].blocks[0].uid;
        const moved = moveBlock(m, blockUid, m.rows[1].uid, 0);
        expect(moved.rows).toHaveLength(1);
        expect(moved.rows[0].blocks.map((b) => b.type)).toEqual(['heading', 'text', 'text']);
    });
    it('moveRow reorders', () => {
        const m = fromTemplate(template, data);
        const moved = moveRow(m, m.rows[0].uid, 1);
        expect(moved.rows[1].blocks[0].id).toBe('title');
    });
    it('removeRow removes by uid', () => {
        const m = fromTemplate(template, data);
        expect(removeRow(m, m.rows[0].uid).rows).toHaveLength(1);
    });
    it('updateTemplateConfig replaces config', () => {
        expect(updateTemplateConfig(fromTemplate(template, data), { page: { format: 'A5' } }).config).toEqual({ page: { format: 'A5' } });
    });
    it('findBlock locates a block', () => {
        const m = fromTemplate(template, data);
        expect(findBlock(m, m.rows[0].blocks[0].uid)?.block.id).toBe('title');
        expect(findBlock(m, 'nope')).toBeNull();
    });
});

describe('insertRows', () => {
    it('appends rows and uniquifies colliding ids', () => {
        const base = fromTemplate({ version: 1, config: {}, rows: [{ blocks: [{ type: 'heading', id: 'invoice-title', config: {} }] }] });
        const result = insertRows(base, presets[0].build()); // preset[0] also uses id 'invoice-title'
        const ids = result.rows.flatMap((r) => r.blocks.map((b) => b.id));
        expect(new Set(ids).size).toBe(ids.length);
        expect(result.rows).toHaveLength(2);
    });
});

describe('replaceModel', () => {
    it('swaps the whole model from a template + data', () => {
        const base = fromTemplate({ version: 1, config: {}, rows: [{ blocks: [{ type: 'text', id: 't', config: {} }] }] });
        const { template: invoiceTemplate, data: invoiceData } = invoiceExample();
        const result = replaceModel(base, invoiceTemplate, invoiceData);
        expect(result.rows.length).toBeGreaterThan(1);
    });
});
