<!-- eslint-disable vue/no-mutating-props -- parent reactive docForm -->
<template>
    <div class="ppms-pd-tab-panel">
        <div class="ppms-pd-stats">
            <div class="ppms-pd-stat ppms-pd-stat--total">
                <span class="ppms-pd-stat-ico ppms-pd-stat-ico--total" aria-hidden="true" />
                <span class="ppms-pd-stat-value">{{ taskStats.total }}</span>
                <span class="ppms-pd-stat-label">{{ t('projects.pdStatTotal') }}</span>
            </div>
            <div class="ppms-pd-stat ppms-pd-stat--overdue">
                <span class="ppms-pd-stat-ico ppms-pd-stat-ico--overdue" aria-hidden="true" />
                <span class="ppms-pd-stat-value">{{ taskStats.overdue }}</span>
                <span class="ppms-pd-stat-label">{{ t('projects.pdStatOverdue') }}</span>
            </div>
            <div class="ppms-pd-stat ppms-pd-stat--progress">
                <span class="ppms-pd-stat-ico ppms-pd-stat-ico--progress" aria-hidden="true" />
                <span class="ppms-pd-stat-value">{{ taskStats.inProgress }}</span>
                <span class="ppms-pd-stat-label">{{ t('projects.pdStatInProgress') }}</span>
            </div>
            <div class="ppms-pd-stat ppms-pd-stat--done">
                <span class="ppms-pd-stat-ico ppms-pd-stat-ico--done" aria-hidden="true" />
                <span class="ppms-pd-stat-value">{{ taskStats.done }}</span>
                <span class="ppms-pd-stat-label">{{ t('projects.pdStatDone') }}</span>
            </div>
            <div class="ppms-pd-stat ppms-pd-stat--pending">
                <span class="ppms-pd-stat-ico ppms-pd-stat-ico--pending" aria-hidden="true" />
                <span class="ppms-pd-stat-value">{{ taskStats.pending }}</span>
                <span class="ppms-pd-stat-label">{{ t('projects.pdStatPending') }}</span>
            </div>
        </div>

        <section class="ppms-card ppms-pd-section">
            <h2>{{ t('projects.pdGeneralTitle') }}</h2>
            <div class="ppms-pd-info-grid">
                <div class="ppms-pd-info-cell">
                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldInternalId') }}</span>
                    <span class="ppms-pd-info-value">#{{ project.id }}</span>
                </div>
                <div v-if="projectCodeDisplay" class="ppms-pd-info-cell">
                    <span class="ppms-pd-info-label">{{ t('projects.colCode') }}</span>
                    <span class="ppms-pd-info-value">{{ projectCodeDisplay }}</span>
                </div>
                <div class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                    <span class="ppms-pd-info-label">{{ t('projects.fieldName') }}</span>
                    <span class="ppms-pd-info-value">{{ project.name }}</span>
                </div>
                <div class="ppms-pd-info-cell">
                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldManager') }}</span>
                    <div class="ppms-pd-people">
                        <span v-if="project.owner" class="ppms-pd-avatar" :title="project.owner.name">{{
                            initials(project.owner.name)
                        }}</span>
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
                    <span class="ppms-pd-badge ppms-pd-badge--status">{{ t(`projects.status.${project.status}`) }}</span>
                </div>
                <div class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldExecutors') }}</span>
                    <ul v-if="executorSlots.length" class="ppms-pd-slot-list" role="list">
                        <li v-for="(slot, i) in executorSlots" :key="'exs-' + i" class="ppms-pd-slot-row" role="listitem">
                            <span class="ppms-pd-avatar" :title="allParticipantPrimaryLabel(slot)">{{ slot.initials }}</span>
                            <div class="ppms-pd-slot-body">
                                <div class="ppms-pd-slot-line1">
                                    <span class="ppms-pd-info-value">{{ allParticipantPrimaryLabel(slot) }}</span>
                                    <span v-if="allParticipantEmailLine(slot)" class="ppms-muted ppms-pd-slot-email">{{
                                        allParticipantEmailLine(slot)
                                    }}</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="ppms-muted ppms-mt-sm">—</p>
                </div>
                <div class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldFollowers') }}</span>
                    <ul v-if="followerSlots.length" class="ppms-pd-slot-list" role="list">
                        <li v-for="(slot, i) in followerSlots" :key="'fos-' + i" class="ppms-pd-slot-row" role="listitem">
                            <span class="ppms-pd-avatar" :title="allParticipantPrimaryLabel(slot)">{{ slot.initials }}</span>
                            <div class="ppms-pd-slot-body">
                                <div class="ppms-pd-slot-line1">
                                    <span class="ppms-pd-info-value">{{ allParticipantPrimaryLabel(slot) }}</span>
                                    <span v-if="allParticipantEmailLine(slot)" class="ppms-muted ppms-pd-slot-email">{{
                                        allParticipantEmailLine(slot)
                                    }}</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="ppms-muted ppms-mt-sm">—</p>
                </div>
                <div class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldStakeholderEmails') }}</span>
                    <ul v-if="stakeholderSlots.length" class="ppms-pd-slot-list" role="list">
                        <li v-for="(slot, i) in stakeholderSlots" :key="'st-' + i" class="ppms-pd-slot-row" role="listitem">
                            <span class="ppms-pd-avatar" :title="allParticipantPrimaryLabel(slot)">{{ slot.initials }}</span>
                            <div class="ppms-pd-slot-body">
                                <div class="ppms-pd-slot-line1">
                                    <span class="ppms-pd-info-value">{{ allParticipantPrimaryLabel(slot) }}</span>
                                    <span v-if="allParticipantEmailLine(slot)" class="ppms-muted ppms-pd-slot-email">{{
                                        allParticipantEmailLine(slot)
                                    }}</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="ppms-muted ppms-mt-sm">—</p>
                </div>
                <div class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldTaskAssignees') }}</span>
                    <div class="ppms-pd-people ppms-pd-people--wrap">
                        <span
                            v-for="u in taskAssignees"
                            :key="'as-' + u.id"
                            class="ppms-pd-avatar"
                            :title="u.name"
                            >{{ initials(u.name) }}</span
                        >
                        <span v-if="!taskAssignees.length" class="ppms-muted">—</span>
                    </div>
                </div>
                <div v-if="estimatedValueDisplay" class="ppms-pd-info-cell">
                    <span class="ppms-pd-info-label">{{ t('projects.createFieldEstimatedValue') }}</span>
                    <span class="ppms-pd-info-value">{{ estimatedValueDisplay }}</span>
                </div>
                <div v-if="labelChips.length" class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldLabels') }}</span>
                    <div class="ppms-pd-label-chips">
                        <span v-for="(lb, i) in labelChips" :key="'lb-' + i" class="ppms-pd-label-chip">{{ lb }}</span>
                    </div>
                </div>
                <div v-if="permissionPresetDisplay" class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                    <span class="ppms-pd-info-label">{{ t('projects.pdFieldPermission') }}</span>
                    <span class="ppms-pd-info-value">{{ permissionPresetDisplay }}</span>
                </div>
                <div class="ppms-pd-info-cell ppms-pd-info-cell--wide">
                    <span class="ppms-pd-info-label">{{ t('projects.progressLabel') }}</span>
                    <div class="ppms-pd-progress-meta">
                        <span class="ppms-pd-info-value ppms-pd-progress-pct">{{ Number(project.progress).toFixed(1) }}%</span>
                        <span class="ppms-muted ppms-pd-progress-calc">{{ progressCalcDisplay }}</span>
                    </div>
                    <div class="ppms-pd-progress-row ppms-pd-progress-row--block">
                        <div class="ppms-pd-progress-track">
                            <div
                                class="ppms-pd-progress-fill"
                                :style="{ width: `${Math.min(100, Number(project.progress) || 0)}%` }"
                            />
                        </div>
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
                        <span v-if="step.isCurrent" class="ppms-process-badge">{{ t('projects.timelineCurrent') }}</span>
                        <time v-if="step.completed_at" :datetime="step.completed_at">{{ step.completed_at }}</time>
                        <span v-else class="ppms-muted">{{ t('projects.timelinePlanned') }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="ppms-card ppms-pd-section">
            <div class="ppms-row ppms-row--spread">
                <h2>{{ t('projects.stakeholdersTitle') }}</h2>
                <button type="button" class="ppms-btn-ghost" @click="$emit('edit-stakeholders')">
                    {{ t('projects.editStakeholders') }}
                </button>
            </div>
            <div class="ppms-split ppms-mt">
                <div>
                    <h3 class="ppms-muted ppms-pd-mini-h">{{ t('projects.customer') }}</h3>
                    <p v-if="project.customer_name" style="margin: 0">{{ project.customer_name }}</p>
                    <p v-else class="ppms-muted" style="margin: 0">{{ t('projects.customerUnset') }}</p>
                    <p
                        v-if="project.customer_email"
                        class="ppms-muted ppms-mt-sm"
                        style="margin: 0; font-size: 0.9rem"
                    >
                        {{ t('projects.customerEmail') }}: {{ project.customer_email }}
                    </p>
                </div>
                <div>
                    <h3 class="ppms-muted ppms-pd-mini-h">{{ t('projects.suppliersTitle') }}</h3>
                    <ul v-if="(project.suppliers || []).length" class="ppms-supplier-list">
                        <li v-for="(s, i) in project.suppliers" :key="i">
                            <strong>{{ s.name }}</strong>
                            <template v-if="s.contact">
                                <span class="ppms-muted"> — {{ t('projects.supplierContactShort') }}: {{ s.contact }}</span>
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
            <h2>{{ t('projects.pdActivityTitle') }}</h2>
            <p v-if="!projectActivities.length" class="ppms-muted ppms-mt-sm">{{ t('projects.pdActivityEmpty') }}</p>
            <ul v-else class="ppms-pd-activity-list ppms-mt-sm" role="list">
                <li v-for="ev in projectActivities" :key="'act-' + ev.id" class="ppms-pd-activity-row">
                    <span class="ppms-pd-activity-time">{{ formatActivityTime(ev.created_at) }}</span>
                    <span class="ppms-pd-activity-user">{{ ev.user?.name || t('projects.pdActivitySystem') }}</span>
                    <span class="ppms-pd-activity-action">{{ formatActivityAction(t, ev) }}</span>
                </li>
            </ul>
        </section>

        <section class="ppms-card ppms-pd-section">
            <div class="ppms-row ppms-row--spread">
                <h2>{{ t('projects.pdMediaSummaryTitle') }}</h2>
                <button type="button" class="ppms-btn-ghost" @click="$emit('go-attachments')">
                    {{ t('projects.pdMediaOpenTab') }}
                </button>
            </div>
            <p class="ppms-muted ppms-mt-sm">{{ t('projects.pdMediaSummaryHint') }}</p>
            <ul class="ppms-pd-media-summary-stats ppms-mt-sm" role="list">
                <li v-if="mediaCounts.project > 0">{{ t('projects.pdMediaCountProject', { n: mediaCounts.project }) }}</li>
                <li v-if="mediaCounts.task > 0">{{ t('projects.pdMediaCountTask', { n: mediaCounts.task }) }}</li>
                <li v-if="!mediaCounts.project && !mediaCounts.task" class="ppms-muted">{{ t('projects.pdMediaEmpty') }}</li>
            </ul>
            <p class="ppms-muted ppms-mt-sm ppms-pd-media-recent-heading">{{ t('projects.pdRecentAttach') }}</p>
            <ul v-if="recentMediaItems.length" class="ppms-pd-attach-list ppms-pd-attach-list--sectioned ppms-mt-sm">
                <template v-for="(seg, segIdx) in recentMediaSegments" :key="seg.type === 'section' ? 'sec-' + seg.key + '-' + segIdx : 'row-' + seg.row.source + '-' + seg.row.source_id">
                    <li v-if="seg.type === 'section'" class="ppms-pd-media-section-head" role="presentation">
                        <span class="ppms-pd-media-section-ico" :data-section="seg.key" aria-hidden="true" />
                        <span class="ppms-pd-media-section-label">{{ t(mediaSectionTitleKey(seg.key)) }}</span>
                    </li>
                    <li
                        v-else
                        class="ppms-pd-attach-row"
                        :class="'ppms-pd-attach-row--' + seg.kind"
                    >
                        <template v-if="seg.row.source === 'project_document'">
                            <div v-if="seg.kind === 'folder'" class="ppms-pd-media-row-ico ppms-pd-media-row-ico--folder" aria-hidden="true">
                                <span class="ppms-pd-attach-folder-ico ppms-pd-attach-folder-ico--sm" />
                            </div>
                            <span
                                v-else
                                class="ppms-pd-file-ico"
                                :class="{ 'ppms-pd-file-ico--link': seg.row.doc_type === 'link' }"
                                :data-ext="projectDocFileExt(seg.row)"
                            />
                            <div class="ppms-pd-attach-meta">
                                <button
                                    v-if="seg.row.doc_type === 'link' && seg.row.url"
                                    type="button"
                                    class="ppms-linklike"
                                    @click="$emit('open-project-doc', seg.row)"
                                >
                                    {{ seg.row.name }}
                                </button>
                                <button
                                    v-else-if="seg.row.doc_type === 'upload'"
                                    type="button"
                                    class="ppms-linklike"
                                    @click="$emit('download-media-row', seg.row)"
                                >
                                    {{ seg.row.original_name || seg.row.name }}
                                </button>
                                <span v-else class="ppms-pd-info-value">{{ seg.row.name }}</span>
                                <div
                                    v-if="pathLabelTreeSegments(seg.row).length > 1"
                                    class="ppms-pd-media-tree-wrap"
                                >
                                    <div class="ppms-pd-media-tree-head">{{ t('projects.pdMediaPathTree') }}</div>
                                    <div
                                        class="ppms-pd-media-tree"
                                        role="tree"
                                        :aria-label="seg.row.path_label || undefined"
                                    >
                                        <template
                                            v-for="(name, ti) in pathLabelTreeSegments(seg.row)"
                                            :key="'pt-' + ti"
                                        >
                                            <div
                                                v-if="isTreeExpandableSegment(seg.row, ti)"
                                                class="ppms-pd-media-tree-path-block"
                                            >
                                                <div
                                                    class="ppms-pd-media-tree-row ppms-pd-media-tree-row--folder-hit"
                                                    role="treeitem"
                                                    :aria-level="ti + 1"
                                                    :style="{ paddingLeft: ti * 0.75 + 'rem' }"
                                                >
                                                    <button
                                                        type="button"
                                                        class="ppms-pd-media-tree-folder-hit"
                                                        :aria-expanded="isTreeFolderExpanded(folderIdAt(seg.row, ti))"
                                                        @click.stop="toggleTreeFolder(folderIdAt(seg.row, ti))"
                                                    >
                                                        <span class="ppms-pd-media-tree-chev" aria-hidden="true">{{
                                                            isTreeFolderExpanded(folderIdAt(seg.row, ti)) ? '▼' : '▶'
                                                        }}</span>
                                                        <span
                                                            v-if="ti > 0"
                                                            class="ppms-pd-media-tree-prefix"
                                                            aria-hidden="true"
                                                            >└─</span
                                                        >
                                                        <span
                                                            class="ppms-pd-attach-folder-ico ppms-pd-attach-folder-ico--sm"
                                                            aria-hidden="true"
                                                        />
                                                        <span class="ppms-pd-media-tree-folder-label">{{ name }}</span>
                                                    </button>
                                                </div>
                                                <ProjectMediaTreeChildren
                                                    v-if="
                                                        folderIdAt(seg.row, ti) != null &&
                                                        isTreeFolderExpanded(folderIdAt(seg.row, ti))
                                                    "
                                                    :folder-id="Number(folderIdAt(seg.row, ti))"
                                                    :depth="0"
                                                    @download-media-row="$emit('download-media-row', $event)"
                                                    @open-project-doc="$emit('open-project-doc', $event)"
                                                />
                                            </div>
                                            <div
                                                v-else-if="shouldShowTreeStaticRow(seg.row, ti)"
                                                class="ppms-pd-media-tree-row"
                                                role="treeitem"
                                                :aria-level="ti + 1"
                                                :style="{ paddingLeft: ti * 0.75 + 'rem' }"
                                            >
                                                <span
                                                    v-if="ti > 0"
                                                    class="ppms-pd-media-tree-prefix"
                                                    aria-hidden="true"
                                                    >└─</span
                                                >
                                                <span
                                                    class="ppms-pd-media-tree-name"
                                                    :class="{
                                                        'is-leaf': ti === pathLabelTreeSegments(seg.row).length - 1,
                                                    }"
                                                    >{{ name }}</span
                                                >
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <span class="ppms-muted ppms-pd-attach-sub ppms-pd-media-meta-tail">
                                    <span v-if="seg.row.doc_type" class="ppms-pd-media-kind">{{
                                        t(`projects.pdDocsType_${seg.row.doc_type}`)
                                    }}</span>
                                    <span v-if="seg.row.created_at"> · {{ String(seg.row.created_at).slice(0, 10) }}</span>
                                </span>
                            </div>
                            <div
                                v-if="(seg.row.doc_type === 'link' && seg.row.url) || seg.row.doc_type === 'folder'"
                                class="ppms-pd-attach-row-actions"
                            >
                                <button
                                    v-if="seg.row.doc_type === 'link' && seg.row.url"
                                    type="button"
                                    class="ppms-btn-ghost ppms-btn-sm"
                                    @click="$emit('open-project-doc', seg.row)"
                                >
                                    {{ t('projects.pdDocsOpen') }}
                                </button>
                                <button
                                    v-else-if="seg.row.doc_type === 'folder'"
                                    type="button"
                                    class="ppms-btn-ghost ppms-btn-sm"
                                    @click="$emit('go-attachments')"
                                >
                                    {{ t('projects.pdMediaOpenTab') }}
                                </button>
                            </div>
                        </template>
                        <template v-else>
                            <span
                                class="ppms-pd-file-ico ppms-pd-file-ico--task"
                                :data-ext="fileExt(seg.row.original_name || seg.row.name)"
                            />
                            <div class="ppms-pd-attach-meta">
                                <button type="button" class="ppms-linklike" @click="$emit('download-media-row', seg.row)">
                                    {{ seg.row.original_name || seg.row.name }}
                                </button>
                                <div v-if="seg.row.task?.name" class="ppms-pd-media-path-row ppms-pd-media-path-row--task">
                                    <span class="ppms-pd-media-path-label">{{ t('projects.pdMediaParentTask') }}</span>
                                    <span class="ppms-pd-media-path-value" :title="seg.row.task.name">{{ seg.row.task.name }}</span>
                                </div>
                                <span class="ppms-muted ppms-pd-attach-sub ppms-pd-media-meta-tail">
                                    {{ taskAttachSizeLine(seg.row) }}
                                </span>
                            </div>
                            <button
                                type="button"
                                class="ppms-btn-ghost ppms-btn-sm"
                                @click="$emit('open-task-from-attachment', taskAttachPayload(seg.row))"
                            >
                                {{ t('projects.pdGoTask') }}
                            </button>
                        </template>
                    </li>
                </template>
            </ul>
            <p v-else class="ppms-muted ppms-mt-sm">{{ t('projects.pdMediaNoRecentItems') }}</p>
        </section>
    </div>
</template>

<script setup>
import { computed, provide, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    fileExt,
    formatActivityAction,
    formatActivityTime,
    formatBytes,
    initials,
    pathLabelTreeSegments,
    workloadTitle,
} from '../../utils/projectDetailFormatters';
import { buildParticipantSlots, displayNameFromEmail } from '../../utils/projectParticipants';
import ProjectMediaTreeChildren from './ProjectMediaTreeChildren.vue';

const { t } = useI18n();

const props = defineProps({
    project: { type: Object, required: true },
    taskStats: { type: Object, required: true },
    plannedSpanDays: { type: Number, default: null },
    workloadRows: { type: Array, required: true },
    timelineDisplay: { type: Array, required: true },
    projectActivities: { type: Array, required: true },
    mediaCounts: { type: Object, required: true },
    projectMedia: { type: Array, default: () => [] },
    recentMediaItems: { type: Array, required: true },
});

/** Mở/đóng thư mục trong cây + danh sách con (inject vào ProjectMediaTreeChildren). */
const treeExpandedFolderIds = reactive({});
provide('treeExpandedFolderIds', treeExpandedFolderIds);
provide('projectMedia', computed(() => props.projectMedia));

const PERMISSION_PRESET_MAP = {
    org_default: 'permissionPresetOrg',
    members_only: 'permissionPresetMembers',
    owner_only: 'permissionPresetOwner',
};

/** API có thể trả mảng hoặc object dạng map; axios/JSON đôi khi tạo object thay vì Array. */
function toUserArray(raw) {
    if (raw == null) {
        return [];
    }
    if (Array.isArray(raw)) {
        return raw;
    }
    if (typeof raw === 'object') {
        return Object.values(raw);
    }
    return [];
}

const allParticipantSlots = computed(() => {
    const p = props.project;
    return buildParticipantSlots({
        ...p,
        executor_users: toUserArray(p.executor_users ?? p.executorUsers),
        follower_users: toUserArray(p.follower_users ?? p.followerUsers),
    });
});

const executorSlots = computed(() => allParticipantSlots.value.filter((s) => s.kind === 'executor'));
const followerSlots = computed(() => allParticipantSlots.value.filter((s) => s.kind === 'follower'));
const stakeholderSlots = computed(() => allParticipantSlots.value.filter((s) => s.kind === 'stakeholder'));

function allParticipantPrimaryLabel(slot) {
    if (slot.kind === 'stakeholder') {
        return displayNameFromEmail(slot.email);
    }
    return slot.user?.name || slot.user?.email || '—';
}

function allParticipantEmailLine(slot) {
    if (slot.kind === 'stakeholder') {
        return slot.email ? `(${slot.email})` : '';
    }
    const em = slot.user?.email;
    return em ? `(${em})` : '';
}

const taskAssignees = computed(() => {
    const tasks = props.project.tasks || [];
    const seen = new Set();
    const out = [];
    for (const row of tasks) {
        if (row.assignee?.id != null && !seen.has(row.assignee.id)) {
            seen.add(row.assignee.id);
            out.push({ id: row.assignee.id, name: row.assignee.name });
        }
    }
    return out;
});

const projectCodeDisplay = computed(() => {
    const c = props.project.code;
    if (c == null) {
        return '';
    }
    const s = String(c).trim();
    return s || '';
});

const estimatedValueDisplay = computed(() => {
    const v = props.project.estimated_value;
    if (v == null || v === '') {
        return '';
    }
    const n = Number(v);
    if (Number.isNaN(n)) {
        return String(v);
    }
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(n);
});

const labelChips = computed(() => {
    const lb = props.project.labels;
    if (!Array.isArray(lb) || !lb.length) {
        return [];
    }
    return lb.map((x) => String(x).trim()).filter(Boolean);
});

const permissionPresetDisplay = computed(() => {
    const k = props.project.permission_preset;
    if (!k) {
        return '';
    }
    const i18nKey = PERMISSION_PRESET_MAP[k];
    return i18nKey ? t(`projects.${i18nKey}`) : k;
});

const progressCalcDisplay = computed(() => {
    const k = props.project.progress_calc || 'weighted_tasks';
    const key = `projects.createProgressCalc_${k}`;
    const s = t(key);
    return s !== key ? s : k;
});

function hasPathIdsForTree(row) {
    const ids = row.path_ids;
    if (!Array.isArray(ids) || ids.length === 0) {
        return false;
    }
    const seg = pathLabelTreeSegments(row);
    return ids.length === seg.length;
}

function isTreeExpandableSegment(row, ti) {
    if (!hasPathIdsForTree(row)) {
        return false;
    }
    const seg = pathLabelTreeSegments(row);
    const last = seg.length - 1;
    if (ti < last) {
        return true;
    }
    if (ti === last && row.doc_type === 'folder') {
        return true;
    }
    return false;
}

function folderIdAt(row, ti) {
    const ids = row.path_ids;
    if (!Array.isArray(ids) || ids[ti] == null) {
        return null;
    }
    return Number(ids[ti]);
}

function toggleTreeFolder(id) {
    if (id == null || Number.isNaN(Number(id))) {
        return;
    }
    const k = Number(id);
    treeExpandedFolderIds[k] = !treeExpandedFolderIds[k];
}

function isTreeFolderExpanded(id) {
    if (id == null || Number.isNaN(Number(id))) {
        return false;
    }
    return !!treeExpandedFolderIds[Number(id)];
}

/** Bỏ dòng lá trùng tên tệp/liên kết (đã hiển thị ở tiêu đề hàng). */
function shouldShowTreeStaticRow(row, ti) {
    const seg = pathLabelTreeSegments(row);
    const last = seg.length - 1;
    if (ti === last && row.doc_type !== 'folder') {
        return false;
    }
    return true;
}

/** Nhóm hiển thị: thư mục | tệp/liên kết thư viện | tệp công việc (giữ thứ tự thời gian từ API). */
function mediaRowKind(row) {
    if (row.source === 'task_attachment') {
        return 'task';
    }
    if (row.source === 'project_document' && row.doc_type === 'folder') {
        return 'folder';
    }
    return 'library';
}

function mediaSectionTitleKey(k) {
    const map = {
        folder: 'projects.pdMediaSectionFolders',
        library: 'projects.pdMediaSectionLibrary',
        task: 'projects.pdMediaSectionTask',
    };
    return map[k] || 'projects.pdRecentAttach';
}

const recentMediaSegments = computed(() => {
    const items = props.recentMediaItems || [];
    const out = [];
    let prev = null;
    for (const row of items) {
        const kind = mediaRowKind(row);
        if (prev === null || kind !== prev) {
            out.push({ type: 'section', key: kind });
        }
        prev = kind;
        out.push({ type: 'row', row, kind });
    }
    return out;
});

function projectDocFileExt(row) {
    if (row.doc_type === 'folder') {
        return 'dir';
    }
    if (row.doc_type === 'link') {
        return 'url';
    }
    return fileExt(row.original_name || row.name);
}

/** Kích thước + ngày (tên công việc hiển thị ở dòng pdMediaParentTask). */
function taskAttachSizeLine(row) {
    const sz = row.size_bytes != null ? formatBytes(row.size_bytes) : '—';
    const d = row.created_at ? String(row.created_at).slice(0, 10) : '';
    const parts = [sz];
    if (d) {
        parts.push(d);
    }
    return parts.join(' · ');
}

function taskAttachPayload(row) {
    return {
        id: row.source_id,
        original_name: row.original_name || row.name,
        task_id: row.task_id,
        task: row.task,
    };
}

defineEmits([
    'edit-stakeholders',
    'go-attachments',
    'download-media-row',
    'open-project-doc',
    'open-task-from-attachment',
]);
</script>
