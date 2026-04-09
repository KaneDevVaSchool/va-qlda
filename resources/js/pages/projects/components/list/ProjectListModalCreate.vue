<!-- eslint-disable vue/no-mutating-props -- form is parent-owned reactive -->
<template>
    <div
        v-if="open"
        class="ppms-modal-backdrop ppms-modal-backdrop--project-create"
        role="presentation"
        @click.self="open = false"
    >
        <div
            id="main-content"
            class="ppms-modal ppms-modal--project-create"
            role="dialog"
            aria-modal="true"
            aria-labelledby="ppms-modal-create-project-title"
            @click.stop
        >
            <div class="ppms-pc-modal-head">
                <h2 id="ppms-modal-create-project-title">{{ modalTitle }}</h2>
                <p class="ppms-pc-modal-subtitle">{{ modalSubtitle }}</p>
            </div>
            <form class="ppms-pc-form" @submit.prevent="$emit('submit')">
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

                        <div v-if="form.editingId" class="ppms-pc-row">
                            <div class="ppms-field ppms-pc-col ppms-pc-col--4">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.colProgress') }}</span>
                                </div>
                                <input :value="progressPctDisplay" type="text" readonly tabindex="-1" name="progress_readonly" />
                                <p class="ppms-pc-field-hint">{{ t('projects.createFieldProgressReadonlyHint') }}</p>
                            </div>
                            <label class="ppms-field ppms-pc-col ppms-pc-col--4">
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

                        <template v-if="!form.editingId">
                            <h4 class="ppms-pc-subsection-title">{{ t('projects.createSectionFinance') }}</h4>
                            <div class="ppms-pc-row">
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
                        </template>

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
                                <select v-model="form.team_id" class="ppms-pc-select" name="team_id">
                                    <option value="">{{ t('projects.fieldTeamNone') }}</option>
                                    <option v-for="tm in teamOptions" :key="'tm-' + tm.id" :value="String(tm.id)">{{ tm.name }}</option>
                                </select>
                                <p class="ppms-pc-field-hint">{{ t('projects.fieldTeamHint') }}</p>
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
                        <label class="ppms-field ppms-pc-full">
                            <div class="ppms-pc-label-row">
                                <span>{{ t('projects.fieldSuppliersHint') }}</span>
                            </div>
                            <textarea
                                v-model="form.suppliers_text"
                                name="suppliers"
                                rows="2"
                                :placeholder="t('projects.createFieldSuppliersPh')"
                            />
                        </label>
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
                    <button type="button" class="ppms-btn-ghost" @click="open = false">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" class="ppms-btn-primary">{{ t('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, reactive, ref, useId, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import enLocale from '@/i18n/locales/en.json';
import viLocale from '@/i18n/locales/vi.json';

const { t, locale } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    formError: { type: String, default: '' },
    teamOptions: { type: Array, default: () => [] },
});

defineEmits(['submit']);

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

        return;
    }
    await hydrateUserCachesFromForm();
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

defineExpose({ handleDocumentClick, closeProgressCalc, closeOwnerLookup });
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
</style>
