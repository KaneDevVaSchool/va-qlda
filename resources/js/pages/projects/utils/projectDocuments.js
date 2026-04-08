/** Tree + display helpers for project documents (detail page). */

export function buildDocumentTree(flat) {
    const list = flat || [];
    const byParent = new Map();
    const rootKey = '__root__';
    for (const d of list) {
        const pk = d.parent_id == null ? rootKey : d.parent_id;
        if (!byParent.has(pk)) {
            byParent.set(pk, []);
        }
        byParent.get(pk).push(d);
    }
    for (const arr of byParent.values()) {
        arr.sort((a, b) => a.sort_order - b.sort_order || a.id - b.id);
    }
    function walk(pk) {
        return (byParent.get(pk) || []).map((d) => ({
            ...d,
            children: walk(d.id),
        }));
    }
    return walk(rootKey);
}

export function flattenDocRows(nodes, depth = 0) {
    const out = [];
    for (const n of nodes) {
        out.push({ doc: n, depth });
        if (n.children?.length) {
            out.push(...flattenDocRows(n.children, depth + 1));
        }
    }
    return out;
}

export function collectFolderOptions(nodes, depth = 0) {
    const out = [];
    for (const n of nodes || []) {
        if (n.doc_type === 'folder') {
            out.push({ id: n.id, label: `${'\u2014 '.repeat(depth)}${n.name}` });
            out.push(...collectFolderOptions(n.children || [], depth + 1));
        }
    }
    return out;
}

/** DB may still store these from older builds; show as web link. */
const LEGACY_LINKISH_DOC_TYPES = new Set(['google_doc', 'google_sheet']);

/** @param {(key: string) => string} t — vue-i18n `t` */
export function projectDocTypeLabel(t, docType) {
    if (LEGACY_LINKISH_DOC_TYPES.has(docType)) {
        return t('projects.pdDocsType_link');
    }
    const key = `projects.pdDocsType_${docType}`;
    const x = t(key);

    return x === key ? docType : x;
}
