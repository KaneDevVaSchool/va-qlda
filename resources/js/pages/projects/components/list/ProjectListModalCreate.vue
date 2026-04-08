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
                <h2 id="ppms-modal-create-project-title">{{ t('projects.createModalTitle') }}</h2>
                <p class="ppms-pc-modal-subtitle">{{ t('projects.createModalSubtitle') }}</p>
            </div>
            <form class="ppms-pc-form" @submit.prevent="$emit('submit')">
                <div class="ppms-pc-form-body">
                    <section class="ppms-pc-card" aria-labelledby="ppms-pc-card-project">
                        <h3 id="ppms-pc-card-project" class="ppms-pc-card__title">{{ t('projects.createCardProjectCore') }}</h3>

                        <h4 class="ppms-pc-subsection-title">{{ t('projects.createSectionBasic') }}</h4>
                        <div class="ppms-pc-row">
                            <label class="ppms-field ppms-pc-col ppms-pc-col--4">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.createFieldCode') }}</span>
                                </div>
                                <input
                                    type="text"
                                    disabled
                                    name="code"
                                    autocomplete="off"
                                    :placeholder="t('projects.createFieldCodePh')"
                                    value=""
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
                            <label class="ppms-field ppms-pc-col ppms-pc-col--6">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.fieldType') }}</span>
                                    <span class="ppms-req" aria-hidden="true">*</span>
                                </div>
                                <select v-model="form.type" class="ppms-pc-select" name="type" required>
                                    <option value="maintenance">{{ t('projects.type.maintenance') }}</option>
                                    <option value="delivery">{{ t('projects.type.delivery') }}</option>
                                    <option value="rnd">{{ t('projects.type.rnd') }}</option>
                                </select>
                            </label>
                            <label class="ppms-field ppms-pc-col ppms-pc-col--6">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.createFieldEstimatedValue') }}</span>
                                </div>
                                <input type="text" disabled name="estimated_value_display" value="—" />
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
                        <div class="ppms-pc-row">
                            <label class="ppms-field ppms-pc-col ppms-pc-col--12">
                                <div class="ppms-pc-label-row">
                                    <span>{{ t('projects.fieldOwner') }}</span>
                                    <span class="ppms-req" aria-hidden="true">*</span>
                                </div>
                                <select v-model="form.owner_id" class="ppms-pc-select" name="owner_id" required>
                                    <option value="" disabled>{{ t('projects.createSelectOwner') }}</option>
                                    <option v-for="u in userOptions" :key="'f-' + u.id" :value="String(u.id)">
                                        {{ u.name }} ({{ u.email }})
                                    </option>
                                </select>
                            </label>
                        </div>
                        <div class="ppms-pc-ms-grid">
                            <div
                                class="ppms-pc-ms-panel"
                                role="group"
                                :aria-label="t('projects.createExecutors')"
                            >
                                <div class="ppms-pc-ms-panel__head">
                                    <span>{{ t('projects.createExecutors') }}</span>
                                    <span class="ppms-pc-ms-panel__badge" aria-hidden="true">{{ executorSelectedCount }}</span>
                                </div>
                                <div class="ppms-pc-ms-panel__body">
                                    <div class="ppms-pc-userpick">
                                        <input
                                            v-model="executorSearch"
                                            type="search"
                                            class="ppms-pc-userpick__search"
                                            :placeholder="t('projects.createUserSearchPlaceholder')"
                                            autocomplete="off"
                                            enterkeyhint="search"
                                        />
                                        <div
                                            v-if="filteredExecutors.length === 0"
                                            class="ppms-pc-userpick__empty"
                                        >
                                            {{ t('projects.createUserSearchEmpty') }}
                                        </div>
                                        <div v-else class="ppms-pc-userpick__list" role="group">
                                            <label
                                                v-for="u in filteredExecutors"
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
                                    <span>{{ t('projects.createFollowers') }}</span>
                                    <span class="ppms-pc-ms-panel__badge" aria-hidden="true">{{ followerSelectedCount }}</span>
                                </div>
                                <div class="ppms-pc-ms-panel__body">
                                    <div class="ppms-pc-userpick">
                                        <input
                                            v-model="followerSearch"
                                            type="search"
                                            class="ppms-pc-userpick__search"
                                            :placeholder="t('projects.createUserSearchPlaceholder')"
                                            autocomplete="off"
                                            enterkeyhint="search"
                                        />
                                        <div
                                            v-if="filteredFollowers.length === 0"
                                            class="ppms-pc-userpick__empty"
                                        >
                                            {{ t('projects.createUserSearchEmpty') }}
                                        </div>
                                        <div v-else class="ppms-pc-userpick__list" role="group">
                                            <label
                                                v-for="u in filteredFollowers"
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
import { computed, ref, useId, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import enLocale from '@/i18n/locales/en.json';
import viLocale from '@/i18n/locales/vi.json';

const { t, locale } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    formError: { type: String, default: '' },
    userOptions: { type: Array, required: true },
});

defineEmits(['submit']);

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

const executorSearch = ref('');
const followerSearch = ref('');

const executorIdSet = computed(() => new Set((props.form.executor_user_ids || []).map(String)));

const followerIdSet = computed(() => new Set((props.form.follower_user_ids || []).map(String)));

function userMatchesQuery(u, q) {
    const needle = q.trim().toLowerCase();
    if (!needle) {
        return true;
    }
    const hay = `${u.name ?? ''} ${u.email ?? ''}`.toLowerCase();

    return hay.includes(needle);
}

const filteredExecutors = computed(() => props.userOptions.filter((u) => userMatchesQuery(u, executorSearch.value)));

const filteredFollowers = computed(() => props.userOptions.filter((u) => userMatchesQuery(u, followerSearch.value)));

function toggleExecutor(userId) {
    const sid = String(userId);
    const arr = [...(props.form.executor_user_ids || [])];
    const i = arr.indexOf(sid);
    if (i >= 0) {
        arr.splice(i, 1);
    } else {
        arr.push(sid);
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
    } else {
        arr.push(sid);
    }
    // eslint-disable-next-line vue/no-mutating-props -- intentional shared form state
    props.form.follower_user_ids = arr;
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

watch(open, (isOpen) => {
    if (!isOpen) {
        progressCalcOpen.value = false;
        executorSearch.value = '';
        followerSearch.value = '';
    }
});

function handleDocumentClick(target) {
    if (!(target instanceof Node)) {
        return;
    }
    if (progressCalcOpen.value && progressCalcEl.value && !progressCalcEl.value.contains(target)) {
        progressCalcOpen.value = false;
    }
}

function closeProgressCalc() {
    progressCalcOpen.value = false;
}

defineExpose({ handleDocumentClick, closeProgressCalc });
</script>
