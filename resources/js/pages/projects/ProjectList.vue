<template>
    <main id="project-list-main" class="ppms-page ppms-project-list-page" tabindex="-1">
        <h1 class="ppms-sr-only">{{ t('projects.pageHeading') }}</h1>
        <datalist id="ppms-pl-label-datalist">
            <option v-for="s in labelsSuggestions" :key="'gdl-' + s" :value="s" />
        </datalist>

        <section v-if="savedViews.length" class="ppms-card ppms-mt ppms-saved-views ppms-saved-views--compact">
            <h3 class="ppms-saved-views-title">{{ t('projects.savedViewsTitle') }}</h3>
            <div class="ppms-saved-views-scroll">
                <ul class="ppms-saved-views-list">
                    <li v-for="v in savedViews" :key="v.id">
                        <button type="button" class="ppms-linklike" @click="applySavedView(v)">{{ v.name }}</button>
                        <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="removeSavedView(v.id)">
                            {{ t('projects.removeView') }}
                        </button>
                    </li>
                </ul>
            </div>
        </section>

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
                            @click="setViewMode('list')"
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
                            @click="setViewMode('kanban')"
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
                    <button type="button" class="ppms-btn-primary ppms-pl-new-project-btn" @click="showForm = true">
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
                <div v-if="lastPage > 1" class="ppms-pl-page-arrows">
                    <button
                        type="button"
                        class="ppms-btn-ghost ppms-pl-page-btn"
                        :disabled="page <= 1"
                        :aria-label="t('projects.prevPage')"
                        @click="goPage(page - 1)"
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
                        @click="goPage(page + 1)"
                    >
                        <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </button>
                </div>
                <div class="ppms-pl-tbar-actions">
                    <button type="button" class="ppms-btn-ghost ppms-pl-tbar-btn" @click="onToolbarLabels">
                        <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path
                                d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82zM7 7h.01"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <span>{{ t('projects.toolbarLabels') }}</span>
                    </button>
                    <button
                        v-if="canImport"
                        type="button"
                        class="ppms-btn-ghost ppms-pl-tbar-btn"
                        @click="openImportModal"
                    >
                        <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>{{ t('projects.toolbarImport') }}</span>
                    </button>
                    <button
                        v-if="canExport"
                        type="button"
                        class="ppms-btn-ghost ppms-pl-tbar-btn"
                        @click="exportFiltered('csv')"
                    >
                        <svg class="ppms-pl-ico-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>{{ t('projects.toolbarExport') }}</span>
                    </button>
                    <div ref="toolbarMenuEl" class="ppms-toolbar-dropdown ppms-toolbar-dropdown--pl">
                        <button
                            type="button"
                            class="ppms-btn-ghost ppms-pl-tbar-btn ppms-pl-tbar-btn--menu ppms-toolbar-dropdown-trigger"
                            :aria-expanded="toolbarMenuOpen"
                            aria-haspopup="true"
                            :aria-controls="toolbarMenuOpen ? 'ppms-project-toolbar-menu' : undefined"
                            @click.stop="toolbarMenuOpen = !toolbarMenuOpen"
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
                        <ul
                            v-show="toolbarMenuOpen"
                            id="ppms-project-toolbar-menu"
                            class="ppms-toolbar-dropdown-panel"
                            role="menu"
                        >
                            <li role="none">
                                <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="onToolbarCopyLink">
                                    {{ t('projects.copyShareLink') }}
                                </button>
                            </li>
                            <li role="none">
                                <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="onToolbarSaveView">
                                    {{ t('projects.saveViewBtn') }}
                                </button>
                            </li>
                            <li role="none">
                                <div class="ppms-dropdown-menuitem ppms-dropdown-menuitem--static" role="presentation">
                                    <label class="ppms-field ppms-field--compact ppms-pl-sort-inline">
                                        <span>{{ t('projects.filterSort') }}</span>
                                        <select v-model="filters.sort" class="ppms-project-list-sort-select" @change="onFilterChange">
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
                                                    @change="onColumnToggle(opt.key, $event)"
                                                />
                                                <span>{{ t('projects.' + opt.labelKey) }}</span>
                                            </label>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <template v-if="canExport">
                                <li role="none">
                                    <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="onToolbarExportCsv">
                                        {{ t('projects.exportCsvFiltered') }}
                                    </button>
                                </li>
                                <li role="none">
                                    <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="onToolbarExportJson">
                                        {{ t('projects.exportJsonFiltered') }}
                                    </button>
                                </li>
                                <li role="none">
                                    <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="onToolbarExportPdf">
                                        {{ t('projects.exportPdfFiltered') }}
                                    </button>
                                </li>
                                <li v-if="selectedProjectIds.length" role="none">
                                    <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="onToolbarExportCsvSelected">
                                        {{ t('projects.exportCsvSelection') }}
                                    </button>
                                </li>
                                <li v-if="selectedProjectIds.length" role="none">
                                    <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="onToolbarExportJsonSelected">
                                        {{ t('projects.exportJsonSelection') }}
                                    </button>
                                </li>
                                <li v-if="selectedProjectIds.length" role="none">
                                    <button type="button" class="ppms-dropdown-menuitem" role="menuitem" @click="onToolbarExportPdfSelected">
                                        {{ t('projects.exportPdfSelection') }}
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <aside
            v-if="canBulkDelete && selectedProjectIds.length && !loading"
            class="ppms-card ppms-bulk-dock ppms-bulk-dock--full ppms-mt ppms-bulk-dock-panel ppms-bulk-dock-panel--danger"
            role="region"
            :aria-label="t('projects.bulkBarTitle', { n: selectedProjectIds.length })"
        >
            <div class="ppms-bulk-dock-head ppms-bulk-dock-head--danger">
                <h3 class="ppms-bulk-dock-title">{{ t('projects.bulkBarTitle', { n: selectedProjectIds.length }) }}</h3>
                <button type="button" class="ppms-linklike ppms-bulk-dock-clear" @click="clearBulkSelection">
                    {{ t('projects.bulkClearSelection') }}
                </button>
            </div>
            <div class="ppms-bulk-danger-row">
                <div class="ppms-bulk-danger-icon" aria-hidden="true">
                    <svg class="ppms-bulk-danger-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
                        <line x1="10" y1="11" x2="10" y2="17" />
                        <line x1="14" y1="11" x2="14" y2="17" />
                    </svg>
                </div>
                <div class="ppms-bulk-danger-copy">
                    <h4 class="ppms-bulk-danger-title">{{ t('projects.bulkDeleteTitle') }}</h4>
                    <p class="ppms-bulk-danger-text">{{ t('projects.bulkDeleteHint', { n: selectedProjectIds.length }) }}</p>
                </div>
                <div class="ppms-bulk-danger-actions">
                    <button type="button" class="ppms-btn-danger-outline ppms-bulk-danger-btn" @click="runBulkDelete">
                        {{ t('projects.bulkDeleteBtn') }}
                    </button>
                </div>
            </div>
        </aside>

        <div
            v-if="loading"
            class="ppms-project-list-loading ppms-mt"
            role="status"
            aria-live="polite"
            aria-busy="true"
        >
            <span class="ppms-sr-only">{{ t('common.loading') }}</span>
            <div v-if="viewMode === 'list'" class="ppms-skeleton-table" aria-hidden="true">
                <div v-for="n in 6" :key="'sk-' + n" class="ppms-skeleton-row">
                    <div class="ppms-skeleton-cell ppms-skeleton-cell--lg" />
                    <div class="ppms-skeleton-cell ppms-skeleton-cell--sm" />
                    <div class="ppms-skeleton-cell ppms-skeleton-cell--md" />
                    <div class="ppms-skeleton-cell ppms-skeleton-cell--sm" />
                    <div class="ppms-skeleton-cell ppms-skeleton-cell--xs" />
                </div>
            </div>
            <div v-else class="ppms-pl-kanban-skeleton" aria-hidden="true">
                <div v-for="n in 6" :key="'ksk-' + n" class="ppms-pl-kanban-skeleton-col" />
            </div>
        </div>
        <section v-else class="ppms-project-list-table-section" :aria-label="t('projects.listSectionTitle')">
            <div v-if="!projects.length" class="ppms-empty-hint ppms-mt">
                <p class="ppms-empty-hint-text">{{ t('projects.tableEmpty') }}</p>
            </div>
            <div v-else-if="viewMode === 'list'" class="ppms-table-scroll ppms-table-scroll--sticky-head ppms-project-list-table-wrap ppms-mt">
                <table class="ppms-table ppms-table--project-staging">
                    <caption class="ppms-sr-only">
                        {{ t('projects.listSectionTitle') }}
                    </caption>
                    <thead>
                        <tr>
                            <th v-if="canBulk" class="ppms-th-check">
                                <input
                                    type="checkbox"
                                    :checked="allPageSelected"
                                    :aria-label="t('projects.selectAll')"
                                    @change="toggleSelectAll($event)"
                                />
                            </th>
                            <th v-if="colVis('admin')" class="ppms-th-admin">{{ t('projects.colAdmin') }}</th>
                            <th v-if="colVis('code')" class="ppms-th-code">{{ t('projects.colCode') }}</th>
                            <th v-if="colVis('name')" class="ppms-th-name">{{ t('projects.colName') }}</th>
                            <th v-if="colVis('participants')" class="ppms-th-participants">{{ t('projects.colParticipants') }}</th>
                            <th v-if="colVis('progress')" class="ppms-th-process">{{ t('projects.colProgress') }}</th>
                            <th v-if="colVis('tasks')" class="ppms-th-num ppms-th-tasks">{{ t('projects.colTasks') }}</th>
                            <th v-if="colVis('start')" class="ppms-th-date">{{ t('projects.colStart') }}</th>
                            <th v-if="colVis('actualStart')" class="ppms-th-date">{{ t('projects.colActualStart') }}</th>
                            <th v-if="colVis('end')" class="ppms-th-date">{{ t('projects.colEnd') }}</th>
                            <th v-if="colVis('status')" class="ppms-th-status">{{ t('projects.colStatus') }}</th>
                            <th v-if="colVis('actions')" class="ppms-th-actions">{{ t('projects.colActions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(row, idx) in displayTableRows" :key="row.kind === 'group' ? 'g-' + row.type + '-' + idx : 'p-' + row.project.id">
                            <tr v-if="row.kind === 'group'" class="ppms-pl-group-row">
                                <td :colspan="groupColspan" class="ppms-pl-group-cell">
                                    <button
                                        type="button"
                                        class="ppms-pl-group-toggle"
                                        :aria-expanded="!isGroupCollapsed(row.type)"
                                        :aria-label="
                                            t('projects.groupToggleLabel', {
                                                type: t(`projects.typeShort.${row.type}`),
                                                count: row.count,
                                            })
                                        "
                                        @click="toggleGroupCollapse(row.type)"
                                    >
                                        <span
                                            class="ppms-pl-group-chevron"
                                            :class="{ 'ppms-pl-group-chevron--collapsed': isGroupCollapsed(row.type) }"
                                            aria-hidden="true"
                                            >▼</span
                                        >
                                        <span class="ppms-pl-group-title">{{ t(`projects.typeGroup.${row.type}`) }}</span>
                                        <span class="ppms-pl-group-count">({{ row.count }})</span>
                                    </button>
                                </td>
                            </tr>
                            <tr v-else class="ppms-pl-data-row">
                                <td v-if="canBulk" class="ppms-td-check">
                                    <input v-model="selectedProjectIds" type="checkbox" :value="row.project.id" />
                                </td>
                                <td v-if="colVis('admin')" class="ppms-td-admin">
                                    <button
                                        type="button"
                                        class="ppms-pl-usercell ppms-pl-usercell-trigger"
                                        :disabled="!row.project.owner"
                                        :aria-label="t('projects.avatarInfoAdmin')"
                                        @click="openAdminUserPopover($event, row.project)"
                                    >
                                        <span
                                            class="ppms-pl-avatar"
                                            :style="{ background: avatarColor(row.project.owner?.name || row.project.owner?.email || '?') }"
                                            :aria-hidden="true"
                                        >
                                            {{ userInitials(row.project.owner?.name) }}
                                        </span>
                                        <span class="ppms-pl-username">{{ row.project.owner?.name || '—' }}</span>
                                    </button>
                                </td>
                                <td v-if="colVis('code')" class="ppms-td-code ppms-muted">{{ projectCode(row.project) }}</td>
                                <td v-if="colVis('name')" class="ppms-td-name">
                                    <router-link class="ppms-pl-name-link" :to="'/projects/' + row.project.id">{{
                                        row.project.name
                                    }}</router-link>
                                    <div class="ppms-pl-name-labels-row">
                                        <div v-if="projectLabelList(row.project).length" class="ppms-pl-name-labels">
                                            <span
                                                v-for="(lb, li) in projectLabelsPreview(row.project).show"
                                                :key="'lb-' + row.project.id + '-' + li"
                                                class="ppms-pl-label-chip ppms-pl-label-chip--under-name"
                                                >{{ lb }}</span
                                            >
                                            <span v-if="projectLabelsPreview(row.project).more > 0" class="ppms-pl-labels-more"
                                                >+{{ projectLabelsPreview(row.project).more }}</span
                                            >
                                        </div>
                                        <template v-if="canBulk">
                                            <input
                                                v-if="quickLabelProjectId === row.project.id"
                                                ref="quickLabelInputEl"
                                                v-model="quickLabelText"
                                                type="text"
                                                class="ppms-pl-quick-label-input"
                                                list="ppms-pl-label-datalist"
                                                autocomplete="off"
                                                :placeholder="t('projects.labelAddPlaceholder')"
                                                :aria-label="t('projects.labelAddPlaceholder')"
                                                :disabled="quickLabelSavingId === row.project.id"
                                                @keydown.enter.prevent="submitQuickLabel(row.project)"
                                                @keydown.esc.prevent="closeQuickLabel"
                                                @click.stop
                                            />
                                            <button
                                                v-else
                                                type="button"
                                                class="ppms-pl-label-add-btn"
                                                :aria-label="t('projects.labelAddAria')"
                                                @click.stop="toggleQuickLabel(row.project)"
                                            >
                                                +
                                            </button>
                                        </template>
                                    </div>
                                </td>
                                <td v-if="colVis('participants')" class="ppms-td-participants">
                                    <div class="ppms-pl-participants">
                                        <button
                                            v-for="(slot, si) in participantVisibleSlots(row.project)"
                                            :key="'pa-' + row.project.id + '-' + si"
                                            type="button"
                                            class="ppms-pl-participants-avatar-btn"
                                            :style="{ zIndex: si + 1 }"
                                            :aria-label="participantSlotAriaLabel(slot)"
                                            @click.stop="openSingleParticipantPopover($event, row.project, slot)"
                                        >
                                            <span
                                                class="ppms-pl-avatar ppms-pl-avatar--sm ppms-pl-participants-stack-avatar"
                                                :style="{ background: avatarColor(slot.colorSeed) }"
                                            >
                                                {{ slot.initials }}
                                            </span>
                                        </button>
                                        <button
                                            v-if="participantOverflowCount(row.project) > 0"
                                            type="button"
                                            class="ppms-pl-participants-more ppms-pl-participants-overflow-btn"
                                            :aria-label="t('projects.userPopoverMoreParticipantsAria', { n: participantOverflowCount(row.project) })"
                                            @click.stop="openParticipantsOverflowMenu($event, row.project)"
                                        >
                                            +{{ participantOverflowCount(row.project) }}
                                        </button>
                                    </div>
                                </td>
                                <td v-if="colVis('progress')" class="ppms-td-process">
                                    <div
                                        class="ppms-progress-track ppms-progress-track--staging"
                                        role="progressbar"
                                        :aria-valuenow="Math.round(clampProgress(row.project.progress))"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        :aria-label="t('projects.colProgress')"
                                    >
                                        <div
                                            class="ppms-progress-fill"
                                            :class="progressToneClass(row.project.progress)"
                                            :style="{ width: `${clampProgress(row.project.progress)}%` }"
                                        />
                                        <span
                                            class="ppms-progress-knob"
                                            :style="{
                                                left: `${clampProgress(row.project.progress)}%`,
                                            }"
                                            aria-hidden="true"
                                        >
                                            <span class="ppms-progress-knob-pct">{{ formatProgress(row.project.progress) }}%</span>
                                        </span>
                                    </div>
                                </td>
                                <td v-if="colVis('tasks')" class="ppms-td-num ppms-td-tasks">{{ row.project.tasks_count ?? '—' }}</td>
                                <td v-if="colVis('start')" class="ppms-td-date">
                                    <span v-if="!row.project.start_date" class="ppms-muted">{{ t('projects.startNone') }}</span>
                                    <span v-else>{{ formatDateIso(row.project.start_date) }}</span>
                                </td>
                                <td v-if="colVis('actualStart')" class="ppms-td-date">
                                    <span v-if="!row.project.actual_start_date" class="ppms-muted">{{ t('projects.actualStartNone') }}</span>
                                    <span v-else>{{ formatDateIso(row.project.actual_start_date) }}</span>
                                </td>
                                <td v-if="colVis('end')" class="ppms-td-date">
                                    <span v-if="!row.project.deadline" class="ppms-muted">{{ t('projects.deadlineNone') }}</span>
                                    <span v-else class="ppms-deadline" :class="deadlineTone(row.project.deadline).cls">
                                        {{ formatDeadline(row.project.deadline) }}
                                    </span>
                                </td>
                                <td v-if="colVis('status')" class="ppms-td-status">
                                    <span class="ppms-status-pill" :class="statusPillClass(row.project.status)">{{
                                        t(`projects.status.${row.project.status}`)
                                    }}</span>
                                </td>
                                <td v-if="colVis('actions')" class="ppms-td-actions">
                                    <router-link :to="'/projects/' + row.project.id" class="ppms-btn-ghost ppms-btn-sm">{{
                                        t('projects.openDetail')
                                    }}</router-link>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div v-else class="ppms-pl-kanban-wrap ppms-mt">
                <p v-if="canBulk" class="ppms-pl-kanban-hint ppms-muted">{{ t('projects.kanbanDragHint') }}</p>
                <p v-if="kanbanShowsSubset" class="ppms-pl-kanban-capacity ppms-muted" role="status">
                    {{ t('projects.kanbanPaginationHint', { shown: projects.length, total }) }}
                </p>
                <div class="ppms-pl-kanban-board" role="region" :aria-label="t('projects.viewModeKanban')">
                    <div class="ppms-pl-kanban-scroll">
                        <div
                            v-for="col in kanbanColumns"
                            :key="'kcol-' + col.phase"
                            class="ppms-pl-kanban-col"
                            :class="{
                                'ppms-pl-kanban-col--over':
                                    canBulk && kanbanDraggingId != null && kanbanDragOverPhase === col.phase,
                            }"
                            @dragover.prevent="onKanbanColumnDragOver(col.phase, $event)"
                            @drop.prevent="onKanbanDrop(col.phase, $event)"
                        >
                            <header class="ppms-pl-kanban-col-head">
                                <span class="ppms-pl-kanban-col-title">{{ t(`projects.phase.${col.phase}`) }}</span>
                                <span class="ppms-pl-kanban-col-count">{{ col.projects.length }}</span>
                            </header>
                            <div class="ppms-pl-kanban-col-body" role="list">
                                <p v-if="!col.projects.length" class="ppms-pl-kanban-empty">{{ t('projects.kanbanEmptyColumn') }}</p>
                                <article
                                    v-for="p in col.projects"
                                    :key="'kc-' + p.id"
                                    class="ppms-pl-kanban-card"
                                    :class="{
                                        'ppms-pl-kanban-card--dragging': kanbanDraggingId === p.id,
                                        'ppms-pl-kanban-card--saving': kanbanSavingId === p.id,
                                    }"
                                    role="listitem"
                                    :draggable="canBulk && kanbanSavingId !== p.id"
                                    :aria-busy="kanbanSavingId === p.id ? 'true' : undefined"
                                    @dragstart="onKanbanDragStart($event, p)"
                                    @dragend="onKanbanDragEnd"
                                >
                                    <div class="ppms-pl-kanban-card-top">
                                        <label v-if="canBulk" class="ppms-pl-card-check ppms-pl-kanban-card-check" @click.stop>
                                            <input v-model="selectedProjectIds" type="checkbox" :value="p.id" />
                                            <span class="ppms-sr-only">{{ t('projects.selectRow', { name: p.name }) }}</span>
                                        </label>
                                        <div class="ppms-pl-kanban-card-head-text">
                                            <router-link class="ppms-pl-kanban-card-title" :to="'/projects/' + p.id">{{ p.name }}</router-link>
                                            <div class="ppms-pl-kanban-labels-row">
                                                <div v-if="projectLabelList(p).length" class="ppms-pl-kanban-card-labels">
                                                    <span
                                                        v-for="(lb, lbi) in kanbanLabelPreview(p).show"
                                                        :key="'kcl-' + p.id + '-' + lbi"
                                                        class="ppms-pl-label-chip ppms-pl-label-chip--under-name"
                                                        >{{ lb }}</span
                                                    >
                                                    <span v-if="kanbanLabelPreview(p).more > 0" class="ppms-pl-labels-more"
                                                        >+{{ kanbanLabelPreview(p).more }}</span
                                                    >
                                                </div>
                                                <template v-if="canBulk">
                                                    <input
                                                        v-if="quickLabelProjectId === p.id"
                                                        ref="quickLabelInputEl"
                                                        v-model="quickLabelText"
                                                        type="text"
                                                        class="ppms-pl-quick-label-input"
                                                        list="ppms-pl-label-datalist"
                                                        autocomplete="off"
                                                        :placeholder="t('projects.labelAddPlaceholder')"
                                                        :aria-label="t('projects.labelAddPlaceholder')"
                                                        :disabled="quickLabelSavingId === p.id"
                                                        @keydown.enter.prevent="submitQuickLabel(p)"
                                                        @keydown.esc.prevent="closeQuickLabel"
                                                        @click.stop
                                                    />
                                                    <button
                                                        v-else
                                                        type="button"
                                                        class="ppms-pl-label-add-btn"
                                                        :aria-label="t('projects.labelAddAria')"
                                                        @click.stop="toggleQuickLabel(p)"
                                                    >
                                                        +
                                                    </button>
                                                </template>
                                            </div>
                                            <span class="ppms-pl-kanban-card-code ppms-muted">{{ projectCode(p) }}</span>
                                        </div>
                                    </div>
                                    <div class="ppms-pl-kanban-card-meta">
                                        <span class="ppms-pl-tag ppms-pl-tag--kanban">{{ t(`projects.typeShort.${p.type}`) }}</span>
                                        <span class="ppms-status-pill ppms-status-pill--kanban" :class="statusPillClass(p.status)">{{
                                            t(`projects.status.${p.status}`)
                                        }}</span>
                                    </div>
                                    <div class="ppms-pl-kanban-card-progress">
                                        <div
                                            class="ppms-progress-track ppms-progress-track--staging ppms-progress-track--kanban"
                                            role="progressbar"
                                            :aria-valuenow="Math.round(clampProgress(p.progress))"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            :aria-label="t('projects.colProgress')"
                                        >
                                            <div
                                                class="ppms-progress-fill"
                                                :class="progressToneClass(p.progress)"
                                                :style="{ width: `${clampProgress(p.progress)}%` }"
                                            />
                                        </div>
                                        <span class="ppms-pl-kanban-card-pct">{{ formatProgress(p.progress) }}%</span>
                                    </div>
                                    <div class="ppms-pl-kanban-card-foot">
                                        <button
                                            type="button"
                                            class="ppms-pl-usercell ppms-pl-kanban-owner ppms-pl-usercell-trigger"
                                            :disabled="!p.owner"
                                            :aria-label="t('projects.avatarInfoAdmin')"
                                            @click="openAdminUserPopover($event, p)"
                                        >
                                            <span
                                                class="ppms-pl-avatar ppms-pl-avatar--sm"
                                                :style="{ background: avatarColor(p.owner?.name || p.owner?.email || '?') }"
                                                aria-hidden="true"
                                            >
                                                {{ userInitials(p.owner?.name) }}
                                            </span>
                                            <span class="ppms-pl-username ppms-pl-kanban-owner-name">{{ p.owner?.name || '—' }}</span>
                                        </button>
                                        <router-link :to="'/projects/' + p.id" class="ppms-btn-ghost ppms-btn-sm ppms-pl-kanban-open">{{
                                            t('projects.openDetail')
                                        }}</router-link>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div
            v-if="showImportModal"
            class="ppms-modal-backdrop"
            role="presentation"
            @click.self="closeImportModal"
        >
            <div
                class="ppms-modal ppms-modal--import"
                role="dialog"
                aria-modal="true"
                aria-labelledby="ppms-modal-import-title"
            >
                <h2 id="ppms-modal-import-title">{{ t('projects.importModalTitle') }}</h2>
                <p class="ppms-muted ppms-import-hint">{{ t('projects.importPreviewHint') }}</p>
                <div class="ppms-import-actions ppms-mt-sm">
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="downloadImportTemplateCsv">
                        {{ t('projects.importDownloadTemplateCsv') }}
                    </button>
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="downloadImportTemplateJson">
                        {{ t('projects.importDownloadTemplateJson') }}
                    </button>
                    <label class="ppms-btn-ghost ppms-btn-sm ppms-import-file-label">
                        <input
                            ref="importFileInput"
                            type="file"
                            accept=".csv,.txt,.json,text/csv,text/plain,application/json"
                            class="ppms-sr-only"
                            @change="onImportFileChange"
                        />
                        <span>{{ t('projects.importPickFile') }}</span>
                    </label>
                    <button
                        type="button"
                        class="ppms-btn-primary ppms-btn-sm"
                        :disabled="importPreviewLoading || !importFileChosen"
                        @click="runImportPreview"
                    >
                        {{ t('projects.importPreview') }}
                    </button>
                </div>
                <p v-if="importSummary.total > 0" class="ppms-import-summary ppms-mt-sm">
                    {{
                        t('projects.importSummary', {
                            valid: importSummary.valid,
                            invalid: importSummary.invalid,
                            total: importSummary.total,
                        })
                    }}
                </p>
                <div v-if="importPreviewRows.length" class="ppms-table-scroll ppms-import-preview-wrap ppms-mt-sm">
                    <table class="ppms-table ppms-table--compact">
                        <thead>
                            <tr>
                                <th>{{ t('projects.importLine') }}</th>
                                <th>{{ t('projects.importStatus') }}</th>
                                <th>{{ t('projects.importAction') }}</th>
                                <th>{{ t('projects.colName') }}</th>
                                <th>{{ t('projects.importErrors') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(r, i) in importPreviewRows" :key="'ir-' + i">
                                <td>{{ r.line }}</td>
                                <td>
                                    <span :class="r.status === 'valid' ? 'ppms-import-badge ppms-import-badge--ok' : 'ppms-import-badge ppms-import-badge--err'">
                                        {{ r.status === 'valid' ? t('projects.importValid') : t('projects.importInvalid') }}
                                    </span>
                                </td>
                                <td>{{ r.action || '—' }}</td>
                                <td>{{ r.name || '—' }}</td>
                                <td class="ppms-import-err-cell">{{ (r.errors || []).join('; ') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="ppms-modal-actions">
                    <button type="button" class="ppms-btn-ghost" @click="closeImportModal">{{ t('common.cancel') }}</button>
                    <button
                        type="button"
                        class="ppms-btn-primary"
                        :disabled="importCommitLoading || !importPreviewId || importSummary.valid < 1"
                        @click="runImportCommit"
                    >
                        {{ t('projects.importCommit') }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="showSaveViewModal"
            class="ppms-modal-backdrop"
            role="presentation"
            @click.self="showSaveViewModal = false"
        >
            <div
                class="ppms-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="ppms-modal-save-view-title"
            >
                <h2 id="ppms-modal-save-view-title">{{ t('projects.saveViewBtn') }}</h2>
                <label class="ppms-field">
                    <span>{{ t('projects.saveViewPrompt') }}</span>
                    <input v-model="saveViewNameInput" type="text" maxlength="80" @keyup.enter="confirmSaveView" />
                </label>
                <div class="ppms-modal-actions">
                    <button type="button" class="ppms-btn-ghost" @click="showSaveViewModal = false">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="button" class="ppms-btn-primary" @click="confirmSaveView">{{ t('common.save') }}</button>
                </div>
            </div>
        </div>

        <div
            v-if="showLabelsModal"
            class="ppms-modal-backdrop"
            role="presentation"
            @click.self="closeLabelsModal"
        >
            <div
                class="ppms-modal ppms-modal--labels"
                role="dialog"
                aria-modal="true"
                aria-labelledby="ppms-modal-labels-title"
                @click.stop
            >
                <h2 id="ppms-modal-labels-title">{{ t('projects.labelsModalTitle') }}</h2>
                <section class="ppms-labels-modal-section">
                    <h3 class="ppms-labels-modal-sub">{{ t('projects.labelsFilterSection') }}</h3>
                    <p class="ppms-muted ppms-labels-modal-hint">{{ t('projects.labelsFilterHint') }}</p>
                    <label class="ppms-field">
                        <span>{{ t('projects.colLabels') }}</span>
                        <input v-model="labelsModal.filterInput" type="text" list="ppms-pl-label-datalist" autocomplete="off" />
                    </label>
                    <div class="ppms-labels-modal-actions-row ppms-mt-sm">
                        <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="applyLabelFilterFromModal">
                            {{ t('projects.labelsFilterApply') }}
                        </button>
                        <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="clearLabelFilterFromModal">
                            {{ t('projects.labelsFilterClear') }}
                        </button>
                    </div>
                </section>
                <section v-if="canBulk" class="ppms-labels-modal-section ppms-mt">
                    <h3 class="ppms-labels-modal-sub">{{ t('projects.labelsBulkSection') }}</h3>
                    <label class="ppms-field">
                        <span>{{ t('projects.labelsBulkAddHint') }}</span>
                        <input v-model="labelsBulkAddText" type="text" autocomplete="off" />
                    </label>
                    <button type="button" class="ppms-btn-primary ppms-btn-sm ppms-mt-sm" @click="runBulkLabelsAdd">
                        {{ t('projects.labelsBulkApplyAdd') }}
                    </button>
                    <label class="ppms-field ppms-mt">
                        <span>{{ t('projects.labelsBulkRemoveHint') }}</span>
                        <input v-model="labelsBulkRemoveText" type="text" autocomplete="off" />
                    </label>
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm ppms-mt-sm" @click="runBulkLabelsRemove">
                        {{ t('projects.labelsBulkApplyRemove') }}
                    </button>
                </section>
                <div class="ppms-modal-actions">
                    <button type="button" class="ppms-btn-ghost" @click="closeLabelsModal">{{ t('common.close') }}</button>
                </div>
            </div>
        </div>

        <div v-if="showForm" class="ppms-modal-backdrop" role="presentation" @click.self="showForm = false">
            <div
                class="ppms-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="ppms-modal-create-project-title"
            >
                <h2 id="ppms-modal-create-project-title">{{ t('projects.createModalTitle') }}</h2>
                <form @submit.prevent="createProject">
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldName') }} *</span>
                        <input v-model="form.name" required />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldType') }} *</span>
                        <select v-model="form.type" required>
                            <option value="maintenance">{{ t('projects.type.maintenance') }}</option>
                            <option value="delivery">{{ t('projects.type.delivery') }}</option>
                            <option value="rnd">{{ t('projects.type.rnd') }}</option>
                        </select>
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldOwner') }} *</span>
                        <select v-model="form.owner_id" required>
                            <option value="" disabled>{{ t('projects.filterAll') }}</option>
                            <option v-for="u in userOptions" :key="'f-' + u.id" :value="String(u.id)">{{ u.name }} ({{ u.email }})</option>
                        </select>
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldDeadline') }}</span>
                        <input v-model="form.deadline" type="date" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.colStart') }}</span>
                        <input v-model="form.start_date" type="date" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldCustomerName') }}</span>
                        <input v-model="form.customer_name" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldCustomerEmail') }}</span>
                        <input v-model="form.customer_email" type="email" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldSuppliersHint') }}</span>
                        <textarea v-model="form.suppliers_text" rows="3" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldDescription') }}</span>
                        <textarea v-model="form.description" rows="3" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldLabelsHint') }}</span>
                        <input v-model="form.labels_text" type="text" autocomplete="off" />
                    </label>
                    <p v-if="formError" class="ppms-error">{{ formError }}</p>
                    <div class="ppms-modal-actions">
                        <button type="button" class="ppms-btn-ghost" @click="showForm = false">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" class="ppms-btn-primary">{{ t('common.save') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="userPopover.open"
                ref="userPopoverEl"
                class="ppms-pl-user-popover"
                role="dialog"
                :aria-label="
                    userPopover.mode === 'admin'
                        ? t('projects.avatarInfoAdmin')
                        : userPopover.menuSlots.length && !userPopover.user
                          ? t('projects.userPopoverPickParticipant')
                          : t('projects.avatarInfoParticipants')
                "
                :style="{ top: userPopover.top + 'px', left: userPopover.left + 'px' }"
                @click.stop
            >
                <template v-if="userPopover.menuSlots.length && !userPopover.user">
                    <p class="ppms-pl-user-popover-extra-title">{{ t('projects.userPopoverPickParticipant') }}</p>
                    <ul class="ppms-pl-user-popover-list ppms-pl-user-popover-pick-list">
                        <li v-for="(slot, pi) in userPopover.menuSlots" :key="'ms-' + pi">
                            <button type="button" class="ppms-pl-user-popover-pick" @click="onPickMenuParticipant(slot)">
                                {{ participantSlotLabel(slot) }}
                            </button>
                        </li>
                    </ul>
                </template>
                <template v-else>
                    <div v-if="userPopover.user" class="ppms-pl-user-popover-head">
                        <span
                            class="ppms-pl-avatar ppms-pl-avatar--sm"
                            :style="{ background: avatarColor(userPopover.user?.name || userPopover.user?.email || '?') }"
                            aria-hidden="true"
                        >
                            {{ userInitials(userPopover.user?.name) }}
                        </span>
                        <div class="ppms-pl-user-popover-text">
                            <strong class="ppms-pl-user-popover-name">{{ userPopover.user?.name || '—' }}</strong>
                            <p v-if="userPopover.user?.email" class="ppms-pl-user-popover-email">{{ userPopover.user.email }}</p>
                        </div>
                    </div>
                    <div v-if="userPopover.stakeholders.length" class="ppms-pl-user-popover-extra">
                        <p class="ppms-pl-user-popover-extra-title">{{ t('projects.userPopoverOtherParticipants') }}</p>
                        <ul class="ppms-pl-user-popover-list">
                            <li v-for="(line, pi) in userPopover.stakeholders" :key="'st-' + pi">{{ line }}</li>
                        </ul>
                    </div>
                </template>
            </div>
        </Teleport>
    </main>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const PPMS_PROJECT_VIEWS_KEY = 'ppms-project-saved-views';
const PPMS_PROJECT_LIST_VIEW_MODE_KEY = 'ppms-project-list-view-mode';
const PPMS_PROJECT_LIST_COLUMNS_KEY = 'ppms-project-list-columns';

const PL_COL_ORDER = ['admin', 'code', 'name', 'participants', 'progress', 'tasks', 'start', 'actualStart', 'end', 'status', 'actions'];

const COLUMN_LABEL_KEYS = {
    admin: 'colAdmin',
    code: 'colCode',
    name: 'colName',
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

const projects = ref([]);
const viewMode = ref('list');
const loading = ref(true);
const showForm = ref(false);
const showSaveViewModal = ref(false);
const saveViewNameInput = ref('');
const formError = ref('');
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);
const rangeFrom = ref(0);
const rangeTo = ref(0);
const perPage = ref(50);

const userOptions = ref([]);
const currentUser = ref(null);
const selectedProjectIds = ref([]);

const filters = reactive({
    search: '',
    type: '',
    phase: '',
    status: '',
    label: '',
    progress_min: null,
    progress_max: null,
    sort: 'type_asc',
    owner_id: '',
    archived: false,
    activePhase: false,
});

const form = reactive({
    name: '',
    type: 'delivery',
    owner_id: '',
    deadline: '',
    start_date: '',
    description: '',
    customer_name: '',
    customer_email: '',
    suppliers_text: '',
    labels_text: '',
});

const savedViews = ref([]);

const toolbarMenuOpen = ref(false);
const toolbarMenuEl = ref(null);

const showLabelsModal = ref(false);
const labelsSuggestions = ref([]);
const labelsModal = reactive({ filterInput: '' });
const labelsBulkAddText = ref('');
const labelsBulkRemoveText = ref('');
const quickLabelProjectId = ref(null);
const quickLabelText = ref('');
const quickLabelInputEl = ref(null);
const quickLabelSavingId = ref(null);

watch(quickLabelProjectId, async (id) => {
    if (id != null) {
        await nextTick();
        quickLabelInputEl.value?.focus?.();
    }
});

const showImportModal = ref(false);
const importFileInput = ref(null);
const importFileChosen = ref(false);
const importPreviewId = ref(null);
const importPreviewRows = ref([]);
const importSummary = ref({ total: 0, valid: 0, invalid: 0 });
const importPreviewLoading = ref(false);
const importCommitLoading = ref(false);

let urlDebounce = null;

/** Ignores one `route.query` watch cycle after our own `router.replace` (stable signature). */
let routeQueryPushEcho = null;

function routeQuerySignature(q) {
    const raw = { ...q };
    const keys = Object.keys(raw).sort();
    const norm = {};
    for (const k of keys) {
        const v = raw[k];
        norm[k] = Array.isArray(v) ? v.join(',') : v == null ? '' : String(v);
    }

    return JSON.stringify(norm);
}

const canExport = computed(() => {
    const r = currentUser.value?.role;

    return r && ['admin', 'pm', 'hr', 'tl'].includes(r);
});

const canBulk = computed(() => {
    const r = currentUser.value?.role;

    return r && ['admin', 'pm', 'tl'].includes(r);
});

const canBulkDelete = computed(() => {
    const r = currentUser.value?.role;

    return r && ['admin', 'pm'].includes(r);
});

const canImport = computed(() => {
    const r = currentUser.value?.role;

    return r && ['admin', 'pm'].includes(r);
});

const allPageSelected = computed(() => {
    if (!projects.value.length) {
        return false;
    }

    return projects.value.every((p) => selectedProjectIds.value.includes(p.id));
});

const groupColspan = computed(() => {
    let n = canBulk.value ? 1 : 0;
    for (const k of PL_COL_ORDER) {
        if (columnVisibility[k]) {
            n++;
        }
    }

    return n;
});

const tableRows = computed(() => {
    const list = projects.value;
    if (!list.length) {
        return [];
    }
    const rows = [];
    let lastType = null;
    for (const p of list) {
        if (p.type !== lastType) {
            lastType = p.type;
            const countInPage = list.filter((x) => x.type === p.type).length;
            rows.push({ kind: 'group', type: p.type, count: countInPage });
        }
        rows.push({ kind: 'project', project: p });
    }

    return rows;
});

const collapsedGroupTypes = reactive({});

function isGroupCollapsed(type) {
    return Boolean(collapsedGroupTypes[type]);
}

function toggleGroupCollapse(type) {
    collapsedGroupTypes[type] = !collapsedGroupTypes[type];
}

const displayTableRows = computed(() => {
    const rows = tableRows.value;
    const out = [];
    for (const row of rows) {
        if (row.kind === 'group') {
            out.push(row);
            continue;
        }
        if (collapsedGroupTypes[row.project.type]) {
            continue;
        }
        out.push(row);
    }

    return out;
});

/** Kanban requests more rows per API page so the board is usable; list view keeps user per_page. */
const KANBAN_FETCH_PER_PAGE = 100;

const KANBAN_PHASE_ORDER = ['planning', 'development', 'uat', 'done', 'maintenance', 'rnd'];

function kanbanPhaseOf(p) {
    const ph = p?.phase;
    if (KANBAN_PHASE_ORDER.includes(ph)) {
        return ph;
    }

    return 'planning';
}

const kanbanColumns = computed(() =>
    KANBAN_PHASE_ORDER.map((phase) => ({
        phase,
        projects: projects.value.filter((p) => kanbanPhaseOf(p) === phase),
    })),
);

const kanbanShowsSubset = computed(
    () => viewMode.value === 'kanban' && total.value > 0 && projects.value.length < total.value,
);

function kanbanLabelPreview(p) {
    return projectLabelsPreview(p, 2);
}

const kanbanDraggingId = ref(null);
const kanbanDragOverPhase = ref(null);
const kanbanSavingId = ref(null);

function onKanbanDragStart(ev, project) {
    if (!canBulk.value) {
        return;
    }
    kanbanDraggingId.value = project.id;
    ev.dataTransfer.setData('text/plain', String(project.id));
    ev.dataTransfer.effectAllowed = 'move';
}

function onKanbanDragEnd() {
    kanbanDraggingId.value = null;
    kanbanDragOverPhase.value = null;
}

function onKanbanColumnDragOver(phase, ev) {
    if (!canBulk.value || kanbanDraggingId.value == null) {
        return;
    }
    ev.preventDefault();
    kanbanDragOverPhase.value = phase;
}

async function onKanbanDrop(targetPhase, ev) {
    ev.preventDefault();
    if (!canBulk.value) {
        return;
    }
    const id = parseInt(ev.dataTransfer.getData('text/plain'), 10);
    kanbanDragOverPhase.value = null;
    kanbanDraggingId.value = null;
    if (!Number.isFinite(id)) {
        return;
    }
    const p = projects.value.find((x) => x.id === id);
    if (!p || p.phase === targetPhase) {
        return;
    }
    const prevPhase = p.phase;
    kanbanSavingId.value = id;
    try {
        await axios.put(`/api/projects/${id}`, { phase: targetPhase });
        p.phase = targetPhase;
        ppmsToastSuccess(t('projects.kanbanPhaseUpdated'));
    } catch (e) {
        p.phase = prevPhase;
        ppmsToastError(formatApiUserMessage(e, t('projects.kanbanPhaseErr')));
    } finally {
        kanbanSavingId.value = null;
    }
}

const userPopover = reactive({
    open: false,
    mode: 'admin',
    user: null,
    stakeholders: [],
    menuSlots: [],
    sourceProject: null,
    anchorEl: null,
    top: 0,
    left: 0,
});

const userPopoverEl = ref(null);

function closeUserPopover() {
    userPopover.open = false;
    userPopover.menuSlots = [];
    userPopover.sourceProject = null;
    userPopover.anchorEl = null;
    userPopover.user = null;
    userPopover.stakeholders = [];
}

function positionPopoverFromAnchor(anchorEl) {
    if (!anchorEl || !(anchorEl instanceof Element)) {
        return;
    }
    const r = anchorEl.getBoundingClientRect();
    const margin = 8;
    const maxW = Math.min(280, window.innerWidth - margin * 2);
    let left = r.left;
    const topBelow = r.bottom + margin;
    if (left + maxW > window.innerWidth - margin) {
        left = window.innerWidth - margin - maxW;
    }
    if (left < margin) {
        left = margin;
    }
    userPopover.left = left;
    userPopover.top = topBelow;
}

function adjustPopoverVertical(anchorEl) {
    const el = userPopoverEl.value;
    if (!el || !anchorEl || !(anchorEl instanceof Element)) {
        return;
    }
    const r = anchorEl.getBoundingClientRect();
    const margin = 8;
    const ph = el.getBoundingClientRect().height;
    if (userPopover.top + ph > window.innerHeight - margin) {
        const above = r.top - margin - ph;
        if (above >= margin) {
            userPopover.top = above;
        }
    }
}

async function openAdminUserPopover(ev, project) {
    ev?.stopPropagation?.();
    const u = project?.owner;
    if (!u) {
        return;
    }
    userPopover.mode = 'admin';
    userPopover.menuSlots = [];
    userPopover.sourceProject = null;
    userPopover.anchorEl = ev.currentTarget;
    userPopover.user = u;
    userPopover.stakeholders = [];
    positionPopoverFromAnchor(ev.currentTarget);
    userPopover.open = true;
    await nextTick();
    adjustPopoverVertical(ev.currentTarget);
}

function buildDisplayUser(slot) {
    if (slot.kind === 'owner') {
        return slot.user;
    }

    const em = slot.email;

    return { name: displayNameFromEmail(em), email: em };
}

function participantSlotsEqual(a, b) {
    if (!a || !b || a.kind !== b.kind) {
        return false;
    }

    return a.kind === 'owner' ? a.user?.id === b.user?.id : a.email === b.email;
}

function otherParticipantLines(project, selectedSlot) {
    return participantAllSlots(project)
        .filter((s) => !participantSlotsEqual(s, selectedSlot))
        .map((s) => (s.kind === 'owner' ? `${s.user.name} (${s.user.email})` : s.email));
}

async function openSingleParticipantPopover(ev, project, slot) {
    ev?.stopPropagation?.();
    if (!project || !slot) {
        return;
    }
    userPopover.mode = 'participant';
    userPopover.menuSlots = [];
    userPopover.sourceProject = project;
    userPopover.anchorEl = ev.currentTarget;
    userPopover.user = buildDisplayUser(slot);
    userPopover.stakeholders = otherParticipantLines(project, slot);
    positionPopoverFromAnchor(ev.currentTarget);
    userPopover.open = true;
    await nextTick();
    adjustPopoverVertical(ev.currentTarget);
}

async function openParticipantsOverflowMenu(ev, project) {
    ev?.stopPropagation?.();
    const overflow = participantOverflowSlots(project);
    if (!overflow.length) {
        return;
    }
    if (overflow.length === 1) {
        await openSingleParticipantPopover(ev, project, overflow[0]);

        return;
    }
    userPopover.mode = 'participants_menu';
    userPopover.sourceProject = project;
    userPopover.anchorEl = ev.currentTarget;
    userPopover.menuSlots = overflow;
    userPopover.user = null;
    userPopover.stakeholders = [];
    positionPopoverFromAnchor(ev.currentTarget);
    userPopover.open = true;
    await nextTick();
    adjustPopoverVertical(ev.currentTarget);
}

function onPickMenuParticipant(slot) {
    const project = userPopover.sourceProject;
    if (!project || !slot) {
        return;
    }
    userPopover.mode = 'participant';
    userPopover.menuSlots = [];
    userPopover.user = buildDisplayUser(slot);
    userPopover.stakeholders = otherParticipantLines(project, slot);
    nextTick(() => {
        const a = userPopover.anchorEl;
        if (a && a instanceof Element) {
            adjustPopoverVertical(a);
        }
    });
}

function readStoredViewMode() {
    try {
        const v = localStorage.getItem(PPMS_PROJECT_LIST_VIEW_MODE_KEY);
        if (v === 'list' || v === 'kanban') {
            return v;
        }
        if (v === 'grid') {
            return 'kanban';
        }
    } catch {
        /* ignore */
    }

    return 'list';
}

function setViewMode(mode) {
    if (mode !== 'list' && mode !== 'kanban') {
        return;
    }
    if (viewMode.value === mode) {
        return;
    }
    viewMode.value = mode;
    try {
        localStorage.setItem(PPMS_PROJECT_LIST_VIEW_MODE_KEY, mode);
    } catch {
        /* ignore */
    }
    page.value = 1;
    load();
    scheduleUrlReplace();
}

function clampProgress(v) {
    const n = Number(v);
    if (Number.isNaN(n)) {
        return 0;
    }

    return Math.min(100, Math.max(0, n));
}

function formatProgress(v) {
    return clampProgress(v).toFixed(1);
}

function progressToneClass(v) {
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

function deadlineTone(iso) {
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

function formatDeadline(iso) {
    if (!iso) {
        return '';
    }
    const s = String(iso).slice(0, 10);
    const [y, m, d] = s.split('-');

    return d && m && y ? `${d}/${m}/${y}` : s;
}

function closeLabelsModal() {
    showLabelsModal.value = false;
}

async function onToolbarLabels() {
    labelsModal.filterInput = filters.label || '';
    showLabelsModal.value = true;
    await fetchLabelSuggestions();
}

async function fetchLabelSuggestions() {
    try {
        const { data } = await axios.get('/api/projects/label-suggestions');
        labelsSuggestions.value = Array.isArray(data?.labels) ? data.labels : [];
    } catch {
        labelsSuggestions.value = [];
    }
}

function parseCommaLabelTokens(s) {
    return String(s || '')
        .split(/[,;]/)
        .map((x) => x.trim())
        .filter(Boolean);
}

function toggleQuickLabel(project) {
    if (!canBulk.value || !project?.id) {
        return;
    }
    if (quickLabelProjectId.value === project.id) {
        closeQuickLabel();

        return;
    }
    quickLabelProjectId.value = project.id;
    quickLabelText.value = '';
}

function closeQuickLabel() {
    quickLabelProjectId.value = null;
    quickLabelText.value = '';
}

async function submitQuickLabel(project) {
    if (!project?.id || quickLabelSavingId.value === project.id) {
        return;
    }
    const add = parseCommaLabelTokens(quickLabelText.value);
    if (!add.length) {
        ppmsToastError(t('projects.labelAddEmpty'));

        return;
    }
    quickLabelSavingId.value = project.id;
    try {
        await axios.post('/api/projects/bulk', {
            project_ids: [project.id],
            add_labels: add,
        });
        ppmsToastSuccess(t('projects.labelAddOk'));
        closeQuickLabel();
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.labelAddErr')));
    } finally {
        quickLabelSavingId.value = null;
    }
}

function projectLabelList(p) {
    return Array.isArray(p?.labels) ? p.labels.filter(Boolean) : [];
}

function projectLabelsPreview(p, max = 3) {
    const list = projectLabelList(p);
    const show = list.slice(0, max);

    return { show, more: Math.max(0, list.length - max) };
}

function applyLabelFilterFromModal() {
    filters.label = labelsModal.filterInput.trim();
    closeLabelsModal();
    if (page.value !== 1) {
        page.value = 1;
    }
    load();
    scheduleUrlReplace();
}

function clearLabelFilterFromModal() {
    labelsModal.filterInput = '';
    filters.label = '';
    closeLabelsModal();
    if (page.value !== 1) {
        page.value = 1;
    }
    load();
    scheduleUrlReplace();
}

async function runBulkLabelsAdd() {
    if (!selectedProjectIds.value.length) {
        ppmsToastError(t('projects.labelsBulkNeedSelection'));

        return;
    }
    const add = parseCommaLabelTokens(labelsBulkAddText.value);
    if (!add.length) {
        return;
    }
    try {
        await axios.post('/api/projects/bulk', {
            project_ids: selectedProjectIds.value,
            add_labels: add,
        });
        labelsBulkAddText.value = '';
        ppmsToastSuccess(t('projects.labelsBulkOk'));
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.labelsBulkErr')));
    }
}

async function runBulkLabelsRemove() {
    if (!selectedProjectIds.value.length) {
        ppmsToastError(t('projects.labelsBulkNeedSelection'));

        return;
    }
    const remove = parseCommaLabelTokens(labelsBulkRemoveText.value);
    if (!remove.length) {
        return;
    }
    try {
        await axios.post('/api/projects/bulk', {
            project_ids: selectedProjectIds.value,
            remove_labels: remove,
        });
        labelsBulkRemoveText.value = '';
        ppmsToastSuccess(t('projects.labelsBulkOk'));
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.labelsBulkErr')));
    }
}

function userInitials(name) {
    if (!name || !String(name).trim()) {
        return '?';
    }
    const parts = String(name).trim().split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }
    const w = parts[0];

    return w.length >= 2 ? w.slice(0, 2).toUpperCase() : w.charAt(0).toUpperCase();
}

function hashHue(seed) {
    let h = 0;
    const s = String(seed || '');
    for (let i = 0; i < s.length; i++) {
        h = (h * 31 + s.charCodeAt(i)) >>> 0;
    }

    return h % 360;
}

function avatarColor(seed) {
    const hue = hashHue(seed);

    return `hsl(${hue} 48% 40%)`;
}

function projectCode(p) {
    return `DA-${String(p.id).padStart(5, '0')}`;
}

const PARTICIPANTS_AVATAR_MAX = 3;

function participantStakeholderEmails(p) {
    const emails = p.stakeholder_emails;

    return Array.isArray(emails) ? emails.filter(Boolean) : [];
}

function initialsFromEmail(email) {
    const local = String(email).split('@')[0].trim();
    if (!local) {
        return '?';
    }
    const parts = local.split(/[._+-]+/).filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }

    return local.length >= 2 ? local.slice(0, 2).toUpperCase() : local.charAt(0).toUpperCase();
}

function displayNameFromEmail(email) {
    const local = String(email).split('@')[0].trim();

    return local.replace(/[._+-]+/g, ' ').trim() || String(email);
}

/** Thứ tự: owner trước, sau đó stakeholder theo email. */
function participantAllSlots(p) {
    const slots = [];
    if (p.owner) {
        const seed = p.owner.name || p.owner.email || '?';
        slots.push({
            kind: 'owner',
            user: p.owner,
            colorSeed: seed,
            initials: userInitials(p.owner.name),
        });
    }
    for (const em of participantStakeholderEmails(p)) {
        slots.push({
            kind: 'stakeholder',
            email: em,
            colorSeed: em,
            initials: initialsFromEmail(em),
        });
    }

    return slots;
}

function participantVisibleSlots(p) {
    return participantAllSlots(p).slice(0, PARTICIPANTS_AVATAR_MAX);
}

function participantOverflowSlots(p) {
    return participantAllSlots(p).slice(PARTICIPANTS_AVATAR_MAX);
}

function participantOverflowCount(p) {
    return participantOverflowSlots(p).length;
}

function participantSlotLabel(slot) {
    if (slot.kind === 'owner') {
        return slot.user?.name || slot.user?.email || '—';
    }

    const em = slot.email;

    return `${displayNameFromEmail(em)} (${em})`;
}

function participantSlotAriaLabel(slot) {
    if (slot.kind === 'owner') {
        const n = slot.user?.name || slot.user?.email;

        return n ? `${t('projects.avatarInfoParticipants')}: ${n}` : t('projects.avatarInfoParticipants');
    }

    return `${t('projects.avatarInfoParticipants')}: ${slot.email}`;
}

function formatDateIso(iso) {
    if (!iso) {
        return '—';
    }

    return formatDeadline(String(iso).slice(0, 10));
}

function statusPillClass(s) {
    switch (s) {
        case 'on_track':
            return 'ppms-status-pill--track';
        case 'at_risk':
            return 'ppms-status-pill--risk';
        case 'delayed':
            return 'ppms-status-pill--late';
        case 'blocked':
            return 'ppms-status-pill--blocked';
        default:
            return 'ppms-status-pill--muted';
    }
}

function hydrateFromRouteQuery(q) {
    const vq = q.view;
    if (vq === 'kanban') {
        viewMode.value = 'kanban';
    } else if (vq === 'list') {
        viewMode.value = 'list';
    }
    filters.search = typeof q.search === 'string' ? q.search : '';
    filters.type = typeof q.type === 'string' ? q.type : '';
    filters.phase = typeof q.phase === 'string' ? q.phase : '';
    filters.activePhase =
        q.active_phase === '1' || q.active_phase === 1 || q.active_phase === true || q.active_phase === 'true';
    if (filters.phase) {
        filters.activePhase = false;
    }
    filters.status = typeof q.status === 'string' ? q.status : '';
    filters.label = typeof q.label === 'string' ? q.label : '';
    const pmin = q.progress_min;
    filters.progress_min = pmin !== undefined && pmin !== '' ? Number(pmin) : null;
    const pmax = q.progress_max;
    filters.progress_max = pmax !== undefined && pmax !== '' ? Number(pmax) : null;
    filters.sort = typeof q.sort === 'string' && q.sort ? q.sort : 'type_asc';
    const oid = q.owner_id;
    filters.owner_id = oid !== undefined && oid !== '' ? String(oid) : '';
    filters.archived = q.archived === '1' || q.archived === 1 || q.archived === true || q.archived === 'true';
    const pg = q.page;
    page.value = pg ? parseInt(String(pg), 10) || 1 : 1;
    const pp = q.per_page;
    if (pp !== undefined && pp !== '') {
        const n = parseInt(String(pp), 10);
        if (!Number.isNaN(n)) {
            perPage.value = Math.min(100, Math.max(10, n));
        }
    }
}

function serializeUrlQuery() {
    const q = {};
    if (filters.search.trim()) {
        q.search = filters.search.trim();
    }
    if (filters.type) {
        q.type = filters.type;
    }
    if (filters.phase) {
        q.phase = filters.phase;
    }
    if (filters.status) {
        q.status = filters.status;
    }
    if (filters.label.trim()) {
        q.label = filters.label.trim();
    }
    if (filters.progress_min !== null && filters.progress_min !== '' && !Number.isNaN(Number(filters.progress_min))) {
        q.progress_min = String(filters.progress_min);
    }
    if (filters.progress_max !== null && filters.progress_max !== '' && !Number.isNaN(Number(filters.progress_max))) {
        q.progress_max = String(filters.progress_max);
    }
    if (filters.sort && filters.sort !== 'type_asc') {
        q.sort = filters.sort;
    }
    if (filters.owner_id !== '' && !Number.isNaN(Number(filters.owner_id))) {
        q.owner_id = String(filters.owner_id);
    }
    if (filters.archived) {
        q.archived = '1';
    }
    if (filters.activePhase && !filters.phase) {
        q.active_phase = '1';
    }
    if (perPage.value !== 50) {
        q.per_page = String(perPage.value);
    }
    if (page.value > 1) {
        q.page = String(page.value);
    }
    if (viewMode.value === 'kanban') {
        q.view = 'kanban';
    }

    return q;
}

function scheduleUrlReplace() {
    clearTimeout(urlDebounce);
    urlDebounce = setTimeout(async () => {
        const q = serializeUrlQuery();
        routeQueryPushEcho = routeQuerySignature(q);
        try {
            await router.replace({ name: 'projects', query: q });
        } catch {
            routeQueryPushEcho = null;
        }
    }, 450);
}

function buildQueryParams() {
    const isKanban = viewMode.value === 'kanban';
    const params = {
        page: page.value,
        per_page: isKanban ? KANBAN_FETCH_PER_PAGE : perPage.value,
    };
    const s = filters.search.trim();
    if (s) {
        params.search = s;
    }
    if (filters.type) {
        params.type = filters.type;
    }
    if (filters.phase) {
        params.phase = filters.phase;
    } else if (filters.activePhase) {
        params.active_phase = 1;
    }
    if (filters.status) {
        params.status = filters.status;
    }
    const lb = filters.label.trim();
    if (lb) {
        params.label = lb;
    }
    if (filters.progress_min !== null && filters.progress_min !== '' && !Number.isNaN(Number(filters.progress_min))) {
        params.progress_min = Number(filters.progress_min);
    }
    if (filters.progress_max !== null && filters.progress_max !== '' && !Number.isNaN(Number(filters.progress_max))) {
        params.progress_max = Number(filters.progress_max);
    }
    if (filters.sort && filters.sort !== 'type_asc') {
        params.sort = filters.sort;
    }
    if (filters.owner_id !== '' && !Number.isNaN(Number(filters.owner_id))) {
        params.owner_id = Number(filters.owner_id);
    }
    if (filters.archived) {
        params.archived = 1;
    }

    return params;
}

function buildExportParams() {
    const p = { ...buildQueryParams() };
    delete p.page;

    return p;
}

async function load() {
    loading.value = true;
    const isKanban = viewMode.value === 'kanban';
    try {
        const { data } = await axios.get('/api/projects', { params: buildQueryParams() });
        if (Array.isArray(data.data)) {
            projects.value = data.data;
            lastPage.value = data.last_page || 1;
            page.value = data.current_page || 1;
            total.value = data.total ?? 0;
            if (!isKanban) {
                perPage.value = data.per_page ?? perPage.value;
            }
            rangeFrom.value = data.from;
            rangeTo.value = data.to;
        } else {
            projects.value = data;
            lastPage.value = 1;
            if (!isKanban) {
                perPage.value = data.length || perPage.value;
            }
            total.value = data.length;
            rangeFrom.value = projects.value.length ? 1 : null;
            rangeTo.value = projects.value.length || null;
        }
    } finally {
        loading.value = false;
    }
}

function onFilterChange() {
    if (page.value !== 1) {
        page.value = 1;
    } else {
        load();
    }
    scheduleUrlReplace();
}

function goPage(p) {
    page.value = p;
    load();
    scheduleUrlReplace();
}

function loadSavedViews() {
    try {
        const raw = localStorage.getItem(PPMS_PROJECT_VIEWS_KEY);
        savedViews.value = raw ? JSON.parse(raw) : [];
        if (!Array.isArray(savedViews.value)) {
            savedViews.value = [];
        }
    } catch {
        savedViews.value = [];
    }
}

function persistSavedViews() {
    localStorage.setItem(PPMS_PROJECT_VIEWS_KEY, JSON.stringify(savedViews.value));
}

function openSaveViewModal() {
    saveViewNameInput.value = '';
    showSaveViewModal.value = true;
}

function confirmSaveView() {
    const name = saveViewNameInput.value.trim();
    if (!name) {
        return;
    }
    const id = crypto.randomUUID?.() || String(Date.now());
    savedViews.value.push({ id, name, query: serializeUrlQuery() });
    persistSavedViews();
    showSaveViewModal.value = false;
    ppmsToastSuccess(t('projects.viewSaved'));
}

async function applySavedView(v) {
    await router.push({ name: 'projects', query: v.query || {} });
}

function removeSavedView(id) {
    savedViews.value = savedViews.value.filter((x) => x.id !== id);
    persistSavedViews();
}

function absoluteProjectsUrl(query) {
    const resolved = router.resolve({ name: 'projects', query });
    const path = resolved.href.startsWith('http') ? resolved.href : `${window.location.origin}${resolved.href}`;

    return path;
}

async function copyShareLink() {
    const url = absoluteProjectsUrl(serializeUrlQuery());
    try {
        await navigator.clipboard.writeText(url);
        ppmsToastSuccess(t('projects.linkCopied'));
    } catch {
        ppmsToastError(t('projects.exportError'));
    }
}

function closeToolbarMenu() {
    toolbarMenuOpen.value = false;
}

/**
 * Đóng menu/popover khi click ra ngoài.
 * Dùng `click` (bubble, mặc định) trên document — chạy sau handler trên nút; nút mở có @click.stop
 * nên không tới document. Không dùng mousedown + setTimeout: sau patch DOM của Vue, target cũ có thể
 * detach và contains() sai → đóng ngay panel vừa mở.
 */
function onDocumentClickOutside(e) {
    const target = e.target;
    if (!(target instanceof Node)) {
        return;
    }
    if (toolbarMenuOpen.value && toolbarMenuEl.value && !toolbarMenuEl.value.contains(target)) {
        toolbarMenuOpen.value = false;
    }
    if (userPopover.open) {
        if (userPopoverEl.value?.contains(target)) {
            return;
        }
        closeUserPopover();
    }
    if (quickLabelProjectId.value != null && target instanceof Element) {
        if (!target.closest('.ppms-pl-name-labels-row') && !target.closest('.ppms-pl-kanban-labels-row')) {
            closeQuickLabel();
        }
    }
}

function onGlobalKeydown(e) {
    if (e.key === 'Escape') {
        toolbarMenuOpen.value = false;
        closeUserPopover();
        closeQuickLabel();
        closeLabelsModal();
    }
}

async function onToolbarCopyLink() {
    closeToolbarMenu();
    await copyShareLink();
}

function onToolbarSaveView() {
    closeToolbarMenu();
    openSaveViewModal();
}

async function onToolbarExportCsv() {
    closeToolbarMenu();
    await exportFiltered('csv');
}

async function onToolbarExportJson() {
    closeToolbarMenu();
    await exportFiltered('json');
}

async function onToolbarExportPdf() {
    closeToolbarMenu();
    await exportFiltered('pdf');
}

async function onToolbarExportCsvSelected() {
    closeToolbarMenu();
    if (!selectedProjectIds.value.length) {
        ppmsToastError(t('projects.exportNeedSelection'));

        return;
    }
    await exportFiltered('csv', { ids: selectedProjectIds.value.join(',') });
}

async function onToolbarExportJsonSelected() {
    closeToolbarMenu();
    if (!selectedProjectIds.value.length) {
        ppmsToastError(t('projects.exportNeedSelection'));

        return;
    }
    await exportFiltered('json', { ids: selectedProjectIds.value.join(',') });
}

async function onToolbarExportPdfSelected() {
    closeToolbarMenu();
    if (!selectedProjectIds.value.length) {
        ppmsToastError(t('projects.exportNeedSelection'));

        return;
    }
    await exportFiltered('pdf', { ids: selectedProjectIds.value.join(',') });
}

function openImportModal() {
    importPreviewId.value = null;
    importPreviewRows.value = [];
    importSummary.value = { total: 0, valid: 0, invalid: 0 };
    importFileChosen.value = false;
    if (importFileInput.value) {
        importFileInput.value.value = '';
    }
    showImportModal.value = true;
}

function closeImportModal() {
    showImportModal.value = false;
}

function onImportFileChange() {
    importFileChosen.value = Boolean(importFileInput.value?.files?.length);
    importPreviewId.value = null;
    importPreviewRows.value = [];
    importSummary.value = { total: 0, valid: 0, invalid: 0 };
}

async function downloadImportTemplateCsv() {
    try {
        const res = await axios.get('/api/reports/import/projects-template.csv', { responseType: 'blob' });
        const blob = res.data;
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'ppms-projects-import-template.csv';
        a.click();
        URL.revokeObjectURL(url);
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.importErr')));
    }
}

async function downloadImportTemplateJson() {
    try {
        const res = await axios.get('/api/reports/import/projects-template.json', { responseType: 'blob' });
        const blob = res.data;
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'ppms-projects-import-template.json';
        a.click();
        URL.revokeObjectURL(url);
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.importErr')));
    }
}

async function runImportPreview() {
    const input = importFileInput.value;
    const file = input?.files?.[0];
    if (!file) {
        return;
    }
    importPreviewLoading.value = true;
    importPreviewId.value = null;
    try {
        const fd = new FormData();
        fd.append('file', file);
        const { data } = await axios.post('/api/projects/import/preview', fd);
        importPreviewId.value = data.preview_id || null;
        importPreviewRows.value = Array.isArray(data.rows) ? data.rows : [];
        importSummary.value = {
            total: data.summary?.total ?? 0,
            valid: data.summary?.valid ?? 0,
            invalid: data.summary?.invalid ?? 0,
        };
    } catch (e) {
        importPreviewRows.value = [];
        importSummary.value = { total: 0, valid: 0, invalid: 0 };
        ppmsToastError(formatApiUserMessage(e, t('projects.importErr')));
    } finally {
        importPreviewLoading.value = false;
    }
}

async function runImportCommit() {
    const id = importPreviewId.value;
    const n = importSummary.value.valid;
    if (!id || n < 1) {
        return;
    }
    if (!(await ppmsConfirm(t('projects.importCommitConfirm', { n }), { title: t('projects.toolbarImport') }))) {
        return;
    }
    importCommitLoading.value = true;
    try {
        const { data } = await axios.post('/api/projects/import/commit', { preview_id: id });
        ppmsToastSuccess(
            t('projects.importOk', { created: data.created ?? 0, updated: data.updated ?? 0 }),
        );
        closeImportModal();
        await load();
    } catch (e) {
        const msg = formatApiUserMessage(e, t('projects.importErr'));
        if (e.response?.status === 410) {
            ppmsToastError(t('projects.importPreviewExpired'));
        } else {
            ppmsToastError(msg);
        }
    } finally {
        importCommitLoading.value = false;
    }
}

async function exportFiltered(kind, opts = {}) {
    const params = { ...buildExportParams() };
    if (opts.ids) {
        params.ids = opts.ids;
    }
    const path =
        kind === 'pdf'
            ? '/api/reports/export/projects-filtered.pdf'
            : kind === 'json'
              ? '/api/reports/export/projects-filtered.json'
              : '/api/reports/export/projects-filtered.csv';
    try {
        const res = await axios.get(path, {
            params,
            responseType: 'blob',
        });
        const blob = res.data;
        const dispo = res.headers['content-disposition'];
        let filename =
            kind === 'pdf' ? 'projects.pdf' : kind === 'json' ? 'projects.json' : 'projects.csv';
        if (dispo && dispo.includes('filename=')) {
            const m = dispo.match(/filename="?([^";]+)"?/);
            if (m) {
                filename = m[1];
            }
        }
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.exportError')));
    }
}

function toggleSelectAll(ev) {
    const checked = ev.target.checked;
    const idsOnPage = projects.value.map((p) => p.id);
    if (checked) {
        selectedProjectIds.value = [...new Set([...selectedProjectIds.value, ...idsOnPage])];
    } else {
        const drop = new Set(idsOnPage);
        selectedProjectIds.value = selectedProjectIds.value.filter((id) => !drop.has(id));
    }
}

function clearBulkSelection() {
    selectedProjectIds.value = [];
}

async function runBulkDelete() {
    if (!selectedProjectIds.value.length) {
        ppmsToastError(t('projects.bulkNeedSelection'));

        return;
    }
    if (!(await ppmsConfirm(t('projects.bulkDeleteConfirm', { n: selectedProjectIds.value.length })))) {
        return;
    }
    try {
        const { data } = await axios.post('/api/projects/bulk-destroy', { project_ids: selectedProjectIds.value });
        const n = data.deleted ?? selectedProjectIds.value.length;
        ppmsToastSuccess(t('projects.bulkDeleteOk', { n }));
        selectedProjectIds.value = [];
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.bulkDeleteErr')));
    }
}

async function loadUsers() {
    try {
        const { data } = await axios.get('/api/users/lookup');
        userOptions.value = Array.isArray(data) ? data : [];
    } catch {
        userOptions.value = [];
    }
}

async function loadCurrentUser() {
    try {
        const { data } = await axios.get('/api/user');
        currentUser.value = data;
    } catch {
        currentUser.value = null;
    }
}

function parseSuppliersPayload() {
    const lines = form.suppliers_text
        .split('\n')
        .map((s) => s.trim())
        .filter(Boolean);
    if (!lines.length) {
        return null;
    }

    return lines.map((name) => ({ name }));
}

async function createProject() {
    formError.value = '';
    if (!(await ppmsConfirm(t('projects.confirmCreate')))) {
        return;
    }
    if (!form.owner_id) {
        formError.value = t('projects.bulkNeedField');

        return;
    }
    try {
        const suppliers = parseSuppliersPayload();
        const labelTokens = parseCommaLabelTokens(form.labels_text);
        const payload = {
            name: form.name,
            type: form.type,
            owner_id: Number(form.owner_id),
            deadline: form.deadline || null,
            start_date: form.start_date || null,
            description: form.description || null,
            customer_name: form.customer_name?.trim() || null,
            customer_email: form.customer_email?.trim() || null,
            suppliers,
        };
        if (labelTokens.length) {
            payload.labels = labelTokens;
        }
        await axios.post('/api/projects', payload);
        showForm.value = false;
        form.suppliers_text = '';
        form.customer_name = '';
        form.customer_email = '';
        form.labels_text = '';
        ppmsToastSuccess(t('projects.createOk'));
        page.value = 1;
        await load();
    } catch (e) {
        formError.value = formatApiUserMessage(e, t('projects.createErr'));
    }
}

watch(
    () => route.query,
    async () => {
        const sig = routeQuerySignature(route.query);
        if (routeQueryPushEcho !== null && sig === routeQueryPushEcho) {
            routeQueryPushEcho = null;

            return;
        }
        routeQueryPushEcho = null;
        hydrateFromRouteQuery(route.query);
        if (route.query.view !== 'kanban' && route.query.view !== 'list') {
            viewMode.value = readStoredViewMode();
        }
        await load();
    },
    { deep: true },
);

onMounted(async () => {
    document.addEventListener('click', onDocumentClickOutside);
    document.addEventListener('keydown', onGlobalKeydown);
    hydrateFromRouteQuery(route.query);
    if (route.query.view !== 'kanban' && route.query.view !== 'list') {
        viewMode.value = readStoredViewMode();
    }
    loadSavedViews();
    await Promise.all([loadCurrentUser(), loadUsers(), fetchLabelSuggestions()]);
    await load();
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClickOutside);
    document.removeEventListener('keydown', onGlobalKeydown);
});
</script>
