<!-- eslint-disable vue/no-mutating-props -- docForm.parent_id synced with current folder -->
<template>
    <div class="ppms-pd-tab-panel ppms-pd-media-tab">
        <section class="ppms-card ppms-pd-section ppms-pd-attach-library">
            <header class="ppms-pd-attach-page-head">
                <div class="ppms-pd-attach-page-head-text">
                    <h2 class="ppms-pd-attach-page-title">{{ t('projects.pdTabAttachments') }}</h2>
                    <p class="ppms-pd-attach-page-intro">{{ t('projects.pdMediaIntro') }}</p>
                </div>
                <div class="ppms-pd-attach-toolbar ppms-pd-media-toolbar">
                    <div class="ppms-pd-attach-toolbar-main">
                        <div class="ppms-pd-attach-field ppms-pd-attach-field--search">
                            <label class="ppms-pd-attach-field-label" for="ppms-pd-attach-q">{{
                                t('projects.pdMediaSearchLabel')
                            }}</label>
                            <input
                                id="ppms-pd-attach-q"
                                v-model="searchQ"
                                type="search"
                                class="ppms-pd-media-search-input"
                                :placeholder="t('projects.pdMediaSearchPlaceholder')"
                                autocomplete="off"
                            />
                        </div>
                    </div>
                    <p v-if="mediaCounts" class="ppms-pd-attach-counts">
                        {{ t('projects.pdMediaCountsHint', mediaCounts) }}
                    </p>
                </div>
            </header>

            <nav class="ppms-pd-attach-tabs" role="tablist" :aria-label="t('projects.pdAttachTabsAria')">
                <button
                    id="ppms-pd-attach-tab-project"
                    type="button"
                    role="tab"
                    class="ppms-pd-attach-tab"
                    :class="{ 'is-active': attachViewTab === 'project' }"
                    :aria-selected="attachViewTab === 'project'"
                    :tabindex="attachViewTab === 'project' ? 0 : -1"
                    @click="attachViewTab = 'project'"
                >
                    <span class="ppms-pd-attach-tab-label">{{ t('projects.pdAttachColProjectDocs') }}</span>
                    <span v-if="mediaCounts" class="ppms-pd-attach-tab-badge" aria-hidden="true">{{ mediaCounts.project }}</span>
                </button>
                <button
                    id="ppms-pd-attach-tab-task"
                    type="button"
                    role="tab"
                    class="ppms-pd-attach-tab"
                    :class="{ 'is-active': attachViewTab === 'task' }"
                    :aria-selected="attachViewTab === 'task'"
                    :tabindex="attachViewTab === 'task' ? 0 : -1"
                    @click="attachViewTab = 'task'"
                >
                    <span class="ppms-pd-attach-tab-label">{{ t('projects.pdAttachColTaskFiles') }}</span>
                    <span v-if="mediaCounts" class="ppms-pd-attach-tab-badge" aria-hidden="true">{{ mediaCounts.task }}</span>
                </button>
            </nav>

            <div v-if="filteredEmpty" class="ppms-pd-attach-empty-wrap">
                <div v-if="noResultsFromFilters" class="ppms-pd-attach-empty ppms-pd-attach-empty--filter">
                    <div class="ppms-pd-attach-empty-visual ppms-pd-attach-empty-visual--search" aria-hidden="true" />
                    <p class="ppms-pd-attach-empty-title">{{ t('projects.pdAttachEmptyFilteredTitle') }}</p>
                    <p class="ppms-pd-attach-empty-desc">{{ t('projects.pdAttachEmptyFilteredHint') }}</p>
                    <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="clearAttachFilters">
                        {{ t('projects.pdAttachClearFilters') }}
                    </button>
                </div>
                <div v-else class="ppms-pd-attach-empty ppms-pd-attach-empty--zero">
                    <template v-if="attachViewTab === 'project'">
                        <div class="ppms-pd-attach-empty-visual" aria-hidden="true" />
                        <p class="ppms-pd-attach-empty-title">{{ t('projects.pdAttachEmptyStateTitle') }}</p>
                        <p class="ppms-pd-attach-empty-desc">{{ t('projects.pdAttachEmptyStateHint') }}</p>
                        <div
                            v-if="canManageDocuments"
                            class="ppms-pd-attach-upload-zone ppms-pd-attach-upload-zone--hero"
                            :class="uploadZoneClasses"
                            role="group"
                            :aria-label="t('projects.pdAttachDropzoneAria')"
                            @dragover.prevent="onUploadDragOver"
                            @dragleave.prevent="onUploadDragLeave"
                            @drop.prevent="onUploadDrop"
                        >
                            <input
                                ref="projectDocFileRef"
                                type="file"
                                class="ppms-sr-only ppms-pd-attach-file-input"
                                tabindex="-1"
                                @change="onNativeFileChange"
                            />
                            <p class="ppms-pd-attach-upload-zone-title">{{ t('projects.pdAttachDropzoneTitle') }}</p>
                            <p class="ppms-pd-attach-upload-zone-hint">{{ t('projects.pdAttachDropzoneHint') }}</p>
                            <div class="ppms-pd-attach-upload-zone-actions">
                                <button type="button" class="ppms-btn-primary ppms-btn-sm" @click.stop="triggerProjectDocFile">
                                    {{ t('projects.pdAttachDropzoneButton') }}
                                </button>
                            </div>
                            <p v-if="uploadStatusLine" class="ppms-pd-attach-upload-status" :data-status="projectDocUploadState.status">
                                {{ uploadStatusLine }}
                            </p>
                        </div>
                        <p v-else class="ppms-muted ppms-pd-attach-empty-readonly">{{ t('projects.pdAttachEmptyReadonly') }}</p>
                    </template>
                    <template v-else>
                        <div class="ppms-pd-attach-empty-visual ppms-pd-attach-empty-visual--task" aria-hidden="true" />
                        <p class="ppms-pd-attach-empty-title">{{ t('projects.pdAttachEmptyTasks') }}</p>
                        <p class="ppms-pd-attach-empty-desc">{{ t('projects.pdAttachEmptyTasksHint') }}</p>
                    </template>
                </div>
            </div>

            <div
                v-else
                class="ppms-pd-attach-split ppms-mt-sm"
                :class="{
                    'ppms-pd-attach-split--file-preview': selectedFileRow,
                    'ppms-pd-attach-split--task-tab': attachViewTab === 'task',
                }"
            >
                <div
                    v-if="showProjectCol"
                    class="ppms-pd-attach-col ppms-pd-attach-col--project"
                    role="tabpanel"
                    aria-labelledby="ppms-pd-attach-tab-project"
                >
                    <div class="ppms-pd-attach-project-body">
                        <div class="ppms-pd-attach-project-list">
                    <nav v-if="browseMode" class="ppms-pd-attach-bc-wrap" aria-label="breadcrumb">
                        <button
                            type="button"
                            class="ppms-pd-attach-bc-item"
                            :class="{ 'is-current': browseFolderId == null }"
                            @click="goToRoot"
                        >
                            {{ t('projects.pdAttachBreadcrumbRoot') }}
                        </button>
                        <template v-for="(crumb, i) in breadcrumbTrail" :key="'bc-' + crumb.id">
                            <span class="ppms-pd-attach-bc-sep" aria-hidden="true">/</span>
                            <button
                                v-if="i < breadcrumbTrail.length - 1"
                                type="button"
                                class="ppms-pd-attach-bc-item"
                                @click="goToFolder(crumb.id)"
                            >
                                {{ crumb.name }}
                            </button>
                            <span v-else class="ppms-pd-attach-bc-current">{{ crumb.name }}</span>
                        </template>
                    </nav>

                    <p v-if="!browseMode" class="ppms-muted ppms-pd-attach-search-hint ppms-mt-sm">{{ t('projects.pdAttachSearchHint') }}</p>

                    <div v-if="canManageDocuments && browseMode" class="ppms-pd-attach-panel ppms-mt-sm">
                        <div class="ppms-pd-attach-actions">
                            <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('doc-add', 'folder')">
                                {{ t('projects.pdDocsAddFolder') }}
                            </button>
                            <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('doc-add', 'link')">
                                {{ t('projects.pdDocsAddLink') }}
                            </button>
                        </div>

                        <div
                            class="ppms-pd-attach-upload-zone ppms-pd-attach-upload-zone--inline"
                            :class="uploadZoneClasses"
                            role="group"
                            :aria-label="t('projects.pdAttachDropzoneAria')"
                            @dragover.prevent="onUploadDragOver"
                            @dragleave.prevent="onUploadDragLeave"
                            @drop.prevent="onUploadDrop"
                        >
                            <input
                                ref="projectDocFileRef"
                                type="file"
                                class="ppms-sr-only ppms-pd-attach-file-input"
                                tabindex="-1"
                                @change="onNativeFileChange"
                            />
                            <div class="ppms-pd-attach-upload-zone-row">
                                <div class="ppms-pd-attach-upload-zone-text">
                                    <p class="ppms-pd-attach-upload-zone-title">{{ t('projects.pdAttachDropzoneInlineTitle') }}</p>
                                    <p class="ppms-pd-attach-upload-zone-hint">{{ t('projects.pdAttachDropzoneInlineHint') }}</p>
                                </div>
                                <button type="button" class="ppms-btn-primary ppms-btn-sm" @click.stop="triggerProjectDocFile">
                                    {{ t('projects.pdAttachDropzoneButton') }}
                                </button>
                            </div>
                            <p
                                v-if="uploadStatusLine"
                                class="ppms-pd-attach-upload-status"
                                :data-status="projectDocUploadState.status"
                            >
                                {{ uploadStatusLine }}
                            </p>
                        </div>
                    </div>

                    <div v-if="canManageDocuments && browseMode && docAddMode" class="ppms-pd-doc-form ppms-pd-doc-form--attach ppms-mt-sm">
                        <p class="ppms-muted ppms-pd-attach-into-hint">{{ t('projects.pdAttachIntoFolderHint') }}</p>
                        <div class="ppms-task-form ppms-pd-doc-form-grid">
                            <label class="ppms-field ppms-pd-doc-form-field">
                                <span>{{ t('projects.pdDocsName') }}</span>
                                <input v-model="docForm.name" type="text" autocomplete="off" />
                            </label>
                            <label v-if="docAddNeedsUrl" class="ppms-field ppms-pd-doc-form-field">
                                <span>{{ t('projects.pdDocsUrl') }}</span>
                                <input v-model="docForm.url" type="url" :placeholder="t('projects.pdDocsUrlPh')" />
                            </label>
                        </div>
                        <div class="ppms-pd-doc-form-actions">
                            <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="$emit('submit-doc')">
                                {{ t('projects.pdDocsSave') }}
                            </button>
                            <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('cancel-doc-add')">
                                {{ t('projects.pdDocsCancel') }}
                            </button>
                        </div>
                    </div>

                    <div v-if="projectFolders.length" class="ppms-pd-attach-block ppms-mt-sm">
                        <div class="ppms-pd-attach-block-head">
                            <h4 class="ppms-pd-attach-block-h">{{ t('projects.pdAttachSectionFolder') }}</h4>
                            <span class="ppms-pd-attach-block-hint">{{ t('projects.pdAttachFolderClickHint') }}</span>
                        </div>
                        <div class="ppms-pd-attach-folder-row">
                            <button
                                v-for="row in projectFolders"
                                :key="rowKey(row)"
                                type="button"
                                class="ppms-pd-attach-folder-card"
                                :class="{ 'is-selected': Number(selectedFolderId) === Number(row.source_id) }"
                                :title="t('projects.pdAttachFolderCardTitle')"
                                @click="selectFolder(row)"
                                @dblclick.prevent="enterFolder(row)"
                            >
                                <span class="ppms-pd-attach-folder-ico" aria-hidden="true" />
                                <div class="ppms-pd-attach-folder-body">
                                    <span class="ppms-pd-attach-folder-name">{{ truncateName(row.name) }}</span>
                                    <span class="ppms-pd-attach-card-meta"
                                        >{{ formatDateTime(row.created_at) }} · {{ folderSizeOrDash(row) }}</span
                                    >
                                </div>
                            </button>
                        </div>
                    </div>
                    <div v-if="projectFiles.length" class="ppms-pd-attach-block">
                        <div class="ppms-pd-attach-block-head">
                            <h4 class="ppms-pd-attach-block-h">{{ t('projects.pdAttachSectionFile') }}</h4>
                            <span class="ppms-pd-attach-block-hint">{{ t('projects.pdAttachFileClickHint') }}</span>
                        </div>
                        <div class="ppms-pd-attach-file-grid ppms-pd-attach-file-grid--large">
                            <article
                                v-for="row in projectFiles"
                                :key="rowKey(row)"
                                class="ppms-pd-attach-file-card ppms-pd-attach-file-card--large"
                                :class="{ 'is-selected': isFileSelected(row) }"
                            >
                                <button
                                    type="button"
                                    class="ppms-pd-attach-file-card-main"
                                    @click="selectFileForPreview(row)"
                                    @dblclick.prevent="$emit('download', row)"
                                >
                                    <div class="ppms-pd-attach-thumb" :class="thumbClass(row)">
                                        <span class="ppms-pd-attach-thumb-label">{{ thumbLabel(row) }}</span>
                                    </div>
                                    <div class="ppms-pd-attach-file-body">
                                        <span class="ppms-pd-attach-file-name" :title="row.name">{{ truncateName(row.name, 48) }}</span>
                                        <span class="ppms-pd-attach-file-line">
                                            <span
                                                class="ppms-pd-file-ico ppms-pd-file-ico--sm"
                                                :data-ext="fileExtForRow(row)"
                                                aria-hidden="true"
                                            />
                                            <span class="ppms-pd-attach-file-meta"
                                                >{{ formatDateTime(row.created_at) }} · {{ sizeDisplay(row) }}</span
                                            >
                                        </span>
                                        <span v-if="!browseMode && row.path_label" class="ppms-pd-attach-path-chip">{{ row.path_label }}</span>
                                    </div>
                                </button>
                                <div v-if="canManageDocuments" class="ppms-pd-attach-card-actions">
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('download', row)">
                                        {{ t('projects.pdAttachFileDownload') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="ppms-btn-ghost ppms-btn-sm"
                                        @click="$emit('open-project-doc', toProjectDoc(row))"
                                    >
                                        {{ t('projects.pdDocsOpen') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="ppms-btn-ghost ppms-btn-sm"
                                        @click="$emit('delete-project-doc', toProjectDoc(row))"
                                    >
                                        {{ t('projects.pdDocsDelete') }}
                                    </button>
                                </div>
                            </article>
                        </div>
                    </div>
                    <p v-if="projectSideEmpty" class="ppms-muted ppms-mt-sm">{{ t('projects.pdAttachFolderEmpty') }}</p>
                        </div>

                        <aside
                            v-if="browseMode && !selectedFileRow"
                            class="ppms-pd-attach-folder-preview"
                            :aria-label="t('projects.pdAttachPreviewTitle')"
                        >
                            <template v-if="selectedFolderId == null">
                                <p class="ppms-pd-attach-folder-preview-kicker">{{ t('projects.pdAttachPreviewKicker') }}</p>
                                <p class="ppms-pd-attach-preview-placeholder">{{ t('projects.pdAttachPreviewPlaceholder') }}</p>
                            </template>
                            <p v-else-if="selectedFolderId != null && !selectedFolderRow" class="ppms-muted">
                                {{ t('projects.pdAttachPreviewMissing') }}
                            </p>
                            <template v-else-if="selectedFolderRow">
                                <div class="ppms-pd-attach-preview-head">
                                    <h4 class="ppms-pd-attach-preview-title">{{ selectedFolderRow.name }}</h4>
                                    <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="enterFolderFromPreview">
                                        {{ t('projects.pdAttachEnterFolder') }}
                                    </button>
                                </div>
                                <p class="ppms-muted ppms-pd-attach-preview-sub">{{ t('projects.pdAttachPreviewSub') }}</p>
                                <div v-if="previewFolders.length" class="ppms-pd-attach-preview-block">
                                    <span class="ppms-pd-attach-preview-label">{{ t('projects.pdAttachSectionFolder') }}</span>
                                    <ul class="ppms-pd-attach-preview-list" role="list">
                                        <li v-for="row in previewFolders" :key="'pf-' + rowKey(row)">
                                            <button
                                                type="button"
                                                class="ppms-pd-attach-preview-row"
                                                @click="selectFolder(row)"
                                                @dblclick.prevent="enterFolder(row)"
                                            >
                                                <span class="ppms-pd-attach-folder-ico ppms-pd-attach-folder-ico--sm" aria-hidden="true" />
                                                <span class="ppms-pd-attach-preview-name">{{ row.name }}</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                                <div v-if="previewFiles.length" class="ppms-pd-attach-preview-block">
                                    <span class="ppms-pd-attach-preview-label">{{ t('projects.pdAttachSectionFile') }}</span>
                                    <ul class="ppms-pd-attach-preview-list" role="list">
                                        <li v-for="row in previewFiles" :key="'pff-' + rowKey(row)">
                                            <button
                                                type="button"
                                                class="ppms-pd-attach-preview-row"
                                                @click="selectFileForPreview(row)"
                                                @dblclick.prevent="$emit('download', row)"
                                            >
                                                <span
                                                    class="ppms-pd-file-ico ppms-pd-file-ico--sm"
                                                    :data-ext="fileExtForRow(row)"
                                                    aria-hidden="true"
                                                />
                                                <span class="ppms-pd-attach-preview-name">{{ truncateName(row.name, 40) }}</span>
                                                <span class="ppms-pd-attach-preview-meta">{{ sizeDisplay(row) }}</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                                <p v-if="!previewFolders.length && !previewFiles.length" class="ppms-muted ppms-pd-attach-preview-empty">
                                    {{ t('projects.pdAttachPreviewEmpty') }}
                                </p>
                            </template>
                        </aside>
                    </div>
                </div>

                <div
                    v-if="showTaskCol"
                    class="ppms-pd-attach-col ppms-pd-attach-col--task"
                    role="tabpanel"
                    aria-labelledby="ppms-pd-attach-tab-task"
                >
                    <p v-if="taskFiles.length" class="ppms-pd-attach-col-sub ppms-pd-attach-col-sub--only">{{ t('projects.pdAttachFileClickHint') }}</p>
                    <div v-if="taskFiles.length" class="ppms-pd-attach-file-grid ppms-pd-attach-file-grid--task">
                        <article
                            v-for="row in taskFiles"
                            :key="rowKey(row)"
                            class="ppms-pd-attach-file-card ppms-pd-attach-file-card--task"
                            :class="{ 'is-selected': isFileSelected(row) }"
                        >
                            <button
                                type="button"
                                class="ppms-pd-attach-file-card-main"
                                @click="selectFileForPreview(row)"
                                @dblclick.prevent="$emit('download', row)"
                            >
                                <div class="ppms-pd-attach-thumb ppms-pd-attach-thumb--sm" :class="thumbClass(row)">
                                    <span class="ppms-pd-attach-thumb-label">{{ thumbLabel(row) }}</span>
                                </div>
                                <div class="ppms-pd-attach-file-body">
                                    <span class="ppms-pd-attach-file-name" :title="row.name">{{ truncateName(row.name, 36) }}</span>
                                    <span class="ppms-pd-attach-file-line">
                                        <span
                                            class="ppms-pd-file-ico ppms-pd-file-ico--sm"
                                            :data-ext="fileExtForRow(row)"
                                            aria-hidden="true"
                                        />
                                        <span class="ppms-pd-attach-file-meta"
                                            >{{ formatDateTime(row.created_at) }} · {{ sizeDisplay(row) }}</span
                                        >
                                    </span>
                                    <span v-if="row.task" class="ppms-pd-attach-task-pill">{{ row.task.name }}</span>
                                </div>
                            </button>
                            <div class="ppms-pd-attach-task-actions">
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('download', row)">
                                    {{ t('projects.pdAttachFileDownload') }}
                                </button>
                                <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="$emit('open-task', row)">
                                    {{ t('projects.pdGoTask') }}
                                </button>
                            </div>
                        </article>
                    </div>
                    <div v-else class="ppms-pd-attach-empty ppms-pd-attach-empty--task-col ppms-mt-sm">
                        <p class="ppms-pd-attach-empty-title">{{ t('projects.pdAttachEmptyTasks') }}</p>
                        <p class="ppms-pd-attach-empty-desc">{{ t('projects.pdAttachEmptyTasksHint') }}</p>
                    </div>
                </div>

                <aside
                    v-if="selectedFileRow"
                    class="ppms-pd-attach-file-preview-aside"
                    :aria-label="t('projects.pdAttachFilePreviewTitle')"
                >
                    <div class="ppms-pd-attach-file-preview-head">
                        <h4 class="ppms-pd-attach-preview-title" :title="selectedFileRow.name">{{ selectedFileRow.name }}</h4>
                        <div class="ppms-pd-attach-file-preview-actions">
                            <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="$emit('download', selectedFileRow)">
                                {{ t('projects.pdAttachFileDownload') }}
                            </button>
                            <button
                                v-if="selectedFileRow.doc_type === 'link' || projectDocIsWebLink(selectedFileRow)"
                                type="button"
                                class="ppms-btn-ghost ppms-btn-sm"
                                @click="$emit('open-project-doc', toProjectDoc(selectedFileRow))"
                            >
                                {{ t('projects.pdDocsOpen') }}
                            </button>
                            <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="clearFilePreviewSelection">
                                {{ t('projects.pdAttachFilePreviewClose') }}
                            </button>
                        </div>
                    </div>
                    <p v-if="selectedFileRow.scope === 'task' && selectedFileRow.task" class="ppms-muted ppms-pd-attach-preview-sub">
                        {{ selectedFileRow.task.name }}
                    </p>

                    <div v-if="filePreviewState.status === 'loading'" class="ppms-pd-attach-file-preview-loading">
                        {{ t('projects.pdAttachFilePreviewLoading') }}
                    </div>
                    <p v-else-if="filePreviewState.status === 'error'" class="ppms-muted ppms-pd-attach-file-preview-error">
                        {{ t('projects.pdAttachFilePreviewError') }}
                    </p>
                    <div v-else-if="filePreviewState.status === 'link'" class="ppms-pd-attach-file-preview-link">
                        <a
                            v-if="filePreviewState.linkUrl"
                            :href="filePreviewState.linkUrl"
                            class="ppms-pd-attach-file-preview-link-a"
                            target="_blank"
                            rel="noopener noreferrer"
                            >{{ filePreviewState.linkUrl }}</a
                        >
                        <p v-else class="ppms-muted">{{ t('projects.pdAttachFilePreviewNoUrl') }}</p>
                    </div>
                    <p v-else-if="filePreviewState.status === 'unsupported'" class="ppms-muted ppms-pd-attach-file-preview-unsupported">
                        {{ t('projects.pdAttachFilePreviewUnsupported') }}
                    </p>
                    <template v-else-if="filePreviewState.status === 'ready' && filePreviewState.kind === 'pdf'">
                        <div class="ppms-pd-attach-file-preview-pdf-tools">
                            <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="openFilePreviewLightbox">
                                {{ t('projects.pdAttachPreviewEnlarge') }}
                            </button>
                        </div>
                        <iframe
                            class="ppms-pd-attach-file-preview-frame"
                            :src="filePreviewState.blobUrl"
                            :title="selectedFileRow.name"
                        />
                    </template>
                    <div
                        v-else-if="filePreviewState.status === 'ready' && filePreviewState.kind === 'image'"
                        class="ppms-pd-attach-file-preview-media"
                        role="button"
                        tabindex="0"
                        :aria-label="t('projects.pdAttachPreviewEnlargeAria')"
                        @click="openFilePreviewLightbox"
                        @keydown.enter.prevent="openFilePreviewLightbox"
                        @keydown.space.prevent="openFilePreviewLightbox"
                    >
                        <img
                            class="ppms-pd-attach-file-preview-img"
                            :src="filePreviewState.blobUrl"
                            :alt="selectedFileRow.name"
                        />
                        <span class="ppms-pd-attach-file-preview-media-hint">{{ t('projects.pdAttachPreviewClickEnlarge') }}</span>
                    </div>
                    <div
                        v-else-if="filePreviewState.status === 'ready' && filePreviewState.kind === 'text'"
                        class="ppms-pd-attach-file-preview-text-wrap"
                        role="button"
                        tabindex="0"
                        :aria-label="t('projects.pdAttachPreviewEnlargeAria')"
                        @click="openFilePreviewLightbox"
                        @keydown.enter.prevent="openFilePreviewLightbox"
                        @keydown.space.prevent="openFilePreviewLightbox"
                    >
                        <pre class="ppms-pd-attach-file-preview-text">{{ filePreviewState.text }}</pre>
                        <span class="ppms-pd-attach-file-preview-media-hint">{{ t('projects.pdAttachPreviewClickEnlarge') }}</span>
                    </div>
                </aside>
            </div>
        </section>

        <Teleport to="body">
            <div
                v-if="filePreviewLightboxOpen && selectedFileRow && filePreviewState.status === 'ready'"
                class="ppms-modal-backdrop ppms-pd-attach-lightbox-backdrop"
                role="presentation"
                @click.self="closeFilePreviewLightbox"
            >
                <div
                    class="ppms-pd-attach-lightbox"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="ppms-pd-attach-lightbox-title"
                    tabindex="-1"
                    @click.stop
                >
                    <header class="ppms-pd-attach-lightbox-head">
                        <h2 id="ppms-pd-attach-lightbox-title" class="ppms-pd-attach-lightbox-title">
                            {{ selectedFileRow.name }}
                        </h2>
                        <div class="ppms-pd-attach-lightbox-actions">
                            <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="$emit('download', selectedFileRow)">
                                {{ t('projects.pdAttachFileDownload') }}
                            </button>
                            <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="closeFilePreviewLightbox">
                                {{ t('common.close') }}
                            </button>
                        </div>
                    </header>
                    <div class="ppms-pd-attach-lightbox-body">
                        <iframe
                            v-if="filePreviewState.kind === 'pdf'"
                            class="ppms-pd-attach-lightbox-frame"
                            :src="filePreviewState.blobUrl"
                            :title="selectedFileRow.name"
                        />
                        <img
                            v-else-if="filePreviewState.kind === 'image'"
                            class="ppms-pd-attach-lightbox-img"
                            :src="filePreviewState.blobUrl"
                            :alt="selectedFileRow.name"
                        />
                        <pre v-else-if="filePreviewState.kind === 'text'" class="ppms-pd-attach-lightbox-text">{{ filePreviewState.text }}</pre>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
/* eslint-disable vue/no-mutating-props -- sync docForm.parent_id to current folder (parent-owned reactive) */
import axios from 'axios';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { fileExt, formatBytes } from '../../utils/projectDetailFormatters';

const { t } = useI18n();

const props = defineProps({
    projectMedia: { type: Array, required: true },
    mediaCounts: { type: Object, default: null },
    /** @type {{ status: 'idle'|'uploading'|'success'|'error', fileName?: string }} */
    projectDocUploadState: { type: Object, default: () => ({ status: 'idle' }) },
    canManageDocuments: { type: Boolean, default: false },
    docAddMode: { type: [String, null], default: null },
    docForm: { type: Object, required: true },
    docAddNeedsUrl: { type: Boolean, default: false },
});

const emit = defineEmits([
    'download',
    'open-task',
    'open-project-doc',
    'delete-project-doc',
    'doc-add',
    'cancel-doc-add',
    'submit-doc',
    'project-doc-upload',
]);

const projectDocFileRef = ref(null);
const uploadDragOver = ref(false);
let uploadDragLeaveTimer = null;
/** Tab nội dung: thư viện dự án hoặc tệp từ công việc */
const attachViewTab = ref('project');
const searchQ = ref('');
const browseFolderId = ref(null);
const selectedFolderId = ref(null);
const selectedFileRow = ref(null);
const filePreviewLightboxOpen = ref(false);
const filePreviewState = ref({
    status: 'idle',
    blobUrl: null,
    text: null,
    kind: null,
    linkUrl: null,
    error: null,
});

let filePreviewLoadSeq = 0;

const browseMode = computed(() => !searchQ.value.trim());

const filteredBase = computed(() => {
    const q = searchQ.value.trim().toLowerCase();
    let rows = props.projectMedia || [];
    rows = rows.filter((r) =>
        attachViewTab.value === 'project' ? r.scope === 'project' : r.scope === 'task',
    );
    if (!q) {
        return rows;
    }
    return rows.filter((r) => {
        const blob = [r.name, r.original_name, r.path_label, r.task?.name].filter(Boolean).join(' ').toLowerCase();

        return blob.includes(q);
    });
});

const projectScopedRows = computed(() => filteredBase.value.filter((r) => r.scope === 'project'));

const projectScopeFiltered = computed(() => {
    const rows = projectScopedRows.value;
    if (!browseMode.value) {
        return rows;
    }
    const pid = browseFolderId.value;
    return rows.filter((r) => {
        const p = r.parent_id;
        if (pid == null) {
            return p == null || p === undefined;
        }

        return Number(p) === Number(pid);
    });
});

const projectFolders = computed(() => projectScopeFiltered.value.filter((r) => r.doc_type === 'folder'));

const projectFiles = computed(() => projectScopeFiltered.value.filter((r) => r.doc_type !== 'folder'));

const taskFiles = computed(() => filteredBase.value.filter((r) => r.scope === 'task'));

const showProjectCol = computed(() => attachViewTab.value === 'project');

const showTaskCol = computed(() => attachViewTab.value === 'task');

const projectSideEmpty = computed(() => !projectFolders.value.length && !projectFiles.value.length);

const filteredEmpty = computed(() => !filteredBase.value.length);

const hasAnyMedia = computed(() => (props.projectMedia || []).length > 0);

/** Chỉ hiện “không có kết quả” khi có từ khóa tìm (tránh nhầm khi tab trống nhưng dữ liệu ở tab kia). */
const noResultsFromFilters = computed(
    () => filteredEmpty.value && hasAnyMedia.value && searchQ.value.trim().length > 0,
);

const uploadZoneClasses = computed(() => ({
    'is-dragover': uploadDragOver.value,
    'is-busy': props.projectDocUploadState?.status === 'uploading',
    'is-success': props.projectDocUploadState?.status === 'success',
    'is-error': props.projectDocUploadState?.status === 'error',
}));

const uploadStatusLine = computed(() => {
    const st = props.projectDocUploadState;
    if (!st || st.status === 'idle') {
        return '';
    }
    if (st.status === 'uploading') {
        return t('projects.pdAttachUploading', { name: st.fileName || '…' });
    }
    if (st.status === 'success') {
        return t('projects.pdAttachUploadSuccess', { name: st.fileName || '…' });
    }
    if (st.status === 'error') {
        return t('projects.pdAttachUploadError', { name: st.fileName || '…' });
    }

    return '';
});

const folderById = computed(() => {
    const m = new Map();
    for (const r of props.projectMedia || []) {
        if (r.scope === 'project' && r.doc_type === 'folder') {
            m.set(Number(r.source_id), r);
        }
    }

    return m;
});

const selectedFolderRow = computed(() => {
    if (selectedFolderId.value == null) {
        return null;
    }

    return folderById.value.get(Number(selectedFolderId.value)) || null;
});

const previewChildren = computed(() => {
    if (selectedFolderId.value == null) {
        return [];
    }
    const pid = Number(selectedFolderId.value);

    return (props.projectMedia || []).filter(
        (r) => r.scope === 'project' && r.parent_id != null && Number(r.parent_id) === pid,
    );
});

const previewFolders = computed(() => previewChildren.value.filter((r) => r.doc_type === 'folder'));

const previewFiles = computed(() => previewChildren.value.filter((r) => r.doc_type !== 'folder'));

const breadcrumbTrail = computed(() => {
    const parts = [];
    let id = browseFolderId.value;
    const seen = new Set();
    while (id != null && !seen.has(id)) {
        seen.add(id);
        const row = folderById.value.get(Number(id));
        if (!row) {
            break;
        }
        parts.unshift({ id: row.source_id, name: row.name });
        id = row.parent_id != null ? Number(row.parent_id) : null;
    }

    return parts;
});

watch(
    browseFolderId,
    (id) => {
        props.docForm.parent_id = id != null ? String(id) : '';
        selectedFolderId.value = null;
        selectedFileRow.value = null;
    },
    { immediate: true },
);

watch(searchQ, (q) => {
    if (q.trim()) {
        selectedFolderId.value = null;
        selectedFileRow.value = null;
    }
});

watch(attachViewTab, () => {
    selectedFileRow.value = null;
    selectedFolderId.value = null;
});

watch(
    () => props.projectMedia,
    () => {
        if (selectedFolderId.value != null && !folderById.value.has(Number(selectedFolderId.value))) {
            selectedFolderId.value = null;
        }
        if (selectedFileRow.value) {
            const k = rowKey(selectedFileRow.value);
            const still = (props.projectMedia || []).some((r) => rowKey(r) === k);
            if (!still) {
                selectedFileRow.value = null;
            }
        }
    },
    { deep: true },
);

function goToRoot() {
    browseFolderId.value = null;
}

function goToFolder(id) {
    browseFolderId.value = id != null ? Number(id) : null;
}

function selectFolder(row) {
    if (row.doc_type !== 'folder') {
        return;
    }
    selectedFileRow.value = null;
    selectedFolderId.value = Number(row.source_id);
}

function enterFolderFromPreview() {
    if (!selectedFolderRow.value) {
        return;
    }
    enterFolder(selectedFolderRow.value);
}

function enterFolder(row) {
    if (row.doc_type !== 'folder') {
        return;
    }
    searchQ.value = '';
    browseFolderId.value = Number(row.source_id);
}

function rowKey(row) {
    return `${row.source}-${row.source_id}`;
}

function formatDateTime(iso) {
    if (iso == null) {
        return '—';
    }
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) {
        return '—';
    }
    const pad = (n) => String(n).padStart(2, '0');

    return `${pad(d.getHours())}:${pad(d.getMinutes())} ${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
}

function sizeDisplay(row) {
    if (row.size_bytes == null) {
        return '—';
    }

    return formatBytes(row.size_bytes);
}

function folderSizeOrDash(row) {
    return row.size_bytes != null ? formatBytes(row.size_bytes) : '—';
}

function truncateName(s, max = 32) {
    const x = String(s || '').trim();
    if (x.length <= max) {
        return x;
    }

    return `${x.slice(0, Math.max(0, max - 1))}…`;
}

function fileExtForRow(row) {
    if (row.source === 'task_attachment') {
        return fileExt(row.original_name || row.name);
    }
    if (row.doc_type === 'upload') {
        return fileExt(row.original_name || row.name);
    }

    return row.doc_type === 'folder' ? 'folder' : 'link';
}

/** Project doc with URL but not folder/upload (covers legacy stored doc_type values). */
function projectDocIsWebLink(row) {
    if (row.source !== 'project_document' || !String(row.url || '').trim()) {
        return false;
    }
    if (row.doc_type === 'folder' || row.doc_type === 'upload' || row.doc_type === 'link') {
        return false;
    }

    return true;
}

function thumbLabel(row) {
    const ext = fileExtForRow(row);
    if (row.doc_type === 'link' || projectDocIsWebLink(row)) {
        return 'URL';
    }
    if (!ext || ext === 'folder') {
        return 'FILE';
    }
    return ext.slice(0, 4).toUpperCase();
}

function thumbClass(row) {
    const ext = fileExtForRow(row).toLowerCase();
    if (row.doc_type === 'link' || projectDocIsWebLink(row)) {
        return 'ppms-pd-attach-thumb--link';
    }
    if (['pdf'].includes(ext)) {
        return 'ppms-pd-attach-thumb--pdf';
    }
    if (['xls', 'xlsx', 'csv'].includes(ext)) {
        return 'ppms-pd-attach-thumb--xls';
    }
    if (['doc', 'docx'].includes(ext)) {
        return 'ppms-pd-attach-thumb--doc';
    }
    if (['ppt', 'pptx'].includes(ext)) {
        return 'ppms-pd-attach-thumb--ppt';
    }
    if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].includes(ext)) {
        return 'ppms-pd-attach-thumb--img';
    }

    return 'ppms-pd-attach-thumb--default';
}

function toProjectDoc(row) {
    return {
        id: row.source_id,
        doc_type: row.doc_type,
        name: row.name,
        url: row.url,
        original_name: row.original_name,
    };
}

function previewKindForExt(ext) {
    const e = String(ext || '')
        .toLowerCase()
        .replace(/^\./, '');
    if (e === 'pdf') {
        return 'pdf';
    }
    if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].includes(e)) {
        return 'image';
    }
    if (['txt', 'csv', 'md', 'json', 'log', 'xml'].includes(e)) {
        return 'text';
    }

    return null;
}

function revokeFilePreviewBlob() {
    const b = filePreviewState.value.blobUrl;
    if (b) {
        URL.revokeObjectURL(b);
    }
}

function resetFilePreviewState() {
    revokeFilePreviewBlob();
    filePreviewLoadSeq++;
    filePreviewState.value = {
        status: 'idle',
        blobUrl: null,
        text: null,
        kind: null,
        linkUrl: null,
        error: null,
    };
}

async function loadFilePreview(row) {
    const seq = ++filePreviewLoadSeq;
    revokeFilePreviewBlob();
    filePreviewState.value = {
        status: 'loading',
        blobUrl: null,
        text: null,
        kind: null,
        linkUrl: null,
        error: null,
    };

    if (row.doc_type === 'link' || projectDocIsWebLink(row)) {
        if (seq !== filePreviewLoadSeq) {
            return;
        }
        filePreviewState.value = {
            status: 'link',
            linkUrl: String(row.url || '').trim(),
            blobUrl: null,
            text: null,
            kind: null,
            error: null,
        };

        return;
    }

    if (row.source === 'project_document' && row.doc_type !== 'upload') {
        if (seq !== filePreviewLoadSeq) {
            return;
        }
        filePreviewState.value = {
            status: 'unsupported',
            blobUrl: null,
            text: null,
            kind: null,
            linkUrl: null,
            error: null,
        };

        return;
    }

    const ext = fileExtForRow(row).toLowerCase();
    const kind = previewKindForExt(ext);
    if (!kind) {
        if (seq !== filePreviewLoadSeq) {
            return;
        }
        filePreviewState.value = {
            status: 'unsupported',
            blobUrl: null,
            text: null,
            kind: null,
            linkUrl: null,
            error: null,
        };

        return;
    }

    try {
        const url =
            row.source === 'task_attachment'
                ? `/api/attachments/${row.source_id}/download`
                : `/api/project-documents/${row.source_id}/download`;
        const res = await axios.get(url, { responseType: 'blob' });
        if (seq !== filePreviewLoadSeq) {
            return;
        }
        const blob = res.data;
        if (kind === 'text') {
            const text = await blob.text();
            if (seq !== filePreviewLoadSeq) {
                return;
            }
            filePreviewState.value = {
                status: 'ready',
                kind: 'text',
                text: text.slice(0, 400000),
                blobUrl: null,
                linkUrl: null,
                error: null,
            };

            return;
        }
        const burl = URL.createObjectURL(blob);
        filePreviewState.value = {
            status: 'ready',
            kind,
            blobUrl: burl,
            text: null,
            linkUrl: null,
            error: null,
        };
    } catch {
        if (seq !== filePreviewLoadSeq) {
            return;
        }
        filePreviewState.value = {
            status: 'error',
            blobUrl: null,
            text: null,
            kind: null,
            linkUrl: null,
            error: null,
        };
    }
}

function openFilePreviewLightbox() {
    if (filePreviewState.value.status !== 'ready') {
        return;
    }
    filePreviewLightboxOpen.value = true;
}

function closeFilePreviewLightbox() {
    filePreviewLightboxOpen.value = false;
}

function onLightboxEscape(e) {
    if (e.key === 'Escape') {
        closeFilePreviewLightbox();
    }
}

watch(filePreviewLightboxOpen, (open) => {
    if (open) {
        document.addEventListener('keydown', onLightboxEscape);
        document.body.classList.add('ppms-modal-open');
    } else {
        document.removeEventListener('keydown', onLightboxEscape);
        document.body.classList.remove('ppms-modal-open');
    }
});

watch(selectedFileRow, (row) => {
    filePreviewLightboxOpen.value = false;
    if (!row) {
        resetFilePreviewState();

        return;
    }
    loadFilePreview(row);
});

function selectFileForPreview(row) {
    if (row.doc_type === 'folder') {
        return;
    }
    selectedFolderId.value = null;
    selectedFileRow.value = row;
}

function clearFilePreviewSelection() {
    selectedFileRow.value = null;
}

function isFileSelected(row) {
    return selectedFileRow.value ? rowKey(selectedFileRow.value) === rowKey(row) : false;
}

onBeforeUnmount(() => {
    revokeFilePreviewBlob();
    if (uploadDragLeaveTimer) {
        clearTimeout(uploadDragLeaveTimer);
    }
    document.removeEventListener('keydown', onLightboxEscape);
    document.body.classList.remove('ppms-modal-open');
});

function triggerProjectDocFile() {
    projectDocFileRef.value?.click();
}

function clearAttachFilters() {
    searchQ.value = '';
}

function onNativeFileChange(e) {
    const files = e.target?.files;
    if (!files?.length) {
        return;
    }
    emit('project-doc-upload', { files, target: e.target, skipConfirm: false });
}

function onUploadDrop(e) {
    if (uploadDragLeaveTimer) {
        clearTimeout(uploadDragLeaveTimer);
        uploadDragLeaveTimer = null;
    }
    uploadDragOver.value = false;
    const dt = e.dataTransfer;
    const f = dt?.files?.[0];
    if (!f) {
        return;
    }
    emit('project-doc-upload', { files: dt.files, skipConfirm: true });
}

function onUploadDragOver(e) {
    e.preventDefault();
    if (uploadDragLeaveTimer) {
        clearTimeout(uploadDragLeaveTimer);
        uploadDragLeaveTimer = null;
    }
    uploadDragOver.value = true;
}

function onUploadDragLeave(e) {
    e.preventDefault();
    uploadDragLeaveTimer = setTimeout(() => {
        uploadDragOver.value = false;
        uploadDragLeaveTimer = null;
    }, 90);
}
</script>
