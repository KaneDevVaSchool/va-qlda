<!-- eslint-disable vue/no-mutating-props -- form is parent-owned reactive -->
<template>
    <div
        v-if="open"
        class="ppms-modal-backdrop ppms-modal-backdrop--project-create"
        role="presentation"
        @click.self="onBackdropClick"
    >
        <div
            id="main-content"
            class="ppms-modal ppms-modal--project-create"
            role="dialog"
            aria-modal="true"
            aria-labelledby="ppms-modal-create-project-title"
            @click.stop
        >
            <div
                v-if="!form.editingId && draftBannerVisible && storedDraftMeta"
                class="ppms-pc-draft-banner"
                role="status"
            >
                <div class="ppms-pc-draft-banner__text">
                    <strong>{{ t('projects.draftBannerTitle') }}</strong>
                    <span class="ppms-pc-draft-banner__meta">{{ draftSavedAtLabel }}</span>
                </div>
                <div class="ppms-pc-draft-banner__actions">
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="dismissDraftBanner">
                        {{ t('projects.draftBannerDismiss') }}
                    </button>
                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="deleteStoredDraft">
                        {{ t('projects.draftBannerDelete') }}
                    </button>
                    <button type="button" class="ppms-btn-primary ppms-btn-sm" @click="restoreStoredDraft">
                        {{ t('projects.draftBannerRestore') }}
                    </button>
                </div>
            </div>
            <div class="ppms-pc-modal-head ppms-pc-modal-head--toolbar">
                <div class="ppms-pc-modal-head__main">
                    <h2 id="ppms-modal-create-project-title">{{ modalTitle }}</h2>
                    <p class="ppms-pc-modal-subtitle">{{ modalSubtitle }}</p>
                </div>
                <button
                    type="button"
                    class="ppms-pc-modal-close"
                    :aria-label="t('common.close')"
                    :title="t('projects.modalCloseHint')"
                    @click="tryClose"
                >
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form class="ppms-pc-form" @submit.prevent="emit('submit')">
                <div class="ppms-pc-form-body">
                    <section class="ppms-pc-card" aria-labelledby="ppms-pc-card-project">
                        <h3 id="ppms-pc-card-project" class="ppms-pc-card__title">{{ t('projects.createCardProjectCore') }}</h3>

                        <h4 class="ppms-pc-subsection-title">{{ t('projects.createSectionInfoStatus') }}</h4>
                        <p class="ppms-pc-section-lead">{{ t('projects.createSectionInfoStatusLead') }}</p>
                        <div class="ppms-pc-row">
                            <label class="ppms-field ppms-pc-col ppms-pc-col--4">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.createFieldCode') }}</span>
                                </div>
                                <input
                                    v-model="form.project_code"
                                    type="text"
                                    disabled
                                    name="code"
                                    autocomplete="off"
                                    :placeholder="t('projects.createFieldCodePh')"
                                />
                                <p class="ppms-pc-field-hint">{{ t('projects.createFieldCodeHint') }}</p>
                            </label>
                            <label class="ppms-field ppms-pc-col ppms-pc-col--8">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.fieldName') }}</span>
                                    <span class="ppms-req" aria-hidden="true">*</span>
                                </div>
                                <input
                                    v-model="form.name"
                                    name="name"
                                    required
                                    autocomplete="off"
                                    :placeholder="t('projects.createFieldNamePh')"
                                />
                            </label>
                        </div>

                        <div class="ppms-pc-row ppms-pc-row--info-grid">
                            <label class="ppms-field ppms-pc-col ppms-pc-col--4">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.fieldType') }}</span>
                                    <span class="ppms-req" aria-hidden="true">*</span>
                                </div>
                                <select v-model="form.type" class="ppms-pc-select" name="type" required>
                                    <option value="maintenance">{{ t('projects.type.maintenance') }}</option>
                                    <option value="delivery">{{ t('projects.type.delivery') }}</option>
                                    <option value="rnd">{{ t('projects.type.rnd') }}</option>
                                </select>
                                <p class="ppms-pc-field-hint">{{ t('projects.createFieldTypeHint') }}</p>
                            </label>
                            <label class="ppms-field ppms-pc-col ppms-pc-col--4">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.colPhase') }}</span>
                                </div>
                                <select v-model="form.phase" class="ppms-pc-select" name="phase">
                                    <option value="planning">{{ t('projects.phase.planning') }}</option>
                                    <option value="development">{{ t('projects.phase.development') }}</option>
                                    <option value="uat">{{ t('projects.phase.uat') }}</option>
                                    <option value="done">{{ t('projects.phase.done') }}</option>
                                    <option value="maintenance">{{ t('projects.phase.maintenance') }}</option>
                                    <option value="rnd">{{ t('projects.phase.rnd') }}</option>
                                </select>
                                <p class="ppms-pc-field-hint">{{ t('projects.createFieldPhaseHint') }}</p>
                            </label>
                            <label class="ppms-field ppms-pc-col ppms-pc-col--4">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.colStatus') }}</span>
                                </div>
                                <select v-model="form.status" class="ppms-pc-select" name="status">
                                    <option value="on_track">{{ t('projects.status.on_track') }}</option>
                                    <option value="at_risk">{{ t('projects.status.at_risk') }}</option>
                                    <option value="delayed">{{ t('projects.status.delayed') }}</option>
                                    <option value="blocked">{{ t('projects.status.blocked') }}</option>
                                </select>
                                <p class="ppms-pc-field-hint">{{ t('projects.createFieldStatusHint') }}</p>
                            </label>
                        </div>

                        <h4 class="ppms-pc-subsection-title">{{ t('projects.createSectionSchedule') }}</h4>
                        <div class="ppms-pc-row">
                            <div class="ppms-field ppms-pc-col ppms-pc-col--5">
                                <div class="ppms-pc-label-row">
                                    <label class="ppms-pc-date-label" :for="startDateInputId">{{
                                        t('projects.colStart')
                                    }}</label>
                                </div>
                                <input
                                    :id="startDateInputId"
                                    :value="startDateInputValue"
                                    type="date"
                                    name="start_date"
                                    class="ppms-pc-date-input"
                                    autocomplete="off"
                                    @input="onStartDateChange"
                                    @change="onStartDateChange"
                                />
                            </div>
                            <div class="ppms-field ppms-pc-col ppms-pc-col--5">
                                <div class="ppms-pc-label-row">
                                    <label class="ppms-pc-date-label" :for="deadlineInputId">{{
                                        t('projects.fieldDeadline')
                                    }}</label>
                                </div>
                                <input
                                    :id="deadlineInputId"
                                    :value="deadlineInputValue"
                                    type="date"
                                    name="deadline"
                                    class="ppms-pc-date-input"
                                    autocomplete="off"
                                    @input="onDeadlineChange"
                                    @change="onDeadlineChange"
                                />
                            </div>
                            <div class="ppms-field ppms-pc-col ppms-pc-col--2">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.createFieldDaySpan') }}</span>
                                </div>
                                <input :value="plannedDaysDisplay" type="text" readonly tabindex="-1" />
                            </div>
                        </div>

                        <h4 class="ppms-pc-subsection-title">{{ t('projects.createSectionFinance') }}</h4>
                        <div class="ppms-pc-row">
                            <div v-if="form.editingId" class="ppms-field ppms-pc-col ppms-pc-col--6">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.colProgress') }}</span>
                                </div>
                                <input :value="progressPctDisplay" type="text" readonly tabindex="-1" name="progress_readonly" />
                                <p class="ppms-pc-field-hint">{{ t('projects.createFieldProgressReadonlyHint') }}</p>
                            </div>
                            <label class="ppms-field ppms-pc-col ppms-pc-col--6">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.createFieldEstimatedValue') }}</span>
                                </div>
                                <input
                                    v-model="form.estimated_value"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    name="estimated_value"
                                    autocomplete="off"
                                />
                                <p class="ppms-pc-field-hint">{{ t('projects.createFieldEstimatedValueHint') }}</p>
                            </label>
                        </div>

                        <h4 class="ppms-pc-subsection-title">
                            {{ t('projects.createFieldProgressCalc') }}
                            <span class="ppms-req" aria-hidden="true">*</span>
                        </h4>
                        <div ref="progressCalcEl" class="ppms-field ppms-pc-field--flush">
                            <div class="ppms-pc-combobox" :class="{ 'ppms-pc-combobox--open': progressCalcOpen }">
                                <button
                                    type="button"
                                    class="ppms-pc-combobox-trigger"
                                    :aria-expanded="progressCalcOpen"
                                    aria-haspopup="listbox"
                                    :aria-controls="progressCalcOpen ? 'ppms-pc-progress-calc-listbox' : undefined"
                                    @click.stop="toggleProgressCalcDropdown"
                                >
                                    <span class="ppms-pc-combobox-trigger-text">{{ selectedProgressCalcTitle }}</span>
                                    <svg
                                        class="ppms-pc-combobox-caret"
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
                                    v-show="progressCalcOpen"
                                    id="ppms-pc-progress-calc-listbox"
                                    class="ppms-pc-combobox-panel"
                                    role="listbox"
                                    :aria-label="t('projects.createFieldProgressCalc')"
                                >
                                    <li v-for="opt in progressCalcOptions" :key="'pc-' + opt.value" role="none">
                                        <button
                                            type="button"
                                            class="ppms-pc-combobox-opt"
                                            :class="{ 'ppms-pc-combobox-opt--selected': form.progress_calc === opt.value }"
                                            role="option"
                                            :aria-selected="form.progress_calc === opt.value"
                                            @click="selectProgressCalc(opt.value)"
                                        >
                                            <span class="ppms-pc-combobox-opt-title">{{ opt.title }}</span>
                                            <span class="ppms-pc-combobox-opt-detail">{{ opt.detail }}</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="ppms-pc-card" aria-labelledby="ppms-pc-card-team">
                        <h3 id="ppms-pc-card-team" class="ppms-pc-card__title">{{ t('projects.createSectionTeam') }}</h3>
                        <p class="ppms-pc-section-lead">{{ t('projects.createSectionTeamLead') }}</p>
                        <div class="ppms-pc-row">
                            <label class="ppms-field ppms-pc-col ppms-pc-col--12">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.fieldTeam') }}</span>
                                </div>
                                <select
                                    v-model="form.team_id"
                                    class="ppms-pc-select"
                                    name="team_id"
                                    :disabled="teamLocked"
                                >
                                    <option value="">{{ t('projects.fieldTeamNone') }}</option>
                                    <option v-for="tm in teamOptions" :key="'tm-' + tm.id" :value="String(tm.id)">{{ tm.name }}</option>
                                </select>
                                <p class="ppms-pc-field-hint">{{ teamLocked ? t('projects.fieldTeamLockedHint') : t('projects.fieldTeamHint') }}</p>
                            </label>
                        </div>
                        <div class="ppms-pc-row">
                            <div class="ppms-field ppms-pc-col ppms-pc-col--12">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.fieldOwner') }}</span>
                                    <span class="ppms-req" aria-hidden="true">*</span>
                                </div>
                                <div v-if="form.owner_id" class="ppms-pc-owner-picked">
                                    <span class="ppms-pc-owner-picked__text">
                                        {{ userCache[form.owner_id]?.name }} ({{ userCache[form.owner_id]?.email }})
                                    </span>
                                    <button
                                        type="button"
                                        class="ppms-pc-owner-picked__clear"
                                        :aria-label="t('common.close')"
                                        @click="clearOwnerSelection"
                                    >
                                        ×
                                    </button>
                                </div>
                                <div v-else ref="ownerLookupEl" class="ppms-pc-lookup-combo">
                                    <input
                                        v-model="ownerQuery"
                                        type="search"
                                        name="owner_lookup"
                                        class="ppms-pc-userpick__search"
                                        :placeholder="t('projects.createUserSearchPlaceholder')"
                                        autocomplete="off"
                                        enterkeyhint="search"
                                        @input="scheduleOwnerLookup"
                                    />
                                    <ul v-if="ownerLookupOpen && ownerHits.length" class="ppms-pc-lookup-hits" role="listbox">
                                        <li v-for="u in ownerHits" :key="'ow-' + u.id" role="none">
                                            <button type="button" class="ppms-pc-lookup-hit" @click="pickOwner(u)">
                                                {{ u.name }} — {{ u.email }}
                                            </button>
                                        </li>
                                    </ul>
                                    <p v-if="!form.owner_id && ownerQuery.trim().length > 0 && ownerQuery.trim().length < LOOKUP_MIN_LEN" class="ppms-pc-field-hint">
                                        {{ t('projects.createUserSearchMinHint') }}
                                    </p>
                                    <p
                                        v-if="!form.owner_id && ownerQuery.trim().length >= LOOKUP_MIN_LEN && ownerLookupPending"
                                        class="ppms-muted ppms-pc-field-hint"
                                    >
                                        {{ t('common.loading') }}
                                    </p>
                                    <p
                                        v-else-if="!form.owner_id && ownerQuery.trim().length >= LOOKUP_MIN_LEN && !ownerHits.length"
                                        class="ppms-pc-userpick__empty"
                                    >
                                        {{ t('projects.createUserSearchEmpty') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="ppms-pc-ms-grid">
                            <div
                                class="ppms-pc-ms-panel"
                                role="group"
                                :aria-label="t('projects.createExecutors')"
                            >
                                <div class="ppms-pc-ms-panel__head">
                                    <span>{{ t('projects.createGroupExecutors') }}</span>
                                    <span class="ppms-pc-ms-panel__badge" aria-hidden="true">{{ executorSelectedCount }}</span>
                                </div>
                                <div class="ppms-pc-ms-panel__body">
                                    <div v-if="executorChipsOnly.length" class="ppms-pc-chips" aria-label="selected">
                                        <span v-for="u in executorChipsOnly" :key="'exc-' + u.id" class="ppms-pc-chip">
                                            <span class="ppms-pc-chip__text">{{ u.name }}</span>
                                            <button
                                                type="button"
                                                class="ppms-pc-chip__remove"
                                                :aria-label="t('common.close')"
                                                @click="toggleExecutor(u.id)"
                                            >
                                                ×
                                            </button>
                                        </span>
                                    </div>
                                    <div class="ppms-pc-userpick">
                                        <input
                                            v-model="executorSearch"
                                            type="search"
                                            class="ppms-pc-userpick__search"
                                            :placeholder="t('projects.createUserSearchPlaceholder')"
                                            autocomplete="off"
                                            enterkeyhint="search"
                                            @input="scheduleExecutorLookup"
                                        />
                                        <p v-if="executorSearch.trim().length > 0 && executorSearch.trim().length < LOOKUP_MIN_LEN" class="ppms-pc-field-hint">
                                            {{ t('projects.createUserSearchMinHint') }}
                                        </p>
                                        <p
                                            v-else-if="executorSearch.trim().length >= LOOKUP_MIN_LEN && executorLookupPending"
                                            class="ppms-muted ppms-pc-field-hint"
                                        >
                                            {{ t('common.loading') }}
                                        </p>
                                        <div
                                            v-else-if="executorSearch.trim().length >= LOOKUP_MIN_LEN && executorHits.length === 0"
                                            class="ppms-pc-userpick__empty"
                                        >
                                            {{ t('projects.createUserSearchEmpty') }}
                                        </div>
                                        <div v-else-if="executorSearch.trim().length >= LOOKUP_MIN_LEN" class="ppms-pc-userpick__list" role="group">
                                            <label
                                                v-for="u in executorHits"
                                                :key="'ex-' + u.id"
                                                class="ppms-pc-userpick__row"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="executorIdSet.has(String(u.id))"
                                                    @change="toggleExecutor(u.id)"
                                                />
                                                <span class="ppms-pc-userpick__text">
                                                    <span class="ppms-pc-userpick__name">{{ u.name }}</span>
                                                    <span class="ppms-pc-userpick__email">{{ u.email }}</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <p class="ppms-pc-field-hint">{{ t('projects.createExecutorsHint') }}</p>
                                </div>
                            </div>
                            <div
                                class="ppms-pc-ms-panel"
                                role="group"
                                :aria-label="t('projects.createFollowers')"
                            >
                                <div class="ppms-pc-ms-panel__head">
                                    <span>{{ t('projects.createGroupFollowers') }}</span>
                                    <span class="ppms-pc-ms-panel__badge" aria-hidden="true">{{ followerSelectedCount }}</span>
                                </div>
                                <div class="ppms-pc-ms-panel__body">
                                    <div v-if="followerChipsOnly.length" class="ppms-pc-chips" aria-label="selected">
                                        <span v-for="u in followerChipsOnly" :key="'fwc-' + u.id" class="ppms-pc-chip">
                                            <span class="ppms-pc-chip__text">{{ u.name }}</span>
                                            <button
                                                type="button"
                                                class="ppms-pc-chip__remove"
                                                :aria-label="t('common.close')"
                                                @click="toggleFollower(u.id)"
                                            >
                                                ×
                                            </button>
                                        </span>
                                    </div>
                                    <div class="ppms-pc-userpick">
                                        <input
                                            v-model="followerSearch"
                                            type="search"
                                            class="ppms-pc-userpick__search"
                                            :placeholder="t('projects.createUserSearchPlaceholder')"
                                            autocomplete="off"
                                            enterkeyhint="search"
                                            @input="scheduleFollowerLookup"
                                        />
                                        <p v-if="followerSearch.trim().length > 0 && followerSearch.trim().length < LOOKUP_MIN_LEN" class="ppms-pc-field-hint">
                                            {{ t('projects.createUserSearchMinHint') }}
                                        </p>
                                        <p
                                            v-else-if="followerSearch.trim().length >= LOOKUP_MIN_LEN && followerLookupPending"
                                            class="ppms-muted ppms-pc-field-hint"
                                        >
                                            {{ t('common.loading') }}
                                        </p>
                                        <div
                                            v-else-if="followerSearch.trim().length >= LOOKUP_MIN_LEN && followerHits.length === 0"
                                            class="ppms-pc-userpick__empty"
                                        >
                                            {{ t('projects.createUserSearchEmpty') }}
                                        </div>
                                        <div v-else-if="followerSearch.trim().length >= LOOKUP_MIN_LEN" class="ppms-pc-userpick__list" role="group">
                                            <label
                                                v-for="u in followerHits"
                                                :key="'fw-' + u.id"
                                                class="ppms-pc-userpick__row"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="followerIdSet.has(String(u.id))"
                                                    @change="toggleFollower(u.id)"
                                                />
                                                <span class="ppms-pc-userpick__text">
                                                    <span class="ppms-pc-userpick__name">{{ u.name }}</span>
                                                    <span class="ppms-pc-userpick__email">{{ u.email }}</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <p class="ppms-pc-field-hint">{{ t('projects.createFollowersHint') }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="ppms-pc-card" aria-labelledby="ppms-pc-card-perm">
                        <h3 id="ppms-pc-card-perm" class="ppms-pc-card__title">
                            {{ t('projects.createSectionPermissions') }}
                            <span class="ppms-req" aria-hidden="true">*</span>
                        </h3>
                        <div class="ppms-pc-perm">
                            <p id="ppms-pc-perm-hint" class="ppms-pc-perm__intro">
                                {{ t('projects.createSectionPermissionsSub') }}
                            </p>
                            <div
                                class="ppms-pc-perm__group"
                                role="radiogroup"
                                aria-labelledby="ppms-pc-card-perm"
                                aria-describedby="ppms-pc-perm-hint"
                            >
                                <label v-for="preset in permissionPresets" :key="preset.value" class="ppms-pc-perm-option">
                                    <input
                                        v-model="form.permission_preset"
                                        class="ppms-pc-perm-option__radio"
                                        type="radio"
                                        name="permission_preset"
                                        :value="preset.value"
                                    />
                                    <span class="ppms-pc-perm-option__body">
                                        <span class="ppms-pc-perm-option__title">{{ preset.title }}</span>
                                        <span class="ppms-pc-perm-option__desc">{{ preset.description }}</span>
                                    </span>
                                </label>
                            </div>
                            <p class="ppms-pc-perm__footnote">{{ t('projects.createSectionPermissionsUpgrade') }}</p>
                        </div>
                    </section>

                    <section class="ppms-pc-card" aria-labelledby="ppms-pc-card-stake">
                        <h3 id="ppms-pc-card-stake" class="ppms-pc-card__title">{{ t('projects.createSectionStakeholders') }}</h3>
                        <p class="ppms-pc-section-lead">{{ t('projects.createSectionStakeholdersLead') }}</p>

                        <div class="ppms-pc-row ppms-pc-row--stake-grid">
                            <div class="ppms-field ppms-pc-col ppms-pc-col--6">
                                <div class="ppms-pc-label-row ppms-pc-label-row--tools">
                                    <span>{{ t('projects.createFieldDepartment') }}</span>
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="deptQuickOpen = true">
                                        {{ t('projects.createQuickDepartment') }}
                                    </button>
                                </div>
                                <select
                                    v-model="form.department_id"
                                    class="ppms-pc-select"
                                    name="department_id"
                                    @change="onDepartmentChange"
                                >
                                    <option value="">{{ t('projects.createSelectPlaceholder') }}</option>
                                    <option v-for="d in departmentsList" :key="'dept-' + d.id" :value="String(d.id)">
                                        {{ d.code ? `${d.name} (${d.code})` : d.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="ppms-field ppms-pc-col ppms-pc-col--6">
                                <div class="ppms-pc-label-row ppms-pc-label-row--tools">
                                    <span>{{ t('projects.createFieldBlock') }}</span>
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="blockQuickOpen = true">
                                        {{ t('projects.createQuickBlock') }}
                                    </button>
                                </div>
                                <select v-model="form.block_id" class="ppms-pc-select" name="block_id">
                                    <option value="">{{ t('projects.createSelectPlaceholder') }}</option>
                                    <option v-for="b in blocksList" :key="'blk-' + b.id" :value="String(b.id)">
                                        {{ b.code ? `${b.name} (${b.code})` : b.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="ppms-pc-row">
                            <label class="ppms-field ppms-pc-col ppms-pc-col--6">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.fieldCustomerName') }}</span>
                                </div>
                                <input
                                    v-model="form.customer_name"
                                    name="customer_name"
                                    autocomplete="organization"
                                    :placeholder="t('projects.createFieldCustomerNamePh')"
                                />
                            </label>
                            <label class="ppms-field ppms-pc-col ppms-pc-col--6">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.fieldCustomerEmail') }}</span>
                                </div>
                                <input
                                    v-model="form.customer_email"
                                    type="email"
                                    name="customer_email"
                                    :placeholder="customerEmailPlaceholder"
                                />
                            </label>
                        </div>

                        <div class="ppms-field ppms-pc-full">
                            <div class="ppms-pc-label-row ppms-pc-label-row--tools">
                                <span>{{ t('projects.fieldSuppliersHint') }}</span>
                                <span class="ppms-pc-inline-actions">
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="vendorPickOpen = true">
                                        {{ t('projects.createPickVendor') }}
                                    </button>
                                    <button type="button" class="ppms-btn-ghost ppms-btn-sm" @click="vendorQuickOpen = true">
                                        {{ t('projects.createQuickVendor') }}
                                    </button>
                                </span>
                            </div>
                            <textarea
                                v-model="form.suppliers_text"
                                name="suppliers"
                                rows="3"
                                :placeholder="t('projects.createFieldSuppliersPh')"
                            />
                        </div>
                    </section>

                    <section class="ppms-pc-card" aria-labelledby="ppms-pc-card-extra">
                        <h3 id="ppms-pc-card-extra" class="ppms-pc-card__title">{{ t('projects.createSectionExtra') }}</h3>
                        <label class="ppms-field ppms-pc-full">
                            <div class="ppms-pc-label-row">
                                <span>{{ t('projects.fieldDescription') }}</span>
                            </div>
                            <textarea
                                v-model="form.description"
                                name="description"
                                rows="4"
                                :placeholder="t('projects.createFieldDescriptionPh')"
                            />
                        </label>

                        <label class="ppms-field ppms-pc-full">
                            <div class="ppms-pc-label-row">
                                <span>{{ t('projects.fieldLabelsHint') }}</span>
                            </div>
                            <input
                                v-model="form.labels_text"
                                type="text"
                                name="labels"
                                autocomplete="off"
                                :placeholder="t('projects.createFieldLabelsPh')"
                            />
                        </label>

                        <div class="ppms-pc-attach-drop" :aria-label="t('projects.createFieldAttach')">
                            <div class="ppms-pc-attach-drop-inner">
                                <span class="ppms-pc-attach-ico" aria-hidden="true" />
                                <div>
                                    <strong>{{ t('projects.createFieldAttach') }}</strong>
                                    <p class="ppms-muted ppms-pc-attach-hint">{{ t('projects.createFieldAttachHint') }}</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <p v-if="formError" class="ppms-error">{{ formError }}</p>
                <div class="ppms-modal-actions ppms-pc-footer">
                    <button type="button" class="ppms-btn-ghost" @click="tryClose">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" class="ppms-btn-primary">{{ t('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div
        v-if="open && leaveConfirmOpen"
        class="ppms-modal-backdrop ppms-pc-leave-backdrop"
        role="presentation"
        @click.self="dismissLeaveConfirm"
    >
        <div
            class="ppms-modal ppms-pc-leave-dialog"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="leaveConfirmIsEdit ? 'ppms-pc-leave-edit-title' : 'ppms-pc-leave-title'"
            @click.stop
        >
            <template v-if="leaveConfirmIsEdit">
                <h3 id="ppms-pc-leave-edit-title" class="ppms-modal-title">{{ t('projects.leaveEditTitle') }}</h3>
                <p class="ppms-modal-msg">{{ t('projects.leaveEditMessage') }}</p>
                <div class="ppms-modal-actions ppms-pc-leave-actions">
                    <button type="button" class="ppms-btn-primary" @click="dismissLeaveConfirm">
                        {{ t('projects.leaveEditStay') }}
                    </button>
                    <button type="button" class="ppms-btn-ghost" @click="discardAndClose">
                        {{ t('projects.leaveEditDiscard') }}
                    </button>
                </div>
            </template>
            <template v-else>
                <h3 id="ppms-pc-leave-title" class="ppms-modal-title">{{ t('projects.leaveCreateTitle') }}</h3>
                <p class="ppms-modal-msg">{{ t('projects.leaveCreateMessage') }}</p>
                <div class="ppms-modal-actions ppms-pc-leave-actions">
                    <button type="button" class="ppms-btn-primary" @click="dismissLeaveConfirm">
                        {{ t('projects.leaveCreateStay') }}
                    </button>
                    <button type="button" class="ppms-btn-ghost" @click="discardAndClose">
                        {{ t('projects.leaveCreateDiscard') }}
                    </button>
                    <button type="button" class="ppms-btn-ghost" @click="saveDraftAndClose">
                        {{ t('projects.leaveCreateSaveDraft') }}
                    </button>
                </div>
            </template>
        </div>
    </div>

    <div
        v-if="open && vendorPickOpen"
        class="ppms-modal-backdrop ppms-pc-submodal-backdrop"
        role="presentation"
        @click.self="vendorPickOpen = false"
    >
        <div
            class="ppms-modal ppms-pc-submodal ppms-pc-submodal--polish"
            role="dialog"
            aria-modal="true"
            aria-labelledby="ppms-pc-vpick-title"
            @click.stop
        >
            <header class="ppms-pc-submodal-head">
                <p class="ppms-pc-submodal-kicker">{{ t('projects.createPickVendor') }}</p>
                <h3 id="ppms-pc-vpick-title" class="ppms-pc-submodal-title">{{ t('projects.createPickVendorTitle') }}</h3>
                <p class="ppms-pc-submodal-lead">{{ t('projects.createPickVendorLead') }}</p>
            </header>
            <div class="ppms-pc-submodal-body">
                <label class="ppms-pc-submodal-field">
                    <span class="ppms-pc-submodal-label">{{ t('common.search') }}</span>
                    <input
                        id="ppms-pc-vpick-search"
                        v-model="vendorPickFilter"
                        type="search"
                        class="ppms-pc-input ppms-pc-input--emphasis"
                        :placeholder="t('projects.createVendorSearchPh')"
                        autocomplete="off"
                        :aria-label="t('projects.createVendorSearchPh')"
                    />
                </label>
                <ul class="ppms-pc-vendor-pick-list" role="listbox" :aria-label="t('projects.createPickVendorTitle')">
                    <li v-for="v in vendorPickFiltered" :key="'vp-' + v.id">
                        <button type="button" class="ppms-pc-vendor-pick-btn" @click="appendVendorLine(v)">
                            <span class="ppms-pc-vendor-pick-name">{{ v.name }}</span>
                            <span v-if="v.tax_code" class="ppms-pc-vendor-pick-meta">{{ v.tax_code }}</span>
                        </button>
                    </li>
                </ul>
                <p v-if="vendorPickFiltered.length === 0" class="ppms-pc-submodal-empty">{{ t('projects.createVendorPickEmpty') }}</p>
            </div>
            <footer class="ppms-pc-submodal-foot">
                <button type="button" class="ppms-btn-ghost" @click="vendorPickOpen = false">{{ t('common.cancel') }}</button>
            </footer>
        </div>
    </div>

    <div
        v-if="open && vendorQuickOpen"
        class="ppms-modal-backdrop ppms-pc-submodal-backdrop"
        role="presentation"
        @click.self="vendorQuickOpen = false"
    >
        <div
            class="ppms-modal ppms-pc-submodal ppms-pc-submodal--polish"
            role="dialog"
            aria-modal="true"
            aria-labelledby="ppms-pc-vquick-title"
            @click.stop
        >
            <header class="ppms-pc-submodal-head">
                <p class="ppms-pc-submodal-kicker">{{ t('projects.createQuickVendor') }}</p>
                <h3 id="ppms-pc-vquick-title" class="ppms-pc-submodal-title">{{ t('projects.createQuickVendorTitle') }}</h3>
                <p class="ppms-pc-submodal-lead">{{ t('projects.createQuickVendorHint') }}</p>
            </header>
            <div class="ppms-pc-submodal-body">
                <label class="ppms-pc-submodal-field">
                    <span class="ppms-pc-submodal-label">{{ t('vendors.fieldName') }} <abbr class="ppms-pc-req" :title="t('vendors.requiredField')">*</abbr></span>
                    <input
                        v-model="quickVendorForm.name"
                        type="text"
                        class="ppms-pc-input ppms-pc-input--emphasis"
                        maxlength="255"
                        autocomplete="organization"
                        :placeholder="t('projects.createQuickVendorNamePh')"
                        :aria-invalid="!!quickVendorErr"
                    />
                </label>
                <p v-if="quickVendorErr" class="ppms-error ppms-pc-submodal-err" role="alert">{{ quickVendorErr }}</p>
            </div>
            <footer class="ppms-pc-submodal-foot">
                <button type="button" class="ppms-btn-ghost" @click="vendorQuickOpen = false">{{ t('common.cancel') }}</button>
                <button type="button" class="ppms-btn-primary" :disabled="vendorQuickSaving" @click="submitQuickVendor">
                    {{ t('vendors.modalCreateSave') }}
                </button>
            </footer>
        </div>
    </div>

    <div
        v-if="open && deptQuickOpen"
        class="ppms-modal-backdrop ppms-pc-submodal-backdrop"
        role="presentation"
        @click.self="deptQuickOpen = false"
    >
        <div
            class="ppms-modal ppms-pc-submodal ppms-pc-submodal--polish"
            role="dialog"
            aria-modal="true"
            aria-labelledby="ppms-pc-dept-quick-title"
            @click.stop
        >
            <header class="ppms-pc-submodal-head">
                <p class="ppms-pc-submodal-kicker">{{ t('projects.createQuickDepartment') }}</p>
                <h3 id="ppms-pc-dept-quick-title" class="ppms-pc-submodal-title">{{ t('projects.createQuickDepartmentTitle') }}</h3>
                <p class="ppms-pc-submodal-lead">{{ t('projects.createQuickDepartmentLead') }}</p>
            </header>
            <div class="ppms-pc-submodal-body">
                <label class="ppms-pc-submodal-field">
                    <span class="ppms-pc-submodal-label">{{ t('projects.createDeptNameLabel') }} *</span>
                    <input
                        v-model="newDeptName"
                        type="text"
                        class="ppms-pc-input ppms-pc-input--emphasis"
                        maxlength="255"
                        autocomplete="organization"
                        :placeholder="t('projects.createDeptNamePh')"
                        :aria-invalid="!!deptQuickErr"
                    />
                </label>
                <label class="ppms-pc-submodal-field">
                    <span class="ppms-pc-submodal-label">{{ t('projects.createDeptCodeLabel') }}</span>
                    <input
                        v-model="newDeptCode"
                        type="text"
                        class="ppms-pc-input"
                        maxlength="64"
                        autocomplete="off"
                        :placeholder="t('projects.createDeptCodePh')"
                    />
                </label>
                <p v-if="deptQuickErr" class="ppms-error ppms-pc-submodal-err" role="alert">{{ deptQuickErr }}</p>
            </div>
            <footer class="ppms-pc-submodal-foot">
                <button type="button" class="ppms-btn-ghost" @click="deptQuickOpen = false">{{ t('common.cancel') }}</button>
                <button type="button" class="ppms-btn-primary" :disabled="deptQuickSaving" @click="submitQuickDepartment">
                    {{ t('projects.createQuickDepartmentSave') }}
                </button>
            </footer>
        </div>
    </div>

    <div
        v-if="open && blockQuickOpen"
        class="ppms-modal-backdrop ppms-pc-submodal-backdrop"
        role="presentation"
        @click.self="blockQuickOpen = false"
    >
        <div
            class="ppms-modal ppms-pc-submodal ppms-pc-submodal--polish"
            role="dialog"
            aria-modal="true"
            aria-labelledby="ppms-pc-block-quick-title"
            @click.stop
        >
            <header class="ppms-pc-submodal-head">
                <p class="ppms-pc-submodal-kicker">{{ t('projects.createQuickBlock') }}</p>
                <h3 id="ppms-pc-block-quick-title" class="ppms-pc-submodal-title">{{ t('projects.createQuickBlockTitle') }}</h3>
                <p class="ppms-pc-submodal-lead">{{ t('projects.createQuickBlockLead') }}</p>
            </header>
            <div class="ppms-pc-submodal-body">
                <label class="ppms-pc-submodal-field">
                    <span class="ppms-pc-submodal-label">{{ t('projects.createBlockNameLabel') }} *</span>
                    <input
                        v-model="newBlockName"
                        type="text"
                        class="ppms-pc-input ppms-pc-input--emphasis"
                        maxlength="255"
                        autocomplete="off"
                        :placeholder="t('projects.createBlockNamePh')"
                        :aria-invalid="!!blockQuickErr"
                    />
                </label>
                <label class="ppms-pc-submodal-field">
                    <span class="ppms-pc-submodal-label">{{ t('projects.createBlockCodeLabel') }}</span>
                    <input
                        v-model="newBlockCode"
                        type="text"
                        class="ppms-pc-input"
                        maxlength="64"
                        autocomplete="off"
                        :placeholder="t('projects.createBlockCodePh')"
                    />
                </label>
                <p v-if="blockQuickErr" class="ppms-error ppms-pc-submodal-err" role="alert">{{ blockQuickErr }}</p>
            </div>
            <footer class="ppms-pc-submodal-foot">
                <button type="button" class="ppms-btn-ghost" @click="blockQuickOpen = false">{{ t('common.cancel') }}</button>
                <button type="button" class="ppms-btn-primary" :disabled="blockQuickSaving" @click="submitQuickBlock">
                    {{ t('projects.createQuickBlockSave') }}
                </button>
            </footer>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, useId, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import enLocale from '@/i18n/locales/en.json';
import viLocale from '@/i18n/locales/vi.json';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsToastSuccess } from '@/ppmsUi';
import {
    applyProjectCreateForm,
    clearProjectCreateDraft,
    loadProjectCreateDraft,
    saveProjectCreateDraft,
    serializeProjectCreateForm,
} from '../../utils/projectCreateDraft';

const { t, locale } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    formError: { type: String, default: '' },
    teamOptions: { type: Array, default: () => [] },
    /** User chỉ thuộc một team — khóa chọn team (dự án thuộc team đó). */
    teamLocked: { type: Boolean, default: false },
    stakeholderLookups: {
        type: Object,
        default: () => ({ departments: [], blocks: [], vendors: [] }),
    },
});

const emit = defineEmits(['submit', 'refresh-stakeholder-lookups']);

const departmentsList = computed(() => props.stakeholderLookups?.departments ?? []);
const blocksList = computed(() => props.stakeholderLookups?.blocks ?? []);
const vendorsList = computed(() => props.stakeholderLookups?.vendors ?? []);

const vendorPickOpen = ref(false);
const vendorPickFilter = ref('');
const vendorQuickOpen = ref(false);
const vendorQuickSaving = ref(false);
const quickVendorErr = ref('');
const quickVendorForm = reactive({
    name: '',
});

const deptQuickOpen = ref(false);
const deptQuickSaving = ref(false);
const deptQuickErr = ref('');
const newDeptName = ref('');
const newDeptCode = ref('');

const blockQuickOpen = ref(false);
const blockQuickSaving = ref(false);
const blockQuickErr = ref('');
const newBlockName = ref('');
const newBlockCode = ref('');

const vendorPickFiltered = computed(() => {
    const q = vendorPickFilter.value.trim().toLowerCase();
    const list = vendorsList.value;
    if (!q) {
        return list;
    }
    return list.filter((v) => {
        const name = (v?.name || '').toLowerCase();
        const tax = String(v?.tax_code || '').toLowerCase();

        return name.includes(q) || tax.includes(q);
    });
});

function appendSupplierTextLine(line) {
    const tLine = (line || '').trim();
    if (!tLine) {
        return;
    }
    const cur = (props.form.suppliers_text || '').trim();
    // eslint-disable-next-line vue/no-mutating-props -- shared form
    props.form.suppliers_text = cur ? `${cur}\n${tLine}` : tLine;
}

function appendVendorLine(v) {
    if (!v?.name) {
        return;
    }
    const line = v.tax_code ? `${v.name} — ${v.tax_code}` : v.name;
    appendSupplierTextLine(line);
    vendorPickOpen.value = false;
    vendorPickFilter.value = '';
}

function onDepartmentChange() {
    const id = props.form.department_id;
    if (!id) {
        return;
    }
    const d = departmentsList.value.find((x) => String(x.id) === String(id));
    if (d?.name && !(props.form.customer_name || '').trim()) {
        // eslint-disable-next-line vue/no-mutating-props
        props.form.customer_name = d.name;
    }
}

watch(vendorPickOpen, (v) => {
    if (v) {
        vendorPickFilter.value = '';
    }
});

watch(deptQuickOpen, (v) => {
    if (v) {
        newDeptName.value = '';
        newDeptCode.value = '';
        deptQuickErr.value = '';
    }
});

watch(blockQuickOpen, (v) => {
    if (v) {
        newBlockName.value = '';
        newBlockCode.value = '';
        blockQuickErr.value = '';
    }
});

watch(vendorQuickOpen, (v) => {
    if (v) {
        quickVendorForm.name = '';
        quickVendorErr.value = '';
    }
});

async function submitQuickVendor() {
    const name = quickVendorForm.name.trim();
    if (!name) {
        quickVendorErr.value = t('projects.createQuickVendorNameRequired');

        return;
    }
    vendorQuickSaving.value = true;
    quickVendorErr.value = '';
    try {
        const { data } = await axios.post('/api/vendors', {
            name,
            kind: 'active',
            status: 'active',
        });
        appendSupplierTextLine(data?.name || name);
        vendorQuickOpen.value = false;
        emit('refresh-stakeholder-lookups');
        ppmsToastSuccess(t('projects.createQuickVendorOk'));
    } catch (e) {
        quickVendorErr.value = formatApiUserMessage(e, t('projects.createQuickVendorErr'));
    } finally {
        vendorQuickSaving.value = false;
    }
}

async function submitQuickDepartment() {
    const name = newDeptName.value.trim();
    if (!name) {
        deptQuickErr.value = t('projects.createDeptNameRequired');

        return;
    }
    deptQuickSaving.value = true;
    deptQuickErr.value = '';
    try {
        const { data } = await axios.post('/api/departments', {
            name,
            code: newDeptCode.value.trim() || null,
        });
        emit('refresh-stakeholder-lookups');
        await nextTick();
        if (data?.id != null) {
            // eslint-disable-next-line vue/no-mutating-props
            props.form.department_id = String(data.id);
            onDepartmentChange();
        }
        deptQuickOpen.value = false;
        ppmsToastSuccess(t('projects.createQuickDepartmentOk'));
    } catch (e) {
        deptQuickErr.value = formatApiUserMessage(e, t('projects.createQuickDepartmentErr'));
    } finally {
        deptQuickSaving.value = false;
    }
}

async function submitQuickBlock() {
    const name = newBlockName.value.trim();
    if (!name) {
        blockQuickErr.value = t('projects.createBlockNameRequired');

        return;
    }
    blockQuickSaving.value = true;
    blockQuickErr.value = '';
    try {
        const { data } = await axios.post('/api/blocks', {
            name,
            code: newBlockCode.value.trim() || null,
        });
        emit('refresh-stakeholder-lookups');
        await nextTick();
        if (data?.id != null) {
            // eslint-disable-next-line vue/no-mutating-props
            props.form.block_id = String(data.id);
        }
        blockQuickOpen.value = false;
        ppmsToastSuccess(t('projects.createQuickBlockOk'));
    } catch (e) {
        blockQuickErr.value = formatApiUserMessage(e, t('projects.createQuickBlockErr'));
    } finally {
        blockQuickSaving.value = false;
    }
}

const modalTitle = computed(() => (props.form.editingId ? t('projects.editModalTitle') : t('projects.createModalTitle')));

const modalSubtitle = computed(() =>
    props.form.editingId ? t('projects.editModalSubtitle') : t('projects.createModalSubtitle'),
);

const progressPctDisplay = computed(() => {
    const v = props.form.progress_pct;
    if (v === '' || v == null) {
        return '—';
    }
    const n = Number(v);

    return Number.isNaN(n) ? String(v) : `${n}%`;
});

const open = defineModel('open', { type: Boolean, default: false });

const leaveConfirmOpen = ref(false);
const leaveConfirmIsEdit = ref(false);
const baselineJson = ref('');
const editBaselineJson = ref('');
const draftBannerVisible = ref(false);
const storedDraftMeta = ref(null);

const hasDirtyCreate = computed(() => {
    if (!open.value || props.form.editingId) {
        return false;
    }
    return JSON.stringify(serializeProjectCreateForm(props.form)) !== baselineJson.value;
});

const hasDirtyEdit = computed(() => {
    if (!open.value || !props.form.editingId) {
        return false;
    }
    if (!editBaselineJson.value) {
        return false;
    }
    return JSON.stringify(serializeProjectCreateForm(props.form)) !== editBaselineJson.value;
});

const shouldConfirmLeave = computed(() => hasDirtyCreate.value || hasDirtyEdit.value);

const draftSavedAtLabel = computed(() => {
    const ts = storedDraftMeta.value?.savedAt;
    if (!ts) {
        return '';
    }
    try {
        const d = new Date(ts);

        return d.toLocaleString(locale.value === 'vi' ? 'vi-VN' : undefined, {
            dateStyle: 'short',
            timeStyle: 'short',
        });
    } catch {
        return '';
    }
});

function dismissLeaveConfirm() {
    leaveConfirmOpen.value = false;
    leaveConfirmIsEdit.value = false;
}

async function tryClose() {
    if (!shouldConfirmLeave.value) {
        open.value = false;

        return;
    }
    leaveConfirmIsEdit.value = !!props.form.editingId;
    leaveConfirmOpen.value = true;
}

/** Backdrop: cùng logic đóng có xác nhận khi có thay đổi (tránh mất dữ liệu khi sửa). */
function onBackdropClick() {
    tryClose();
}

function discardAndClose() {
    dismissLeaveConfirm();
    open.value = false;
}

function saveDraftAndClose() {
    saveProjectCreateDraft(props.form);
    dismissLeaveConfirm();
    open.value = false;
    ppmsToastSuccess(t('projects.draftSavedToast'));
}

async function restoreStoredDraft() {
    const d = loadProjectCreateDraft();
    if (!d?.form) {
        draftBannerVisible.value = false;

        return;
    }
    applyProjectCreateForm(props.form, d.form);
    draftBannerVisible.value = false;
    await nextTick();
    baselineJson.value = JSON.stringify(serializeProjectCreateForm(props.form));
    await hydrateUserCachesFromForm();
    ppmsToastSuccess(t('projects.draftRestoredToast'));
}

function dismissDraftBanner() {
    draftBannerVisible.value = false;
}

function deleteStoredDraft() {
    clearProjectCreateDraft();
    storedDraftMeta.value = null;
    draftBannerVisible.value = false;
}

function onDocumentKeydown(e) {
    if (e.key !== 'Escape') {
        return;
    }
    if (!open.value) {
        return;
    }
    if (leaveConfirmOpen.value) {
        dismissLeaveConfirm();
        e.preventDefault();

        return;
    }
    e.preventDefault();
    tryClose();
}

onMounted(() => {
    document.addEventListener('keydown', onDocumentKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onDocumentKeydown);
});

const dateFieldUid = useId();
const startDateInputId = `${dateFieldUid}-start`;
const deadlineInputId = `${dateFieldUid}-deadline`;

/** Giá trị chuẩn yyyy-mm-dd; nếu form có chuỗi ISO đầy đủ thì cắt phần ngày để input[type=date] hoạt động ổn định. */
function normalizeDateInputValue(raw) {
    if (raw == null || raw === '') {
        return '';
    }
    if (typeof raw !== 'string') {
        return '';
    }
    const m = raw.match(/^(\d{4}-\d{2}-\d{2})/);

    return m ? m[1] : '';
}

const startDateInputValue = computed(() => normalizeDateInputValue(props.form.start_date));

const deadlineInputValue = computed(() => normalizeDateInputValue(props.form.deadline));

function onStartDateChange(e) {
    const el = e.target;
    if (!(el instanceof HTMLInputElement)) {
        return;
    }
    // eslint-disable-next-line vue/no-mutating-props -- shared form object from parent
    props.form.start_date = el.value;
}

function onDeadlineChange(e) {
    const el = e.target;
    if (!(el instanceof HTMLInputElement)) {
        return;
    }
    // eslint-disable-next-line vue/no-mutating-props -- shared form object from parent
    props.form.deadline = el.value;
}

/** Thứ tự hiển thị giống mockup: bình quân → ngày → tỷ trọng công việc. */
const PROGRESS_CALC_ORDER = ['average_task_pct', 'time_proportion', 'weighted_tasks'];

const executorSelectedCount = computed(() => {
    const v = props.form.executor_user_ids;

    return Array.isArray(v) ? v.length : 0;
});

const followerSelectedCount = computed(() => {
    const v = props.form.follower_user_ids;

    return Array.isArray(v) ? v.length : 0;
});

const LOOKUP_DEBOUNCE_MS = 300;
const LOOKUP_MIN_LEN = 1;

const userCache = reactive({});

function mergeUsersToCache(users) {
    for (const u of users) {
        if (u && u.id != null) {
            userCache[String(u.id)] = { id: u.id, name: u.name, email: u.email };
        }
    }
}

const ownerLookupEl = ref(null);
const ownerQuery = ref('');
const ownerHits = ref([]);
const ownerLookupOpen = ref(false);
const ownerLookupPending = ref(false);
let ownerLookupTimer = null;

function scheduleOwnerLookup() {
    clearTimeout(ownerLookupTimer);
    const q = ownerQuery.value.trim();
    if (q.length < LOOKUP_MIN_LEN) {
        ownerHits.value = [];
        ownerLookupOpen.value = false;
        ownerLookupPending.value = false;

        return;
    }
    ownerLookupTimer = setTimeout(async () => {
        ownerLookupPending.value = true;
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q } });
            ownerHits.value = Array.isArray(data) ? data : [];
            mergeUsersToCache(ownerHits.value);
            ownerLookupOpen.value = ownerHits.value.length > 0;
        } catch {
            ownerHits.value = [];
            ownerLookupOpen.value = false;
        } finally {
            ownerLookupPending.value = false;
        }
    }, LOOKUP_DEBOUNCE_MS);
}

function pickOwner(u) {
    // eslint-disable-next-line vue/no-mutating-props -- intentional shared form state
    props.form.owner_id = String(u.id);
    mergeUsersToCache([u]);
    ownerQuery.value = '';
    ownerHits.value = [];
    ownerLookupOpen.value = false;
}

function clearOwnerSelection() {
    // eslint-disable-next-line vue/no-mutating-props -- intentional shared form state
    props.form.owner_id = '';
    ownerQuery.value = '';
    ownerHits.value = [];
    ownerLookupOpen.value = false;
}

const executorSearch = ref('');
const followerSearch = ref('');
const executorHits = ref([]);
const followerHits = ref([]);
const executorLookupPending = ref(false);
const followerLookupPending = ref(false);
let executorLookupTimer = null;
let followerLookupTimer = null;

function scheduleExecutorLookup() {
    clearTimeout(executorLookupTimer);
    const q = executorSearch.value.trim();
    if (q.length < LOOKUP_MIN_LEN) {
        executorHits.value = [];
        executorLookupPending.value = false;

        return;
    }
    executorLookupTimer = setTimeout(async () => {
        executorLookupPending.value = true;
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q } });
            executorHits.value = Array.isArray(data) ? data : [];
            mergeUsersToCache(executorHits.value);
        } catch {
            executorHits.value = [];
        } finally {
            executorLookupPending.value = false;
        }
    }, LOOKUP_DEBOUNCE_MS);
}

function scheduleFollowerLookup() {
    clearTimeout(followerLookupTimer);
    const q = followerSearch.value.trim();
    if (q.length < LOOKUP_MIN_LEN) {
        followerHits.value = [];
        followerLookupPending.value = false;

        return;
    }
    followerLookupTimer = setTimeout(async () => {
        followerLookupPending.value = true;
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q } });
            followerHits.value = Array.isArray(data) ? data : [];
            mergeUsersToCache(followerHits.value);
        } catch {
            followerHits.value = [];
        } finally {
            followerLookupPending.value = false;
        }
    }, LOOKUP_DEBOUNCE_MS);
}

const executorIdSet = computed(() => new Set((props.form.executor_user_ids || []).map(String)));

const followerIdSet = computed(() => new Set((props.form.follower_user_ids || []).map(String)));

const executorHitIdSet = computed(() => new Set(executorHits.value.map((u) => String(u.id))));

const followerHitIdSet = computed(() => new Set(followerHits.value.map((u) => String(u.id))));

const executorChipsOnly = computed(() => {
    const ids = props.form.executor_user_ids || [];
    return ids
        .filter((id) => !executorHitIdSet.value.has(String(id)))
        .map((id) => userCache[String(id)])
        .filter(Boolean);
});

const followerChipsOnly = computed(() => {
    const ids = props.form.follower_user_ids || [];
    return ids
        .filter((id) => !followerHitIdSet.value.has(String(id)))
        .map((id) => userCache[String(id)])
        .filter(Boolean);
});

function toggleExecutor(userId) {
    const sid = String(userId);
    const arr = [...(props.form.executor_user_ids || [])];
    const i = arr.indexOf(sid);
    if (i >= 0) {
        arr.splice(i, 1);
        const still =
            String(props.form.owner_id) === sid ||
            (props.form.follower_user_ids || []).map(String).includes(sid);
        if (!still) {
            delete userCache[sid];
        }
    } else {
        arr.push(sid);
        const u = executorHits.value.find((x) => String(x.id) === sid);
        if (u) {
            mergeUsersToCache([u]);
        }
    }
    // eslint-disable-next-line vue/no-mutating-props -- intentional shared form state
    props.form.executor_user_ids = arr;
}

function toggleFollower(userId) {
    const sid = String(userId);
    const arr = [...(props.form.follower_user_ids || [])];
    const i = arr.indexOf(sid);
    if (i >= 0) {
        arr.splice(i, 1);
        const still =
            String(props.form.owner_id) === sid ||
            (props.form.executor_user_ids || []).map(String).includes(sid);
        if (!still) {
            delete userCache[sid];
        }
    } else {
        arr.push(sid);
        const u = followerHits.value.find((x) => String(x.id) === sid);
        if (u) {
            mergeUsersToCache([u]);
        }
    }
    // eslint-disable-next-line vue/no-mutating-props -- intentional shared form state
    props.form.follower_user_ids = arr;
}

async function hydrateUserCachesFromForm() {
    const ids = [];
    if (props.form.owner_id) {
        ids.push(Number(props.form.owner_id));
    }
    for (const id of props.form.executor_user_ids || []) {
        ids.push(Number(id));
    }
    for (const id of props.form.follower_user_ids || []) {
        ids.push(Number(id));
    }
    const uniq = [...new Set(ids.filter((n) => Number.isFinite(n) && n > 0))];
    if (uniq.length === 0) {
        return;
    }
    try {
        const { data } = await axios.get('/api/users/lookup', { params: { ids: uniq } });
        mergeUsersToCache(Array.isArray(data) ? data : []);
    } catch {
        /* ignore */
    }
}

const permissionPresets = computed(() => {
    locale.value;

    return [
        {
            value: 'org_default',
            title: t('projects.permissionPresetOrg'),
            description: t('projects.permissionPresetOrgDesc'),
        },
        {
            value: 'members_only',
            title: t('projects.permissionPresetMembers'),
            description: t('projects.permissionPresetMembersDesc'),
        },
        {
            value: 'owner_only',
            title: t('projects.permissionPresetOwner'),
            description: t('projects.permissionPresetOwnerDesc'),
        },
    ];
});

const progressCalcOptions = computed(() => {
    const bundle = locale.value === 'en' ? enLocale.projects : viLocale.projects;
    return PROGRESS_CALC_ORDER.map((value) => {
        const rich =
            bundle[`createProgressCalcRich_${value}`] || bundle[`createProgressCalcHint_${value}`] || '';

        return {
            value,
            title: bundle[`createProgressCalc_${value}`] || value,
            detail: rich,
        };
    });
});

const selectedProgressCalcTitle = computed(() => {
    const bundle = locale.value === 'en' ? enLocale.projects : viLocale.projects;
    const k = props.form.progress_calc || 'weighted_tasks';

    return bundle[`createProgressCalc_${k}`] || k;
});

const plannedDaysDisplay = computed(() => {
    const a = props.form.start_date;
    const b = props.form.deadline;
    if (!a || !b) {
        return '';
    }
    const d1 = new Date(`${a}T12:00:00`);
    const d2 = new Date(`${b}T12:00:00`);
    const ms = d2.getTime() - d1.getTime();
    if (Number.isNaN(ms)) {
        return '';
    }
    const days = Math.round(ms / 86400000);

    return days >= 0 ? String(days) : '';
});

const customerEmailPlaceholder = computed(() => {
    const bundle = locale.value === 'en' ? enLocale.projects : viLocale.projects;

    return bundle.createFieldCustomerEmailPh || '';
});

const progressCalcOpen = ref(false);
const progressCalcEl = ref(null);

function toggleProgressCalcDropdown() {
    progressCalcOpen.value = !progressCalcOpen.value;
}

function selectProgressCalc(value) {
    // Parent `form` is a reactive object passed by reference (same pattern as labels modal).
    // eslint-disable-next-line vue/no-mutating-props -- intentional shared form state
    props.form.progress_calc = value;
    progressCalcOpen.value = false;
}

watch(open, async (isOpen) => {
    if (!isOpen) {
        progressCalcOpen.value = false;
        executorSearch.value = '';
        followerSearch.value = '';
        ownerQuery.value = '';
        ownerHits.value = [];
        executorHits.value = [];
        followerHits.value = [];
        ownerLookupOpen.value = false;
        ownerLookupPending.value = false;
        executorLookupPending.value = false;
        followerLookupPending.value = false;
        leaveConfirmOpen.value = false;
        leaveConfirmIsEdit.value = false;
        editBaselineJson.value = '';

        return;
    }
    await hydrateUserCachesFromForm();
    if (!props.form.editingId) {
        await nextTick();
        baselineJson.value = JSON.stringify(serializeProjectCreateForm(props.form));
        const d = loadProjectCreateDraft();
        storedDraftMeta.value = d;
        draftBannerVisible.value = !!d;
    } else {
        draftBannerVisible.value = false;
        storedDraftMeta.value = null;
        await nextTick();
        editBaselineJson.value = JSON.stringify(serializeProjectCreateForm(props.form));
    }
});

function handleDocumentClick(target) {
    if (!(target instanceof Node)) {
        return;
    }
    if (progressCalcOpen.value && progressCalcEl.value && !progressCalcEl.value.contains(target)) {
        progressCalcOpen.value = false;
    }
    if (ownerLookupOpen.value && ownerLookupEl.value && !ownerLookupEl.value.contains(target)) {
        ownerLookupOpen.value = false;
    }
}

function closeProgressCalc() {
    progressCalcOpen.value = false;
}

function closeOwnerLookup() {
    ownerLookupOpen.value = false;
}

function clearCreateDraft() {
    clearProjectCreateDraft();
    storedDraftMeta.value = null;
    draftBannerVisible.value = false;
}

defineExpose({ handleDocumentClick, closeProgressCalc, closeOwnerLookup, clearCreateDraft });
</script>

<style scoped>
.ppms-pc-owner-picked {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.12));
    border-radius: 8px;
    background: var(--ppms-surface-muted, rgba(0, 0, 0, 0.04));
}
.ppms-pc-owner-picked__text {
    flex: 1;
    min-width: 0;
    word-break: break-word;
}
.ppms-pc-owner-picked__clear {
    flex-shrink: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1.25rem;
    line-height: 1;
    padding: 0.15rem 0.35rem;
    border-radius: 4px;
}
.ppms-pc-owner-picked__clear:hover {
    background: rgba(0, 0, 0, 0.06);
}
.ppms-pc-lookup-combo {
    position: relative;
}
.ppms-pc-lookup-hits {
    list-style: none;
    margin: 0.35rem 0 0;
    padding: 0;
    border: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.12));
    border-radius: 8px;
    max-height: 240px;
    overflow: auto;
    background: var(--ppms-surface, #fff);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
.ppms-pc-lookup-hit {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.5rem 0.75rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font: inherit;
}
.ppms-pc-lookup-hit:hover {
    background: rgba(0, 0, 0, 0.06);
}
.ppms-pc-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-bottom: 0.5rem;
}
.ppms-pc-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    padding: 0.15rem 0.35rem 0.15rem 0.55rem;
    font-size: 0.85rem;
    border-radius: 999px;
    background: var(--ppms-surface-muted, rgba(0, 0, 0, 0.06));
    max-width: 100%;
}
.ppms-pc-chip__text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ppms-pc-chip__remove {
    flex-shrink: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    line-height: 1;
    padding: 0 0.2rem;
    font-size: 1.1rem;
    border-radius: 4px;
}
.ppms-pc-chip__remove:hover {
    background: rgba(0, 0, 0, 0.08);
}

.ppms-pc-draft-banner {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.65rem;
    margin: 0 0 0.85rem;
    padding: 0.65rem 0.75rem;
    border-radius: 8px;
    border: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.12));
    background: rgba(59, 130, 246, 0.07);
}

.ppms-pc-draft-banner__text {
    flex: 1 1 12rem;
    min-width: 0;
    font-size: 0.88rem;
    line-height: 1.4;
    color: var(--ppms-text);
}

.ppms-pc-draft-banner__meta {
    display: block;
    font-size: 0.8rem;
    font-weight: 400;
    color: var(--ppms-muted);
    margin-top: 0.25rem;
}

.ppms-pc-draft-banner__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: center;
    justify-content: flex-end;
}

.ppms-pc-leave-backdrop {
    z-index: 10001;
}

.ppms-pc-leave-dialog {
    max-width: 420px;
}

.ppms-pc-leave-actions {
    flex-direction: column;
    align-items: stretch;
}

@media (min-width: 520px) {
    .ppms-pc-leave-actions {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: flex-end;
        align-items: center;
    }
}

.ppms-pc-label-row--tools {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
}

.ppms-pc-inline-actions {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.ppms-pc-row--stake-grid {
    margin-bottom: 0.75rem;
}

.ppms-pc-input {
    width: 100%;
    padding: 0.5rem 0.65rem;
    border-radius: 8px;
    border: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.14));
    font: inherit;
    font-size: 0.9rem;
    background: var(--ppms-surface, #fff);
    color: var(--ppms-text);
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.ppms-pc-input::placeholder {
    color: var(--ppms-muted);
    opacity: 0.9;
}

.ppms-pc-input:hover {
    border-color: rgba(37, 99, 235, 0.35);
}

.ppms-pc-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
}

.ppms-pc-input--emphasis {
    min-height: 2.65rem;
}

.ppms-pc-req {
    text-decoration: none;
    color: #b91c1c;
    font-weight: 700;
}

.ppms-pc-submodal-backdrop {
    z-index: 10002;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(3px);
}

.ppms-pc-submodal {
    max-width: 420px;
    width: 100%;
    max-height: min(90vh, 640px);
    display: flex;
    flex-direction: column;
    padding: 0;
    overflow: hidden;
}

.ppms-pc-submodal--polish {
    border-radius: 12px;
    box-shadow:
        0 24px 48px rgba(15, 23, 42, 0.12),
        0 0 0 1px rgba(148, 163, 184, 0.2);
}

.ppms-pc-submodal-head {
    padding: 1rem 1.15rem 0.75rem;
    border-bottom: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.08));
    background: linear-gradient(180deg, rgba(59, 130, 246, 0.06) 0%, transparent 100%);
}

.ppms-pc-submodal-kicker {
    margin: 0 0 0.25rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #2563eb;
}

.ppms-pc-submodal-title {
    margin: 0 0 0.4rem;
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--ppms-text);
    line-height: 1.25;
}

.ppms-pc-submodal-lead {
    margin: 0;
    font-size: 0.84rem;
    line-height: 1.45;
    color: var(--ppms-muted);
}

.ppms-pc-submodal-body {
    padding: 1rem 1.15rem;
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
}

.ppms-pc-submodal-field {
    display: block;
    margin-bottom: 0.85rem;
}

.ppms-pc-submodal-field:last-child {
    margin-bottom: 0;
}

.ppms-pc-submodal-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.35rem;
    color: var(--ppms-text);
}

.ppms-pc-submodal-foot {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0.85rem 1.15rem 1rem;
    border-top: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.08));
    background: var(--ppms-surface-muted, rgba(248, 250, 252, 0.85));
}

.ppms-pc-submodal-err {
    margin: 0.35rem 0 0;
    font-size: 0.86rem;
}

.ppms-pc-vendor-pick-list {
    list-style: none;
    margin: 0.5rem 0 0;
    padding: 0;
    max-height: 240px;
    overflow: auto;
    border: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.12));
    border-radius: 8px;
    background: var(--ppms-surface, #fff);
}

.ppms-pc-vendor-pick-btn {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
    text-align: left;
    padding: 0.5rem 0.65rem;
    border: none;
    border-bottom: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.08));
    background: transparent;
    cursor: pointer;
    font: inherit;
}

.ppms-pc-vendor-pick-list li:last-child .ppms-pc-vendor-pick-btn {
    border-bottom: none;
}

.ppms-pc-vendor-pick-btn:hover {
    background: rgba(0, 0, 0, 0.04);
}

.ppms-pc-vendor-pick-name {
    font-weight: 600;
}

.ppms-pc-vendor-pick-meta {
    font-size: 0.82rem;
    color: var(--ppms-muted);
}

.ppms-pc-submodal-empty {
    margin: 0.75rem 0 0;
    font-size: 0.86rem;
    text-align: center;
    padding: 1rem 0.5rem;
    color: var(--ppms-muted);
    border: 1px dashed var(--ppms-border-subtle, rgba(0, 0, 0, 0.18));
    border-radius: 8px;
    background: rgba(248, 250, 252, 0.6);
}

/* Header: tiêu đề + nút đóng rõ ràng (giảm đóng nhầm chỉ bằng backdrop) */
.ppms-pc-modal-head--toolbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem 1rem;
}
.ppms-pc-modal-head__main {
    min-width: 0;
    flex: 1;
}
.ppms-pc-modal-close {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    margin: -0.2rem -0.25rem 0 0;
    padding: 0;
    border: none;
    border-radius: 10px;
    background: transparent;
    color: var(--ppms-muted, #64748b);
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}
.ppms-pc-modal-close:hover {
    background: rgba(15, 23, 42, 0.06);
    color: var(--ppms-text, #0f172a);
}
.ppms-pc-modal-close:focus-visible {
    outline: 2px solid var(--ppms-accent, #4f46e5);
    outline-offset: 2px;
}
</style>
