<template>
    <div v-if="modelValue" class="ppms-modal-backdrop" @click.self="close">
        <div class="ppms-modal ppms-modal--wide ppms-modal--task-o1" role="dialog" aria-modal="true" aria-labelledby="o1-task-regular-h" @click.stop>
            <h2 id="o1-task-regular-h" class="ppms-modal-title">{{ t('projects.o1TaskRegularTitle') }}</h2>
            <p class="o1tf-modal-lead">{{ t('projects.o1ModalSubtitle') }}</p>
            <div class="o1tf-modal-body">
                <div class="o1tf-tabs">
                    <div class="o1tf-tab-head" role="tablist" aria-label="task-form-tabs">
                        <button
                            id="tab-btn-general"
                            type="button"
                            class="o1tf-tab-label"
                            role="tab"
                            :class="{ 'is-active': tab === 'general' }"
                            :aria-selected="tab === 'general'"
                            :tabindex="tab === 'general' ? 0 : -1"
                            aria-controls="tab-panel-general"
                            @click="tab = 'general'"
                        >
                            {{ t('projects.o1TabGeneral') }}
                        </button>
                        <button
                            id="tab-btn-advanced"
                            type="button"
                            class="o1tf-tab-label"
                            role="tab"
                            :class="{ 'is-active': tab === 'advanced' }"
                            :aria-selected="tab === 'advanced'"
                            :tabindex="tab === 'advanced' ? 0 : -1"
                            aria-controls="tab-panel-advanced"
                            @click="tab = 'advanced'"
                        >
                            {{ t('projects.o1TabAdvanced') }}
                        </button>
                    </div>

                    <div
                        id="tab-panel-general"
                        class="o1tf-tab-pane"
                        role="tabpanel"
                        aria-labelledby="tab-btn-general"
                        :class="{ 'is-active': tab === 'general' }"
                    >
                        <!-- Thông tin chung -->
                        <section class="o1tf-section">
                            <button type="button" class="o1tf-section-title--btn" @click="sec.general = !sec.general">
                                <span class="o1tf-caret-sec" :class="{ 'is-open': sec.general }" aria-hidden="true">▸</span>
                                {{ t('projects.o1SecGeneral') }}
                                <span class="o1tf-sr-only">{{ sec.general ? t('projects.o1Collapse') : t('projects.o1Expand') }}</span>
                            </button>
                            <div v-show="sec.general" class="o1tf-section-body">
                                <div class="o1tf-grid">
                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-4">
                                        <label class="o1tf-label" for="o1-code">{{ t('projects.o1FieldCode') }}</label>
                                        <input
                                            id="o1-code"
                                            class="o1tf-input-el is-disabled"
                                            type="text"
                                            :value="codePlaceholder"
                                            disabled
                                            readonly
                                            :aria-label="t('projects.o1FieldCode')"
                                            :placeholder="t('projects.o1PhCode')"
                                        />
                                        <span class="o1tf-field-hint">{{ t('projects.o1CodeHint') }}</span>
                                    </div>
                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-8">
                                        <label class="o1tf-label" for="o1-title">{{ t('projects.o1FieldTitle') }} <span class="req">*</span></label>
                                        <input
                                            id="o1-title"
                                            v-model="form.name"
                                            class="o1tf-input-el"
                                            type="text"
                                            name="title"
                                            autocomplete="off"
                                            required
                                            :placeholder="t('projects.o1PhTitle')"
                                        />
                                    </div>

                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-12">
                                        <label class="o1tf-label" for="o1-phase">{{ t('projects.taskPhasePh') }}</label>
                                        <select
                                            id="o1-phase"
                                            v-model="form.project_phase_id"
                                            class="o1tf-input-el"
                                            :aria-label="t('projects.taskPhasePh')"
                                        >
                                            <option value="">{{ t('projects.o1PhPhase') }}</option>
                                            <option v-for="ph in projectPhases" :key="'rg-ph-' + ph.id" :value="String(ph.id)">
                                                {{ ph.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="o1tf-span-6 o1tf-field--outlined">
                                        <span class="o1tf-subgroup-label">{{ t('projects.o1Start') }}</span>
                                        <div class="o1tf-datepair">
                                            <input
                                                v-model="form.time_start"
                                                class="o1tf-input-el o1tf-time-input"
                                                type="time"
                                                step="60"
                                                :disabled="!form.is_assign_hour"
                                                :class="{ 'is-disabled': !form.is_assign_hour }"
                                                :aria-label="t('projects.o1Start') + ' ' + t('projects.o1PhTime')"
                                            />
                                            <input
                                                v-model="form.start_date"
                                                class="o1tf-input-el o1tf-date-input"
                                                type="date"
                                                :aria-label="t('projects.o1Start')"
                                                :placeholder="t('projects.o1PhDateStart')"
                                            />
                                        </div>
                                        <span class="o1tf-field-hint">{{ t('projects.o1StartHint') }}</span>
                                    </div>
                                    <div class="o1tf-span-6 o1tf-field--outlined">
                                        <span class="o1tf-subgroup-label">{{ t('projects.o1End') }}</span>
                                        <div class="o1tf-datepair">
                                            <input
                                                v-model="form.time_end"
                                                class="o1tf-input-el o1tf-time-input"
                                                type="time"
                                                step="60"
                                                :disabled="!form.is_assign_hour"
                                                :class="{ 'is-disabled': !form.is_assign_hour }"
                                                :aria-label="t('projects.o1End') + ' ' + t('projects.o1PhTime')"
                                            />
                                            <input
                                                v-model="form.due_date"
                                                class="o1tf-input-el o1tf-date-input"
                                                type="date"
                                                :aria-label="t('projects.o1End')"
                                                :placeholder="t('projects.o1PhDateEnd')"
                                            />
                                        </div>
                                    </div>

                                    <div class="o1tf-span-12 o1tf-check-row">
                                        <label class="o1tf-check">
                                            <input v-model="form.is_assign_hour" type="checkbox" />
                                            <span>{{ t('projects.o1AssignHour') }}</span>
                                        </label>
                                        <label class="o1tf-check">
                                            <input v-model="form.restrict_subtask_time" type="checkbox" />
                                            <span>{{ t('projects.o1RestrictSubtask') }}</span>
                                        </label>
                                    </div>

                                    <div class="o1tf-user-grid">
                                        <div class="o1tf-field o1tf-field--outlined">
                                            <span class="o1tf-label">{{ t('projects.thAssigneeFull') }}</span>
                                            <O1UserPicker
                                                v-model="form.assignee_ids"
                                                :users="userOptions"
                                                :search-placeholder="t('projects.o1UserSearchPh')"
                                                :search-aria="t('projects.o1UserAriaSearchAssignees')"
                                                :list-aria="t('projects.o1UserListAssignees')"
                                                :empty-text="t('projects.o1UserEmpty')"
                                                :remove-chip-label="chipRemoveLabel"
                                            />
                                            <span class="o1tf-field-hint">{{ t('projects.o1PhAssignees') }} — {{ t('projects.o1MultiHint') }}</span>
                                        </div>
                                        <div class="o1tf-field o1tf-field--outlined">
                                            <span class="o1tf-label">{{ t('projects.o1Owner') }}</span>
                                            <O1UserPicker
                                                v-model="form.owner_ids"
                                                :users="userOptions"
                                                :search-placeholder="t('projects.o1UserSearchPh')"
                                                :search-aria="t('projects.o1UserAriaSearchOwners')"
                                                :list-aria="t('projects.o1UserListOwners')"
                                                :empty-text="t('projects.o1UserEmpty')"
                                                :remove-chip-label="chipRemoveLabel"
                                            />
                                            <span class="o1tf-field-hint">{{ t('projects.o1PhOwners') }}</span>
                                        </div>
                                        <div class="o1tf-field o1tf-field--outlined">
                                            <span class="o1tf-label">{{ t('projects.o1Followers') }}</span>
                                            <O1UserPicker
                                                v-model="form.follower_ids"
                                                :users="userOptions"
                                                :search-placeholder="t('projects.o1UserSearchPh')"
                                                :search-aria="t('projects.o1UserAriaSearchFollowers')"
                                                :list-aria="t('projects.o1UserListFollowers')"
                                                :empty-text="t('projects.o1UserEmpty')"
                                                :remove-chip-label="chipRemoveLabel"
                                            />
                                            <span class="o1tf-field-hint">{{ t('projects.o1PhFollowers') }}</span>
                                        </div>
                                    </div>

                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-12">
                                        <label class="o1tf-label" for="o1-desc">{{ t('projects.o1FieldDesc') }}</label>
                                        <textarea
                                            id="o1-desc"
                                            v-model="form.description"
                                            class="o1tf-input-el"
                                            rows="4"
                                            :placeholder="t('projects.o1PhDesc')"
                                        />
                                    </div>

                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-12">
                                        <span class="o1tf-label">{{ t('projects.o1Attachments') }}</span>
                                        <div class="o1tf-attach-zone">
                                            <input
                                                class="o1tf-attach-input o1tf-input-el"
                                                type="file"
                                                multiple
                                                :aria-label="t('projects.o1Attachments')"
                                                @change="onAttachInput"
                                            />
                                            <span class="o1tf-field-hint">{{ t('projects.o1AttachHint') }}</span>
                                            <ul v-if="pendingFiles.length" class="o1tf-attach-list">
                                                <li v-for="(f, fi) in pendingFiles" :key="'pf-' + fi + '-' + f.name + f.size">
                                                    <span>{{ f.name }} ({{ formatFileSize(f.size) }})</span>
                                                    <button type="button" class="o1tf-attach-rm" @click="removePendingFile(fi)">
                                                        {{ t('projects.o1AttachRemove') }}
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Cài đặt khác -->
                        <section class="o1tf-section">
                            <button type="button" class="o1tf-section-title--btn" @click="sec.other = !sec.other">
                                <span class="o1tf-caret-sec" :class="{ 'is-open': sec.other }" aria-hidden="true">▸</span>
                                {{ t('projects.o1SecOther') }}
                            </button>
                            <div v-show="sec.other" class="o1tf-section-body">
                                <div class="o1tf-grid">
                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-7">
                                        <label class="o1tf-label" for="o1-ttype">{{ t('projects.o1TaskType') }}</label>
                                        <select id="o1-ttype" v-model="form.task_type" class="o1tf-input-el">
                                            <option value="">{{ t('projects.o1SelectType') }}</option>
                                            <option value="general">{{ t('projects.o1TypeGeneral') }}</option>
                                            <option value="report">{{ t('projects.o1TypeReport') }}</option>
                                            <option value="dev">{{ t('projects.o1TypeDev') }}</option>
                                            <option value="meeting">{{ t('projects.o1TypeMeeting') }}</option>
                                            <option value="legal">{{ t('projects.o1TypeLegal') }}</option>
                                        </select>
                                    </div>
                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-5">
                                        <label class="o1tf-label" for="o1-pri">{{ t('projects.o1Priority') }}</label>
                                        <select id="o1-pri" v-model="form.priority" class="o1tf-input-el">
                                            <option value="high">{{ t('projects.o1PriHigh') }}</option>
                                            <option value="medium">{{ t('projects.o1PriMedium') }}</option>
                                            <option value="normal">{{ t('projects.o1PriNormal') }}</option>
                                            <option value="low">{{ t('projects.o1PriLow') }}</option>
                                        </select>
                                    </div>

                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-12">
                                        <label class="o1tf-label" for="o1-pm">{{ t('projects.o1FieldProgressMode') }} <span class="req">*</span></label>
                                        <select id="o1-pm" v-model="form.progress_mode" class="o1tf-input-el" required>
                                            <option v-for="m in progressModes" :key="m.id" :value="m.id">{{ m.label }}</option>
                                        </select>
                                    </div>

                                    <details class="o1tf-metric-block">
                                        <summary>{{ t('projects.o1MetricHelpTitle') }}</summary>
                                        <div class="o1tf-metric-block__body">
                                            <p>
                                                <strong>{{ t('projects.o1EstimateHoursLabel') }}.</strong>
                                                {{ t('projects.o1EstimateHoursHelp') }}
                                            </p>
                                            <p>
                                                <strong>{{ t('projects.o1Complexity') }}.</strong>
                                                {{ t('projects.o1ComplexityHelp') }}
                                            </p>
                                            <p>
                                                <strong>{{ t('projects.o1Impact') }}.</strong>
                                                {{ t('projects.o1ImpactHelp') }}
                                            </p>
                                            <p class="o1tf-computed-weight">{{ t('projects.o1ComputedWeightHint', { value: computedWeightPreview }) }}</p>
                                        </div>
                                    </details>

                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-4">
                                        <label class="o1tf-label" for="o1-est">{{ t('projects.o1EstimateHoursLabel') }}</label>
                                        <input
                                            id="o1-est"
                                            v-model.number="form.estimate_hours"
                                            class="o1tf-input-el"
                                            type="number"
                                            min="0"
                                            step="0.5"
                                        />
                                        <span class="o1tf-field-hint">{{ t('projects.o1EstimateHoursShort') }}</span>
                                    </div>
                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-4">
                                        <label class="o1tf-label" for="o1-c">{{ t('projects.o1Complexity') }}</label>
                                        <input
                                            id="o1-c"
                                            v-model.number="form.complexity"
                                            class="o1tf-input-el"
                                            type="number"
                                            min="1"
                                            max="5"
                                        />
                                        <span class="o1tf-field-hint">{{ t('projects.o1CiScaleHint') }}</span>
                                    </div>
                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-4">
                                        <label class="o1tf-label" for="o1-i">{{ t('projects.o1Impact') }}</label>
                                        <input
                                            id="o1-i"
                                            v-model.number="form.impact"
                                            class="o1tf-input-el"
                                            type="number"
                                            min="1"
                                            max="5"
                                        />
                                        <span class="o1tf-field-hint">{{ t('projects.o1CiScaleHint') }}</span>
                                    </div>

                                    <div v-if="form.progress_mode === 'manual_pct'" class="o1tf-field o1tf-field--outlined o1tf-span-6">
                                        <label class="o1tf-label" for="o1-mp">{{ t('projects.o1ManualPct') }}</label>
                                        <input
                                            id="o1-mp"
                                            v-model.number="form.manual_progress_pct"
                                            class="o1tf-input-el"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                        />
                                    </div>
                                    <template v-if="form.progress_mode === 'volume_ratio'">
                                        <div class="o1tf-field o1tf-field--outlined o1tf-span-6">
                                            <label class="o1tf-label" for="o1-vt">{{ t('projects.o1VolTotal') }}</label>
                                            <input id="o1-vt" v-model.number="form.volume_total" class="o1tf-input-el" type="number" min="0" />
                                        </div>
                                        <div class="o1tf-field o1tf-field--outlined o1tf-span-6">
                                            <label class="o1tf-label" for="o1-vd">{{ t('projects.o1VolDone') }}</label>
                                            <input id="o1-vd" v-model.number="form.volume_done" class="o1tf-input-el" type="number" min="0" />
                                        </div>
                                    </template>
                                    <template v-if="form.progress_mode === 'checklist_ratio'">
                                        <div class="o1tf-field o1tf-field--outlined o1tf-span-6">
                                            <label class="o1tf-label" for="o1-ct">{{ t('projects.o1ClTotal') }}</label>
                                            <input id="o1-ct" v-model.number="form.checklist_total" class="o1tf-input-el" type="number" min="0" />
                                        </div>
                                        <div class="o1tf-field o1tf-field--outlined o1tf-span-6">
                                            <label class="o1tf-label" for="o1-cd">{{ t('projects.o1ClDone') }}</label>
                                            <input id="o1-cd" v-model.number="form.checklist_done" class="o1tf-input-el" type="number" min="0" />
                                        </div>
                                    </template>

                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-6">
                                        <label class="o1tf-label" for="o1-cat">{{ t('projects.o1FieldCategory') }}</label>
                                        <input
                                            id="o1-cat"
                                            v-model="form.category"
                                            class="o1tf-input-el"
                                            type="text"
                                            :placeholder="t('projects.o1PhCategory')"
                                        />
                                    </div>
                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-6">
                                        <label class="o1tf-label" for="o1-tl">{{ t('projects.o1Tasklist') }}</label>
                                        <select id="o1-tl" v-model="form.tasklist_id" class="o1tf-input-el">
                                            <option value="">{{ t('projects.o1ParentEmpty') }}</option>
                                            <option v-for="ph in projectPhases" :key="'tl-' + ph.id" :value="String(ph.id)">
                                                {{ ph.name }}
                                            </option>
                                        </select>
                                        <span class="o1tf-field-hint">{{ t('projects.o1PhTasklist') }}</span>
                                    </div>

                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-12">
                                        <label class="o1tf-label" for="o1-parent">{{ t('projects.o1ParentTask') }}</label>
                                        <select id="o1-parent" v-model="form.parent_id" class="o1tf-input-el">
                                            <option value="">{{ t('projects.o1ParentEmpty') }}</option>
                                            <option v-for="tk in parentTaskOptions" :key="'pt-' + tk.id" :value="String(tk.id)">
                                                {{ tk.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Đối tượng & nhãn & tùy biến -->
                        <section class="o1tf-section">
                            <button type="button" class="o1tf-section-title--btn" @click="sec.related = !sec.related">
                                <span class="o1tf-caret-sec" :class="{ 'is-open': sec.related }" aria-hidden="true">▸</span>
                                {{ t('projects.o1SecRelated') }}
                            </button>
                            <div v-show="sec.related" class="o1tf-section-body">
                                <div class="o1tf-grid">
                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-12">
                                        <label class="o1tf-label" for="o1-rel">{{ t('projects.o1SecRelated') }}</label>
                                        <textarea
                                            id="o1-rel"
                                            v-model="form.related_note"
                                            class="o1tf-input-el"
                                            rows="2"
                                            :placeholder="t('projects.o1PhRelated')"
                                        />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="o1tf-section">
                            <button type="button" class="o1tf-section-title--btn" @click="sec.tags = !sec.tags">
                                <span class="o1tf-caret-sec" :class="{ 'is-open': sec.tags }" aria-hidden="true">▸</span>
                                {{ t('projects.o1SecTags') }}
                            </button>
                            <div v-show="sec.tags" class="o1tf-section-body">
                                <div class="o1tf-field o1tf-field--outlined">
                                    <label class="o1tf-label" for="o1-tags">{{ t('projects.o1SecTags') }}</label>
                                    <input
                                        id="o1-tags"
                                        v-model="form.tags_text"
                                        class="o1tf-input-el"
                                        type="text"
                                        :placeholder="t('projects.o1PhTags')"
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="o1tf-section">
                            <button type="button" class="o1tf-section-title--btn" @click="sec.custom = !sec.custom">
                                <span class="o1tf-caret-sec" :class="{ 'is-open': sec.custom }" aria-hidden="true">▸</span>
                                {{ t('projects.o1SecCustom') }}
                            </button>
                            <div v-show="sec.custom" class="o1tf-section-body">
                                <div class="o1tf-grid">
                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-12">
                                        <label class="o1tf-label" for="o1-cf1">{{ t('projects.o1PhCfManager') }}</label>
                                        <textarea
                                            id="o1-cf1"
                                            v-model="form.cf_manager"
                                            class="o1tf-input-el"
                                            rows="2"
                                            :placeholder="t('projects.o1PhCfManager')"
                                        />
                                    </div>
                                    <div class="o1tf-field o1tf-field--outlined o1tf-span-12">
                                        <label class="o1tf-label" for="o1-cf2">{{ t('projects.o1PhCfLegal') }}</label>
                                        <textarea
                                            id="o1-cf2"
                                            v-model="form.cf_legal"
                                            class="o1tf-input-el"
                                            rows="2"
                                            :placeholder="t('projects.o1PhCfLegal')"
                                        />
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div
                        id="tab-panel-advanced"
                        class="o1tf-tab-pane"
                        role="tabpanel"
                        aria-labelledby="tab-btn-advanced"
                        :class="{ 'is-active': tab === 'advanced' }"
                    >
                        <section class="o1tf-section">
                            <button type="button" class="o1tf-section-title--btn" @click="sec.advanced = !sec.advanced">
                                <span class="o1tf-caret-sec" :class="{ 'is-open': sec.advanced }" aria-hidden="true">▸</span>
                                {{ t('projects.o1SecPermissions') }}
                            </button>
                            <div v-show="sec.advanced" class="o1tf-section-body">
                                <div class="o1tf-field--outlined">
                                    <p class="o1tf-field-hint" style="margin-top: 0">{{ t('projects.o1AdvancedHint') }}</p>
                                    <div class="o1tf-check-row">
                                        <label class="o1tf-check">
                                            <input type="checkbox" disabled />
                                            <span>{{ t('projects.o1PermCross') }}</span>
                                        </label>
                                    </div>
                                    <div class="o1tf-check-row">
                                        <label class="o1tf-check">
                                            <input type="checkbox" disabled />
                                            <span>{{ t('projects.o1PermParent') }}</span>
                                        </label>
                                    </div>
                                    <fieldset class="o1tf-field" style="border: none; padding: 0; margin: 0.75rem 0 0">
                                        <legend class="o1tf-label">{{ t('projects.o1ReportDesc') }}</legend>
                                        <div class="o1tf-radio-group">
                                            <label><input v-model="form._advDesc" type="radio" value="no" disabled /> {{ t('projects.o1No') }}</label>
                                            <label><input type="radio" value="alway" disabled /> {{ t('projects.o1ReportDescAlway') }}</label>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
                <p v-if="err" class="ppms-error" role="alert">{{ err }}</p>
            </div>
            <div class="ppms-modal-actions">
                <button type="button" class="ppms-btn-ghost" @click="close">{{ t('common.cancel') }}</button>
                <button type="button" class="ppms-btn-primary" @click="submit">{{ t('common.save') }}</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import O1UserPicker from './O1UserPicker.vue';

const { t } = useI18n();

const props = defineProps({
    modelValue: { type: Boolean, required: true },
    project: { type: Object, required: true },
    projectPhases: { type: Array, required: true },
});

const emit = defineEmits(['update:modelValue', 'submit']);

const tab = ref('general');
const err = ref('');
const pendingFiles = ref([]);

const sec = ref({
    general: true,
    other: true,
    related: false,
    tags: false,
    custom: false,
    advanced: true,
});

const form = ref({
    name: '',
    description: '',
    project_phase_id: '',
    tasklist_id: '',
    parent_id: '',
    assignee_ids: [],
    owner_ids: [],
    follower_ids: [],
    estimate_hours: 8,
    complexity: 3,
    impact: 3,
    priority: 'normal',
    task_type: '',
    due_date: '',
    start_date: '',
    time_start: '',
    time_end: '',
    category: '',
    progress_mode: 'status_default',
    manual_progress_pct: null,
    volume_total: null,
    volume_done: null,
    checklist_total: null,
    checklist_done: null,
    is_assign_hour: false,
    restrict_subtask_time: false,
    related_note: '',
    tags_text: '',
    cf_manager: '',
    cf_legal: '',
    _advDesc: 'no',
});

const PRI_MAP = {
    high: { c: 4, i: 5 },
    medium: { c: 3, i: 4 },
    normal: { c: 3, i: 3 },
    low: { c: 2, i: 2 },
};

const TYPE_LABEL_KEY = {
    general: 'o1TypeGeneral',
    report: 'o1TypeReport',
    dev: 'o1TypeDev',
    meeting: 'o1TypeMeeting',
    legal: 'o1TypeLegal',
};

const codePlaceholder = computed(() => {
    const y = new Date().getFullYear();

    return `DA_{${y}}_{0001}`;
});

const progressModes = computed(() =>
    ['status_default', 'manual_pct', 'volume_ratio', 'checklist_ratio', 'child_weight', 'time_auto'].map((id) => ({
        id,
        label: t(`projects.pdPm_${id}`),
    })),
);

const computedWeightPreview = computed(() => {
    const c = Math.min(5, Math.max(1, Number(form.value.complexity) || 3));
    const i = Math.min(5, Math.max(1, Number(form.value.impact) || 3));

    return (c * 0.4 + i * 0.6).toFixed(2);
});

const userOptions = computed(() => {
    const m = new Map();
    for (const u of props.project?.executor_users || []) {
        m.set(u.id, u);
    }
    for (const u of props.project?.follower_users || []) {
        m.set(u.id, u);
    }
    const owner = props.project?.owner;
    if (owner?.id) {
        m.set(owner.id, { id: owner.id, name: owner.name, email: owner.email });
    }

    return [...m.values()].sort((a, b) => String(a.name).localeCompare(String(b.name)));
});

function chipRemoveLabel(u) {
    return t('projects.o1UserChipRemove', { name: u.name || String(u.id) });
}

function formatFileSize(bytes) {
    if (bytes == null || !Number.isFinite(bytes)) {
        return '';
    }
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function onAttachInput(e) {
    const files = e.target.files;
    if (!files?.length) {
        return;
    }
    pendingFiles.value = [...pendingFiles.value, ...Array.from(files)];
    e.target.value = '';
}

function removePendingFile(idx) {
    pendingFiles.value = pendingFiles.value.filter((_, j) => j !== idx);
}

const parentTaskOptions = computed(() => {
    const tasks = props.project?.tasks || [];

    return tasks.filter((tk) => !tk.parent_id);
});

watch(
    () => form.value.priority,
    (p) => {
        const row = PRI_MAP[p];
        if (row) {
            form.value.complexity = row.c;
            form.value.impact = row.i;
        }
    },
);

function resetForm() {
    err.value = '';
    pendingFiles.value = [];
    tab.value = 'general';
    sec.value = {
        general: true,
        other: true,
        related: false,
        tags: false,
        custom: false,
        advanced: true,
    };
    form.value = {
        name: '',
        description: '',
        project_phase_id: '',
        tasklist_id: '',
        parent_id: '',
        assignee_ids: [],
        owner_ids: [],
        follower_ids: [],
        estimate_hours: 8,
        complexity: 3,
        impact: 3,
        priority: 'normal',
        task_type: '',
        due_date: '',
        start_date: '',
        time_start: '',
        time_end: '',
        category: '',
        progress_mode: 'status_default',
        manual_progress_pct: null,
        volume_total: null,
        volume_done: null,
        checklist_total: null,
        checklist_done: null,
        is_assign_hour: false,
        restrict_subtask_time: false,
        related_note: '',
        tags_text: '',
        cf_manager: '',
        cf_legal: '',
        _advDesc: 'no',
    };
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) {
            resetForm();
        }
    },
);

function close() {
    emit('update:modelValue', false);
}

function phaseName(id) {
    if (!id) {
        return '';
    }
    const ph = props.projectPhases.find((p) => String(p.id) === String(id));

    return ph?.name || String(id);
}

function buildMetaLines(f) {
    const lines = [];
    if (f.is_assign_hour) {
        lines.push(`${t('projects.o1AssignHour')}: ${f.time_start || '--'} → ${f.time_end || '--'}`);
    }
    if (f.restrict_subtask_time) {
        lines.push(`[${t('projects.o1RestrictSubtask')}]`);
    }
    if (f.tasklist_id && String(f.tasklist_id) !== String(f.project_phase_id || '')) {
        lines.push(`${t('projects.o1Tasklist')}: ${phaseName(f.tasklist_id)}`);
    }
    if (f.task_type && TYPE_LABEL_KEY[f.task_type]) {
        lines.push(`${t('projects.o1TaskType')}: ${t('projects.' + TYPE_LABEL_KEY[f.task_type])}`);
    }
    if (f.related_note?.trim()) {
        lines.push(`${t('projects.o1SecRelated')}: ${f.related_note.trim()}`);
    }
    if (f.tags_text?.trim()) {
        lines.push(`${t('projects.o1SecTags')}: ${f.tags_text.trim()}`);
    }
    if (f.cf_manager?.trim()) {
        lines.push(`${t('projects.o1PhCfManager')}: ${f.cf_manager.trim()}`);
    }
    if (f.cf_legal?.trim()) {
        lines.push(`${t('projects.o1PhCfLegal')}: ${f.cf_legal.trim()}`);
    }

    return lines;
}

function mergeDescription(base, metaLines) {
    const tail = metaLines.filter(Boolean).join('\n');
    if (!tail) {
        return base?.trim() || null;
    }
    const head = base?.trim() || '';

    return head ? `${head}\n\n—\n${tail}` : tail;
}

function buildPayload() {
    const f = form.value;
    const name = String(f.name || '').trim();
    if (!name) {
        return null;
    }

    const payload = {
        name,
        estimate_hours: Number(f.estimate_hours) || 0,
        complexity: Math.min(5, Math.max(1, Number(f.complexity) || 3)),
        impact: Math.min(5, Math.max(1, Number(f.impact) || 3)),
        progress_mode: f.progress_mode || 'status_default',
    };

    const assignees = Array.isArray(f.assignee_ids) ? f.assignee_ids.map(Number).filter(Boolean) : [];
    const owners = Array.isArray(f.owner_ids) ? f.owner_ids.map(Number).filter(Boolean) : [];
    const followers = Array.isArray(f.follower_ids) ? f.follower_ids.map(Number).filter(Boolean) : [];
    payload.assignee_ids = assignees;
    payload.owner_ids = owners;
    payload.follower_ids = followers;
    if (assignees.length) {
        payload.assignee_id = assignees[0];
    }

    if (f.project_phase_id) {
        payload.project_phase_id = Number(f.project_phase_id);
    }
    if (f.parent_id) {
        payload.parent_id = Number(f.parent_id);
    }
    if (f.due_date) {
        payload.due_date = f.due_date;
    }

    const catParts = [];
    if (f.task_type && TYPE_LABEL_KEY[f.task_type]) {
        catParts.push(t('projects.' + TYPE_LABEL_KEY[f.task_type]));
    }
    if (f.category?.trim()) {
        catParts.push(f.category.trim());
    }
    if (catParts.length) {
        payload.category = catParts.join(' · ').slice(0, 128);
    }

    if (f.progress_mode === 'manual_pct' && f.manual_progress_pct != null && f.manual_progress_pct !== '') {
        payload.manual_progress_pct = Number(f.manual_progress_pct);
    }
    if (f.progress_mode === 'volume_ratio') {
        if (f.volume_total != null && f.volume_total !== '') {
            payload.volume_total = Number(f.volume_total);
        }
        if (f.volume_done != null && f.volume_done !== '') {
            payload.volume_done = Number(f.volume_done);
        }
    }
    if (f.progress_mode === 'checklist_ratio') {
        if (f.checklist_total != null && f.checklist_total !== '') {
            payload.checklist_total = Number(f.checklist_total);
        }
        if (f.checklist_done != null && f.checklist_done !== '') {
            payload.checklist_done = Number(f.checklist_done);
        }
    }

    let desc = f.description?.trim() || '';
    if (f.start_date) {
        desc = desc ? `${desc}\n${t('projects.o1Start')}: ${f.start_date}` : `${t('projects.o1Start')}: ${f.start_date}`;
    }

    const meta = buildMetaLines(f);
    payload.description = mergeDescription(desc, meta);

    return payload;
}

function submit() {
    const payload = buildPayload();
    if (!payload) {
        err.value = t('projects.o1ErrTitle');

        return;
    }
    err.value = '';
    emit('submit', { payload, files: [...pendingFiles.value] });
}
</script>
