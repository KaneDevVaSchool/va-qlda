<template>
    <main id="project-list-main" class="ppms-page ppms-project-list-page" tabindex="-1">
        <h1 class="ppms-sr-only">{{ t('projects.pageHeading') }}</h1>
        <datalist id="ppms-pl-label-datalist">
            <option v-for="s in labelsSuggestions" :key="'gdl-' + s" :value="s" />
        </datalist>

        <ProjectListSavedViews :saved-views="savedViews" @apply="applySavedView($event)" @remove="removeSavedView" />

        <ProjectListToolbar
            ref="toolbarRef"
            v-model:menu-open="toolbarMenuOpen"
            :view-mode="viewMode"
            :loading="loading"
            :page="page"
            :last-page="lastPage"
            :total="total"
            :range-from="rangeFrom"
            :range-to="rangeTo"
            :per-page="perPage"
            :can-import="canImport"
            :can-export="canExport"
            :filters="filters"
            :team-options="teamOptions"
            :column-picker-options="columnPickerOptions"
            :column-visibility="columnVisibility"
            :selected-count="selectedProjectIds.length"
            @set-view-mode="setViewMode"
            @open-create="openCreateModal"
            @go-page="goPage"
            @set-per-page="onSetPerPage"
            @toolbar-labels="onToolbarLabels"
            @open-import="openImportModal"
            @export-csv-filtered="onToolbarExportCsv"
            @export-json-filtered="onToolbarExportJson"
            @export-pdf-filtered="onToolbarExportPdf"
            @export-csv-selected="onToolbarExportCsvSelected"
            @export-json-selected="onToolbarExportJsonSelected"
            @export-pdf-selected="onToolbarExportPdfSelected"
            @copy-link="onToolbarCopyLink"
            @save-view="onToolbarSaveView"
            @filter-change="onFilterChange"
            @column-toggle="onColumnToggle"
        />

        <ProjectListBulkDeleteDock
            :can-bulk-delete="canBulkDelete"
            :selected-count="selectedProjectIds.length"
            :loading="loading"
            @clear="clearBulkSelection"
            @bulk-delete="runBulkDelete"
        />

        <ProjectListLoadingState :loading="loading" :view-mode="viewMode" />
        <section v-if="!loading" class="ppms-project-list-table-section" :aria-label="t('projects.listSectionTitle')">
            <div v-if="!projects.length" class="ppms-empty-hint ppms-mt">
                <p class="ppms-empty-hint-text">{{ t('projects.tableEmpty') }}</p>
            </div>
            <div v-else-if="viewMode === 'list'" class="ppms-pl-list-view ppms-mt">
                <div v-if="projects.length" class="ppms-pl-list-meta">
                    <ul class="ppms-pl-list-meta-list">
                        <li>{{ t('projects.listGroupHint') }}</li>
                        <li>{{ t('projects.listGroupByCustomerHint') }}</li>
                    </ul>
                    <div class="ppms-pl-list-meta-actions" role="group" :aria-label="t('projects.listMetaActionsAria')">
                        <button type="button" class="ppms-pl-list-meta-btn" @click="collapseAllCustomerGroups">
                            {{ t('projects.listCollapseAll') }}
                        </button>
                        <button type="button" class="ppms-pl-list-meta-btn ppms-pl-list-meta-btn--primary" @click="expandAllCustomerGroups">
                            {{ t('projects.listExpandAll') }}
                        </button>
                    </div>
                </div>
                <div class="ppms-table-scroll ppms-table-scroll--sticky-head ppms-project-list-table-wrap">
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
                            <th v-if="colVis('team')" class="ppms-th-team">{{ t('projects.colTeam') }}</th>
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
                        <template v-for="group in listGroupsByCustomer" :key="'cust-' + group.key">
                            <tr class="ppms-pl-customer-group-row">
                                <td :colspan="listTableColspan" class="ppms-pl-customer-group-cell">
                                    <button
                                        type="button"
                                        class="ppms-pl-customer-group-head"
                                        :aria-expanded="!isCustomerGroupCollapsed(group.key)"
                                        @click="toggleCustomerGroup(group.key)"
                                    >
                                        <svg
                                            class="ppms-pl-customer-group-chevron"
                                            :class="{ 'ppms-pl-customer-group-chevron--collapsed': isCustomerGroupCollapsed(group.key) }"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            aria-hidden="true"
                                        >
                                            <polyline points="9 18 15 12 9 6" />
                                        </svg>
                                        <span class="ppms-pl-customer-group-title">{{ customerGroupLabel(group) }}</span>
                                        <span class="ppms-pl-customer-group-count">{{
                                            t('projects.listGroupCustomerCount', { n: group.projects.length })
                                        }}</span>
                                    </button>
                                </td>
                            </tr>
                            <template v-if="!isCustomerGroupCollapsed(group.key)">
                                <tr
                                    v-for="p in group.projects"
                                    :key="p.id"
                                    class="ppms-pl-data-row"
                                    :class="{ 'ppms-pl-data-row--inline': isInlineEditing(p) }"
                                >
                                <td v-if="canBulk" class="ppms-td-check">
                                    <input v-model="selectedProjectIds" type="checkbox" :value="p.id" />
                                </td>
                                <td v-if="colVis('admin')" class="ppms-td-admin">
                                    <button
                                        type="button"
                                        class="ppms-pl-usercell ppms-pl-usercell-trigger"
                                        :disabled="!p.owner"
                                        :aria-label="t('projects.avatarInfoAdmin')"
                                        @click="openAdminUserPopover($event, p)"
                                    >
                                        <span
                                            class="ppms-pl-avatar"
                                            :style="{ background: avatarColor(p.owner?.name || p.owner?.email || '?') }"
                                            :aria-hidden="true"
                                        >
                                            {{ userInitials(p.owner?.name) }}
                                        </span>
                                        <span class="ppms-pl-username">{{ p.owner?.name || '—' }}</span>
                                    </button>
                                </td>
                                <td v-if="colVis('code')" class="ppms-td-code ppms-muted">
                                    <div v-if="canEditProject && isInlineEditing(p)" class="ppms-pl-inline-field-stack">
                                        <input
                                            v-model="inlineDraft.code"
                                            type="text"
                                            class="ppms-input ppms-pl-inline-input"
                                            :placeholder="t('projects.inlinePlaceholderCode')"
                                            @click.stop
                                            @focus="setInlineEditAnchor('code')"
                                        />
                                        <ProjectListInlineEditBar
                                            v-if="inlineEditAnchor === 'code'"
                                            :disabled="inlineSaving"
                                            @save="saveInlineEdit"
                                            @cancel="cancelInlineEdit"
                                        />
                                    </div>
                                    <template v-else>{{ projectCode(p) }}</template>
                                </td>
                                <td v-if="colVis('name')" class="ppms-td-name">
                                    <template v-if="canEditProject && isInlineEditing(p)">
                                        <div class="ppms-pl-inline-field-stack">
                                            <input
                                                v-model="inlineDraft.name"
                                                type="text"
                                                class="ppms-input ppms-pl-inline-input"
                                                :placeholder="t('projects.inlinePlaceholderName')"
                                                @click.stop
                                                @focus="setInlineEditAnchor('name')"
                                            />
                                            <ProjectListInlineEditBar
                                                v-if="inlineEditAnchor === 'name'"
                                                :disabled="inlineSaving"
                                                @save="saveInlineEdit"
                                                @cancel="cancelInlineEdit"
                                            />
                                        </div>
                                    </template>
                                    <template v-else>
                                    <router-link class="ppms-pl-name-link" :to="'/projects/' + p.id">{{ p.name }}</router-link>
                                    <div class="ppms-pl-name-labels-row">
                                        <div v-if="projectLabelList(p).length" class="ppms-pl-name-labels">
                                            <span
                                                v-for="(lb, li) in projectLabelsPreview(p).show"
                                                :key="'lb-' + p.id + '-' + li"
                                                class="ppms-pl-label-chip ppms-pl-label-chip--under-name"
                                                >{{ lb }}</span
                                            >
                                            <span v-if="projectLabelsPreview(p).more > 0" class="ppms-pl-labels-more"
                                                >+{{ projectLabelsPreview(p).more }}</span
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
                                    </template>
                                </td>
                                <td v-if="colVis('team')" class="ppms-td-team">
                                    <div v-if="canEditProject && isInlineEditing(p)" class="ppms-pl-inline-field-stack">
                                        <select
                                            v-model="inlineDraft.team_id"
                                            class="ppms-input ppms-pl-inline-select"
                                            @click.stop
                                            @focus="setInlineEditAnchor('team')"
                                        >
                                            <option value="">{{ t('projects.inlineTeamUnset') }}</option>
                                            <option v-for="tm in teamOptions" :key="'itm-' + tm.id" :value="String(tm.id)">{{ tm.name }}</option>
                                        </select>
                                        <ProjectListInlineEditBar
                                            v-if="inlineEditAnchor === 'team'"
                                            :disabled="inlineSaving"
                                            @save="saveInlineEdit"
                                            @cancel="cancelInlineEdit"
                                        />
                                    </div>
                                    <template v-else>
                                    <span v-if="p.team?.name" class="ppms-pl-team-pill" :title="p.team.name">{{ p.team.name }}</span>
                                    <span v-else class="ppms-muted">—</span>
                                    </template>
                                </td>
                                <td v-if="colVis('participants')" class="ppms-td-participants">
                                    <div class="ppms-pl-participants">
                                        <button
                                            v-for="(slot, si) in participantVisibleSlots(p)"
                                            :key="'pa-' + p.id + '-' + si"
                                            type="button"
                                            class="ppms-pl-participants-avatar-btn"
                                            :style="{ zIndex: si + 1 }"
                                            :aria-label="participantSlotAriaLabel(slot)"
                                            @click.stop="openSingleParticipantPopover($event, p, slot)"
                                        >
                                            <span
                                                class="ppms-pl-avatar ppms-pl-avatar--sm ppms-pl-participants-stack-avatar"
                                                :style="{ background: avatarColor(slot.colorSeed) }"
                                            >
                                                {{ slot.initials }}
                                            </span>
                                        </button>
                                        <button
                                            v-if="participantOverflowCount(p) > 0"
                                            type="button"
                                            class="ppms-pl-participants-more ppms-pl-participants-overflow-btn"
                                            :aria-label="t('projects.userPopoverMoreParticipantsAria', { n: participantOverflowCount(p) })"
                                            @click.stop="openParticipantsOverflowMenu($event, p)"
                                        >
                                            +{{ participantOverflowCount(p) }}
                                        </button>
                                    </div>
                                </td>
                                <td v-if="colVis('progress')" class="ppms-td-process">
                                    <div
                                        class="ppms-progress-track ppms-progress-track--staging"
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
                                        <span
                                            class="ppms-progress-knob"
                                            :style="{
                                                left: `${clampProgress(p.progress)}%`,
                                            }"
                                            aria-hidden="true"
                                        >
                                            <span class="ppms-progress-knob-pct">{{ formatProgress(p.progress) }}%</span>
                                        </span>
                                    </div>
                                </td>
                                <td v-if="colVis('tasks')" class="ppms-td-num ppms-td-tasks">{{ p.tasks_count ?? '—' }}</td>
                                <td v-if="colVis('start')" class="ppms-td-date">
                                    <div v-if="canEditProject && isInlineEditing(p)" class="ppms-pl-inline-field-stack ppms-pl-inline-field-stack--tight">
                                        <input
                                            v-model="inlineDraft.start_date"
                                            type="date"
                                            class="ppms-input ppms-pl-inline-date"
                                            @click.stop
                                            @focus="setInlineEditAnchor('start')"
                                        />
                                        <ProjectListInlineEditBar
                                            v-if="inlineEditAnchor === 'start'"
                                            :disabled="inlineSaving"
                                            @save="saveInlineEdit"
                                            @cancel="cancelInlineEdit"
                                        />
                                    </div>
                                    <template v-else>
                                    <span v-if="!p.start_date" class="ppms-muted">{{ t('projects.startNone') }}</span>
                                    <span v-else>{{ formatDateIso(p.start_date) }}</span>
                                    </template>
                                </td>
                                <td v-if="colVis('actualStart')" class="ppms-td-date">
                                    <div v-if="canEditProject && isInlineEditing(p)" class="ppms-pl-inline-field-stack ppms-pl-inline-field-stack--tight">
                                        <input
                                            v-model="inlineDraft.actual_start_date"
                                            type="date"
                                            class="ppms-input ppms-pl-inline-date"
                                            @click.stop
                                            @focus="setInlineEditAnchor('actualStart')"
                                        />
                                        <ProjectListInlineEditBar
                                            v-if="inlineEditAnchor === 'actualStart'"
                                            :disabled="inlineSaving"
                                            @save="saveInlineEdit"
                                            @cancel="cancelInlineEdit"
                                        />
                                    </div>
                                    <template v-else>
                                    <span v-if="!p.actual_start_date" class="ppms-muted">{{ t('projects.actualStartNone') }}</span>
                                    <span v-else>{{ formatDateIso(p.actual_start_date) }}</span>
                                    </template>
                                </td>
                                <td v-if="colVis('end')" class="ppms-td-date">
                                    <div v-if="canEditProject && isInlineEditing(p)" class="ppms-pl-inline-field-stack ppms-pl-inline-field-stack--tight">
                                        <input
                                            v-model="inlineDraft.deadline"
                                            type="date"
                                            class="ppms-input ppms-pl-inline-date"
                                            @click.stop
                                            @focus="setInlineEditAnchor('end')"
                                        />
                                        <ProjectListInlineEditBar
                                            v-if="inlineEditAnchor === 'end'"
                                            :disabled="inlineSaving"
                                            @save="saveInlineEdit"
                                            @cancel="cancelInlineEdit"
                                        />
                                    </div>
                                    <template v-else>
                                    <span v-if="!p.deadline" class="ppms-muted">{{ t('projects.deadlineNone') }}</span>
                                    <span v-else class="ppms-deadline" :class="deadlineTone(p.deadline).cls">
                                        {{ formatDeadline(p.deadline) }}
                                    </span>
                                    </template>
                                </td>
                                <td v-if="colVis('status')" class="ppms-td-status">
                                    <div
                                        v-if="canEditProject && isInlineEditing(p)"
                                        class="ppms-pl-inline-field-stack"
                                        @focusin="setInlineEditAnchor('status')"
                                    >
                                        <div class="ppms-pl-inline-type-status">
                                            <select v-model="inlineDraft.type" class="ppms-input ppms-pl-inline-select ppms-pl-inline-select--sm" @click.stop>
                                                <option value="maintenance">{{ t('projects.typeShort.maintenance') }}</option>
                                                <option value="delivery">{{ t('projects.typeShort.delivery') }}</option>
                                                <option value="rnd">{{ t('projects.typeShort.rnd') }}</option>
                                            </select>
                                            <select v-model="inlineDraft.phase" class="ppms-input ppms-pl-inline-select ppms-pl-inline-select--sm" @click.stop>
                                                <option value="planning">{{ t('projects.phase.planning') }}</option>
                                                <option value="development">{{ t('projects.phase.development') }}</option>
                                                <option value="uat">{{ t('projects.phase.uat') }}</option>
                                                <option value="done">{{ t('projects.phase.done') }}</option>
                                                <option value="maintenance">{{ t('projects.phase.maintenance') }}</option>
                                                <option value="rnd">{{ t('projects.phase.rnd') }}</option>
                                            </select>
                                            <select v-model="inlineDraft.status" class="ppms-input ppms-pl-inline-select ppms-pl-inline-select--sm" @click.stop>
                                                <option value="on_track">{{ t('projects.status.on_track') }}</option>
                                                <option value="at_risk">{{ t('projects.status.at_risk') }}</option>
                                                <option value="delayed">{{ t('projects.status.delayed') }}</option>
                                                <option value="blocked">{{ t('projects.status.blocked') }}</option>
                                            </select>
                                        </div>
                                        <ProjectListInlineEditBar
                                            v-if="inlineEditAnchor === 'status'"
                                            :disabled="inlineSaving"
                                            @save="saveInlineEdit"
                                            @cancel="cancelInlineEdit"
                                        />
                                    </div>
                                    <span v-else class="ppms-status-pill" :class="statusPillClass(p.status)">{{
                                        t(`projects.status.${p.status}`)
                                    }}</span>
                                </td>
                                <td v-if="colVis('actions')" class="ppms-td-actions">
                                    <div class="ppms-pl-actions-cell">
                                        <div
                                            v-if="canEditProject && isInlineEditing(p)"
                                            class="ppms-pl-actions-cell--inline-spacer"
                                            aria-hidden="true"
                                        />
                                        <div
                                            v-else
                                            class="ppms-pl-actions-dd"
                                            :class="{ 'ppms-pl-actions-dd--open': actionsMenuOpenId === p.id }"
                                        >
                                            <button
                                                :id="'pl-actions-trigger-' + p.id"
                                                type="button"
                                                class="ppms-pl-actions-dd__trigger"
                                                :aria-controls="'pl-actions-menu-' + p.id"
                                                :aria-expanded="actionsMenuOpenId === p.id"
                                                :aria-haspopup="true"
                                                :aria-label="t('projects.actionsMenuAria')"
                                                @click.stop="toggleActionsMenu(p.id)"
                                            >
                                                <svg
                                                    class="ppms-pl-actions-dd__trigger-ico"
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                >
                                                    <circle cx="12" cy="5" r="2" />
                                                    <circle cx="12" cy="12" r="2" />
                                                    <circle cx="12" cy="19" r="2" />
                                                </svg>
                                            </button>
                                            <div
                                                v-show="actionsMenuOpenId === p.id"
                                                :id="'pl-actions-menu-' + p.id"
                                                class="ppms-pl-actions-dd__panel"
                                                role="menu"
                                                :aria-labelledby="'pl-actions-trigger-' + p.id"
                                                @click.stop
                                            >
                                                <router-link
                                                    role="menuitem"
                                                    class="ppms-pl-actions-dd__item"
                                                    :to="'/projects/' + p.id"
                                                    @click="closeActionsMenu"
                                                >
                                                    <svg
                                                        class="ppms-pl-actions-dd__item-ico"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        aria-hidden="true"
                                                    >
                                                        <path d="M5 12h14M12 5l7 7-7 7" />
                                                    </svg>
                                                    <span>{{ t('projects.openDetail') }}</span>
                                                </router-link>
                                                <button
                                                    v-if="canEditProject"
                                                    type="button"
                                                    role="menuitem"
                                                    class="ppms-pl-actions-dd__item"
                                                    @click="onActionInlineEdit(p)"
                                                >
                                                    <span class="ppms-pl-actions-dd__item-ico ppms-pl-actions-dd__item-ico--text" aria-hidden="true"
                                                        >⚡</span
                                                    >
                                                    <span>{{ t('projects.inlineOpen') }}</span>
                                                </button>
                                                <button
                                                    v-if="canEditProject"
                                                    type="button"
                                                    role="menuitem"
                                                    class="ppms-pl-actions-dd__item"
                                                    @click="onActionOpenEditModal(p)"
                                                >
                                                    <svg
                                                        class="ppms-pl-actions-dd__item-ico"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        aria-hidden="true"
                                                    >
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                    </svg>
                                                    <span>{{ t('common.edit') }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                </tr>
                            </template>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="ppms-pl-table-foot-row">
                            <td :colspan="listTableColspan" class="ppms-pl-table-foot-cell">
                                <span class="ppms-pl-table-foot-inner">
                                    <span v-if="rangeFrom != null && rangeTo != null" class="ppms-pl-table-foot-stat">{{
                                        t('projects.listTableFootRange', { from: rangeFrom, to: rangeTo, total })
                                    }}</span>
                                    <span v-else class="ppms-pl-table-foot-stat">{{ t('projects.listTableFootTotal', { total }) }}</span>
                                    <span v-if="lastPage > 1" class="ppms-pl-table-foot-page">{{
                                        t('projects.pagination', { current: page, last: lastPage })
                                    }}</span>
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                </div>
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
                                'ppms-pl-kanban-col--many': col.projects.length > 5,
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
                                    tabindex="0"
                                    :draggable="canBulk && kanbanSavingId !== p.id"
                                    :aria-busy="kanbanSavingId === p.id ? 'true' : undefined"
                                    :aria-label="`${p.name} — ${t('projects.openDetail')}`"
                                    @pointerdown="onKanbanCardPointerDown"
                                    @click="onKanbanCardClick(p, $event)"
                                    @keydown.enter.prevent="goKanbanProjectDetail(p)"
                                    @keydown.space.prevent="goKanbanProjectDetail(p)"
                                    @dragstart="onKanbanDragStart($event, p)"
                                    @dragend="onKanbanDragEnd"
                                >
                                    <div class="ppms-pl-kanban-card-top">
                                        <label v-if="canBulk" class="ppms-pl-card-check ppms-pl-kanban-card-check" @click.stop>
                                            <input v-model="selectedProjectIds" type="checkbox" :value="p.id" />
                                            <span class="ppms-sr-only">{{ t('projects.selectRow', { name: p.name }) }}</span>
                                        </label>
                                        <div class="ppms-pl-kanban-card-head-text">
                                            <span class="ppms-pl-kanban-card-title">{{ p.name }}</span>
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
                                    <div v-if="p.team?.name" class="ppms-pl-kanban-team-row">
                                        <span class="ppms-pl-team-pill ppms-pl-team-pill--kanban">{{ p.team.name }}</span>
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
                                            @click.stop="openAdminUserPopover($event, p)"
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
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <ProjectListModalImport
            ref="importModalRef"
            v-model="showImportModal"
            :file-chosen="importFileChosen"
            :preview-loading="importPreviewLoading"
            :commit-loading="importCommitLoading"
            :preview-id="importPreviewId"
            :summary="importSummary"
            :preview-rows="importPreviewRows"
            @close="closeImportModal"
            @download-template="onImportDownloadTemplate"
            @file-change="onImportFileChange"
            @preview="runImportPreview"
            @commit="runImportCommit"
        />

        <ProjectListModalSaveView
            v-model:open="showSaveViewModal"
            v-model:name="saveViewNameInput"
            @confirm="confirmSaveView"
        />

        <ProjectListModalLabels
            v-model="showLabelsModal"
            :labels-modal="labelsModal"
            :labels-bulk-add-text="labelsBulkAddText"
            :labels-bulk-remove-text="labelsBulkRemoveText"
            :can-bulk="canBulk"
            @close="closeLabelsModal"
            @apply-filter="applyLabelFilterFromModal"
            @clear-filter="clearLabelFilterFromModal"
            @bulk-add="runBulkLabelsAdd"
            @bulk-remove="runBulkLabelsRemove"
            @update:labels-bulk-add-text="labelsBulkAddText = $event"
            @update:labels-bulk-remove-text="labelsBulkRemoveText = $event"
        />

        <ProjectListModalCreate
            ref="createModalRef"
            v-model:open="showForm"
            :form="form"
            :form-error="formError"
            :team-options="teamOptions"
            :team-locked="createTeamLocked"
            :stakeholder-lookups="contractLookups"
            @submit="submitProject"
            @refresh-stakeholder-lookups="loadContractLookups"
        />

        <ProjectListUserPopover
            ref="userPopoverRef"
            :open="userPopover.open"
            :top="userPopover.top"
            :left="userPopover.left"
            :mode="userPopover.mode"
            :user="userPopover.user"
            :menu-slots="userPopover.menuSlots"
            :stakeholders="userPopover.stakeholders"
            :participant-slot-label="participantSlotLabel"
            :avatar-color="avatarColor"
            :user-initials="userInitials"
            @pick="onPickMenuParticipant"
        />
    </main>
</template>

<style scoped>
.ppms-pl-data-row--inline {
    background: rgba(238, 242, 255, 0.42);
}
.ppms-pl-data-row--inline .ppms-input,
.ppms-pl-data-row--inline .ppms-pl-inline-select,
.ppms-pl-data-row--inline .ppms-pl-inline-date {
    border-radius: 8px;
    border-color: rgba(148, 163, 184, 0.85);
    padding: 0.35rem 0.5rem;
    min-height: 2rem;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}
.ppms-pl-data-row--inline .ppms-input:focus,
.ppms-pl-data-row--inline .ppms-pl-inline-select:focus,
.ppms-pl-data-row--inline .ppms-pl-inline-date:focus {
    border-color: var(--ppms-primary, #2563eb);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
    outline: none;
}
.ppms-pl-inline-input,
.ppms-pl-inline-select,
.ppms-pl-inline-date {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    box-sizing: border-box;
    font-size: 0.8125rem;
}
.ppms-pl-inline-type-status {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    min-width: 8rem;
}
.ppms-pl-inline-field-stack {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.4rem;
    width: 100%;
    min-width: 0;
}
.ppms-td-date .ppms-pl-inline-field-stack {
    align-items: center;
}
.ppms-pl-inline-field-stack--tight {
    gap: 0.3rem;
}
.ppms-pl-inline-select--sm {
    font-size: 0.75rem;
    min-height: 2rem;
}
.ppms-pl-actions-cell {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 2rem;
}
.ppms-pl-actions-cell--inline-spacer {
    width: 100%;
    min-height: 2.25rem;
}
.ppms-pl-actions-dd {
    position: relative;
    display: inline-flex;
    justify-content: center;
}
.ppms-pl-actions-dd__trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    padding: 0;
    margin: 0;
    border: 1px solid var(--ppms-border, #e2e8f0);
    border-radius: 10px;
    background: var(--ppms-surface, #fff);
    color: var(--ppms-muted, #64748b);
    cursor: pointer;
    transition:
        background 0.15s ease,
        border-color 0.15s ease,
        color 0.15s ease;
}
.ppms-pl-actions-dd__trigger:hover {
    border-color: #c7d2fe;
    background: #f8fafc;
    color: var(--ppms-primary, #2563eb);
}
.ppms-pl-actions-dd--open .ppms-pl-actions-dd__trigger {
    border-color: var(--ppms-primary, #2563eb);
    color: var(--ppms-primary, #2563eb);
    background: rgba(238, 242, 255, 0.9);
}
.ppms-pl-actions-dd__trigger-ico {
    width: 1.1rem;
    height: 1.1rem;
    opacity: 0.9;
}
.ppms-pl-actions-dd__panel {
    position: absolute;
    z-index: 50;
    right: 0;
    top: calc(100% + 4px);
    min-width: 12rem;
    padding: 0.35rem;
    border-radius: 10px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: var(--ppms-surface, #fff);
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
    text-align: left;
}
.ppms-pl-actions-dd__item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.45rem 0.55rem;
    margin: 0;
    border: none;
    border-radius: 8px;
    background: transparent;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--ppms-text, #0f172a);
    text-decoration: none;
    cursor: pointer;
    text-align: left;
    transition: background 0.12s ease;
}
.ppms-pl-actions-dd__item:hover {
    background: var(--ppms-primary-soft, #eef2ff);
}
.ppms-pl-actions-dd__item-ico {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
    opacity: 0.85;
}
.ppms-pl-actions-dd__item-ico--text {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1rem;
    font-size: 0.85rem;
    line-height: 1;
}
</style>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, unref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';
import { useProjectListColumns } from './composables/useProjectListColumns';
import { clampProgress, deadlineTone, formatDeadline, formatProgress, progressToneClass } from './utils/projectListDisplay';
import { parseCommaLabelTokens, projectLabelList, projectLabelsPreview } from './utils/projectLabels';
import { buildParticipantSlots as participantAllSlots, displayNameFromEmail } from './utils/projectParticipants';
import {
    KANBAN_FETCH_PER_PAGE,
    KANBAN_PHASE_ORDER,
    PPMS_PROJECT_LIST_VIEW_MODE_KEY,
    PPMS_PROJECT_VIEWS_KEY,
} from './constants/projectList';
import { PPMS_PROJECT_LIST_FILTERS_KEY } from './constants/projectListStorage';
import ProjectListBulkDeleteDock from './components/list/ProjectListBulkDeleteDock.vue';
import ProjectListLoadingState from './components/list/ProjectListLoadingState.vue';
import ProjectListModalCreate from './components/list/ProjectListModalCreate.vue';
import ProjectListModalImport from './components/list/ProjectListModalImport.vue';
import ProjectListModalLabels from './components/list/ProjectListModalLabels.vue';
import ProjectListModalSaveView from './components/list/ProjectListModalSaveView.vue';
import ProjectListSavedViews from './components/list/ProjectListSavedViews.vue';
import ProjectListToolbar from './components/list/ProjectListToolbar.vue';
import ProjectListUserPopover from './components/list/ProjectListUserPopover.vue';
import ProjectListInlineEditBar from './components/list/ProjectListInlineEditBar.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const { columnVisibility, colVis, onColumnToggle, columnPickerOptions, PL_COL_ORDER } = useProjectListColumns();

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
const rangeFrom = ref(null);
const rangeTo = ref(null);
const perPage = ref(50);

const currentUser = ref(null);
const selectedProjectIds = ref([]);

const teamOptions = ref([]);
const contractLookups = ref({ departments: [], blocks: [], vendors: [] });

const inlineEditingId = ref(null);
const inlineSaving = ref(false);
/** Cột đang “neo” thanh Lưu/Hủy (theo focus ô chỉnh sửa). */
const inlineEditAnchor = ref('name');

const INLINE_EDIT_ANCHOR_KEYS = ['code', 'name', 'team', 'start', 'actualStart', 'end', 'status'];

function firstVisibleInlineAnchor() {
    for (const k of INLINE_EDIT_ANCHOR_KEYS) {
        if (colVis(k)) {
            return k;
        }
    }

    return 'name';
}

function setInlineEditAnchor(key) {
    inlineEditAnchor.value = key;
}
/** Một hàng: menu thao tác (⋯) đang mở */
const actionsMenuOpenId = ref(null);
const inlineDraft = reactive({
    id: null,
    code: '',
    name: '',
    type: 'delivery',
    phase: 'planning',
    status: 'on_track',
    team_id: '',
    start_date: '',
    actual_start_date: '',
    deadline: '',
});

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
    team_id: '',
    archived: false,
    activePhase: false,
});

const form = reactive({
    name: '',
    type: 'delivery',
    phase: 'planning',
    status: 'on_track',
    owner_id: '',
    deadline: '',
    start_date: '',
    progress_calc: 'weighted_tasks',
    executor_user_ids: [],
    follower_user_ids: [],
    permission_preset: 'org_default',
    description: '',
    customer_name: '',
    customer_email: '',
    department_id: '',
    block_id: '',
    suppliers_text: '',
    labels_text: '',
    project_code: '',
    progress_pct: '',
    estimated_value: '',
    team_id: '',
    editingId: null,
});

function resetCreateForm() {
    form.name = '';
    form.type = 'delivery';
    form.phase = 'planning';
    form.status = 'on_track';
    form.owner_id = '';
    form.deadline = '';
    form.start_date = '';
    form.progress_calc = 'weighted_tasks';
    form.executor_user_ids = [];
    form.follower_user_ids = [];
    form.permission_preset = 'org_default';
    form.description = '';
    form.customer_name = '';
    form.customer_email = '';
    form.department_id = '';
    form.block_id = '';
    form.suppliers_text = '';
    form.labels_text = '';
    form.project_code = '';
    form.progress_pct = '';
    form.estimated_value = '';
    form.team_id = '';
    form.editingId = null;
}

/** Gán team mặc định theo team user đang thuộc (1 team → cố định; nhiều team → ưu tiên bộ lọc trang rồi team đầu). */
function applyDefaultTeamForCreate() {
    const memberTeams = currentUser.value?.teams;
    if (!Array.isArray(memberTeams) || memberTeams.length === 0) {
        return;
    }
    if (memberTeams.length === 1) {
        form.team_id = String(memberTeams[0].id);

        return;
    }
    const filterTid = filters.team_id;
    if (filterTid !== '' && memberTeams.some((t) => String(t.id) === String(filterTid))) {
        form.team_id = String(filterTid);

        return;
    }
    form.team_id = String(memberTeams[0].id);
}

const createTeamLocked = computed(() => {
    const teams = currentUser.value?.teams;

    return Array.isArray(teams) && teams.length === 1;
});

function openCreateModal() {
    resetCreateForm();
    applyDefaultTeamForCreate();
    showForm.value = true;
}

function dateToInputValue(d) {
    if (d == null || d === '') {
        return '';
    }
    if (typeof d === 'string') {
        const m = d.match(/^(\d{4}-\d{2}-\d{2})/);

        return m ? m[1] : '';
    }

    return '';
}

function closeActionsMenu() {
    actionsMenuOpenId.value = null;
}

function toggleActionsMenu(id) {
    actionsMenuOpenId.value = actionsMenuOpenId.value === id ? null : id;
}

function onActionInlineEdit(p) {
    closeActionsMenu();
    startInlineEdit(p);
}

function onActionOpenEditModal(p) {
    closeActionsMenu();
    openEditModal(p);
}

function startInlineEdit(p) {
    closeActionsMenu();
    if (inlineEditingId.value && inlineEditingId.value !== p.id) {
        cancelInlineEdit();
    }
    inlineEditingId.value = p.id;
    inlineDraft.id = p.id;
    inlineDraft.code = p.code || '';
    inlineDraft.name = p.name || '';
    inlineDraft.type = p.type || 'delivery';
    inlineDraft.phase = p.phase || 'planning';
    inlineDraft.status = p.status || 'on_track';
    inlineDraft.team_id = p.team_id != null ? String(p.team_id) : '';
    inlineDraft.start_date = dateToInputValue(p.start_date);
    inlineDraft.actual_start_date = dateToInputValue(p.actual_start_date);
    inlineDraft.deadline = dateToInputValue(p.deadline);
    inlineEditAnchor.value = firstVisibleInlineAnchor();
}

function cancelInlineEdit() {
    inlineEditingId.value = null;
    inlineSaving.value = false;
}

async function saveInlineEdit() {
    if (!inlineDraft.id) {
        return;
    }
    if (!(await ppmsConfirm(t('projects.inlineEditConfirm'), { title: t('projects.inlineEditTitle') }))) {
        return;
    }
    inlineSaving.value = true;
    try {
        await axios.patch(`/api/projects/${inlineDraft.id}`, {
            name: inlineDraft.name,
            code: inlineDraft.code || null,
            type: inlineDraft.type,
            phase: inlineDraft.phase,
            status: inlineDraft.status,
            team_id: inlineDraft.team_id ? Number(inlineDraft.team_id) : null,
            start_date: inlineDraft.start_date || null,
            actual_start_date: inlineDraft.actual_start_date || null,
            deadline: inlineDraft.deadline || null,
        });
        ppmsToastSuccess(t('projects.inlineEditOk'));
        cancelInlineEdit();
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.inlineEditErr')));
    } finally {
        inlineSaving.value = false;
    }
}

function suppliersArrayToText(suppliers) {
    if (!Array.isArray(suppliers) || !suppliers.length) {
        return '';
    }

    return suppliers
        .map((s) => {
            const n = (s?.name || '').trim();
            const c = (s?.contact || '').trim();
            if (!n) {
                return '';
            }

            return c ? `${n} — ${c}` : n;
        })
        .filter(Boolean)
        .join('\n');
}

function fillProjectFormFromRow(project) {
    form.name = project.name || '';
    form.type = project.type || 'delivery';
    form.phase = project.phase || 'planning';
    form.status = project.status || 'on_track';
    form.owner_id = project.owner_id != null ? String(project.owner_id) : '';
    form.team_id = project.team_id != null ? String(project.team_id) : '';
    form.deadline = dateToInputValue(project.deadline);
    form.start_date = dateToInputValue(project.start_date);
    form.progress_calc = project.progress_calc || 'weighted_tasks';
    form.executor_user_ids = (project.executor_user_ids || []).map(String);
    form.follower_user_ids = (project.follower_user_ids || []).map(String);
    form.permission_preset = project.permission_preset || 'org_default';
    form.description = project.description || '';
    form.customer_name = project.customer_name || '';
    form.customer_email = project.customer_email || '';
    form.department_id = project.department_id != null ? String(project.department_id) : '';
    form.block_id = project.block_id != null ? String(project.block_id) : '';
    form.suppliers_text = suppliersArrayToText(project.suppliers);
    form.labels_text = Array.isArray(project.labels) ? project.labels.join(', ') : '';
    form.project_code = project.code || '';
    form.progress_pct = project.progress != null ? String(project.progress) : '';
    form.estimated_value = project.estimated_value != null ? String(project.estimated_value) : '';
    form.editingId = project.id;
}

function openEditModal(project) {
    fillProjectFormFromRow(project);
    showForm.value = true;
}

const createModalRef = ref(null);

const savedViews = ref([]);

const toolbarMenuOpen = ref(false);
const toolbarRef = ref(null);

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
const importModalRef = ref(null);

function importFileInputEl() {
    return unref(importModalRef.value?.fileInputRef);
}
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

/** Chỉnh sửa nhanh từ danh sách (cùng quyền gần với thao tác hàng loạt). */
const canEditProject = computed(() => {
    const r = currentUser.value?.role;

    return r && ['admin', 'pm', 'tl'].includes(r);
});

function isInlineEditing(p) {
    return inlineEditingId.value === p.id;
}

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

/** Nhóm theo khách hàng (phòng ban) — trường customer_name trên dự án. */
const listGroupsByCustomer = computed(() => {
    const map = new Map();
    for (const p of projects.value) {
        const raw = (p.customer_name && String(p.customer_name).trim()) || '';
        const key = raw || '__none__';
        if (!map.has(key)) {
            map.set(key, { key, label: raw || null, projects: [] });
        }
        map.get(key).projects.push(p);
    }
    const arr = Array.from(map.values());
    arr.sort((a, b) => {
        if (a.key === '__none__') {
            return 1;
        }
        if (b.key === '__none__') {
            return -1;
        }

        return (a.label || '').localeCompare(b.label || '', 'vi');
    });

    return arr;
});

const listTableColspan = computed(() => {
    let n = 0;
    if (canBulk.value) {
        n++;
    }
    for (const k of PL_COL_ORDER) {
        if (colVis(k)) {
            n++;
        }
    }

    return Math.max(1, n);
});

const collapsedCustomerGroups = ref(new Set());

function isCustomerGroupCollapsed(key) {
    return collapsedCustomerGroups.value.has(key);
}

function toggleCustomerGroup(key) {
    const next = new Set(collapsedCustomerGroups.value);
    if (next.has(key)) {
        next.delete(key);
    } else {
        next.add(key);
    }
    collapsedCustomerGroups.value = next;
}

function collapseAllCustomerGroups() {
    collapsedCustomerGroups.value = new Set(listGroupsByCustomer.value.map((g) => g.key));
}

function expandAllCustomerGroups() {
    collapsedCustomerGroups.value = new Set();
}

function customerGroupLabel(group) {
    if (group.key === '__none__') {
        return t('projects.listGroupCustomerUnassigned');
    }

    return group.label || t('projects.listGroupCustomerUnassigned');
}

function kanbanLabelPreview(p) {
    return projectLabelsPreview(p, 2);
}

const kanbanDraggingId = ref(null);
const kanbanDragOverPhase = ref(null);
const kanbanSavingId = ref(null);

/** Gốc pointer trên card (tránh mở chi tiết sau thao tác kéo). */
const kanbanCardPointer = ref({ x: 0, y: 0, el: null });

function onKanbanCardPointerDown(e) {
    if (e.button !== 0) {
        return;
    }
    kanbanCardPointer.value = { x: e.clientX, y: e.clientY, el: e.currentTarget };
}

function goKanbanProjectDetail(p) {
    router.push({ name: 'project-detail', params: { id: String(p.id) } });
}

function onKanbanCardClick(p, e) {
    const start = kanbanCardPointer.value;
    if (start.el !== e.currentTarget) {
        return;
    }
    const dx = Math.abs(e.clientX - start.x);
    const dy = Math.abs(e.clientY - start.y);
    if (dx > 14 || dy > 14) {
        return;
    }
    if (e.target.closest('button, input, label, textarea, select')) {
        return;
    }
    goKanbanProjectDetail(p);
}

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

const userPopoverRef = ref(null);

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
    const el = unref(userPopoverRef.value?.rootEl);
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
    if (slot.kind === 'owner' || slot.kind === 'executor' || slot.kind === 'follower') {
        return slot.user;
    }

    const em = slot.email;

    return { name: displayNameFromEmail(em), email: em };
}

function participantSlotsEqual(a, b) {
    if (!a || !b || a.kind !== b.kind) {
        return false;
    }

    if (a.kind === 'stakeholder') {
        return a.email === b.email;
    }

    return a.user?.id === b.user?.id;
}

function otherParticipantLines(project, selectedSlot) {
    return participantAllSlots(project)
        .filter((s) => !participantSlotsEqual(s, selectedSlot))
        .map((s) => {
            if (s.kind === 'owner' || s.kind === 'executor' || s.kind === 'follower') {
                const u = s.user;

                return `${u.name} (${u.email})`;
            }

            return s.email;
        });
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
    const c = p.code != null && String(p.code).trim() ? String(p.code).trim() : '';

    return c || `DA-${String(p.id).padStart(5, '0')}`;
}

const PARTICIPANTS_AVATAR_MAX = 3;

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
    if (slot.kind === 'owner' || slot.kind === 'executor' || slot.kind === 'follower') {
        return slot.user?.name || slot.user?.email || '—';
    }

    const em = slot.email;

    return `${displayNameFromEmail(em)} (${em})`;
}

function participantSlotAriaLabel(slot) {
    if (slot.kind === 'owner' || slot.kind === 'executor' || slot.kind === 'follower') {
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
    const tid = q.team_id;
    filters.team_id = tid !== undefined && tid !== '' && tid !== null ? String(tid) : '';
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
    if (filters.team_id !== '' && !Number.isNaN(Number(filters.team_id))) {
        q.team_id = String(filters.team_id);
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

function isDefaultProjectListQuery(q) {
    const has =
        (q.search && String(q.search).trim()) ||
        q.type ||
        q.phase ||
        q.status ||
        (q.label && String(q.label).trim()) ||
        q.progress_min !== undefined ||
        q.progress_max !== undefined ||
        (q.sort && q.sort !== 'type_asc') ||
        q.owner_id ||
        q.team_id ||
        q.archived === '1' ||
        q.active_phase === '1' ||
        (q.page && String(q.page) !== '1') ||
        (q.per_page && String(q.per_page) !== '50') ||
        q.view === 'kanban';

    return !has;
}

function saveProjectListFiltersToStorage() {
    try {
        localStorage.setItem(
            PPMS_PROJECT_LIST_FILTERS_KEY,
            JSON.stringify({
                search: filters.search,
                type: filters.type,
                phase: filters.phase,
                status: filters.status,
                label: filters.label,
                progress_min: filters.progress_min,
                progress_max: filters.progress_max,
                sort: filters.sort,
                owner_id: filters.owner_id,
                team_id: filters.team_id,
                archived: filters.archived,
                activePhase: filters.activePhase,
                viewMode: viewMode.value,
                page: page.value,
                perPage: perPage.value,
            }),
        );
    } catch {
        /* ignore */
    }
}

function applySavedProjectListFilters() {
    try {
        const raw = localStorage.getItem(PPMS_PROJECT_LIST_FILTERS_KEY);
        if (!raw) {
            return;
        }
        const o = JSON.parse(raw);
        if (!o || typeof o !== 'object') {
            return;
        }
        if (typeof o.search === 'string') {
            filters.search = o.search;
        }
        if (typeof o.type === 'string') {
            filters.type = o.type;
        }
        if (typeof o.phase === 'string') {
            filters.phase = o.phase;
        }
        if (typeof o.status === 'string') {
            filters.status = o.status;
        }
        if (typeof o.label === 'string') {
            filters.label = o.label;
        }
        if (o.progress_min !== undefined && o.progress_min !== null && o.progress_min !== '') {
            const n = Number(o.progress_min);
            filters.progress_min = Number.isNaN(n) ? null : n;
        }
        if (o.progress_max !== undefined && o.progress_max !== null && o.progress_max !== '') {
            const n = Number(o.progress_max);
            filters.progress_max = Number.isNaN(n) ? null : n;
        }
        if (typeof o.sort === 'string') {
            filters.sort = o.sort;
        }
        if (typeof o.owner_id === 'string') {
            filters.owner_id = o.owner_id;
        }
        if (typeof o.team_id === 'string') {
            filters.team_id = o.team_id;
        }
        if (typeof o.archived === 'boolean') {
            filters.archived = o.archived;
        }
        if (typeof o.activePhase === 'boolean') {
            filters.activePhase = o.activePhase;
        }
        if (typeof o.viewMode === 'string' && (o.viewMode === 'list' || o.viewMode === 'kanban')) {
            viewMode.value = o.viewMode;
        }
        if (typeof o.page === 'number' && o.page >= 1) {
            page.value = Math.floor(o.page);
        }
        if (typeof o.perPage === 'number') {
            perPage.value = Math.min(100, Math.max(10, Math.floor(o.perPage)));
        }
    } catch {
        /* ignore */
    }
}

let saveListFiltersTimer = null;
function scheduleSaveListFiltersToStorage() {
    clearTimeout(saveListFiltersTimer);
    saveListFiltersTimer = setTimeout(() => saveProjectListFiltersToStorage(), 450);
}
watch(filters, scheduleSaveListFiltersToStorage, { deep: true });
watch([viewMode, page, perPage], scheduleSaveListFiltersToStorage);

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
    if (filters.team_id !== '' && !Number.isNaN(Number(filters.team_id))) {
        params.team_id = Number(filters.team_id);
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
            rangeFrom.value = data.from ?? null;
            rangeTo.value = data.to ?? null;
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

function onSetPerPage(n) {
    const v = Math.min(100, Math.max(10, Math.floor(Number(n))));
    if (perPage.value === v) {
        return;
    }
    perPage.value = v;
    page.value = 1;
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
    const toolbarMenuEl = toolbarRef.value?.getMenuEl?.();
    if (toolbarMenuOpen.value && toolbarMenuEl && !toolbarMenuEl.contains(target)) {
        toolbarMenuOpen.value = false;
    }
    if (actionsMenuOpenId.value != null && target instanceof Element) {
        if (!target.closest('.ppms-pl-actions-dd')) {
            closeActionsMenu();
        }
    }
    createModalRef.value?.handleDocumentClick?.(target);
    if (userPopover.open) {
        const popEl = unref(userPopoverRef.value?.rootEl);
        if (popEl?.contains(target)) {
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
        closeActionsMenu();
        createModalRef.value?.closeProgressCalc?.();
        createModalRef.value?.closeOwnerLookup?.();
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
    const input = importFileInputEl();
    if (input) {
        input.value = '';
    }
    showImportModal.value = true;
}

function closeImportModal() {
    showImportModal.value = false;
}

function onImportFileChange() {
    importFileChosen.value = Boolean(importFileInputEl()?.files?.length);
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

function onImportDownloadTemplate(kind) {
    if (kind === 'csv') {
        downloadImportTemplateCsv();
    } else {
        downloadImportTemplateJson();
    }
}

async function runImportPreview() {
    const input = importFileInputEl();
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

async function loadCurrentUser() {
    try {
        const { data } = await axios.get('/api/user');
        currentUser.value = data;
    } catch {
        currentUser.value = null;
    }
}

async function loadTeams() {
    try {
        const { data } = await axios.get('/api/teams');
        teamOptions.value = Array.isArray(data) ? data : [];
    } catch {
        teamOptions.value = [];
    }
}

async function loadContractLookups() {
    try {
        const { data } = await axios.get('/api/contract-lookups');
        contractLookups.value = {
            departments: Array.isArray(data.departments) ? data.departments : [],
            blocks: Array.isArray(data.blocks) ? data.blocks : [],
            vendors: Array.isArray(data.vendors) ? data.vendors : [],
        };
    } catch {
        contractLookups.value = { departments: [], blocks: [], vendors: [] };
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

async function submitProject() {
    formError.value = '';
    const isEdit = form.editingId != null;
    if (!(await ppmsConfirm(t(isEdit ? 'projects.confirmUpdate' : 'projects.confirmCreate')))) {
        return;
    }
    if (!form.owner_id) {
        formError.value = t('projects.createNeedOwner');

        return;
    }
    try {
        const suppliers = parseSuppliersPayload();
        const labelTokens = parseCommaLabelTokens(form.labels_text);
        const execIds = (form.executor_user_ids || []).map((id) => Number(id)).filter((n) => n > 0);
        const folIds = (form.follower_user_ids || []).map((id) => Number(id)).filter((n) => n > 0);
        const payload = {
            name: form.name,
            type: form.type,
            phase: form.phase,
            status: form.status,
            owner_id: Number(form.owner_id),
            team_id: form.team_id !== '' && form.team_id != null ? Number(form.team_id) : null,
            deadline: form.deadline || null,
            start_date: form.start_date || null,
            description: form.description || null,
            customer_name: form.customer_name?.trim() || null,
            customer_email: form.customer_email?.trim() || null,
            department_id:
                form.department_id !== '' && form.department_id != null ? Number(form.department_id) : null,
            block_id: form.block_id !== '' && form.block_id != null ? Number(form.block_id) : null,
            suppliers,
            progress_calc: form.progress_calc || null,
            permission_preset: form.permission_preset || 'org_default',
            executor_user_ids: execIds,
            follower_user_ids: folIds,
        };
        if (form.estimated_value !== '' && form.estimated_value != null) {
            const ev = Number(form.estimated_value);
            if (!Number.isNaN(ev)) {
                payload.estimated_value = ev;
            }
        }
        if (labelTokens.length) {
            payload.labels = labelTokens;
        } else if (isEdit) {
            payload.labels = [];
        }
        if (isEdit) {
            await axios.put(`/api/projects/${form.editingId}`, payload);
            ppmsToastSuccess(t('projects.projectUpdateOk'));
        } else {
            await axios.post('/api/projects', payload);
            ppmsToastSuccess(t('projects.createOk'));
            createModalRef.value?.clearCreateDraft?.();
        }
        showForm.value = false;
        resetCreateForm();
        if (!isEdit) {
            page.value = 1;
        }
        await load();
    } catch (e) {
        formError.value = formatApiUserMessage(e, t(isEdit ? 'projects.updateErr' : 'projects.createErr'));
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
    if (isDefaultProjectListQuery(route.query)) {
        applySavedProjectListFilters();
        scheduleUrlReplace();
    } else if (route.query.view !== 'kanban' && route.query.view !== 'list') {
        viewMode.value = readStoredViewMode();
    }
    loadSavedViews();
    await Promise.all([loadCurrentUser(), fetchLabelSuggestions(), loadTeams(), loadContractLookups()]);
    await load();
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClickOutside);
    document.removeEventListener('keydown', onGlobalKeydown);
});
</script>
