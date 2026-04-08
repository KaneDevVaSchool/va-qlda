/** Progress + deadline display for project list / kanban. */

export function clampProgress(v) {
    const n = Number(v);
    if (Number.isNaN(n)) {
        return 0;
    }

    return Math.min(100, Math.max(0, n));
}

export function formatProgress(v) {
    return clampProgress(v).toFixed(1);
}

export function progressToneClass(v) {
    const p = clampProgress(v);
    if (p >= 100) {
        return 'ppms-progress-fill--done';
    }
    if (p >= 70) {
        return 'ppms-progress-fill--good';
    }
    if (p >= 30) {
        return 'ppms-progress-fill--mid';
    }

    return 'ppms-progress-fill--low';
}

export function deadlineTone(iso) {
    if (!iso) {
        return { cls: '', key: null };
    }
    const s = String(iso).slice(0, 10);
    const parts = s.split('-');
    if (parts.length !== 3) {
        return { cls: 'ppms-deadline--ok', key: null };
    }
    const end = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);
    const diffDays = Math.round((end.getTime() - today.getTime()) / 86400000);
    if (diffDays < 0) {
        return { cls: 'ppms-deadline--overdue', key: 'projects.deadlineOverdue' };
    }
    if (diffDays <= 7) {
        return { cls: 'ppms-deadline--soon', key: 'projects.deadlineSoon' };
    }

    return { cls: 'ppms-deadline--ok', key: null };
}

export function formatDeadline(iso) {
    if (!iso) {
        return '';
    }
    const s = String(iso).slice(0, 10);
    const [y, m, d] = s.split('-');

    return d && m && y ? `${d}/${m}/${y}` : s;
}
