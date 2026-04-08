/** Báo cáo công việc theo nhân sự (tab Báo cáo → Công việc). */

export function startOfDay(d) {
    const x = d instanceof Date ? new Date(d.getTime()) : new Date(d);
    if (Number.isNaN(x.getTime())) {
        return null;
    }
    x.setHours(0, 0, 0, 0);
    return x;
}

export function pct(part, whole) {
    if (whole == null || whole <= 0) {
        return null;
    }
    return Math.round((part / whole) * 10000) / 100;
}

/**
 * Công việc chưa done: tính đúng hạn / quá hạn theo due_date so với hôm nay.
 * Done: đúng hạn nếu ngày cập nhật (proxy hoàn thành) ≤ due; quá hạn nếu > due (cần cả hai).
 *
 * @returns {Array<{ stt: number, userId: number|null, employeeCode: string, name: string, email: string, dept: string, total: number, openOnTime: number, openOverdue: number, doneOnTime: number, doneLate: number }>}
 */
export function buildPersonnelTaskReportRows(tasks, { today = new Date(), unassignedLabel = '—' } = {}) {
    const t0 = startOfDay(today);
    const byKey = new Map();

    const touch = (assigneeId, assignee) => {
        const k = assigneeId == null ? '__none' : String(assigneeId);
        if (!byKey.has(k)) {
            const id = assigneeId;
            const name =
                id == null
                    ? unassignedLabel
                    : assignee?.name || assignee?.email || `#${id}`;
            const email = id == null ? '' : assignee?.email || '';
            byKey.set(k, {
                key: k,
                userId: id,
                employeeCode: id == null ? '—' : `NV-${String(id).padStart(5, '0')}`,
                name,
                email,
                dept: id == null ? '' : assignee?.role || '',
                total: 0,
                openOnTime: 0,
                openOverdue: 0,
                doneOnTime: 0,
                doneLate: 0,
            });
        }
        return byKey.get(k);
    };

    for (const task of tasks || []) {
        const row = touch(task.assignee_id ?? null, task.assignee);
        row.total += 1;
        if (task.status === 'done') {
            const due = task.due_date ? startOfDay(task.due_date) : null;
            const doneDay = task.updated_at ? startOfDay(task.updated_at) : t0;
            if (!due || doneDay <= due) {
                row.doneOnTime += 1;
            } else {
                row.doneLate += 1;
            }
        } else {
            const due = task.due_date ? startOfDay(task.due_date) : null;
            if (due && t0 && due < t0) {
                row.openOverdue += 1;
            } else {
                row.openOnTime += 1;
            }
        }
    }

    const rows = [...byKey.values()];
    rows.sort((a, b) => {
        if (a.key === '__none') {
            return 1;
        }
        if (b.key === '__none') {
            return -1;
        }
        return String(a.name).localeCompare(String(b.name), 'vi');
    });

    return rows.map((r, i) => ({ ...r, stt: i + 1 }));
}
