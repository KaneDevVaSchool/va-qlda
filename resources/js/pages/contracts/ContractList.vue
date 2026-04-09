<template>
    <div class="ppms-page contract-list">

        <section class="ppms-card contract-list__card">
            <div class="contract-list__toolbar" role="region" :aria-label="t('contracts.filterBarTitle')">
                <div class="contract-list__toolbar-head">
                    <div class="contract-list__toolbar-intro">
                        <template v-if="trashMode">
                            <h2 class="contract-list__filter-heading">{{ t('contracts.trashTitle') }}</h2>
                            <p class="contract-list__filter-sub">{{ t('contracts.trashSubtitle') }}</p>
                        </template>
                        <template v-else>
                            <h2 class="contract-list__filter-heading">{{ t('contracts.filterBarTitle') }}</h2>
                            <p class="contract-list__filter-sub">{{ t('contracts.filterBarSubtitle') }}</p>
                        </template>
                    </div>
                    <div class="contract-list__toolbar-actions" role="group" :aria-label="t('contracts.toolbarActionsLabel')">
                        <div class="contract-list__toolbar-actions-secondary">
                            <button
                                v-if="isAdmin"
                                type="button"
                                class="ppms-btn-ghost contract-list__action-btn"
                                :disabled="loading"
                                @click="toggleTrashMode"
                            >
                                {{ trashMode ? t('contracts.trashBackToList') : t('contracts.trashToggle') }}
                            </button>
                            <button
                                v-if="!trashMode"
                                type="button"
                                class="ppms-btn-ghost contract-list__action-btn"
                                :disabled="loading"
                                @click="resetFilters"
                            >
                                {{ t('contracts.resetFilters') }}
                            </button>
                            <button
                                type="button"
                                class="ppms-btn-ghost contract-list__action-btn"
                                :disabled="loading"
                                @click="refresh"
                            >
                                {{ t('contracts.refresh') }}
                            </button>
                            <button
                                v-if="!trashMode"
                                type="button"
                                class="ppms-btn-ghost contract-list__action-btn"
                                :disabled="loading"
                                :title="t('contracts.exportCsvHint')"
                                @click="downloadCsv"
                            >
                                {{ t('contracts.exportCsv') }}
                            </button>
                        </div>
                        <button
                            v-if="!trashMode"
                            type="button"
                            class="ppms-btn-primary contract-list__action-primary"
                            @click="openCreate"
                        >
                            {{ t('contracts.create') }}
                        </button>
                    </div>
                </div>

                <div v-show="!trashMode" class="contract-list__filter-groups" role="search">
                    <div
                        class="contract-list__filter-card"
                        role="group"
                        :aria-labelledby="'cl-filter-legend-entities'"
                    >
                        <div id="cl-filter-legend-entities" class="contract-list__filter-card-title">{{ t('contracts.filterGroupEntities') }}</div>
                        <div class="contract-list__filter-card-grid contract-list__filter-card-grid--3">
                            <div class="contract-list__field">
                                <label class="contract-list__label" for="cl-filter-status">{{ t('contracts.filterStatus') }}</label>
                                <select
                                    id="cl-filter-status"
                                    v-model="filters.status"
                                    class="ppms-input contract-list__input"
                                    @change="onFilterCommit"
                                >
                                    <option value="">{{ t('contracts.allStatuses') }}</option>
                                    <option value="draft">{{ t('contracts.statusDraft') }}</option>
                                    <option value="pending_approval">{{ t('contracts.statusPending') }}</option>
                                    <option value="active">{{ t('contracts.statusActive') }}</option>
                                    <option value="expired">{{ t('contracts.statusExpired') }}</option>
                                    <option value="terminated">{{ t('contracts.statusTerminated') }}</option>
                                </select>
                            </div>
                            <div class="contract-list__field">
                                <label class="contract-list__label" for="cl-filter-vendor">{{ t('contracts.filterVendor') }}</label>
                                <select
                                    id="cl-filter-vendor"
                                    v-model.number="filters.vendor_id"
                                    class="ppms-input contract-list__input"
                                    :aria-describedby="vendorsEmpty ? 'cl-vendor-filter-hint' : undefined"
                                    @change="onFilterCommit"
                                >
                                    <option :value="0">{{ t('contracts.allVendors') }}</option>
                                    <option v-for="v in lookups.vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
                                </select>
                                <p v-if="vendorsEmpty" id="cl-vendor-filter-hint" class="contract-list__field-hint">
                                    {{ t('contracts.vendorFilterHint') }}
                                </p>
                            </div>
                            <div class="contract-list__field">
                                <label class="contract-list__label" for="cl-filter-dept">{{ t('contracts.filterDepartment') }}</label>
                                <select
                                    id="cl-filter-dept"
                                    v-model.number="filters.department_id"
                                    class="ppms-input contract-list__input"
                                    @change="onFilterCommit"
                                >
                                    <option :value="0">{{ t('contracts.allDepartments') }}</option>
                                    <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div
                        class="contract-list__filter-card"
                        role="group"
                        :aria-labelledby="'cl-filter-legend-dates'"
                    >
                        <div id="cl-filter-legend-dates" class="contract-list__filter-card-title">{{ t('contracts.filterGroupEndDate') }}</div>
                        <div class="contract-list__date-range">
                            <div class="contract-list__field contract-list__field--date">
                                <label class="contract-list__label" for="cl-filter-end-from">{{ t('contracts.filterEndFrom') }}</label>
                                <input
                                    id="cl-filter-end-from"
                                    v-model="filters.end_from"
                                    type="date"
                                    class="ppms-input contract-list__input"
                                    @change="onFilterCommit"
                                />
                            </div>
                            <span class="contract-list__date-sep" aria-hidden="true">{{ t('contracts.dateRangeSeparator') }}</span>
                            <div class="contract-list__field contract-list__field--date">
                                <label class="contract-list__label" for="cl-filter-end-to">{{ t('contracts.filterEndTo') }}</label>
                                <input
                                    id="cl-filter-end-to"
                                    v-model="filters.end_to"
                                    type="date"
                                    class="ppms-input contract-list__input"
                                    @change="onFilterCommit"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        class="contract-list__filter-card contract-list__filter-card--compact"
                        role="group"
                        :aria-labelledby="'cl-filter-legend-view'"
                    >
                        <div id="cl-filter-legend-view" class="contract-list__filter-card-title">{{ t('contracts.filterGroupView') }}</div>
                        <div class="contract-list__field">
                            <label class="contract-list__label" for="cl-per-page">{{ t('contracts.perPage') }}</label>
                            <select
                                id="cl-per-page"
                                v-model.number="perPage"
                                class="ppms-input contract-list__input"
                                @change="onPerPageChange"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="lookupWarning" class="contract-list__hint">{{ t('contracts.lookupEmpty') }}</p>

            <div v-if="loading" class="ppms-loading-line contract-list__loading" role="status">{{ t('common.loading') }}</div>

            <template v-else>
                <div v-if="items.length === 0" class="contract-list__empty" role="status">
                    <div class="contract-list__empty-visual" aria-hidden="true" />
                    <p class="contract-list__empty-title">
                        {{
                            trashMode
                                ? t('contracts.trashEmpty')
                                : hasActiveFilters
                                  ? t('contracts.emptyFiltered')
                                  : t('contracts.empty')
                        }}
                    </p>
                    <p class="contract-list__empty-desc">{{ t('contracts.emptyHint') }}</p>
                    <div class="contract-list__empty-actions">
                        <button v-if="!trashMode && hasActiveFilters" type="button" class="ppms-btn-ghost" @click="resetFilters">
                            {{ t('contracts.resetFilters') }}
                        </button>
                        <button v-if="!trashMode" type="button" class="ppms-btn-primary" @click="openCreate">{{ t('contracts.create') }}</button>
                    </div>
                </div>

                <div v-else class="contract-list__table-wrap ppms-table-scroll">
                    <table class="ppms-table contract-list__table">
                        <thead>
                            <tr>
                                <th>
                                    <button type="button" class="contract-list__th-btn" @click="toggleSort('code')">
                                        {{ t('contracts.tableCode') }}
                                        <span v-if="sortKey === 'code'" class="contract-list__sort-ind" aria-hidden="true">{{
                                            sortOrder === 'asc' ? '↑' : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th>{{ t('contracts.tableProduct') }}</th>
                                <th>{{ t('contracts.tableDepartment') }}</th>
                                <th>{{ t('contracts.tableStatus') }}</th>
                                <th>
                                    <div class="contract-list__th-stack">
                                        <button type="button" class="contract-list__th-btn" @click="toggleSort('end_date')">
                                            {{ t('contracts.tablePeriod') }}
                                            <span v-if="sortKey === 'end_date'" class="contract-list__sort-ind" aria-hidden="true">{{
                                                sortOrder === 'asc' ? '↑' : '↓'
                                            }}</span>
                                        </button>
                                        <span class="contract-list__th-sub">{{ t('contracts.tablePeriodProgress') }}</span>
                                    </div>
                                </th>
                                <th class="contract-list__cell-num">
                                    <button type="button" class="contract-list__th-btn" @click="toggleSort('total_value')">
                                        {{ t('contracts.tableValue') }}
                                        <span v-if="sortKey === 'total_value'" class="contract-list__sort-ind" aria-hidden="true">{{
                                            sortOrder === 'asc' ? '↑' : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th>{{ t('contracts.tableExpiresIn') }}</th>
                                <th v-if="!trashMode">
                                    <button type="button" class="contract-list__th-btn" @click="toggleSort('updated_at')">
                                        {{ t('contracts.tableUpdatedAt') }}
                                        <span v-if="sortKey === 'updated_at'" class="contract-list__sort-ind" aria-hidden="true">{{
                                            sortOrder === 'asc' ? '↑' : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th v-else>{{ t('contracts.trashDeletedAt') }}</th>
                                <th>
                                    <button type="button" class="contract-list__th-btn" @click="toggleSort('followed_by_id')">
                                        {{ t('contracts.tableFollower') }}
                                        <span v-if="sortKey === 'followed_by_id'" class="contract-list__sort-ind" aria-hidden="true">{{
                                            sortOrder === 'asc' ? '↑' : '↓'
                                        }}</span>
                                    </button>
                                </th>
                                <th>{{ t('contracts.tableActions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="g in vendorGroups" :key="g.key">
                                <tr class="contract-list__vendor-group">
                                    <td colspan="10">
                                        <button
                                            type="button"
                                            class="contract-list__group-toggle"
                                            :aria-expanded="!isVendorGroupCollapsed(g.key)"
                                            @click.stop="toggleVendorGroup(g.key)"
                                        >
                                            <span
                                                class="contract-list__group-chevron"
                                                :class="{ 'contract-list__group-chevron--collapsed': isVendorGroupCollapsed(g.key) }"
                                                aria-hidden="true"
                                            >▼</span>
                                            <strong>{{ g.vendorName }}</strong>
                                            <span class="contract-list__group-count">{{ t('contracts.vendorGroupCount', { n: g.items.length }) }}</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-for="row in g.items"
                                    v-show="!isVendorGroupCollapsed(g.key)"
                                    :key="row.id"
                                    class="contract-list__row"
                                    :tabindex="trashMode ? -1 : 0"
                                    @click="!trashMode && goDetail(row.id)"
                                    @keydown.enter.prevent="!trashMode && goDetail(row.id)"
                                >
                                    <td>
                                        <router-link
                                            v-if="!trashMode"
                                            :to="`/contracts/${row.id}`"
                                            class="contract-list__link"
                                            @click.stop
                                            >{{ row.code }}</router-link
                                        >
                                        <span v-else class="contract-list__code-muted">{{ row.code }}</span>
                                    </td>
                                    <td class="contract-list__cell-muted">{{ row.product?.name || '—' }}</td>
                                    <td>{{ row.department?.name || '—' }}</td>
                                    <td>
                                        <span class="contract-list__pill" :class="statusPillClass(row.status)">{{ statusLabel(row.status) }}</span>
                                    </td>
                                    <td>
                                        <div class="contract-list__period-cell">
                                            <span class="contract-list__period">{{ row.start_date }} → {{ row.end_date }}</span>
                                            <div v-if="periodProgressPercent(row) !== null" class="contract-list__period-progress">
                                                <div class="contract-list__period-bar-track" role="presentation">
                                                    <div
                                                        class="contract-list__period-bar-fill"
                                                        :style="{ width: `${periodProgressPercent(row)}%` }"
                                                    />
                                                </div>
                                                <span class="contract-list__period-pct">{{ periodProgressPercent(row) }}%</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="contract-list__cell-num">{{ formatMoney(row.total_value) }}</td>
                                    <td>
                                        <span v-if="expiresBadge(row)" class="contract-list__badge-soon">{{ expiresBadge(row) }}</span>
                                        <span v-else class="contract-list__cell-muted">—</span>
                                    </td>
                                    <td v-if="!trashMode" class="contract-list__cell-muted contract-list__cell-nowrap">
                                        {{ formatUpdatedAt(row.updated_at) }}
                                    </td>
                                    <td v-else class="contract-list__cell-muted contract-list__cell-nowrap">
                                        {{ formatUpdatedAt(row.deleted_at) }}
                                    </td>
                                    <td class="contract-list__cell-muted">{{ row.followed_by?.name || '—' }}</td>
                                    <td @click.stop>
                                        <template v-if="trashMode">
                                            <button
                                                type="button"
                                                class="ppms-btn-ghost ppms-btn-sm"
                                                @click="restoreRow(row)"
                                            >
                                                {{ t('contracts.trashRestore') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="ppms-btn-ghost ppms-btn-sm contract-list__btn-danger"
                                                @click="forceDeleteRow(row)"
                                            >
                                                {{ t('contracts.trashForceDelete') }}
                                            </button>
                                        </template>
                                        <router-link v-else :to="`/contracts/${row.id}`" class="ppms-btn-ghost ppms-btn-sm">{{
                                            t('contracts.view')
                                        }}</router-link>
                                        <button
                                            v-if="isAdmin"
                                            type="button"
                                            class="ppms-btn-ghost ppms-btn-sm contract-list__btn-danger"
                                            @click="deleteContractRow(row)"
                                        >
                                            {{ t('common.delete') }}
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>

            <div v-if="!loading && items.length > 0 && meta" class="contract-list__footer">
                <p class="contract-list__meta">
                    {{ t('contracts.rangeShown', { from: meta.from ?? 0, to: meta.to ?? 0 }) }}
                    <span class="contract-list__meta-sep">·</span>
                    {{ t('contracts.totalCount', { n: meta.total ?? 0 }) }}
                </p>
                <div v-if="meta.last_page > 1" class="contract-list__pagination">
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" :disabled="page <= 1" @click="goPrevPage">‹</button>
                    <span class="contract-list__page-label">{{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" :disabled="page >= meta.last_page" @click="goNextPage">
                        ›
                    </button>
                </div>
            </div>
        </section>

        <div
            v-if="modalOpen"
            class="contract-modal__backdrop"
            role="presentation"
            @click.self="!createBusy && (modalOpen = false)"
        >
            <div
                class="contract-modal ppms-card"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-create-title"
                aria-describedby="modal-create-desc"
            >
                <div class="contract-modal__head">
                    <div class="contract-modal__head-text">
                        <h2 id="modal-create-title" class="contract-modal__title">{{ t('contracts.modalCreateTitle') }}</h2>
                        <p id="modal-create-desc" class="contract-modal__subtitle">{{ t('contracts.modalCreateSubtitle') }}</p>
                    </div>
                    <button
                        type="button"
                        class="contract-modal__close"
                        :disabled="createBusy"
                        :aria-label="t('common.cancel')"
                        @click="modalOpen = false"
                    >
                        ×
                    </button>
                </div>
                <form class="contract-modal__form" @submit.prevent="submitCreate">
                    <div class="contract-modal__body">
                        <section class="contract-modal__section">
                            <h3 class="contract-modal__section-title">{{ t('contracts.sectionParties') }}</h3>
                            <div class="contract-modal__grid contract-modal__grid--2">
                                <div class="contract-modal__field">
                                    <label for="cm-vendor">{{ t('contracts.fieldVendor') }}</label>
                                    <input
                                        id="cm-vendor"
                                        v-model.trim="form.vendor_name"
                                        type="text"
                                        class="ppms-input"
                                        required
                                        autocomplete="organization"
                                        list="cm-vendor-list"
                                        :placeholder="t('contracts.fieldVendorPlaceholder')"
                                    />
                                    <datalist id="cm-vendor-list">
                                        <option v-for="v in lookups.vendors" :key="v.id" :value="v.name" />
                                    </datalist>
                                </div>
                                <div class="contract-modal__field">
                                    <label for="cm-product">{{ t('contracts.fieldProduct') }}</label>
                                    <input
                                        id="cm-product"
                                        v-model.trim="form.product_name"
                                        type="text"
                                        class="ppms-input"
                                        required
                                        :placeholder="t('contracts.fieldProductPlaceholder')"
                                    />
                                </div>
                                <div class="contract-modal__field contract-modal__field--full">
                                    <div class="contract-modal__dept-toolbar">
                                        <label for="cm-dept">{{ t('contracts.fieldDepartment') }}</label>
                                        <button
                                            type="button"
                                            class="ppms-btn-ghost ppms-btn-sm contract-modal__dept-add"
                                            @click="deptCreateOpen = !deptCreateOpen"
                                        >
                                            {{ deptCreateOpen ? t('contracts.addDepartmentClose') : t('contracts.addDepartment') }}
                                        </button>
                                    </div>
                                    <select id="cm-dept" v-model.number="form.department_id" class="ppms-input" required>
                                        <option disabled :value="0">{{ t('contracts.fieldDepartment') }}</option>
                                        <option v-for="d in lookups.departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                    </select>
                                    <div v-if="deptCreateOpen" class="contract-modal__dept-inline">
                                        <input
                                            v-model.trim="newDeptName"
                                            type="text"
                                            class="ppms-input"
                                            :placeholder="t('contracts.departmentNewNamePlaceholder')"
                                            maxlength="255"
                                        />
                                        <input
                                            v-model.trim="newDeptCode"
                                            type="text"
                                            class="ppms-input"
                                            :placeholder="t('contracts.departmentNewCodePlaceholder')"
                                            maxlength="64"
                                        />
                                        <button
                                            type="button"
                                            class="ppms-btn-primary ppms-btn-sm"
                                            :disabled="departmentSaving"
                                            @click="submitNewDepartment"
                                        >
                                            {{ t('contracts.departmentCreate') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="contract-modal__field contract-modal__field--full">
                                    <label>{{ t('contracts.fieldFollower') }}</label>
                                    <p class="ppms-hint contract-modal__field-hint">{{ t('contracts.fieldFollowerHint') }}</p>
                                    <O1UserLookupSelect
                                        v-model="form.followed_by_id"
                                        :base-users="[]"
                                        :search-placeholder="t('projects.createUserSearchPlaceholder')"
                                        :search-aria="t('contracts.followerSearchAria')"
                                        :min-hint="t('projects.createUserSearchMinHint')"
                                        :empty-text="t('projects.createUserSearchEmpty')"
                                        :loading-text="t('common.loading')"
                                        :clear-aria="t('contracts.followerClearAria')"
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="contract-modal__section">
                            <h3 class="contract-modal__section-title">{{ t('contracts.sectionScope') }}</h3>
                            <div class="contract-modal__field">
                                <label for="cm-scope">{{ t('contracts.fieldScope') }}</label>
                                <textarea id="cm-scope" v-model="form.scope" class="ppms-input contract-modal__textarea" rows="3" />
                            </div>
                        </section>

                        <section class="contract-modal__section">
                            <h3 class="contract-modal__section-title">{{ t('contracts.sectionTerm') }}</h3>
                            <div class="contract-modal__grid contract-modal__grid--2">
                                <div class="contract-modal__field">
                                    <label for="cm-start">{{ t('contracts.fieldStart') }}</label>
                                    <input id="cm-start" v-model="form.start_date" type="date" class="ppms-input" required />
                                </div>
                                <div class="contract-modal__field">
                                    <label for="cm-end">{{ t('contracts.fieldEnd') }}</label>
                                    <input id="cm-end" v-model="form.end_date" type="date" class="ppms-input" required />
                                </div>
                                <div class="contract-modal__field">
                                    <label for="cm-value">{{ t('contracts.fieldValue') }}</label>
                                    <input
                                        id="cm-value"
                                        v-model="totalValueDisplay"
                                        type="text"
                                        class="ppms-input"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        required
                                        placeholder="0"
                                    />
                                    <p v-if="totalValueInWords" class="contract-modal__amount-words">{{ totalValueInWords }}</p>
                                </div>
                                <div class="contract-modal__field">
                                    <label for="cm-cycle">{{ t('contracts.fieldCycle') }}</label>
                                    <select id="cm-cycle" v-model="form.payment_cycle" class="ppms-input" required>
                                        <option value="monthly">{{ t('contracts.cycleMonthly') }}</option>
                                        <option value="quarterly">{{ t('contracts.cycleQuarterly') }}</option>
                                        <option value="yearly">{{ t('contracts.cycleYearly') }}</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="contract-modal__section contract-modal__section--attachments" :aria-label="t('contracts.sectionAttachments')">
                            <div class="contract-modal__section-head">
                                <h3 class="contract-modal__section-title">{{ t('contracts.sectionAttachments') }}</h3>
                                <span v-if="pendingFiles.length" class="contract-modal__attach-badge">{{ t('contracts.attachCount', { n: pendingFiles.length }) }}</span>
                            </div>
                            <p class="contract-modal__attach-hint">{{ t('contracts.attachHint', { max: attachMaxFiles, sizeMb: attachMaxSizeMb }) }}</p>
                            <div
                                class="contract-modal__dropzone"
                                :class="{ 'contract-modal__dropzone--active': dropzoneActive }"
                                tabindex="0"
                                @click="onDropzoneClick"
                                @keydown.enter.prevent="openFilePicker"
                                @keydown.space.prevent="openFilePicker"
                                @dragenter.prevent="onDragEnterZone"
                                @dragover.prevent="onDragOverZone"
                                @dragleave.prevent="onDragLeaveZone"
                                @drop.prevent="onDropFiles"
                            >
                                <input
                                    id="cm-files"
                                    ref="fileInputRef"
                                    type="file"
                                    class="ppms-sr-only"
                                    multiple
                                    @change="onFileInputChange"
                                />
                                <div class="contract-modal__dropzone-inner" aria-hidden="true">
                                    <svg class="contract-modal__dropzone-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M12 4v12m0 0l-3.5-3.5M12 16l3.5-3.5M4 15v2a2 2 0 002 2h12a2 2 0 002-2v-2"
                                            stroke="currentColor"
                                            stroke-width="1.75"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </div>
                                <p class="contract-modal__dropzone-text">{{ t('contracts.attachDropzone') }}</p>
                                <button
                                    type="button"
                                    class="ppms-btn-ghost contract-modal__browse-btn"
                                    @click.stop.prevent="openFilePicker"
                                >
                                    {{ t('contracts.attachBrowse') }}
                                </button>
                            </div>

                            <ul v-if="pendingFiles.length" class="contract-modal__file-list" role="list">
                                <li v-for="f in pendingFiles" :key="f.uid" class="contract-modal__file-item" role="listitem">
                                    <div class="contract-modal__file-preview">
                                        <img v-if="f.kind === 'image' && f.previewUrl" :src="f.previewUrl" alt="" class="contract-modal__thumb" />
                                        <iframe
                                            v-else-if="f.kind === 'pdf' && f.previewUrl"
                                            :src="f.previewUrl"
                                            class="contract-modal__pdf-frame"
                                            :title="f.name"
                                        />
                                        <div v-else class="contract-modal__file-placeholder" aria-hidden="true">
                                            <span class="contract-modal__file-doc-icon" />
                                        </div>
                                    </div>
                                    <div class="contract-modal__file-meta">
                                        <span class="contract-modal__file-name" :title="f.name">{{ f.name }}</span>
                                        <span class="contract-modal__file-size">{{ formatFileSize(f.size) }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="contract-modal__file-remove ppms-btn-ghost ppms-btn-sm"
                                        :disabled="createBusy"
                                        @click="removePendingFile(f.uid)"
                                    >
                                        {{ t('contracts.attachRemove') }}
                                    </button>
                                </li>
                            </ul>
                        </section>
                    </div>
                    <div class="contract-modal__footer">
                        <p v-if="formError" class="ppms-error contract-modal__error">{{ formError }}</p>
                        <div class="contract-modal__actions">
                            <button type="button" class="ppms-btn-ghost" :disabled="createBusy" @click="modalOpen = false">
                                {{ t('common.cancel') }}
                            </button>
                            <button type="submit" class="ppms-btn-primary contract-modal__submit" :disabled="createBusy">
                                <span v-if="saving">{{ t('contracts.modalSaving') }}</span>
                                <span v-else-if="uploadingAttachments">{{ t('contracts.attachUploading') }}</span>
                                <span v-else>{{ t('common.save') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';
import { readVndAmountVietnamese } from '@/utils/vndReadWords';
import O1UserLookupSelect from '@/pages/projects/components/detail/O1UserLookupSelect.vue';

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();

const loading = ref(true);
const saving = ref(false);
const currentUser = ref(null);
const trashMode = ref(false);
const items = ref([]);
const meta = ref(null);
const page = ref(1);
const perPage = ref(25);
const sortKey = ref('id');
const sortOrder = ref('desc');

const filters = reactive({
    status: '',
    vendor_id: 0,
    department_id: 0,
    end_from: '',
    end_to: '',
});

const modalOpen = ref(false);
const formError = ref('');

const ATTACH_MAX_FILES = 10;
const attachMaxFiles = ATTACH_MAX_FILES;
/** KB — align with config `ppms.upload_max_file_kb` default */
const ATTACH_MAX_KB = 51200;
const attachMaxSizeMb = Math.round(ATTACH_MAX_KB / 1024);

const pendingFiles = ref([]);
const dropzoneActive = ref(false);
const uploadingAttachments = ref(false);
const fileInputRef = ref(null);
let dragDepth = 0;

const departmentSaving = ref(false);
const deptCreateOpen = ref(false);
const newDeptName = ref('');
const newDeptCode = ref('');

const createBusy = computed(() => saving.value || uploadingAttachments.value || departmentSaving.value);

const lookups = reactive({
    vendors: [],
    products: [],
    departments: [],
});

const form = reactive({
    vendor_name: '',
    product_name: '',
    department_id: 0,
    scope: '',
    start_date: '',
    end_date: '',
    total_value: '',
    payment_cycle: 'monthly',
    followed_by_id: '',
});

/** Chỉ chữ số — dùng cho API; ô nhập format kiểu vi-VN qua computed */
const totalValueDisplay = computed({
    get() {
        if (!form.total_value) return '';
        const n = Number(form.total_value);
        if (!Number.isFinite(n)) return form.total_value;
        return new Intl.NumberFormat('vi-VN').format(n);
    },
    set(raw) {
        form.total_value = String(raw ?? '').replace(/\D/g, '');
    },
});

const totalValueInWords = computed(() => {
    if (!form.total_value) return '';
    const n = Number(form.total_value);
    if (!Number.isFinite(n) || n < 0) return '';
    if (locale.value === 'vi') {
        return `${t('contracts.amountInWordsPrefix')}: ${readVndAmountVietnamese(n)}`;
    }
    return `${t('contracts.amountInWordsPrefix')}: ${formatMoney(n)}`;
});

const vendorGroups = computed(() => {
    const map = new Map();
    for (const row of items.value) {
        const key = row.vendor_id != null ? `v-${row.vendor_id}` : `n-${row.vendor?.name ?? '—'}`;
        if (!map.has(key)) {
            map.set(key, {
                key,
                vendorName: row.vendor?.name || '—',
                items: [],
            });
        }
        map.get(key).items.push(row);
    }
    const loc = locale.value === 'vi' ? 'vi' : 'en';
    return Array.from(map.values()).sort((a, b) => a.vendorName.localeCompare(b.vendorName, loc));
});

const collapsedVendorKeys = ref({});

function toggleVendorGroup(key) {
    collapsedVendorKeys.value = { ...collapsedVendorKeys.value, [key]: !collapsedVendorKeys.value[key] };
}

function isVendorGroupCollapsed(key) {
    return !!collapsedVendorKeys.value[key];
}

const lookupWarning = computed(() => lookups.departments.length === 0);

const vendorsEmpty = computed(() => lookups.vendors.length === 0);

const isAdmin = computed(() => currentUser.value?.role === 'admin');

const hasActiveFilters = computed(() => {
    if (trashMode.value) {
        return false;
    }
    return !!(
        filters.status ||
        filters.vendor_id ||
        filters.department_id ||
        filters.end_from ||
        filters.end_to
    );
});

const SORT_KEYS = ['id', 'code', 'end_date', 'start_date', 'total_value', 'status', 'updated_at', 'followed_by_id'];

function clampPerPage(n) {
    return Math.min(100, Math.max(10, n));
}

function buildApiParams() {
    if (trashMode.value) {
        return {
            page: page.value,
            per_page: perPage.value,
        };
    }
    return {
        page: page.value,
        per_page: perPage.value,
        status: filters.status || undefined,
        vendor_id: filters.vendor_id || undefined,
        department_id: filters.department_id || undefined,
        end_from: filters.end_from || undefined,
        end_to: filters.end_to || undefined,
        sort: sortKey.value,
        order: sortOrder.value,
    };
}

function buildRouteQuery() {
    const p = buildApiParams();
    const q = {};
    if (trashMode.value) {
        if (p.page > 1) q.page = String(p.page);
        if (p.per_page !== 25) q.per_page = String(p.per_page);
        q.trash = '1';
        return q;
    }
    if (p.page > 1) q.page = String(p.page);
    if (p.per_page !== 25) q.per_page = String(p.per_page);
    if (p.status) q.status = p.status;
    if (p.vendor_id) q.vendor_id = String(p.vendor_id);
    if (p.department_id) q.department_id = String(p.department_id);
    if (p.end_from) q.end_from = p.end_from;
    if (p.end_to) q.end_to = p.end_to;
    if (p.sort && p.sort !== 'id') q.sort = p.sort;
    if (p.order && p.order !== 'desc') q.order = p.order;
    return q;
}

function readQueryFromRoute(q = route.query) {
    trashMode.value = q.trash === '1' || q.trash === 1;
    page.value = q.page ? Math.max(1, parseInt(String(q.page), 10)) : 1;
    perPage.value = q.per_page ? clampPerPage(parseInt(String(q.per_page), 10)) : 25;
    filters.status = q.status ? String(q.status) : '';
    filters.vendor_id = q.vendor_id ? Number(q.vendor_id) : 0;
    filters.department_id = q.department_id ? Number(q.department_id) : 0;
    filters.end_from = q.end_from ? String(q.end_from) : '';
    filters.end_to = q.end_to ? String(q.end_to) : '';
    const s = q.sort ? String(q.sort) : 'id';
    sortKey.value = SORT_KEYS.includes(s) ? s : 'id';
    sortOrder.value = q.order === 'asc' ? 'asc' : 'desc';
}

async function replaceRouteQuery() {
    const before = route.fullPath;
    await router.replace({ path: route.path, query: buildRouteQuery() });
    if (route.fullPath === before) {
        readQueryFromRoute();
        await load();
    }
}

function statusLabel(status) {
    const map = {
        draft: 'contracts.statusDraft',
        pending_approval: 'contracts.statusPending',
        active: 'contracts.statusActive',
        expired: 'contracts.statusExpired',
        terminated: 'contracts.statusTerminated',
    };
    return map[status] ? t(map[status]) : status;
}

function statusPillClass(status) {
    return {
        'contract-list__pill--draft': status === 'draft',
        'contract-list__pill--pending': status === 'pending_approval',
        'contract-list__pill--active': status === 'active',
        'contract-list__pill--expired': status === 'expired',
        'contract-list__pill--terminated': status === 'terminated',
    };
}

function formatMoney(value) {
    const n = Number(value);
    if (Number.isNaN(n)) {
        return value ?? '—';
    }
    const formatted = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(n);
    return `${formatted} VNĐ`;
}

function formatUpdatedAt(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '—';
    return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(d);
}

function parseDateOnly(str) {
    if (!str) return null;
    const d = new Date(`${str}T12:00:00`);
    return Number.isNaN(d.getTime()) ? null : d;
}

/** Tiến độ thời hạn theo khoảng start→end; null = không hiển thị thanh */
function periodProgressPercent(row) {
    const start = parseDateOnly(row.start_date);
    const end = parseDateOnly(row.end_date);
    if (!start || !end) return null;
    const span = end.getTime() - start.getTime();
    if (span <= 0) return 100;
    if (row.status === 'terminated' || row.status === 'expired') {
        return 100;
    }
    const now = new Date();
    now.setHours(12, 0, 0, 0);
    const t = now.getTime();
    if (t <= start.getTime()) return 0;
    if (t >= end.getTime()) return 100;
    return Math.min(100, Math.max(0, Math.round(((t - start.getTime()) / span) * 100)));
}

function daysUntilEnd(endDateStr) {
    if (!endDateStr) return null;
    const end = new Date(`${endDateStr}T12:00:00`);
    const now = new Date();
    now.setHours(12, 0, 0, 0);
    return Math.ceil((end.getTime() - now.getTime()) / 86400000);
}

function expiresBadge(row) {
    if (row.status !== 'active') return '';
    const d = daysUntilEnd(row.end_date);
    if (d === null || Number.isNaN(d)) return '';
    if (d < 0) return '';
    if (d <= 30) {
        return d === 0 ? t('contracts.expiresSoon') : t('contracts.expiresInDays', { n: d });
    }
    return '';
}

async function toggleSort(key) {
    if (trashMode.value) {
        return;
    }
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortOrder.value = key === 'code' ? 'asc' : 'desc';
    }
    page.value = 1;
    await replaceRouteQuery();
}

async function onFilterCommit() {
    page.value = 1;
    await replaceRouteQuery();
}

async function onPerPageChange() {
    perPage.value = clampPerPage(perPage.value);
    page.value = 1;
    await replaceRouteQuery();
}

async function resetFilters() {
    filters.status = '';
    filters.vendor_id = 0;
    filters.department_id = 0;
    filters.end_from = '';
    filters.end_to = '';
    page.value = 1;
    perPage.value = 25;
    sortKey.value = 'id';
    sortOrder.value = 'desc';
    await replaceRouteQuery();
}

function refresh() {
    load();
}

function goDetail(id) {
    router.push(`/contracts/${id}`);
}

async function goPrevPage() {
    if (page.value <= 1) return;
    page.value -= 1;
    await replaceRouteQuery();
}

async function goNextPage() {
    if (!meta.value || page.value >= meta.value.last_page) return;
    page.value += 1;
    await replaceRouteQuery();
}

async function loadUser() {
    try {
        const { data } = await axios.get('/api/user');
        currentUser.value = data;
    } catch {
        currentUser.value = null;
    }
}

async function toggleTrashMode() {
    if (!isAdmin.value) {
        return;
    }
    trashMode.value = !trashMode.value;
    page.value = 1;
    await replaceRouteQuery();
}

async function restoreRow(row) {
    if (!(await ppmsConfirm(t('contracts.trashRestoreConfirm')))) {
        return;
    }
    try {
        await axios.post(`/api/contracts/${row.id}/restore`);
        ppmsToastSuccess(t('contracts.trashRestored'));
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

async function forceDeleteRow(row) {
    if (!(await ppmsConfirm(t('contracts.trashForceDeleteConfirm')))) {
        return;
    }
    try {
        await axios.delete(`/api/contracts/${row.id}/force`);
        ppmsToastSuccess(t('contracts.trashForceDeleted'));
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

async function deleteContractRow(row) {
    const key =
        row.status && row.status !== 'draft' ? 'contracts.deleteConfirmAdmin' : 'contracts.deleteConfirm';
    if (!(await ppmsConfirm(t(key)))) {
        return;
    }
    try {
        await axios.delete(`/api/contracts/${row.id}`);
        ppmsToastSuccess(t('contracts.deletedToTrash'));
        await load();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

async function load() {
    loading.value = true;
    try {
        const url = trashMode.value ? '/api/contracts/trash' : '/api/contracts';
        const { data } = await axios.get(url, { params: buildApiParams() });
        items.value = data.data || [];
        meta.value = data.meta || null;
    } catch (e) {
        items.value = [];
        meta.value = null;
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    } finally {
        loading.value = false;
    }
}

async function loadLookups() {
    try {
        const { data } = await axios.get('/api/contract-lookups');
        lookups.vendors = data.vendors || [];
        lookups.products = data.products || [];
        lookups.departments = data.departments || [];
    } catch {
        lookups.vendors = [];
        lookups.products = [];
        lookups.departments = [];
    }
}

async function submitNewDepartment() {
    const name = newDeptName.value.trim();
    if (!name) {
        ppmsToastError(t('contracts.departmentNameRequired'));
        return;
    }
    departmentSaving.value = true;
    try {
        const { data } = await axios.post('/api/departments', {
            name,
            code: newDeptCode.value.trim() || null,
        });
        const d = data.data ?? data;
        await loadLookups();
        if (d?.id != null) {
            form.department_id = d.id;
        }
        deptCreateOpen.value = false;
        newDeptName.value = '';
        newDeptCode.value = '';
        ppmsToastSuccess(t('contracts.departmentCreated'));
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    } finally {
        departmentSaving.value = false;
    }
}

function newPendingUid() {
    return typeof crypto !== 'undefined' && crypto.randomUUID
        ? crypto.randomUUID()
        : `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

function fileKind(mime) {
    const m = mime || '';
    if (m.startsWith('image/')) return 'image';
    if (m === 'application/pdf') return 'pdf';
    return 'other';
}

function formatFileSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function clearPendingFiles() {
    pendingFiles.value.forEach((p) => {
        if (p.previewUrl) URL.revokeObjectURL(p.previewUrl);
    });
    pendingFiles.value = [];
}

function removePendingFile(uid) {
    const i = pendingFiles.value.findIndex((p) => p.uid === uid);
    if (i === -1) return;
    const p = pendingFiles.value[i];
    if (p.previewUrl) URL.revokeObjectURL(p.previewUrl);
    pendingFiles.value.splice(i, 1);
}

function addPendingFiles(fileList) {
    const list = Array.from(fileList);
    let skippedLarge = 0;
    for (const file of list) {
        if (pendingFiles.value.length >= ATTACH_MAX_FILES) {
            ppmsToastError(t('contracts.attachMaxFiles', { max: ATTACH_MAX_FILES }));
            break;
        }
        if (file.size > ATTACH_MAX_KB * 1024) {
            skippedLarge++;
            continue;
        }
        const kind = fileKind(file.type || '');
        let previewUrl = null;
        if (kind === 'image' || kind === 'pdf') {
            previewUrl = URL.createObjectURL(file);
        }
        pendingFiles.value.push({
            uid: newPendingUid(),
            file,
            name: file.name,
            size: file.size,
            previewUrl,
            kind,
        });
    }
    if (skippedLarge) {
        ppmsToastError(t('contracts.attachTooLarge', { sizeMb: attachMaxSizeMb }));
    }
}

function onDragOverZone(e) {
    e.preventDefault();
}

function onDragEnterZone(e) {
    e.preventDefault();
    dragDepth++;
    dropzoneActive.value = true;
}

function onDragLeaveZone(e) {
    e.preventDefault();
    dragDepth = Math.max(0, dragDepth - 1);
    if (dragDepth === 0) {
        dropzoneActive.value = false;
    }
}

function onDropFiles(e) {
    e.preventDefault();
    dragDepth = 0;
    dropzoneActive.value = false;
    if (e.dataTransfer?.files?.length) {
        addPendingFiles(e.dataTransfer.files);
    }
}

function openFilePicker() {
    fileInputRef.value?.click();
}

function onDropzoneClick(e) {
    if (e.target?.closest?.('.contract-modal__browse-btn')) return;
    openFilePicker();
}

function onFileInputChange(e) {
    const el = e.target;
    if (el instanceof HTMLInputElement && el.files?.length) {
        addPendingFiles(el.files);
        el.value = '';
    }
}

function openCreate() {
    clearPendingFiles();
    dragDepth = 0;
    dropzoneActive.value = false;
    formError.value = '';
    form.vendor_name = '';
    form.product_name = '';
    form.department_id = lookups.departments[0]?.id || 0;
    form.scope = '';
    form.start_date = '';
    form.end_date = '';
    form.total_value = '';
    form.payment_cycle = 'monthly';
    form.followed_by_id = '';
    deptCreateOpen.value = false;
    newDeptName.value = '';
    newDeptCode.value = '';
    modalOpen.value = true;
}

async function submitCreate() {
    formError.value = '';
    saving.value = true;
    try {
        const createPayload = {
            vendor_name: form.vendor_name,
            product_name: form.product_name,
            department_id: form.department_id,
            scope: form.scope || null,
            start_date: form.start_date,
            end_date: form.end_date,
            total_value: form.total_value === '' ? 0 : Number(form.total_value),
            payment_cycle: form.payment_cycle,
        };
        if (form.followed_by_id) {
            createPayload.followed_by_id = Number(form.followed_by_id);
        }
        const { data: body } = await axios.post('/api/contracts', createPayload);
        const contractId = body.data?.id ?? body.id;
        if (contractId == null) {
            formError.value = t('contracts.loadError');
            return;
        }
        const filesToUpload = [...pendingFiles.value];
        if (filesToUpload.length) {
            uploadingAttachments.value = true;
            let failed = 0;
            for (const p of filesToUpload) {
                try {
                    const fd = new FormData();
                    fd.append('file', p.file);
                    await axios.post(`/api/contracts/${contractId}/files`, fd);
                } catch {
                    failed++;
                }
            }
            uploadingAttachments.value = false;
            if (failed > 0) {
                ppmsToastError(
                    failed === 1 ? t('contracts.attachUploadPartialFailOne') : t('contracts.attachUploadPartialFail', { n: failed }),
                );
            }
        }
        clearPendingFiles();
        ppmsToastSuccess(t('contracts.created'));
        modalOpen.value = false;
        await load();
    } catch (e) {
        formError.value = formatApiUserMessage(e, t('contracts.loadError'));
    } finally {
        saving.value = false;
        uploadingAttachments.value = false;
    }
}

watch(modalOpen, (open) => {
    if (!open) {
        clearPendingFiles();
        dragDepth = 0;
        dropzoneActive.value = false;
    }
});

async function downloadCsv() {
    try {
        const { data, headers } = await axios.get('/api/contracts/export.csv', {
            params: {
                status: filters.status || undefined,
                vendor_id: filters.vendor_id || undefined,
                department_id: filters.department_id || undefined,
                end_from: filters.end_from || undefined,
                end_to: filters.end_to || undefined,
                sort: sortKey.value,
                order: sortOrder.value,
            },
            responseType: 'blob',
        });
        const blob = new Blob([data], { type: 'text/csv;charset=utf-8' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        const cd = headers['content-disposition'] || headers['Content-Disposition'];
        let filename = 'contracts.csv';
        if (cd && typeof cd === 'string') {
            const m = /filename="?([^";]+)"?/i.exec(cd);
            if (m?.[1]) filename = m[1];
        }
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('contracts.loadError')));
    }
}

watch(
    () => route.fullPath,
    () => {
        if (route.name !== 'contracts') return;
        readQueryFromRoute();
        load();
    },
);

onMounted(async () => {
    await loadUser();
    readQueryFromRoute();
    if (trashMode.value && !isAdmin.value) {
        trashMode.value = false;
        await router.replace({ path: route.path, query: {} });
    }
    await loadLookups();
    await load();
});
</script>

<style scoped>
/* —— Page —— */
.contract-list__intro {
    margin-bottom: 20px;
}
.contract-list__title {
    margin: 0 0 8px;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ppms-fg, #0f172a);
}
.contract-list__desc {
    margin: 0;
    max-width: 52rem;
    line-height: 1.55;
    color: var(--ppms-muted, #64748b);
    font-size: 0.95rem;
}

.contract-list__card {
    padding: 0;
    overflow: hidden;
}

/* —— Toolbar & filters —— */
.contract-list__toolbar {
    padding: 0;
    border-bottom: 1px solid var(--ppms-border, #e2e8f0);
    background: linear-gradient(165deg, #f8fafc 0%, #f1f5f9 48%, #fff 100%);
}

.contract-list__toolbar-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px 20px;
    padding: 20px 20px 16px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.85);
}

.contract-list__toolbar-intro {
    flex: 1;
    min-width: min(100%, 240px);
    max-width: 36rem;
}

.contract-list__filter-heading {
    margin: 0 0 4px;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ppms-fg, #0f172a);
}

.contract-list__filter-sub {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.45;
    color: var(--ppms-muted, #64748b);
}

.contract-list__toolbar-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
}

.contract-list__toolbar-actions-secondary {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    padding-right: 4px;
    margin-right: 2px;
    border-right: 1px solid var(--ppms-border, #e2e8f0);
}

.contract-list__action-btn {
    min-height: 40px;
}

.contract-list__action-primary {
    min-height: 40px;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}

.contract-list__filter-groups {
    display: grid;
    grid-template-columns: minmax(0, 2.1fr) minmax(0, 1.4fr) minmax(0, 0.85fr);
    gap: 14px;
    padding: 16px 20px 20px;
    align-items: stretch;
}

@media (max-width: 1100px) {
    .contract-list__filter-groups {
        grid-template-columns: 1fr 1fr;
    }
    .contract-list__filter-card--compact {
        grid-column: 1 / -1;
    }
}

@media (max-width: 720px) {
    .contract-list__toolbar-head {
        flex-direction: column;
        align-items: stretch;
    }
    .contract-list__toolbar-actions {
        flex-direction: column;
        align-items: stretch;
    }
    .contract-list__toolbar-actions-secondary {
        border-right: none;
        padding-right: 0;
        margin-right: 0;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--ppms-border, #e2e8f0);
        justify-content: stretch;
    }
    .contract-list__toolbar-actions-secondary .contract-list__action-btn {
        flex: 1;
        justify-content: center;
    }
    .contract-list__filter-groups {
        grid-template-columns: 1fr;
        padding: 12px 16px 18px;
    }
}

.contract-list__filter-card {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 14px 14px 16px;
    border-radius: 12px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    min-width: 0;
}

.contract-list__filter-card-title {
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--ppms-muted, #64748b);
    margin: 0;
    padding-bottom: 2px;
    border-bottom: 1px solid #f1f5f9;
}

.contract-list__filter-card-grid {
    display: grid;
    gap: 12px;
}

.contract-list__filter-card-grid--3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

@media (max-width: 600px) {
    .contract-list__filter-card-grid--3 {
        grid-template-columns: 1fr;
    }
}

.contract-list__date-range {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
}

.contract-list__field--date {
    flex: 1;
    min-width: min(100%, 160px);
}

.contract-list__date-sep {
    flex-shrink: 0;
    padding-bottom: 10px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--ppms-muted, #94a3b8);
    text-transform: lowercase;
}

.contract-list__field-hint {
    margin: 6px 0 0;
    font-size: 0.75rem;
    line-height: 1.4;
    color: var(--ppms-muted, #64748b);
}

.contract-list__label {
    display: block;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #475569;
    margin-bottom: 8px;
}

.contract-list__input {
    width: 100%;
    min-height: 44px;
    padding: 10px 14px;
    font-size: 0.9375rem;
    line-height: 1.45;
    color: #0f172a;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        background-color 0.15s ease;
}

.contract-list__input::placeholder {
    color: #94a3b8;
}

.contract-list__input:hover:not(:disabled) {
    border-color: #cbd5e1;
}

.contract-list__input:focus {
    outline: none;
    border-color: rgba(79, 70, 229, 0.55);
    box-shadow:
        0 0 0 1px rgba(79, 70, 229, 0.2),
        0 0 0 4px rgba(79, 70, 229, 0.12);
}

.contract-list__input:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    background: #f8fafc;
}

.contract-list__field select.contract-list__input {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    padding: 10px 2.5rem 10px 14px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 1.1rem;
    cursor: pointer;
}

.contract-list__field input[type='date'].contract-list__input {
    color-scheme: light;
    min-height: 44px;
}

.contract-list__hint {
    margin: 0;
    padding: 12px 20px 0;
    font-size: 0.9rem;
    color: var(--ppms-muted, #64748b);
}
.contract-list__loading {
    margin: 16px 20px;
}

/* —— Empty —— */
.contract-list__empty {
    text-align: center;
    padding: 40px 24px 48px;
}
.contract-list__empty-visual {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    border-radius: 16px;
    background: linear-gradient(135deg, #e0e7ff 0%, #f1f5f9 100%);
    border: 1px solid var(--ppms-border, #e2e8f0);
}
.contract-list__empty-title {
    margin: 0 0 8px;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--ppms-fg, #0f172a);
}
.contract-list__empty-desc {
    margin: 0 0 20px;
    font-size: 0.95rem;
    color: var(--ppms-muted, #64748b);
}
.contract-list__empty-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

/* —— Table —— */
.contract-list__table-wrap {
    margin: 0;
}
.contract-list__table {
    margin: 0;
}
.contract-list__th-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin: 0;
    padding: 0;
    border: none;
    background: none;
    font: inherit;
    font-weight: 600;
    color: inherit;
    cursor: pointer;
    text-align: left;
}
.contract-list__th-btn:hover {
    color: var(--ppms-accent, #4f46e5);
}
.contract-list__sort-ind {
    font-size: 0.85em;
    opacity: 0.85;
}
.contract-list__th-stack {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}
.contract-list__th-sub {
    font-size: 0.6875rem;
    font-weight: 500;
    color: var(--ppms-muted, #64748b);
    letter-spacing: 0.02em;
}
.contract-list__vendor-group td {
    padding: 8px 12px;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid var(--ppms-border, #e2e8f0);
}
.contract-list__group-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding: 4px 0;
    border: none;
    background: none;
    font: inherit;
    cursor: pointer;
    color: var(--ppms-fg, #0f172a);
    text-align: left;
}
.contract-list__group-toggle:hover {
    color: var(--ppms-accent, #4f46e5);
}
.contract-list__group-chevron {
    display: inline-block;
    font-size: 0.65rem;
    line-height: 1;
    transition: transform 0.15s ease;
}
.contract-list__group-chevron--collapsed {
    transform: rotate(-90deg);
}
.contract-list__group-count {
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--ppms-muted, #64748b);
}
.contract-list__period-cell {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}
.contract-list__period-progress {
    display: flex;
    align-items: center;
    gap: 8px;
}
.contract-list__period-bar-track {
    flex: 1;
    min-width: 48px;
    max-width: 128px;
    height: 6px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}
.contract-list__period-bar-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #818cf8, #4f46e5);
}
.contract-list__period-pct {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--ppms-muted, #64748b);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.contract-list__row {
    cursor: pointer;
    transition: background 0.12s ease;
}
.contract-list__row:hover {
    background: rgba(79, 70, 229, 0.04);
}
.contract-list__link {
    font-weight: 600;
    color: var(--ppms-accent, #4f46e5);
    text-decoration: none;
}
.contract-list__link:hover {
    text-decoration: underline;
}
.contract-list__code-muted {
    font-weight: 600;
    color: var(--ppms-muted, #64748b);
}
.contract-list__btn-danger {
    color: #b91c1c !important;
    margin-left: 4px;
}
.contract-list__cell-muted {
    color: var(--ppms-muted, #64748b);
    font-size: 0.92em;
}
.contract-list__cell-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.contract-list__cell-nowrap {
    white-space: nowrap;
}
.contract-list__period {
    font-size: 0.92rem;
    white-space: nowrap;
}
.contract-list__pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}
.contract-list__pill--draft {
    background: #f1f5f9;
    color: #475569;
}
.contract-list__pill--pending {
    background: #fef3c7;
    color: #b45309;
}
.contract-list__pill--active {
    background: #d1fae5;
    color: #047857;
}
.contract-list__pill--expired {
    background: #e2e8f0;
    color: #475569;
}
.contract-list__pill--terminated {
    background: #fee2e2;
    color: #b91c1c;
}
.contract-list__badge-soon {
    display: inline-block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #c2410c;
    background: #ffedd5;
    padding: 2px 8px;
    border-radius: 6px;
}

/* —— Footer / pagination —— */
.contract-list__footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 20px 18px;
    border-top: 1px solid var(--ppms-border, #e2e8f0);
    background: rgba(248, 250, 252, 0.6);
}
.contract-list__meta {
    margin: 0;
    font-size: 0.9rem;
    color: var(--ppms-muted, #64748b);
}
.contract-list__meta-sep {
    margin: 0 6px;
    opacity: 0.5;
}
.contract-list__pagination {
    display: flex;
    align-items: center;
    gap: 12px;
}
.contract-list__page-label {
    font-size: 0.9rem;
    font-variant-numeric: tabular-nums;
    color: var(--ppms-fg, #0f172a);
}

</style>
<style src="./contract-modal.css"></style>
