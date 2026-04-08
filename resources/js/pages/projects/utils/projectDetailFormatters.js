/** Pure display helpers for project detail (no Vue / i18n). */

export function initials(name) {
    const s = String(name || '').trim();
    if (!s) {
        return '?';
    }
    const parts = s.split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return s.slice(0, 2).toUpperCase();
}

export function formatBytes(n) {
    const x = Number(n);
    if (!x) {
        return '0 B';
    }
    const u = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let v = x;
    while (v >= 1024 && i < u.length - 1) {
        v /= 1024;
        i++;
    }
    return `${i ? v.toFixed(1) : v} ${u[i]}`;
}

export function fileExt(filename) {
    const m = String(filename || '').match(/\.([a-z0-9]+)$/i);
    return m ? m[1].slice(0, 4).toLowerCase() : '';
}

/**
 * `path_label` từ API dạng "Thư mục A / Thư mục B / tên_mục" — trả về chỉ chuỗi thư mục cha "A › B".
 * @param {{ path_label?: string|null }} row
 */
export function parentFolderBreadcrumb(row) {
    const raw = (row?.path_label || '').trim();
    if (!raw) {
        return '';
    }
    const parts = raw.split(' / ').map((s) => s.trim()).filter(Boolean);
    if (parts.length <= 1) {
        return '';
    }
    return parts.slice(0, -1).join(' › ');
}

/**
 * Các phân đoạn đường dẫn gốc → … → mục (để hiển thị dạng cây).
 * @param {{ path_label?: string|null }} row
 * @returns {string[]}
 */
export function pathLabelTreeSegments(row) {
    const raw = (row?.path_label || '').trim();
    if (!raw) {
        return [];
    }
    return raw.split(' / ').map((s) => s.trim()).filter(Boolean);
}

export function formatActivityTime(iso) {
    if (!iso) {
        return '—';
    }

    return String(iso).slice(0, 19).replace('T', ' ');
}

/** @param {{ done: number, inProgress: number, other: number }} row */
export function workloadTitle(row) {
    return `${row.done} / ${row.inProgress} / ${row.other}`;
}

/** @param {(key: string) => string} t */
export function formatActivityAction(t, ev) {
    const safe = String(ev.action || '').replace(/\./g, '_');
    const key = `projects.pdActivity_${safe}`;
    const x = t(key);

    return x === key ? ev.action : x;
}

/** @param {(key: string) => string} t */
export function ganttTaskStatusLabel(t, status) {
    const key = `projects.taskStatus.${status}`;
    const translated = t(key);

    return translated === key ? status : translated;
}
