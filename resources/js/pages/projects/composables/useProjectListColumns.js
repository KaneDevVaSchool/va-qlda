import { computed, reactive } from 'vue';

const PPMS_PROJECT_LIST_COLUMNS_KEY = 'ppms-project-list-columns';

export const PL_COL_ORDER = [
    'admin',
    'code',
    'name',
    'team',
    'participants',
    'progress',
    'tasks',
    'start',
    'actualStart',
    'end',
    'status',
    'actions',
];

export const COLUMN_LABEL_KEYS = {
    admin: 'colAdmin',
    code: 'colCode',
    name: 'colName',
    team: 'colTeam',
    participants: 'colParticipants',
    progress: 'colProgress',
    tasks: 'colTasks',
    start: 'colStart',
    actualStart: 'colActualStart',
    end: 'colEnd',
    status: 'colStatus',
    actions: 'colActions',
};

function defaultColumnVisibility() {
    return PL_COL_ORDER.reduce((acc, k) => {
        acc[k] = true;

        return acc;
    }, {});
}

function loadProjectListColumns() {
    const d = defaultColumnVisibility();
    try {
        const raw = localStorage.getItem(PPMS_PROJECT_LIST_COLUMNS_KEY);
        if (raw) {
            const o = JSON.parse(raw);
            if (o && typeof o === 'object') {
                for (const k of PL_COL_ORDER) {
                    if (typeof o[k] === 'boolean') {
                        d[k] = o[k];
                    }
                }
            }
        }
    } catch {
        /* ignore */
    }
    d.name = true;
    d.actions = true;

    return d;
}

export function useProjectListColumns() {
    const columnVisibility = reactive(loadProjectListColumns());

    function persistProjectListColumns() {
        columnVisibility.name = true;
        columnVisibility.actions = true;
        try {
            localStorage.setItem(PPMS_PROJECT_LIST_COLUMNS_KEY, JSON.stringify({ ...columnVisibility }));
        } catch {
            /* ignore */
        }
    }

    function colVis(key) {
        return columnVisibility[key] !== false;
    }

    function columnLocked(key) {
        return key === 'name' || key === 'actions';
    }

    function onColumnToggle(key, ev) {
        if (columnLocked(key)) {
            return;
        }
        columnVisibility[key] = Boolean(ev?.target?.checked);
        persistProjectListColumns();
    }

    const columnPickerOptions = computed(() =>
        PL_COL_ORDER.map((key) => ({
            key,
            labelKey: COLUMN_LABEL_KEYS[key],
            locked: columnLocked(key),
        })),
    );

    return {
        columnVisibility,
        colVis,
        columnLocked,
        onColumnToggle,
        columnPickerOptions,
        PL_COL_ORDER,
    };
}
