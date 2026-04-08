/**
 * Cây công việc theo phase + parent_id để hiển thị board/Gantt giống 1Office.
 * @param {Array<{ id: number, parent_id?: number|null, project_phase_id?: number|null, sort_order?: number }>} tasks
 * @param {Array<{ id: number, name: string, sort_order?: number }>} phases
 * @param {Array<{ task_id: number }>} bars — thứ tự từ API Gantt (đã lọc)
 */

function taskCompare(a, b) {
    const so = (a.sort_order ?? 0) - (b.sort_order ?? 0);
    if (so !== 0) {
        return so;
    }

    return a.id - b.id;
}

/**
 * @param {typeof tasks} pool
 * @param {Set<number>} idSet
 * @returns {typeof tasks}
 */
function rootsInPhasePool(pool, idSet) {
    return pool
        .filter((t) => {
            if (!t.parent_id) {
                return true;
            }
            return !idSet.has(t.parent_id);
        })
        .sort(taskCompare);
}

/**
 * @param {typeof tasks} pool
 * @param {number} parentId
 * @returns {typeof tasks}
 */
function childrenInPool(pool, parentId) {
    return pool.filter((t) => t.parent_id === parentId).sort(taskCompare);
}

/**
 * @param {typeof tasks} pool
 * @param {Set<number>} idSet
 * @returns {Array<{ task: object, depth: number }>}
 */
function flattenPhaseTree(pool, idSet) {
    const out = [];

    function walk(parentId, depth) {
        const level =
            parentId === null
                ? rootsInPhasePool(pool, idSet)
                : childrenInPool(pool, parentId);
        for (const task of level) {
            out.push({ task, depth });
            walk(task.id, depth + 1);
        }
    }

    walk(null, 0);

    return out;
}

/**
 * @param {Array<{ task_id: number }>} bars
 * @param {Map<number, object>} taskById
 * @returns {Array<{ bar: object, task: object }>}
 */
export function barsWithTasks(bars, taskById) {
    const out = [];
    for (const bar of bars || []) {
        const task = taskById.get(bar.task_id);
        if (task) {
            out.push({ bar, task });
        }
    }
    return out;
}

/**
 * Gom task hiển thị (theo bars) theo phase, thứ tự phase theo `phases`, cây trong từng phase.
 * @param {(key: string) => boolean} isPhaseCollapsed — true = đang thu gọn (ẩn task con)
 * @returns {Array<
 *   | { kind: 'phase'; phaseKey: string; phaseId: number|null; title: string; taskCount: number; isOther: boolean }
 *   | { kind: 'task'; bar: object; task: object; depth: number }
 * >}
 */
export function buildBoardRows(items, phases, isPhaseCollapsed) {
    const phaseList = [...(phases || [])].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
    const phaseMeta = new Map(phaseList.map((p) => [p.id, p]));

    const byPhase = new Map();
    for (const item of items) {
        const pid = item.task.project_phase_id ?? null;
        const key = pid === null ? '_none_' : String(pid);
        if (!byPhase.has(key)) {
            byPhase.set(key, []);
        }
        byPhase.get(key).push(item);
    }

    const phaseKeysOrdered = [];
    for (const p of phaseList) {
        if (byPhase.has(String(p.id))) {
            phaseKeysOrdered.push(String(p.id));
        }
    }
    if (byPhase.has('_none_')) {
        phaseKeysOrdered.push('_none_');
    }
    for (const k of byPhase.keys()) {
        if (!phaseKeysOrdered.includes(k)) {
            phaseKeysOrdered.push(k);
        }
    }

    const rows = [];

    for (const key of phaseKeysOrdered) {
        const pool = byPhase.get(key);
        if (!pool?.length) {
            continue;
        }
        const sample = pool[0].task;
        const phaseId = sample.project_phase_id ?? null;
        const isOther = key === '_none_';
        const title = isOther ? '' : phaseMeta.get(phaseId)?.name || `Phase ${phaseId}`;

        rows.push({
            kind: 'phase',
            phaseKey: key,
            phaseId,
            title,
            isOther,
            taskCount: pool.length,
        });

        if (typeof isPhaseCollapsed === 'function' && isPhaseCollapsed(key)) {
            continue;
        }

        const tasksOnly = pool.map((p) => p.task);
        const idSet = new Set(tasksOnly.map((t) => t.id));
        const ordered = flattenPhaseTree(tasksOnly, idSet);

        const itemByTaskId = new Map(pool.map((p) => [p.task.id, p]));
        for (const { task, depth } of ordered) {
            const it = itemByTaskId.get(task.id);
            if (it) {
                rows.push({
                    kind: 'task',
                    bar: it.bar,
                    task: it.task,
                    depth,
                });
            }
        }
    }

    return rows;
}

/**
 * @param {number} taskId
 * @param {Map<number, object>} taskById
 */
export function taskDepthInProject(taskId, taskById) {
    let d = 0;
    let cur = taskById.get(taskId);
    const seen = new Set();
    while (cur?.parent_id != null && d < 48) {
        if (seen.has(cur.id)) {
            break;
        }
        seen.add(cur.id);
        d += 1;
        cur = taskById.get(cur.parent_id);
    }
    return d;
}
