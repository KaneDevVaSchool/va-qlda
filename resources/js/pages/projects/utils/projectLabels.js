/** Shared label helpers for project list + detail. */

export function parseCommaLabelTokens(s) {
    return String(s || '')
        .split(/[,;]/)
        .map((x) => x.trim())
        .filter(Boolean);
}

export function projectLabelList(p) {
    return Array.isArray(p?.labels) ? p.labels.filter(Boolean) : [];
}

export function projectLabelsPreview(p, max = 3) {
    const list = projectLabelList(p);
    const show = list.slice(0, max);

    return { show, more: Math.max(0, list.length - max) };
}
