/** Single source for project detail UI (tabs, timeline, i18n phase keys). */

export const TIMELINE_PHASES = ['planning', 'development', 'uat', 'done', 'maintenance'];

/** Thứ tự giống thanh tab (URL `?tab=`). */
export const PROJECT_DETAIL_TAB_IDS = ['info', 'tasks', 'finance', 'supplies', 'attachments'];

export const PROJECT_DETAIL_TAB = {
    info: 'info',
    tasks: 'tasks',
    finance: 'finance',
    supplies: 'supplies',
    attachments: 'attachments',
};

export function isValidProjectDetailTab(id) {
    return typeof id === 'string' && PROJECT_DETAIL_TAB_IDS.includes(id);
}

export function projectGanttFiltersStorageKey(projectId) {
    return `ppms:projectGanttFilters:${String(projectId)}`;
}
