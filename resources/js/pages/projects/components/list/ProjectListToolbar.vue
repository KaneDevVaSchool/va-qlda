<!-- eslint-disable vue/no-mutating-props -- filters is parent reactive -->
<template>
    <div class="ppms-pl-subtoolbar ppms-pl-subtoolbar--unified ppms-mt">
        <div class="ppms-pl-subtoolbar-left">
            <div class="ppms-pl-leading-cluster">
                <div class="ppms-pl-view-toggle" role="group" :aria-label="t('projects.viewModeToggleAria')">
                    <button
                        type="button"
                        class="ppms-pl-view-btn"
                        :class="{ 'ppms-pl-view-btn--active': viewMode === 'list' }"
                        :title="t('projects.viewModeList')"
                        :aria-pressed="viewMode === 'list'"
                        :aria-label="t('projects.viewModeList')"
                        @click="$emit('set-view-mode', 'list')"
                    >
                        <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <line x1="8" y1="6" x2="21" y2="6" />
                            <line x1="8" y1="12" x2="21" y2="12" />
                            <line x1="8" y1="18" x2="21" y2="18" />
                            <line x1="3" y1="6" x2="3.01" y2="6" />
                            <line x1="3" y1="12" x2="3.01" y2="12" />
                            <line x1="3" y1="18" x2="3.01" y2="18" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="ppms-pl-view-btn"
                        :class="{ 'ppms-pl-view-btn--active': viewMode === 'kanban' }"
                        :title="t('projects.viewModeKanbanTitle')"
                        :aria-pressed="viewMode === 'kanban'"
                        :aria-label="t('projects.viewModeKanban')"
                        @click="$emit('set-view-mode', 'kanban')"
                    >
                        <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="4" y="3" width="4.5" height="18" rx="1" />
                            <rect x="10.5" y="3" width="4.5" height="18" rx="1" />
                            <rect x="17" y="3" width="4.5" height="18" rx="1" />
                            <line x1="5.25" y1="7" x2="7.25" y2="7" stroke-linecap="round" />
                            <line x1="5.25" y1="10" x2="7.25" y2="10" stroke-linecap="round" />
                            <line x1="11.75" y1="7" x2="13.75" y2="7" stroke-linecap="round" />
                            <line x1="11.75" y1="10" x2="13.75" y2="10" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>
                <button type="button" class="ppms-btn-primary ppms-pl-new-project-btn" @click="$emit('open-create')">
                    <svg
                        class="ppms-pl-ico-svg ppms-pl-ico-svg--on-primary"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.25"
                        stroke-linecap="round"
                        aria-hidden="true"
                    >
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    <span>{{ t('projects.newProject') }}</span>
                </button>
            </div>
        </div>
        <div v-if="!loading" class="ppms-pl-subtoolbar-right ppms-pl-subtoolbar-right--fill">
            <label class="ppms-field ppms-field--inline ppms-pl-team-filter-field ppms-pl-subtoolbar-team">
                <span>{{ t('projects.filterTeam') }}</span>
                <select v-model="filters.team_id" class="ppms-select ppms-pl-team-select" @change="$emit('filter-change')">
                    <option value="">{{ t('projects.filterTeamAll') }}</option>
                    <option v-for="tm in teamOptions" :key="'tf-' + tm.id" :value="String(tm.id)">{{ tm.name }}</option>
                </select>
            </label>
            <div v-if="viewMode === 'list' && total > 0" class="ppms-pl-page-cluster ppms-pl-page-cluster--list">
                <div class="ppms-pl-page-arrows">
                    <button
                        type="button"
                        class="ppms-btn-ghost ppms-pl-page-btn"
                        :disabled="page <= 1"
                        :aria-label="t('projects.prevPage')"
                        @click="$emit('go-page', page - 1)"
                    >
                        <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <polyline points="15 18 9 12 15 6" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="ppms-btn-ghost ppms-pl-page-btn"
                        :disabled="page >= lastPage"
                        :aria-label="t('projects.nextPage')"
                        @click="$emit('go-page', page + 1)"
                    >
                        <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </button>
                </div>
                <span class="ppms-pl-page-indicator ppms-muted" aria-live="polite">{{
                    t('projects.pagination', { current: page, last: lastPage })
                }}</span>
                <span
                    v-if="rangeFrom != null && rangeTo != null"
                    class="ppms-pl-page-range ppms-muted"
                    aria-live="polite"
                    >{{ t('projects.paginationRange', { from: rangeFrom, to: rangeTo, total }) }}</span
                >
                <label class="ppms-field ppms-field--inline ppms-pl-per-page">
                    <span>{{ t('projects.perPageLabel') }}</span>
                    <select
                        class="ppms-select ppms-pl-per-page-select"
                        :value="perPage"
                        @change="onPerPageChange"
                    >
                        <option v-for="n in perPageSelectOptions" :key="'pp-' + n" :value="n">{{ n }}</option>
                    </select>
                </label>
            </div>
            <div v-else-if="viewMode === 'kanban' && lastPage > 1" class="ppms-pl-page-arrows">
                <button
                    type="button"
                    class="ppms-btn-ghost ppms-pl-page-btn"
                    :disabled="page <= 1"
                    :aria-label="t('projects.prevPage')"
                    @click="$emit('go-page', page - 1)"
                >
                    <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="15 18 9 12 15 6" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="ppms-btn-ghost ppms-pl-page-btn"
                    :disabled="page >= lastPage"
                    :aria-label="t('projects.nextPage')"
                    @click="$emit('go-page', page + 1)"
                >
                    <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </button>
            </div>
            <div class="ppms-pl-tbar-actions">
                <button type="button" class="ppms-btn-ghost ppms-pl-tbar-btn" @click="$emit('toolbar-labels')">
                    <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path
                            d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82zM7 7h.01"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>{{ t('projects.toolbarLabels') }}</span>
                </button>
                <button v-if="canImport" type="button" class="ppms-btn-ghost ppms-pl-tbar-btn" @click="$emit('open-import')">
                    <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>{{ t('projects.toolbarImport') }}</span>
                </button>
                <button v-if="canExport" type="button" class="ppms-btn-ghost ppms-pl-tbar-btn" @click="$emit('export-csv-filtered')">
                    <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>{{ t('projects.toolbarExport') }}</span>
                </button>
                <div ref="menuElRef" class="ppms-toolbar-dropdown ppms-toolbar-dropdown--pl">
                    <button
                        type="button"
                        class="ppms-btn-ghost ppms-pl-tbar-btn ppms-pl-tbar-btn--menu ppms-toolbar-dropdown-trigger"
                        :aria-expanded="menuOpen"
                        aria-haspopup="true"
                        :aria-controls="menuOpen ? 'ppms-project-toolbar-menu' : undefined"
                        @click.stop="menuOpen = !menuOpen"
                    >
                        <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <line x1="4" y1="21" x2="4" y2="14" />
                            <line x1="4" y1="10" x2="4" y2="3" />
                            <line x1="12" y1="21" x2="12" y2="12" />
                            <line x1="12" y1="8" x2="12" y2="3" />
                            <line x1="20" y1="21" x2="20" y2="16" />
                            <line x1="20" y1="12" x2="20" y2="3" />
                            <circle cx="4" cy="12" r="2" />
                            <circle cx="12" cy="14" r="2" />
                            <circle cx="20" cy="8" r="2" />
                        </svg>
                        <span>{{ t('projects.toolbarSettings') }}</span>
                        <svg
                            class="ppms-pl-ico-svg ppms-pl-ico-svg--caret"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>
                    <ul v-show="menuOpen" id="ppms-project-toolbar-menu" class="ppms-toolbar-dropdown-panel" role="menu">
                        <li role="none">
                            <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="$emit('copy-link')">
                                {{ t('projects.copyShareLink') }}
                            </button>
                        </li>
                        <li role="none">
                            <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="$emit('save-view')">
                                {{ t('projects.saveViewBtn') }}
                            </button>
                        </li>
                        <li role="none">
                            <div class="ppms-dropdown-menuitem ppms-dropdown-menuitem--static" role="presentation">
                                <label class="ppms-field ppms-field--compact ppms-pl-sort-inline">
                                    <span>{{ t('projects.filterSort') }}</span>
                                    <select v-model="filters.sort" class="ppms-project-list-sort-select" @change="$emit('filter-change')">
                                        <option value="type_asc">{{ t('projects.sortTypeAsc') }}</option>
                                        <option value="updated_desc">{{ t('projects.sortUpdatedDesc') }}</option>
                                        <option value="progress_desc">{{ t('projects.sortProgressDesc') }}</option>
                                        <option value="progress_asc">{{ t('projects.sortProgressAsc') }}</option>
                                        <option value="name_asc">{{ t('projects.sortNameAsc') }}</option>
                                        <option value="deadline_asc">{{ t('projects.sortDeadlineAsc') }}</option>
                                    </select>
                                </label>
                            </div>
                        </li>
                        <li role="none">
                            <div
                                class="ppms-dropdown-menuitem ppms-dropdown-menuitem--static ppms-pl-column-picker-wrap"
                                role="group"
                                :aria-label="t('projects.columnPickerTitle')"
                            >
                                <div class="ppms-pl-column-picker-title">{{ t('projects.columnPickerTitle') }}</div>
                                <p class="ppms-pl-column-picker-hint ppms-muted">{{ t('projects.columnPickerHint') }}</p>
                                <ul class="ppms-pl-column-picker-list">
                                    <li v-for="opt in columnPickerOptions" :key="'col-' + opt.key">
                                        <label class="ppms-pl-column-picker-row">
                                            <input
                                                type="checkbox"
                                                :checked="columnVisibility[opt.key]"
                                                :disabled="opt.locked"
                                                @change="$emit('column-toggle', opt.key, $event)"
                                            />
                                            <span>{{ t('projects.' + opt.labelKey) }}</span>
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <template v-if="canExport">
                            <li role="none">
                                <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="$emit('export-csv-filtered')">
                                    {{ t('projects.exportCsvFiltered') }}
                                </button>
                            </li>
                            <li role="none">
                                <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="$emit('export-json-filtered')">
                                    {{ t('projects.exportJsonFiltered') }}
                                </button>
                            </li>
                            <li role="none">
                                <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="$emit('export-pdf-filtered')">
                                    {{ t('projects.exportPdfFiltered') }}
                                </button>
                            </li>
                            <li v-if="selectedCount > 0" role="none">
                                <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="$emit('export-csv-selected')">
                                    {{ t('projects.exportCsvSelection') }}
                                </button>
                            </li>
                            <li v-if="selectedCount > 0" role="none">
                                <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="$emit('export-json-selected')">
                                    {{ t('projects.exportJsonSelection') }}
                                </button>
                            </li>
                            <li v-if="selectedCount > 0" role="none">
                                <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="$emit('export-pdf-selected')">
                                    {{ t('projects.exportPdfSelection') }}
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    viewMode: { type: String, required: true },
    loading: { type: Boolean, required: true },
    page: { type: Number, required: true },
    lastPage: { type: Number, required: true },
    total: { type: Number, default: 0 },
    rangeFrom: { type: Number, default: null },
    rangeTo: { type: Number, default: null },
    perPage: { type: Number, default: 50 },
    canImport: { type: Boolean, default: false },
    canExport: { type: Boolean, default: false },
    filters: { type: Object, required: true },
    teamOptions: { type: Array, default: () => [] },
    columnPickerOptions: { type: Array, required: true },
    columnVisibility: { type: Object, required: true },
    selectedCount: { type: Number, required: true },
});

const perPageSelectOptions = computed(() => {
    const base = [10, 25, 50, 100];
    const p = props.perPage;
    if (typeof p === 'number' && !base.includes(p)) {
        return [...base, p].sort((a, b) => a - b);
    }

    return base;
});

const menuOpen = defineModel('menuOpen', { type: Boolean, default: false });

const menuElRef = ref(null);

const emit = defineEmits([
    'set-view-mode',
    'open-create',
    'go-page',
    'set-per-page',
    'toolbar-labels',
    'open-import',
    'export-csv-filtered',
    'export-json-filtered',
    'export-pdf-filtered',
    'export-csv-selected',
    'export-json-selected',
    'export-pdf-selected',
    'copy-link',
    'save-view',
    'filter-change',
    'column-toggle',
]);

function onPerPageChange(e) {
    const raw = e?.target && 'value' in e.target ? e.target.value : '';
    const n = Number(raw);
    if (!Number.isNaN(n)) {
        emit('set-per-page', n);
    }
}

defineExpose({
    getMenuEl: () => menuElRef.value,
});
</script>
