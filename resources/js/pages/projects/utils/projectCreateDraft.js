/** Bản nháp form «Tạo dự án» trong trình duyệt (localStorage). */

export const PROJECT_CREATE_DRAFT_KEY = 'ppms.projectCreateDraft.v1';

/**
 * @param {Record<string, unknown>} form
 * @returns {Record<string, unknown>}
 */
export function serializeProjectCreateForm(form) {
    return {
        name: form.name ?? '',
        type: form.type ?? 'delivery',
        phase: form.phase ?? 'planning',
        status: form.status ?? 'on_track',
        owner_id: form.owner_id ?? '',
        deadline: form.deadline ?? '',
        start_date: form.start_date ?? '',
        progress_calc: form.progress_calc ?? 'weighted_tasks',
        executor_user_ids: Array.isArray(form.executor_user_ids) ? [...form.executor_user_ids] : [],
        follower_user_ids: Array.isArray(form.follower_user_ids) ? [...form.follower_user_ids] : [],
        permission_preset: form.permission_preset ?? 'org_default',
        description: form.description ?? '',
        customer_name: form.customer_name ?? '',
        customer_email: form.customer_email ?? '',
        department_id: form.department_id ?? '',
        block_id: form.block_id ?? '',
        suppliers_text: form.suppliers_text ?? '',
        labels_text: form.labels_text ?? '',
        project_code: form.project_code ?? '',
        progress_pct: form.progress_pct ?? '',
        estimated_value: form.estimated_value ?? '',
        team_id: form.team_id ?? '',
    };
}

/**
 * @param {Record<string, unknown>} target — reactive form từ ProjectList
 * @param {Record<string, unknown>} data
 */
export function applyProjectCreateForm(target, data) {
    if (!data || typeof data !== 'object') {
        return;
    }
    const t = target;
    t.name = data.name ?? '';
    t.type = data.type ?? 'delivery';
    t.phase = data.phase ?? 'planning';
    t.status = data.status ?? 'on_track';
    t.owner_id = data.owner_id ?? '';
    t.deadline = data.deadline ?? '';
    t.start_date = data.start_date ?? '';
    t.progress_calc = data.progress_calc ?? 'weighted_tasks';
    t.executor_user_ids = Array.isArray(data.executor_user_ids) ? [...data.executor_user_ids] : [];
    t.follower_user_ids = Array.isArray(data.follower_user_ids) ? [...data.follower_user_ids] : [];
    t.permission_preset = data.permission_preset ?? 'org_default';
    t.description = data.description ?? '';
    t.customer_name = data.customer_name ?? '';
    t.customer_email = data.customer_email ?? '';
    t.department_id = data.department_id ?? '';
    t.block_id = data.block_id ?? '';
    t.suppliers_text = data.suppliers_text ?? '';
    t.labels_text = data.labels_text ?? '';
    t.project_code = data.project_code ?? '';
    t.progress_pct = data.progress_pct ?? '';
    t.estimated_value = data.estimated_value ?? '';
    t.team_id = data.team_id ?? '';
}

/**
 * @returns {{ v: number, savedAt: number, form: Record<string, unknown> } | null}
 */
export function loadProjectCreateDraft() {
    try {
        const raw = localStorage.getItem(PROJECT_CREATE_DRAFT_KEY);
        if (!raw) {
            return null;
        }
        const o = JSON.parse(raw);
        if (o?.v !== 1 || !o.form || typeof o.form !== 'object') {
            return null;
        }
        return o;
    } catch {
        return null;
    }
}

/**
 * @param {Record<string, unknown>} form
 */
export function saveProjectCreateDraft(form) {
    const payload = {
        v: 1,
        savedAt: Date.now(),
        form: serializeProjectCreateForm(form),
    };
    localStorage.setItem(PROJECT_CREATE_DRAFT_KEY, JSON.stringify(payload));
}

export function clearProjectCreateDraft() {
    try {
        localStorage.removeItem(PROJECT_CREATE_DRAFT_KEY);
    } catch {
        /* ignore */
    }
}
