<!-- eslint-disable vue/no-mutating-props -- metaForm là state dùng chung với parent -->
<template>
    <div v-if="modelValue" class="ppms-modal-backdrop" @click.self="$emit('update:modelValue', false)">
        <div class="ppms-modal ppms-modal--meta-edit" role="dialog" aria-modal="true" :aria-labelledby="metaTitleId">
            <h2 :id="metaTitleId">{{ t('projects.editMetaModalTitle') }}</h2>
            <form class="ppms-stack ppms-meta-edit-form" @submit.prevent="$emit('save')">
                <label class="ppms-field">
                    <span>{{ t('projects.fieldName') }} <span class="ppms-req" aria-hidden="true">*</span></span>
                    <input v-model="metaForm.name" type="text" required autocomplete="off" />
                </label>
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

                <section v-if="canManageProject" class="ppms-meta-team-block" :aria-label="t('projects.createSectionTeam')">
                    <h3 class="ppms-meta-team-block__title">{{ t('projects.createSectionTeam') }}</h3>
                    <p class="ppms-pc-section-lead">{{ t('projects.createSectionTeamLead') }}</p>
                    <div class="ppms-pc-row">
                        <div class="ppms-field ppms-pc-col ppms-pc-col--12">
                            <div class="ppms-pc-label-row">
                                <span>{{ t('projects.fieldOwner') }}</span>
                                <span class="ppms-req" aria-hidden="true">*</span>
                            </div>
                            <div v-if="metaForm.owner_id" class="ppms-pc-owner-picked">
                                <span class="ppms-pc-owner-picked__text">
                                    {{ userCache[metaForm.owner_id]?.name }} ({{ userCache[metaForm.owner_id]?.email }})
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
                                    name="owner_lookup_meta"
                                    class="ppms-pc-userpick__search"
                                    :placeholder="t('projects.createUserSearchPlaceholder')"
                                    autocomplete="off"
                                    enterkeyhint="search"
                                    @input="scheduleOwnerLookup"
                                />
                                <ul v-if="ownerLookupOpen && ownerHits.length" class="ppms-pc-lookup-hits" role="listbox">
                                    <li v-for="u in ownerHits" :key="'owm-' + u.id" role="none">
                                        <button type="button" class="ppms-pc-lookup-hit" @click="pickOwner(u)">
                                            {{ u.name }} — {{ u.email }}
                                        </button>
                                    </li>
                                </ul>
                                <p
                                    v-if="!metaForm.owner_id && ownerQuery.trim().length > 0 && ownerQuery.trim().length < LOOKUP_MIN_LEN"
                                    class="ppms-pc-field-hint"
                                >
                                    {{ t('projects.createUserSearchMinHint') }}
                                </p>
                                <p
                                    v-if="!metaForm.owner_id && ownerQuery.trim().length >= LOOKUP_MIN_LEN && ownerLookupPending"
                                    class="ppms-muted ppms-pc-field-hint"
                                >
                                    {{ t('common.loading') }}
                                </p>
                                <p
                                    v-else-if="!metaForm.owner_id && ownerQuery.trim().length >= LOOKUP_MIN_LEN && !ownerHits.length"
                                    class="ppms-pc-userpick__empty"
                                >
                                    {{ t('projects.createUserSearchEmpty') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="ppms-pc-ms-grid">
                        <div class="ppms-pc-ms-panel" role="group" :aria-label="t('projects.createExecutors')">
                            <div class="ppms-pc-ms-panel__head">
                                <span>{{ t('projects.createGroupExecutors') }}</span>
                                <span class="ppms-pc-ms-panel__badge" aria-hidden="true">{{ executorSelectedCount }}</span>
                            </div>
                            <div class="ppms-pc-ms-panel__body">
                                <div v-if="executorChipsOnly.length" class="ppms-pc-chips" aria-label="selected">
                                    <span v-for="u in executorChipsOnly" :key="'exc-m-' + u.id" class="ppms-pc-chip">
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
                                    <p
                                        v-if="executorSearch.trim().length > 0 && executorSearch.trim().length < LOOKUP_MIN_LEN"
                                        class="ppms-pc-field-hint"
                                    >
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
                                    <div
                                        v-else-if="executorSearch.trim().length >= LOOKUP_MIN_LEN"
                                        class="ppms-pc-userpick__list"
                                        role="group"
                                    >
                                        <label
                                            v-for="u in executorHits"
                                            :key="'exm-' + u.id"
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
                        <div class="ppms-pc-ms-panel" role="group" :aria-label="t('projects.createFollowers')">
                            <div class="ppms-pc-ms-panel__head">
                                <span>{{ t('projects.createGroupFollowers') }}</span>
                                <span class="ppms-pc-ms-panel__badge" aria-hidden="true">{{ followerSelectedCount }}</span>
                            </div>
                            <div class="ppms-pc-ms-panel__body">
                                <div v-if="followerChipsOnly.length" class="ppms-pc-chips" aria-label="selected">
                                    <span v-for="u in followerChipsOnly" :key="'fw-m-' + u.id" class="ppms-pc-chip">
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
                                    <p
                                        v-if="followerSearch.trim().length > 0 && followerSearch.trim().length < LOOKUP_MIN_LEN"
                                        class="ppms-pc-field-hint"
                                    >
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
                                    <div
                                        v-else-if="followerSearch.trim().length >= LOOKUP_MIN_LEN"
                                        class="ppms-pc-userpick__list"
                                        role="group"
                                    >
                                        <label
                                            v-for="u in followerHits"
                                            :key="'fwm-' + u.id"
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

                <div>
                    <span class="ppms-field" style="display: block; margin-bottom: 0.5rem">{{ t('projects.suppliersTitle') }}</span>
                    <div v-for="(s, i) in metaForm.suppliers" :key="i" class="ppms-supplier-edit-row ppms-mt-sm">
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
                            @click.prevent="$emit('remove-supplier', i)"
                        >
                            {{ t('projects.removeSupplier') }}
                        </button>
                    </div>
                    <button type="button" class="ppms-btn-ghost ppms-mt-sm" @click="$emit('add-supplier')">
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
                <p v-if="metaErr" class="ppms-error" role="alert">{{ metaErr }}</p>
                <div class="ppms-modal-actions">
                    <button type="button" class="ppms-btn-ghost" @click="$emit('update:modelValue', false)">
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
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { TIMELINE_PHASES } from '../../constants/projectDetail';

const { t } = useI18n();

const props = defineProps({
    modelValue: { type: Boolean, required: true },
    metaForm: { type: Object, required: true },
    metaErr: { type: String, default: '' },
    canManageProject: { type: Boolean, default: false },
});

defineEmits(['update:modelValue', 'save', 'add-supplier', 'remove-supplier']);

const metaTitleId = 'ppms-meta-edit-title';

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
    props.metaForm.owner_id = String(u.id);
    mergeUsersToCache([u]);
    ownerQuery.value = '';
    ownerHits.value = [];
    ownerLookupOpen.value = false;
}

function clearOwnerSelection() {
    props.metaForm.owner_id = '';
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

const executorSelectedCount = computed(() => {
    const v = props.metaForm.executor_user_ids;

    return Array.isArray(v) ? v.length : 0;
});

const followerSelectedCount = computed(() => {
    const v = props.metaForm.follower_user_ids;

    return Array.isArray(v) ? v.length : 0;
});

const executorIdSet = computed(() => new Set((props.metaForm.executor_user_ids || []).map(String)));

const followerIdSet = computed(() => new Set((props.metaForm.follower_user_ids || []).map(String)));

const executorHitIdSet = computed(() => new Set(executorHits.value.map((u) => String(u.id))));

const followerHitIdSet = computed(() => new Set(followerHits.value.map((u) => String(u.id))));

const executorChipsOnly = computed(() => {
    const ids = props.metaForm.executor_user_ids || [];

    return ids
        .filter((id) => !executorHitIdSet.value.has(String(id)))
        .map((id) => userCache[String(id)])
        .filter(Boolean);
});

const followerChipsOnly = computed(() => {
    const ids = props.metaForm.follower_user_ids || [];

    return ids
        .filter((id) => !followerHitIdSet.value.has(String(id)))
        .map((id) => userCache[String(id)])
        .filter(Boolean);
});

function toggleExecutor(userId) {
    const sid = String(userId);
    const arr = [...(props.metaForm.executor_user_ids || [])];
    const i = arr.indexOf(sid);
    if (i >= 0) {
        arr.splice(i, 1);
        const still =
            String(props.metaForm.owner_id) === sid || (props.metaForm.follower_user_ids || []).map(String).includes(sid);
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
    props.metaForm.executor_user_ids = arr;
}

function toggleFollower(userId) {
    const sid = String(userId);
    const arr = [...(props.metaForm.follower_user_ids || [])];
    const i = arr.indexOf(sid);
    if (i >= 0) {
        arr.splice(i, 1);
        const still =
            String(props.metaForm.owner_id) === sid || (props.metaForm.executor_user_ids || []).map(String).includes(sid);
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
    props.metaForm.follower_user_ids = arr;
}

async function hydrateUserCachesFromForm() {
    const ids = [];
    if (props.metaForm.owner_id) {
        ids.push(Number(props.metaForm.owner_id));
    }
    for (const id of props.metaForm.executor_user_ids || []) {
        ids.push(Number(id));
    }
    for (const id of props.metaForm.follower_user_ids || []) {
        ids.push(Number(id));
    }
    const uniq = [...new Set(ids.filter((n) => Number.isFinite(n) && n > 0))];
    if (!uniq.length) {
        return;
    }
    try {
        const { data } = await axios.get('/api/users/lookup', { params: { ids: uniq } });
        mergeUsersToCache(Array.isArray(data) ? data : []);
    } catch {
        /* ignore */
    }
}

watch(
    () => props.modelValue,
    async (open) => {
        if (!open) {
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
    },
);

function handleDocumentClick(target) {
    if (!(target instanceof Node)) {
        return;
    }
    if (ownerLookupOpen.value && ownerLookupEl.value && !ownerLookupEl.value.contains(target)) {
        ownerLookupOpen.value = false;
    }
}

function closeOwnerLookup() {
    ownerLookupOpen.value = false;
}

defineExpose({ handleDocumentClick, closeOwnerLookup });
</script>

<style scoped>
.ppms-modal--meta-edit {
    width: min(96vw, 44rem);
    max-height: min(92vh, 52rem);
    overflow: auto;
}
.ppms-meta-edit-form {
    max-height: none;
}
.ppms-meta-team-block {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--ppms-border-subtle, rgba(0, 0, 0, 0.1));
}
.ppms-meta-team-block__title {
    margin: 0 0 0.35rem;
    font-size: 1rem;
    font-weight: 600;
}
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
