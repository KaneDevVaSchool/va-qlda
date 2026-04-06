<template>
    <div class="ppms-page ppms-page--project-detail">
        <div v-if="loading" class="pbi-loading" role="status">
            <span class="pbi-loading-bar" aria-hidden="true" />
            {{ t('common.loading') }}
        </div>
        <template v-else-if="project">
            <div class="ppms-pd-shell">
                <div class="ppms-pd-main-col">
                    <header class="ppms-pd-head">
                        <router-link to="/projects" class="ppms-back">{{ t('projects.listBack') }}</router-link>
                        <div class="ppms-pd-head-row">
                            <div class="ppms-pd-head-title">
                                <h1>{{ project.name }}</h1>
                                <div v-if="projectLabelList(project).length" class="ppms-pl-detail-name-labels">
                                    <span
                                        v-for="(plb, pli) in projectLabelList(project)"
                                        :key="'phl-' + pli"
                                        class="ppms-pl-label-chip ppms-pl-label-chip--header"
                                        >{{ plb }}</span
                                    >
                                </div>
                            </div>
                            <div class="ppms-pd-head-actions">
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="openMetaEdit">
                                    {{ t('projects.pdActionStatus') }}
                                </button>
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="toastJoin">
                                    {{ t('projects.pdActionJoin') }}
                                </button>
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="toastGroup">
                                    {{ t('projects.pdActionGroup') }}
                                </button>
                                <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="goAddTask">
                                    {{ t('projects.pdActionAddTask') }}
                                </button>
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="toastGroup">
                                    {{ t('projects.pdActionAddGroup') }}
                                </button>
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="duplicateProject">
                                    {{ t('projects.duplicate') }}
                                </button>
                            </div>
                        </div>
                        <nav class="ppms-pd-tabs" role="tablist" :aria-label="t('projects.detailTitle')">
                            <button
                                v-for="tab in detailTabs"
                                :key="tab.id"
                                type="button"
                                role="tab"
                                class="ppms-pd-tab"
                                :class="{ 'is-active': activeTab === tab.id }"
                                :aria-selected="activeTab === tab.id"
                                @click="activeTab = tab.id"
                            >
                                {{ tab.label }}
                                <span v-if="tab.badge != null" class="ppms-pd-tab-badge">{{ tab.badge }}</span>
                            </button>
                        </nav>
                    </header>

                    <!-- Info -->
                    <div v-show="activeTab === 'info'" class="ppms-pd-tab-panel">
                        <div class="ppms-pd-stats">
                            <div class="ppms-pd-stat ppms-pd-stat--total">
                                <span class="ppms-pd-stat-value">{{ taskStats.total }}</span>
                                <span class="ppms-pd-stat-label">{{ t('projects.pdStatTotal') }}</span>
                            </div>
                            <div class="ppms-pd-stat ppms-pd-stat--overdue">
                                <span class="ppms-pd-stat-value">{{ taskStats.overdue }}</span>
                                <span class="ppms-pd-stat-label">{{ t('projects.pdStatOverdue') }}</span>
                            </div>
                            <div class="ppms-pd-stat ppms-pd-stat--progress">
                                <span class="ppms-pd-stat-value">{{ taskStats.inProgress }}</span>
                                <span class="ppms-pd-stat-label">{{ t('projects.pdStatInProgress') }}</span>
                            </div>
                            <div class="ppms-pd-stat ppms-pd-stat--done">
                                <span class="ppms-pd-stat-value">{{ taskStats.done }}</span>
                                <span class="ppms-pd-stat-label">{{ t('projects.pdStatDone') }}</span>
                            </div>
                            <div class="ppms-pd-stat ppms-pd-stat--pending">
                                <span class="ppms-pd-stat-value">{{ taskStats.pending }}</span>
                                <span class="ppms-pd-stat-label">{{ t('projects.pdStatPending') }}</span>
                            </div>
                        </div>

                        <section class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.pdGeneralTitle') }}</h2>
                            <div class="ppms-pd-info-grid">
                                <div class="ppms-pd-info-cell">
                                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldId') }}</span>
                                    <span class="ppms-pd-info-value">#{{ project.id }}</span>
                                </div>
                                <div class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                                    <span class="ppms-pd-info-label">{{ t('projects.fieldName') }}</span>
                                    <span class="ppms-pd-info-value">{{ project.name }}</span>
                                </div>
                                <div class="ppms-pd-info-cell">
                                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldManager') }}</span>
                                    <div class="ppms-pd-people">
                                        <span
                                            v-if="project.owner"
                                            class="ppms-pd-avatar"
                                            :title="project.owner.name"
                                            >{{ initials(project.owner.name) }}</span
                                        >
                                        <span v-else class="ppms-muted">—</span>
                                        <span v-if="project.owner" class="ppms-pd-info-value">{{ project.owner.name }}</span>
                                    </div>
                                </div>
                                <div class="ppms-pd-info-cell">
                                    <span class="ppms-pd-info-label">{{ t('projects.colPhase') }}</span>
                                    <span class="ppms-pd-badge">{{ t(`projects.phase.${project.phase}`) }}</span>
                                </div>
                                <div class="ppms-pd-info-cell">
                                    <span class="ppms-pd-info-label">{{ t('projects.colStatus') }}</span>
                                    <span class="ppms-pd-badge ppms-pd-badge--status">{{
                                        t(`projects.status.${project.status}`)
                                    }}</span>
                                </div>
                                <div class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldParticipants') }}</span>
                                    <div class="ppms-pd-people ppms-pd-people--wrap">
                                        <span
                                            v-for="u in participants"
                                            :key="'p-' + u.id"
                                            class="ppms-pd-avatar"
                                            :title="u.name"
                                            >{{ initials(u.name) }}</span
                                        >
                                        <span v-if="!participants.length" class="ppms-muted">—</span>
                                    </div>
                                </div>
                                <div class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                                    <span class="ppms-pd-info-label">{{ t('projects.progressLabel') }}</span>
                                    <div class="ppms-pd-progress-row">
                                        <div class="ppms-pd-progress-track">
                                            <div
                                                class="ppms-pd-progress-fill"
                                                :style="{ width: `${Math.min(100, Number(project.progress) || 0)}%` }"
                                            />
                                        </div>
                                        <span class="ppms-pd-info-value">{{ Number(project.progress).toFixed(1) }}%</span>
                                    </div>
                                </div>
                                <div class="ppms-pd-info-cell">
                                    <span class="ppms-pd-info-label">{{ t('projects.colStart') }}</span>
                                    <span class="ppms-pd-info-value">{{
                                        project.start_date ? String(project.start_date).slice(0, 10) : t('projects.startNone')
                                    }}</span>
                                </div>
                                <div class="ppms-pd-info-cell">
                                    <span class="ppms-pd-info-label">{{ t('projects.colActualStart') }}</span>
                                    <span class="ppms-pd-info-value">{{
                                        project.actual_start_date
                                            ? String(project.actual_start_date).slice(0, 10)
                                            : t('projects.actualStartNone')
                                    }}</span>
                                </div>
                                <div class="ppms-pd-info-cell">
                                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldDeadline') }}</span>
                                    <span class="ppms-pd-info-value">{{
                                        project.deadline ? String(project.deadline).slice(0, 10) : '—'
                                    }}</span>
                                </div>
                                <div class="ppms-pd-info-cell">
                                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldDuration') }}</span>
                                    <span class="ppms-pd-info-value">{{
                                        plannedSpanDays != null ? t('projects.pdDays', { n: plannedSpanDays }) : '—'
                                    }}</span>
                                </div>
                                <div class="ppms-pd-info-cell">
                                    <span class="ppms-pd-info-label">{{ t('projects.colType') }}</span>
                                    <span class="ppms-pd-info-value">{{ t(`projects.type.${project.type}`) }}</span>
                                </div>
                            </div>
                            <div v-if="project.description" class="ppms-pd-desc">
                                <h3 class="ppms-pd-desc-title">{{ t('projects.fieldDescription') }}</h3>
                                <p class="ppms-pd-desc-body">{{ project.description }}</p>
                            </div>
                        </section>

                        <section class="ppms-card ppms-pd-section">
                            <div class="ppms-row ppms-row--spread">
                                <h2>{{ t('projects.stakeholdersTitle') }}</h2>
                                <button type="button" class="ppms-btn-ghost" @click="openMetaEdit">
                                    {{ t('projects.editStakeholders') }}
                                </button>
                            </div>
                            <div class="ppms-split ppms-mt">
                                <div>
                                    <h3 class="ppms-muted ppms-pd-mini-h">{{ t('projects.customer') }}</h3>
                                    <p v-if="project.customer_name" style="margin: 0">{{ project.customer_name }}</p>
                                    <p v-else class="ppms-muted" style="margin: 0">{{ t('projects.customerUnset') }}</p>
                                    <p v-if="project.customer_email" class="ppms-muted ppms-mt-sm" style="margin: 0; font-size: 0.9rem">
                                        {{ t('projects.customerEmail') }}: {{ project.customer_email }}
                                    </p>
                                </div>
                                <div>
                                    <h3 class="ppms-muted ppms-pd-mini-h">{{ t('projects.suppliersTitle') }}</h3>
                                    <ul v-if="(project.suppliers || []).length" class="ppms-supplier-list">
                                        <li v-for="(s, i) in project.suppliers" :key="i">
                                            <strong>{{ s.name }}</strong>
                                            <template v-if="s.contact">
                                                <span class="ppms-muted">
                                                    — {{ t('projects.supplierContactShort') }}: {{ s.contact }}
                                                </span>
                                            </template>
                                        </li>
                                    </ul>
                                    <p v-else class="ppms-muted" style="margin: 0">{{ t('projects.suppliersEmpty') }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.pdWorkloadTitle') }}</h2>
                            <p v-if="!workloadRows.length" class="ppms-muted ppms-mt-sm">{{ t('projects.ganttEmpty') }}</p>
                            <div v-else class="ppms-pd-workload-table-wrap ppms-mt-sm">
                                <table class="ppms-table ppms-pd-workload">
                                    <thead>
                                        <tr>
                                            <th>{{ t('projects.thAssignee') }}</th>
                                            <th>{{ t('projects.pdColTasks') }}</th>
                                            <th>{{ t('projects.pdColHours') }}</th>
                                            <th>{{ t('projects.pdColDistribution') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="row in workloadRows" :key="row.key">
                                            <td>
                                                <div class="ppms-pd-people">
                                                    <span class="ppms-pd-avatar" :title="row.name">{{ initials(row.name) }}</span>
                                                    <span>{{ row.name }}</span>
                                                </div>
                                            </td>
                                            <td>{{ row.total }}</td>
                                            <td>{{ row.est.toFixed(1) }} / {{ row.act.toFixed(1) }}</td>
                                            <td>
                                                <div class="ppms-pd-stack-wrap" :title="workloadTitle(row)">
                                                    <div v-if="row.total" class="ppms-pd-stack">
                                                        <span
                                                            class="ppms-pd-stack-seg ppms-pd-stack-seg--done"
                                                            :style="{ width: `${(row.done / row.total) * 100}%` }"
                                                        />
                                                        <span
                                                            class="ppms-pd-stack-seg ppms-pd-stack-seg--prog"
                                                            :style="{ width: `${(row.inProgress / row.total) * 100}%` }"
                                                        />
                                                        <span
                                                            class="ppms-pd-stack-seg ppms-pd-stack-seg--other"
                                                            :style="{ width: `${(row.other / row.total) * 100}%` }"
                                                        />
                                                    </div>
                                                    <span v-else class="ppms-muted">—</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="ppms-pd-stack-legend">
                                    <span><i class="ppms-pd-dot ppms-pd-dot--done" />{{ t('projects.taskStatus.done') }}</span>
                                    <span><i class="ppms-pd-dot ppms-pd-dot--prog" />{{ t('projects.taskStatus.in_progress') }}</span>
                                    <span><i class="ppms-pd-dot ppms-pd-dot--other" />{{ t('projects.taskStatus.todo') }} + …</span>
                                </div>
                            </div>
                        </section>

                        <section class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.timelineTitle') }}</h2>
                            <p class="ppms-muted ppms-mt-sm">{{ t('projects.timelineHint') }}</p>
                            <div class="ppms-process-timeline ppms-mt-sm" role="list">
                                <div
                                    v-for="step in timelineDisplay"
                                    :key="step.phase"
                                    class="ppms-process-step"
                                    :class="{ 'is-current': step.isCurrent, 'is-done': !!step.completed_at }"
                                    role="listitem"
                                >
                                    <span class="ppms-process-dot" aria-hidden="true" />
                                    <div class="ppms-process-body">
                                        <span class="ppms-process-label">{{ t(`projects.phase.${step.phase}`) }}</span>
                                        <span v-if="step.isCurrent" class="ppms-process-badge">{{
                                            t('projects.timelineCurrent')
                                        }}</span>
                                        <time v-if="step.completed_at" :datetime="step.completed_at">{{ step.completed_at }}</time>
                                        <span v-else class="ppms-muted">{{ t('projects.timelinePlanned') }}</span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.pdDocsTitle') }}</h2>
                            <p class="ppms-muted ppms-mt-sm">{{ t('projects.pdDocsHint') }}</p>
                            <div v-if="canManageDocuments" class="ppms-pd-doc-toolbar ppms-mt-sm">
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="openDocAdd('folder')">
                                    {{ t('projects.pdDocsAddFolder') }}
                                </button>
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="openDocAdd('google_doc')">
                                    {{ t('projects.pdDocsAddGoogleDoc') }}
                                </button>
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="openDocAdd('google_sheet')">
                                    {{ t('projects.pdDocsAddGoogleSheet') }}
                                </button>
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="openDocAdd('link')">
                                    {{ t('projects.pdDocsAddLink') }}
                                </button>
                            </div>
                            <div v-if="canManageDocuments && docAddMode" class="ppms-pd-doc-form ppms-mt-sm">
                                <div class="ppms-task-form ppms-pd-doc-form-grid">
                                    <label class="ppms-field ppms-pd-doc-form-field">
                                        <span>{{ t('projects.pdDocsName') }}</span>
                                        <input v-model="docForm.name" type="text" autocomplete="off" />
                                    </label>
                                    <label v-if="docAddNeedsUrl" class="ppms-field ppms-pd-doc-form-field">
                                        <span>{{ t('projects.pdDocsUrl') }}</span>
                                        <input v-model="docForm.url" type="url" :placeholder="t('projects.pdDocsUrlPh')" />
                                    </label>
                                    <label v-if="docAddMode === 'folder'" class="ppms-field ppms-pd-doc-form-field">
                                        <span>{{ t('projects.pdDocsUrlDriveOptional') }}</span>
                                        <input v-model="docForm.url" type="url" :placeholder="t('projects.pdDocsUrlPh')" />
                                    </label>
                                    <label class="ppms-field ppms-pd-doc-form-field">
                                        <span>{{ t('projects.pdDocsParent') }}</span>
                                        <select v-model="docForm.parent_id">
                                            <option value="">{{ t('projects.pdDocsRoot') }}</option>
                                            <option v-for="fo in folderSelectOptions" :key="'fo-' + fo.id" :value="String(fo.id)">
                                                {{ fo.label }}
                                            </option>
                                        </select>
                                    </label>
                                </div>
                                <div class="ppms-pd-doc-form-actions">
                                    <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="submitDocForm">
                                        {{ t('projects.pdDocsSave') }}
                                    </button>
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="cancelDocAdd">
                                        {{ t('projects.pdDocsCancel') }}
                                    </button>
                                </div>
                            </div>
                            <div v-if="canManageDocuments" class="ppms-pd-doc-upload ppms-mt-sm">
                                <label class="ppms-field ppms-pd-doc-upload-inner">
                                    <span>{{ t('projects.pdDocsParent') }}</span>
                                    <select v-model="docForm.parent_id">
                                        <option value="">{{ t('projects.pdDocsRoot') }}</option>
                                        <option v-for="fo in folderSelectOptions" :key="'fu-' + fo.id" :value="String(fo.id)">
                                            {{ fo.label }}
                                        </option>
                                    </select>
                                </label>
                                <input
                                    ref="projectDocFileRef"
                                    type="file"
                                    class="ppms-sr-only"
                                    @change="onProjectDocUpload"
                                />
                                <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="triggerProjectDocFile">
                                    {{ t('projects.pdDocsUpload') }}
                                </button>
                            </div>
                            <p v-if="!documentRowsFlat.length" class="ppms-muted ppms-mt-sm">{{ t('projects.pdDocsEmpty') }}</p>
                            <ul v-else class="ppms-pd-doc-list ppms-mt-sm">
                                <li
                                    v-for="{ doc: drow, depth } in documentRowsFlat"
                                    :key="'doc-' + drow.id"
                                    class="ppms-pd-doc-row"
                                    :style="{ paddingLeft: `calc(${depth} * 1.1rem)` }"
                                >
                                    <span class="ppms-pd-doc-ico" :data-doc-type="drow.doc_type" aria-hidden="true" />
                                    <div class="ppms-pd-doc-main">
                                        <div class="ppms-pd-doc-title-row">
                                            <span class="ppms-pd-doc-name">{{ drow.name }}</span>
                                            <span class="ppms-pd-doc-type ppms-muted">{{ projectDocTypeLabel(drow.doc_type) }}</span>
                                        </div>
                                        <a
                                            v-if="drow.url && drow.doc_type !== 'upload'"
                                            :href="drow.url"
                                            class="ppms-pd-doc-url ppms-muted"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            >{{ drow.url }}</a
                                        >
                                    </div>
                                    <div class="ppms-pd-doc-actions">
                                        <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="openProjectDocument(drow)">
                                            {{ t('projects.pdDocsOpen') }}
                                        </button>
                                        <button
                                            v-if="canManageDocuments"
                                            type="button"
                                            class="ppms-btn-ghost ppms-btn-sm"
                                            @click="deleteProjectDocument(drow)"
                                        >
                                            {{ t('projects.pdDocsDelete') }}
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </section>

                        <section class="ppms-card ppms-pd-section">
                            <div class="ppms-row ppms-row--spread">
                                <h2>{{ t('projects.pdRecentAttach') }}</h2>
                                <button type="button" class="ppms-btn-ghost" @click="activeTab = 'attachments'">
                                    {{ t('projects.pdAttachAll') }}
                                </button>
                            </div>
                            <ul v-if="recentProjectAttachments.length" class="ppms-pd-attach-list ppms-mt-sm">
                                <li v-for="a in recentProjectAttachments" :key="'ra-' + a.id" class="ppms-pd-attach-row">
                                    <span class="ppms-pd-file-ico" :data-ext="fileExt(a.original_name)" />
                                    <div class="ppms-pd-attach-meta">
                                        <button type="button" class="ppms-linklike" @click="downloadFile(a)">
                                            {{ a.original_name }}
                                        </button>
                                        <span class="ppms-muted ppms-pd-attach-sub">
                                            {{ formatBytes(a.size_bytes) }} · {{ String(a.created_at).slice(0, 10) }}
                                        </span>
                                    </div>
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="openTaskForAttachment(a)">
                                        {{ t('projects.pdGoTask') }}
                                    </button>
                                </li>
                            </ul>
                            <p v-else class="ppms-muted ppms-mt-sm">{{ t('projects.ganttEmpty') }}</p>
                        </section>
                    </div>

                    <!-- Tasks -->
                    <div v-show="activeTab === 'tasks'" class="ppms-pd-tab-panel">
                        <section v-if="project.type === 'delivery'" class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.csatTitle') }}</h2>
                            <p v-if="project.csat_metrics" class="ppms-muted ppms-mt-sm">
                                {{
                                    t('projects.csatMetrics', {
                                        count: project.csat_metrics.response_count,
                                        invites: project.csat_metrics.invites_sent,
                                        rate: project.csat_metrics.response_rate_pct,
                                    })
                                }}
                            </p>
                            <form class="ppms-task-form ppms-mt-sm" @submit.prevent="submitCsat">
                                <input v-model.number="csat.rating" type="number" min="1" max="5" required />
                                <input v-model="csat.milestone_label" :placeholder="t('projects.csatMilestonePh')" />
                                <button type="submit" class="ppms-btn-primary">{{ t('projects.csatSubmit') }}</button>
                            </form>
                            <p v-if="csatMsg" class="ppms-muted">{{ csatMsg }}</p>
                        </section>

                        <section class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.ganttTitle') }}</h2>
                            <div class="ppms-gantt-filters ppms-task-form">
                                <select v-model="ganttFilters.assignee_id">
                                    <option value="">— {{ t('projects.ganttAssignee') }} —</option>
                                    <option v-for="o in assigneeOptions" :key="o.id" :value="String(o.id)">{{ o.name }}</option>
                                </select>
                                <input v-model="ganttFilters.status" type="text" :placeholder="t('projects.ganttStatusPh')" />
                                <label class="ppms-inline-check">
                                    <input v-model="ganttFilters.root_only" type="checkbox" />
                                    {{ t('projects.ganttRootOnly') }}
                                </label>
                                <button type="button" class="ppms-btn-ghost" @click="loadGantt">
                                    {{ t('projects.ganttRefresh') }}
                                </button>
                            </div>
                            <p v-if="gantt.window?.start" class="ppms-muted ppms-mt-sm">
                                {{
                                    t('projects.ganttWindow', {
                                        start: gantt.window.start,
                                        end: gantt.window.end,
                                        days: gantt.window.days,
                                    })
                                }}
                            </p>
                            <p v-if="!gantt.bars?.length" class="ppms-muted ppms-mt-sm">{{ t('projects.ganttEmpty') }}</p>
                            <div class="ppms-gantt">
                                <div v-for="b in gantt.bars" :key="b.task_id" class="ppms-gantt-row">
                                    <span class="ppms-gantt-name">{{ b.name }} · {{ ganttTaskStatusLabel(b.status) }}</span>
                                    <div class="ppms-gantt-bar-wrap">
                                        <div
                                            class="ppms-gantt-bar"
                                            :style="{
                                                marginLeft: `${b.layout.left_pct}%`,
                                                width: `${b.layout.width_pct}%`,
                                            }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.tasksTitle') }}</h2>
                            <form ref="taskFormAnchorRef" class="ppms-stack" @submit.prevent="addTask">
                                <div class="ppms-task-form">
                                    <input v-model="taskForm.name" :placeholder="t('projects.taskNamePh')" required />
                                    <input
                                        v-model.number="taskForm.parent_id"
                                        type="number"
                                        :placeholder="t('projects.taskParentPh')"
                                    />
                                    <input v-model.number="taskForm.estimate_hours" type="number" min="0" step="0.5" required />
                                    <input v-model.number="taskForm.complexity" type="number" min="1" max="5" required />
                                    <input v-model.number="taskForm.impact" type="number" min="1" max="5" required />
                                    <input v-model="taskForm.due_date" type="date" />
                                    <input
                                        v-model.number="taskForm.assignee_id"
                                        type="number"
                                        :placeholder="t('projects.taskAssigneePh')"
                                    />
                                </div>
                                <button type="submit" class="ppms-btn-primary">{{ t('projects.taskAdd') }}</button>
                            </form>
                            <p v-if="taskErr" class="ppms-error">{{ taskErr }}</p>

                            <div class="ppms-bulk ppms-mt">
                                <span>{{ t('projects.bulkLabel') }}</span>
                                <input
                                    v-model.number="bulk.assignee_id"
                                    type="number"
                                    :placeholder="t('projects.taskAssigneePh')"
                                />
                                <select v-model="bulk.status">
                                    <option value="">{{ t('projects.bulkStatusPh') }}</option>
                                    <option value="todo">{{ t('projects.taskStatus.todo') }}</option>
                                    <option value="in_progress">{{ t('projects.taskStatus.in_progress') }}</option>
                                    <option value="done">{{ t('projects.taskStatus.done') }}</option>
                                    <option value="blocked">{{ t('projects.taskStatus.blocked') }}</option>
                                </select>
                                <button type="button" class="ppms-btn-primary" :disabled="!selectedIds.length" @click="runBulk">
                                    {{ t('projects.bulkApply') }}
                                </button>
                            </div>

                            <div class="ppms-table-scroll ppms-mt">
                                <table class="ppms-table">
                                    <thead>
                                        <tr>
                                            <th class="ppms-th-check"></th>
                                            <th>{{ t('projects.thTask') }}</th>
                                            <th>{{ t('projects.thPred') }}</th>
                                            <th>{{ t('projects.thWeight') }}</th>
                                            <th>{{ t('projects.thEstAct') }}</th>
                                            <th>{{ t('projects.thStatus') }}</th>
                                            <th>{{ t('projects.thAssignee') }}</th>
                                            <th>{{ t('projects.thActions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="task in project.tasks" :key="task.id">
                                            <td><input v-model="selectedIds" type="checkbox" :value="task.id" /></td>
                                            <td>
                                                <button type="button" class="ppms-linklike" @click="toggleFocus(task)">
                                                    {{ task.name }}
                                                </button>
                                            </td>
                                            <td class="ppms-muted">
                                                <span v-if="task.predecessors?.length">{{
                                                    task.predecessors.map((p) => p.id).join(',')
                                                }}</span>
                                                <span v-else>—</span>
                                            </td>
                                            <td>{{ task.weight }}</td>
                                            <td>{{ task.estimate_hours }} / {{ task.actual_hours }}</td>
                                            <td>
                                                <select :value="task.status" @change="updateStatus(task, $event.target.value)">
                                                    <option value="todo">{{ t('projects.taskStatus.todo') }}</option>
                                                    <option value="in_progress">{{ t('projects.taskStatus.in_progress') }}</option>
                                                    <option value="done">{{ t('projects.taskStatus.done') }}</option>
                                                    <option value="blocked">{{ t('projects.taskStatus.blocked') }}</option>
                                                </select>
                                            </td>
                                            <td>{{ task.assignee?.name || '—' }}</td>
                                            <td>
                                                <button
                                                    type="button"
                                                    class="ppms-btn-ghost ppms-btn-sm"
                                                    @click="removeTask(task)"
                                                >
                                                    {{ t('common.delete') }}
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section v-if="focusTask" class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.taskDetailTitle', { id: focusTask.id }) }}</h2>
                            <div class="ppms-split">
                                <div>
                                    <h3>{{ t('projects.depTitle') }}</h3>
                                    <form class="ppms-task-form" @submit.prevent="addDep">
                                        <input
                                            v-model.number="depPredId"
                                            type="number"
                                            :placeholder="t('projects.depPredPh')"
                                            required
                                        />
                                        <button type="submit" class="ppms-btn-primary">{{ t('common.add') }}</button>
                                    </form>
                                    <p v-if="depErr" class="ppms-error">{{ depErr }}</p>
                                </div>
                                <div>
                                    <h3>{{ t('projects.attachTitle') }}</h3>
                                    <input type="file" @change="onFile" />
                                    <ul class="ppms-filelist">
                                        <li v-for="a in attachments" :key="a.id">
                                            <button type="button" class="ppms-linklike" @click="downloadFile(a)">
                                                {{ a.original_name }}
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Supplies -->
                    <div v-show="activeTab === 'supplies'" class="ppms-pd-tab-panel">
                        <section class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.pdTabSupplies') }}</h2>
                            <p class="ppms-muted ppms-mt-sm">{{ t('projects.pdPlaceholderSoon') }}</p>
                        </section>
                    </div>

                    <!-- Reports -->
                    <div v-show="activeTab === 'reports'" class="ppms-pd-tab-panel">
                        <section class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.pdReportsTitle') }}</h2>
                            <ul class="ppms-pd-report-actions ppms-mt-sm">
                                <li v-if="canPdf">
                                    <button type="button" class="ppms-btn-primary" @click="dlWeeklyPdf">
                                        {{ t('projects.pdDlWeeklyPdf') }}
                                    </button>
                                </li>
                                <li v-if="canCsv">
                                    <button type="button" class="ppms-btn-primary" @click="dlProjectsCsv">
                                        {{ t('projects.pdDlProjectsCsv') }}
                                    </button>
                                </li>
                                <li>
                                    <router-link to="/reports" class="ppms-btn-ghost">{{ t('projects.pdOpenReportsPage') }}</router-link>
                                </li>
                            </ul>
                        </section>
                    </div>

                    <!-- Attachments -->
                    <div v-show="activeTab === 'attachments'" class="ppms-pd-tab-panel">
                        <section class="ppms-card ppms-pd-section">
                            <h2>{{ t('projects.pdTabAttachments') }}</h2>
                            <p v-if="!projectAttachments.length" class="ppms-muted ppms-mt-sm">{{ t('projects.ganttEmpty') }}</p>
                            <div v-else class="ppms-table-scroll ppms-mt-sm">
                                <table class="ppms-table">
                                    <thead>
                                        <tr>
                                            <th>{{ t('projects.pdAttachColFile') }}</th>
                                            <th>{{ t('projects.pdAttachColTask') }}</th>
                                            <th>{{ t('projects.pdAttachColUploaded') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="a in projectAttachments" :key="'pa-' + a.id">
                                            <td>
                                                <button type="button" class="ppms-linklike" @click="downloadFile(a)">
                                                    {{ a.original_name }}
                                                </button>
                                                <div class="ppms-muted ppms-pd-attach-sub">{{ formatBytes(a.size_bytes) }}</div>
                                            </td>
                                            <td>{{ a.task?.name || '—' }}</td>
                                            <td>
                                                {{ a.uploader?.name || '—' }}
                                                <div class="ppms-muted ppms-pd-attach-sub">{{ String(a.created_at).slice(0, 10) }}</div>
                                            </td>
                                            <td>
                                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="openTaskForAttachment(a)">
                                                    {{ t('projects.pdGoTask') }}
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            <div v-if="showMetaEdit" class="ppms-modal-backdrop" @click.self="showMetaEdit = false">
                <div class="ppms-modal">
                    <h2>{{ t('projects.editStakeholders') }}</h2>
                    <form class="ppms-stack" @submit.prevent="saveMeta">
                        <label class="ppms-field">
                            <span>{{ t('projects.fieldCustomerName') }}</span>
                            <input v-model="metaForm.customer_name" />
                        </label>
                        <label class="ppms-field">
                            <span>{{ t('projects.fieldCustomerEmail') }}</span>
                            <input v-model="metaForm.customer_email" type="email" />
                        </label>
                        <label class="ppms-field">
                            <span>{{ t('projects.fieldLabelsHint') }}</span>
                            <input v-model="metaForm.labels_text" type="text" autocomplete="off" />
                        </label>
                        <label class="ppms-field">
                            <span>{{ t('projects.colStart') }}</span>
                            <input v-model="metaForm.start_date" type="date" />
                        </label>
                        <label class="ppms-field">
                            <span>{{ t('projects.colActualStart') }}</span>
                            <input v-model="metaForm.actual_start_date" type="date" />
                        </label>
                        <div>
                            <span class="ppms-field" style="display: block; margin-bottom: 0.5rem">{{
                                t('projects.suppliersTitle')
                            }}</span>
                            <div
                                v-for="(s, i) in metaForm.suppliers"
                                :key="i"
                                class="ppms-supplier-edit-row ppms-mt-sm"
                            >
                                <label class="ppms-field">
                                    <span>{{ t('projects.fieldSupplierName') }}</span>
                                    <input v-model="s.name" />
                                </label>
                                <label class="ppms-field">
                                    <span>{{ t('projects.fieldSupplierContact') }}</span>
                                    <input v-model="s.contact" />
                                </label>
                                <button
                                    v-if="metaForm.suppliers.length > 1"
                                    type="button"
                                    class="ppms-btn-ghost ppms-btn-sm ppms-supplier-edit-remove"
                                    @click.prevent="removeMetaSupplier(i)"
                                >
                                    {{ t('projects.removeSupplier') }}
                                </button>
                            </div>
                            <button type="button" class="ppms-btn-ghost ppms-mt-sm" @click="addMetaSupplier">
                                {{ t('projects.addSupplier') }}
                            </button>
                        </div>
                        <div class="ppms-mt">
                            <span class="ppms-muted" style="font-size: 0.85rem">{{ t('projects.timelineTitle') }}</span>
                            <div class="ppms-stack ppms-mt-sm">
                                <label v-for="ph in TIMELINE_PHASES" :key="ph" class="ppms-field">
                                    <span>{{ t(`projects.phase.${ph}`) }}</span>
                                    <input v-model="metaForm.timelineDates[ph]" type="date" />
                                </label>
                            </div>
                        </div>
                        <p v-if="metaErr" class="ppms-error">{{ metaErr }}</p>
                        <div class="ppms-modal-actions">
                            <button type="button" class="ppms-btn-ghost" @click="showMetaEdit = false">
                                {{ t('common.cancel') }}
                            </button>
                            <button type="submit" class="ppms-btn-primary">{{ t('common.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess, ppmsToastWarning } from '@/ppmsUi';

const { t } = useI18n();

const TIMELINE_PHASES = ['planning', 'development', 'uat', 'done', 'maintenance'];

const props = defineProps({
    id: { type: [String, Number], required: true },
});

const route = useRoute();
const router = useRouter();
const project = ref(null);
const loading = ref(true);
const taskErr = ref('');
const selectedIds = ref([]);
const focusTask = ref(null);
const attachments = ref([]);
const depPredId = ref(null);
const depErr = ref('');
const csat = reactive({ rating: 5, milestone_label: '' });
const csatMsg = ref('');
const gantt = ref({ window: {}, bars: [] });
const ganttFilters = reactive({
    assignee_id: '',
    status: '',
    root_only: false,
});

const activeTab = ref('info');
const projectAttachments = ref([]);
const projectDocuments = ref([]);
const docAddMode = ref(null);
const projectDocFileRef = ref(null);
const docForm = reactive({
    name: '',
    url: '',
    parent_id: '',
});
const me = ref(null);
const taskFormAnchorRef = ref(null);

function buildDocumentTree(flat) {
    const list = flat || [];
    const byParent = new Map();
    const rootKey = '__root__';
    for (const d of list) {
        const pk = d.parent_id == null ? rootKey : d.parent_id;
        if (!byParent.has(pk)) {
            byParent.set(pk, []);
        }
        byParent.get(pk).push(d);
    }
    for (const arr of byParent.values()) {
        arr.sort((a, b) => (a.sort_order - b.sort_order) || (a.id - b.id));
    }
    function walk(pk) {
        return (byParent.get(pk) || []).map((d) => ({
            ...d,
            children: walk(d.id),
        }));
    }
    return walk(rootKey);
}

function flattenDocRows(nodes, depth = 0) {
    const out = [];
    for (const n of nodes) {
        out.push({ doc: n, depth });
        if (n.children?.length) {
            out.push(...flattenDocRows(n.children, depth + 1));
        }
    }
    return out;
}

function collectFolderOptions(nodes, depth = 0) {
    const out = [];
    for (const n of nodes || []) {
        if (n.doc_type === 'folder') {
            out.push({ id: n.id, label: `${'\u2014 '.repeat(depth)}${n.name}` });
            out.push(...collectFolderOptions(n.children || [], depth + 1));
        }
    }
    return out;
}

function projectDocTypeLabel(docType) {
    const key = `projects.pdDocsType_${docType}`;
    const x = t(key);

    return x === key ? docType : x;
}

const taskForm = reactive({
    name: '',
    parent_id: null,
    estimate_hours: 8,
    complexity: 3,
    impact: 3,
    due_date: '',
    assignee_id: null,
});

const bulk = reactive({
    assignee_id: null,
    status: '',
});

const showMetaEdit = ref(false);
const metaErr = ref('');
const metaForm = reactive({
    customer_name: '',
    customer_email: '',
    labels_text: '',
    start_date: '',
    actual_start_date: '',
    suppliers: [{ name: '', contact: '' }],
    timelineDates: {
        planning: '',
        development: '',
        uat: '',
        done: '',
        maintenance: '',
    },
});

const detailTabs = computed(() => [
    { id: 'info', label: t('projects.pdTabInfo') },
    { id: 'tasks', label: t('projects.pdTabTasks'), badge: project.value?.tasks?.length },
    { id: 'supplies', label: t('projects.pdTabSupplies') },
    { id: 'reports', label: t('projects.pdTabReports') },
    {
        id: 'attachments',
        label: t('projects.pdTabAttachments'),
        badge: projectAttachments.value.length || undefined,
    },
]);

const canPdf = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));
const canCsv = computed(() => ['admin', 'pm', 'hr'].includes(me.value?.role));

const canManageDocuments = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));

const docAddNeedsUrl = computed(() => ['google_doc', 'google_sheet', 'link'].includes(docAddMode.value));

const documentTree = computed(() => buildDocumentTree(projectDocuments.value));

const documentRowsFlat = computed(() => flattenDocRows(documentTree.value));

const folderSelectOptions = computed(() => collectFolderOptions(documentTree.value));

const taskStats = computed(() => {
    const tasks = project.value?.tasks || [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    let overdue = 0;
    let inProgress = 0;
    let done = 0;
    let pending = 0;
    for (const row of tasks) {
        if (row.status === 'done') {
            done++;
        } else if (row.status === 'in_progress') {
            inProgress++;
        } else {
            pending++;
        }
        if (row.status !== 'done' && row.due_date) {
            const d = new Date(row.due_date);
            d.setHours(0, 0, 0, 0);
            if (d < today) {
                overdue++;
            }
        }
    }
    return {
        total: tasks.length,
        overdue,
        inProgress,
        done,
        pending,
    };
});

const participants = computed(() => {
    const p = project.value;
    if (!p) {
        return [];
    }
    const out = [];
    const seen = new Set();
    if (p.owner?.id) {
        seen.add(p.owner.id);
        out.push({ id: p.owner.id, name: p.owner.name });
    }
    for (const row of p.tasks || []) {
        if (row.assignee?.id && !seen.has(row.assignee.id)) {
            seen.add(row.assignee.id);
            out.push({ id: row.assignee.id, name: row.assignee.name });
        }
    }
    return out;
});

function parseYmd(s) {
    if (!s) {
        return null;
    }
    const d = new Date(s);
    return Number.isNaN(d.getTime()) ? null : d;
}

const plannedSpanDays = computed(() => {
    const p = project.value;
    if (!p) {
        return null;
    }
    const a = parseYmd(p.start_date);
    const b = parseYmd(p.deadline);
    if (!a || !b) {
        return null;
    }
    const ms = b.getTime() - a.getTime();
    return Math.max(0, Math.round(ms / 86400000));
});

const workloadRows = computed(() => {
    const tasks = project.value?.tasks || [];
    const m = new Map();
    const keyFor = (assigneeId) => (assigneeId != null ? String(assigneeId) : '__none');
    for (const row of tasks) {
        const k = keyFor(row.assignee_id, row.assignee?.name);
        if (!m.has(k)) {
            m.set(k, {
                key: k,
                name: row.assignee?.name || t('projects.pdUnassigned'),
                total: 0,
                done: 0,
                inProgress: 0,
                other: 0,
                est: 0,
                act: 0,
            });
        }
        const agg = m.get(k);
        agg.total++;
        agg.est += Number(row.estimate_hours) || 0;
        agg.act += Number(row.actual_hours) || 0;
        if (row.status === 'done') {
            agg.done++;
        } else if (row.status === 'in_progress') {
            agg.inProgress++;
        } else {
            agg.other++;
        }
    }
    return [...m.values()].sort((a, b) => b.total - a.total);
});

function workloadTitle(row) {
    return `${row.done} / ${row.inProgress} / ${row.other}`;
}

const recentProjectAttachments = computed(() => projectAttachments.value.slice(0, 5));

const timelineDisplay = computed(() => {
    const p = project.value;
    if (!p) {
        return [];
    }
    const byPhase = {};
    for (const r of p.process_timeline || []) {
        if (r.phase) {
            byPhase[r.phase] = r.completed_at || null;
        }
    }
    return TIMELINE_PHASES.map((phase) => ({
        phase,
        completed_at: byPhase[phase] || null,
        isCurrent: p.phase === phase,
    }));
});

function openMetaEdit() {
    const p = project.value;
    if (!p) {
        return;
    }
    metaErr.value = '';
    metaForm.customer_name = p.customer_name || '';
    metaForm.customer_email = p.customer_email || '';
    metaForm.labels_text = Array.isArray(p.labels) && p.labels.length ? p.labels.join(', ') : '';
    metaForm.start_date = p.start_date ? String(p.start_date).slice(0, 10) : '';
    metaForm.actual_start_date = p.actual_start_date ? String(p.actual_start_date).slice(0, 10) : '';
    const sup = p.suppliers || [];
    metaForm.suppliers = sup.length
        ? sup.map((s) => ({ name: s.name || '', contact: s.contact || '' }))
        : [{ name: '', contact: '' }];
    const byPhase = {};
    for (const row of p.process_timeline || []) {
        if (row.phase) {
            byPhase[row.phase] = row.completed_at ? String(row.completed_at).slice(0, 10) : '';
        }
    }
    for (const ph of TIMELINE_PHASES) {
        metaForm.timelineDates[ph] = byPhase[ph] || '';
    }
    showMetaEdit.value = true;
}

function addMetaSupplier() {
    metaForm.suppliers.push({ name: '', contact: '' });
}

function removeMetaSupplier(i) {
    if (metaForm.suppliers.length > 1) {
        metaForm.suppliers.splice(i, 1);
    }
}

function parseCommaLabelTokens(s) {
    return String(s || '')
        .split(/[,;]/)
        .map((x) => x.trim())
        .filter(Boolean);
}

function projectLabelList(p) {
    return Array.isArray(p?.labels) ? p.labels.filter(Boolean) : [];
}

async function saveMeta() {
    if (!(await ppmsConfirm(t('projects.confirmSaveMeta')))) {
        return;
    }
    metaErr.value = '';
    const suppliers = metaForm.suppliers
        .filter((s) => s.name.trim())
        .map((s) => {
            const row = { name: s.name.trim() };
            if (s.contact?.trim()) {
                row.contact = s.contact.trim();
            }

            return row;
        });
    const process_timeline = TIMELINE_PHASES.filter((ph) => metaForm.timelineDates[ph]).map((phase) => ({
        phase,
        completed_at: metaForm.timelineDates[phase],
    }));
    try {
        await axios.patch(`/api/projects/${props.id}`, {
            customer_name: metaForm.customer_name.trim() || null,
            customer_email: metaForm.customer_email.trim() || null,
            labels: parseCommaLabelTokens(metaForm.labels_text),
            start_date: metaForm.start_date || null,
            actual_start_date: metaForm.actual_start_date || null,
            suppliers: suppliers.length ? suppliers : null,
            process_timeline: process_timeline.length ? process_timeline : null,
        });
        showMetaEdit.value = false;
        ppmsToastSuccess(t('projects.saveMetaOk'));
        await load();
    } catch (e) {
        metaErr.value = formatApiUserMessage(e, t('projects.saveMetaErr'));
    }
}

function ganttTaskStatusLabel(status) {
    const key = `projects.taskStatus.${status}`;
    const translated = t(key);

    return translated === key ? status : translated;
}

const assigneeOptions = computed(() => {
    const m = new Map();
    for (const row of project.value?.tasks || []) {
        if (row.assignee_id && row.assignee?.name) {
            m.set(row.assignee_id, row.assignee.name);
        }
    }
    return [...m.entries()].map(([id, name]) => ({ id, name }));
});

async function loadGantt() {
    if (!props.id) {
        return;
    }
    const p = new URLSearchParams();
    if (ganttFilters.assignee_id) {
        p.set('assignee_id', ganttFilters.assignee_id);
    }
    if (ganttFilters.status.trim()) {
        p.set('status', ganttFilters.status.trim());
    }
    if (ganttFilters.root_only) {
        p.set('root_only', '1');
    }
    const q = p.toString();
    const { data } = await axios.get(`/api/projects/${props.id}/gantt${q ? `?${q}` : ''}`);
    gantt.value = data;
}

async function refreshProjectAttachments() {
    const { data } = await axios.get(`/api/projects/${props.id}/attachments`);
    projectAttachments.value = data;
}

async function refreshDocuments() {
    const { data } = await axios.get(`/api/projects/${props.id}/documents`);
    projectDocuments.value = data;
}

function openDocAdd(kind) {
    docAddMode.value = kind;
    docForm.name = '';
    docForm.url = '';
}

function cancelDocAdd() {
    docAddMode.value = null;
}

async function submitDocForm() {
    if (!docAddMode.value) {
        return;
    }
    const name = docForm.name.trim();
    if (!name) {
        return;
    }
    const url = docForm.url.trim();
    if (docAddNeedsUrl.value && !url) {
        return;
    }
    const body = {
        doc_type: docAddMode.value,
        name,
        parent_id: docForm.parent_id ? Number(docForm.parent_id) : null,
    };
    if (url) {
        body.url = url;
    }
    try {
        await axios.post(`/api/projects/${props.id}/documents`, body);
        ppmsToastSuccess(t('projects.pdDocsCreated'));
        cancelDocAdd();
        await refreshDocuments();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdDocsErr')));
    }
}

function triggerProjectDocFile() {
    projectDocFileRef.value?.click();
}

async function onProjectDocUpload(ev) {
    const f = ev.target.files?.[0];
    if (!f) {
        return;
    }
    if (!(await ppmsConfirm(t('projects.pdDocsUploadConfirm')))) {
        ev.target.value = '';

        return;
    }
    const fd = new FormData();
    fd.append('file', f);
    if (docForm.parent_id) {
        fd.append('parent_id', String(docForm.parent_id));
    }
    try {
        await axios.post(`/api/projects/${props.id}/documents/upload`, fd);
        ppmsToastSuccess(t('projects.pdDocsUploadOk'));
        await refreshDocuments();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdDocsErr')));
    }
    ev.target.value = '';
}

function openProjectDocument(drow) {
    if (drow.doc_type === 'upload') {
        projectDocDownload(drow);

        return;
    }
    if (drow.url) {
        window.open(drow.url, '_blank', 'noopener,noreferrer');
    }
}

async function projectDocDownload(drow) {
    const res = await axios.get(`/api/project-documents/${drow.id}/download`, { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const el = document.createElement('a');
    el.href = url;
    el.download = drow.original_name || drow.name;
    el.click();
    URL.revokeObjectURL(url);
}

async function deleteProjectDocument(drow) {
    if (!(await ppmsConfirm(t('projects.pdDocsConfirmDelete'), { destructive: true }))) {
        return;
    }
    try {
        await axios.delete(`/api/project-documents/${drow.id}`);
        ppmsToastSuccess(t('projects.pdDocsDeleted'));
        await refreshDocuments();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.pdDocsErr')));
    }
}

async function load() {
    loading.value = true;
    try {
        const [{ data }, attRes, docsRes] = await Promise.all([
            axios.get(`/api/projects/${props.id}`),
            axios.get(`/api/projects/${props.id}/attachments`),
            axios.get(`/api/projects/${props.id}/documents`),
        ]);
        project.value = data;
        projectAttachments.value = attRes.data;
        projectDocuments.value = docsRes.data;
        await loadGantt();
    } finally {
        loading.value = false;
    }
}

let ganttDebounce = null;
watch(
    ganttFilters,
    () => {
        if (!project.value) {
            return;
        }
        clearTimeout(ganttDebounce);
        ganttDebounce = setTimeout(() => loadGantt(), 350);
    },
    { deep: true },
);

watch(
    () => route.params.id,
    () => load(),
    { immediate: true },
);

watch(focusTask, async (row) => {
    if (!row) {
        attachments.value = [];

        return;
    }
    const { data } = await axios.get(`/api/tasks/${row.id}/attachments`);
    attachments.value = data;
});

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/user');
        me.value = data;
    } catch {
        me.value = null;
    }
});

function initials(name) {
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

function formatBytes(n) {
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

function fileExt(filename) {
    const m = String(filename || '').match(/\.([a-z0-9]+)$/i);
    return m ? m[1].slice(0, 4).toLowerCase() : '';
}

function openTaskForAttachment(a) {
    const taskId = a.task_id;
    const tk = project.value?.tasks?.find((x) => x.id === taskId);
    if (tk) {
        activeTab.value = 'tasks';
        focusTask.value = tk;
        return;
    }
    ppmsToastWarning(t('projects.taskUpdateErr'));
}

function goAddTask() {
    activeTab.value = 'tasks';
    requestAnimationFrame(() => {
        taskFormAnchorRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function toastJoin() {
    ppmsToastWarning(t('projects.pdToastJoin'));
}

function toastGroup() {
    ppmsToastWarning(t('projects.pdToastGroup'));
}

async function dlWeeklyPdf() {
    const res = await axios.get('/api/reports/weekly-status.pdf', { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const el = document.createElement('a');
    el.href = url;
    el.download = 'ppms-weekly.pdf';
    el.click();
    URL.revokeObjectURL(url);
}

async function dlProjectsCsv() {
    const res = await axios.get('/api/reports/export/projects.csv', { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const el = document.createElement('a');
    el.href = url;
    el.download = 'projects.csv';
    el.click();
    URL.revokeObjectURL(url);
}

function toggleFocus(task) {
    focusTask.value = focusTask.value?.id === task.id ? null : task;
}

async function addTask() {
    taskErr.value = '';
    if (!(await ppmsConfirm(t('projects.confirmTaskCreate')))) {
        return;
    }
    try {
        await axios.post(`/api/projects/${props.id}/tasks`, {
            name: taskForm.name,
            parent_id: taskForm.parent_id || null,
            estimate_hours: taskForm.estimate_hours,
            complexity: taskForm.complexity,
            impact: taskForm.impact,
            due_date: taskForm.due_date || null,
            assignee_id: taskForm.assignee_id || null,
        });
        taskForm.name = '';
        taskForm.parent_id = null;
        ppmsToastSuccess(t('projects.taskCreateOk'));
        await load();
    } catch (e) {
        taskErr.value = formatApiUserMessage(e, t('projects.taskCreateErr'));
    }
}

async function updateStatus(task, status) {
    const statusLabel = t(`projects.taskStatus.${status}`);
    if (!(await ppmsConfirm(t('projects.confirmStatusChange', { name: task.name, status: statusLabel })))) {
        return;
    }
    const body = { status };
    if (status === 'blocked') {
        body.blocked_reason = t('projects.blockedFromUi');
    }
    try {
        const res = await axios.put(`/api/tasks/${task.id}`, body);
        if (res.headers['x-ppms-warn-estimate']) {
            ppmsToastWarning(t('projects.warnEstimate'));
        }
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.taskUpdateErr')));
    }
    await load();
}

async function removeTask(task) {
    if (
        !(await ppmsConfirm(t('projects.confirmDeleteTask', { name: task.name }), {
            destructive: true,
            confirmLabel: t('common.delete'),
        }))
    ) {
        return;
    }
    await axios.delete(`/api/tasks/${task.id}`);
    ppmsToastSuccess(t('projects.deleteTaskOk'));
    await load();
}

async function runBulk() {
    if (!selectedIds.value.length) {
        return;
    }
    if (!(await ppmsConfirm(t('projects.confirmBulk', { n: selectedIds.value.length })))) {
        return;
    }
    const body = { task_ids: selectedIds.value };
    if (bulk.assignee_id !== null && bulk.assignee_id !== '') {
        body.assignee_id = bulk.assignee_id;
    }
    if (bulk.status) {
        body.status = bulk.status;
    }
    try {
        await axios.post('/api/tasks/bulk', body);
        selectedIds.value = [];
        ppmsToastSuccess(t('projects.bulkOk'));
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('projects.bulkErr')));
    }
}

async function addDep() {
    depErr.value = '';
    if (!focusTask.value) {
        return;
    }
    if (!(await ppmsConfirm(t('projects.depConfirm')))) {
        return;
    }
    try {
        await axios.post(`/api/tasks/${focusTask.value.id}/dependencies`, {
            predecessor_task_id: depPredId.value,
        });
        depPredId.value = null;
        ppmsToastSuccess(t('projects.depOk'));
        await load();
    } catch (e) {
        depErr.value = formatApiUserMessage(e, t('projects.depErr'));
    }
}

async function onFile(ev) {
    const f = ev.target.files?.[0];
    if (!f || !focusTask.value) {
        return;
    }
    if (!(await ppmsConfirm(t('projects.attachConfirm', { fileName: f.name })))) {
        ev.target.value = '';
        return;
    }
    const fd = new FormData();
    fd.append('file', f);
    await axios.post(`/api/tasks/${focusTask.value.id}/attachments`, fd);
    const { data } = await axios.get(`/api/tasks/${focusTask.value.id}/attachments`);
    attachments.value = data;
    await refreshProjectAttachments();
    ev.target.value = '';
    ppmsToastSuccess(t('projects.attachOk'));
}

async function submitCsat() {
    csatMsg.value = '';
    if (!(await ppmsConfirm(t('projects.confirmCsat')))) {
        return;
    }
    try {
        await axios.post(`/api/projects/${props.id}/csat`, {
            rating: csat.rating,
            milestone_label: csat.milestone_label || null,
        });
        csatMsg.value = t('projects.csatOkMsg');
        ppmsToastSuccess(t('projects.csatOkMsg'));
    } catch (e) {
        csatMsg.value = formatApiUserMessage(e, t('projects.csatErr'));
    }
}

async function downloadFile(a) {
    const res = await axios.get(`/api/attachments/${a.id}/download`, { responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const el = document.createElement('a');
    el.href = url;
    el.download = a.original_name;
    el.click();
    URL.revokeObjectURL(url);
}

async function duplicateProject() {
    if (!(await ppmsConfirm(t('projects.confirmDuplicate')))) {
        return;
    }
    const { data } = await axios.post(`/api/projects/${props.id}/duplicate`, { reset_dates: true });
    ppmsToastSuccess(t('projects.duplicateOk'));
    router.push({ name: 'project-detail', params: { id: String(data.id) } });
}
</script>
