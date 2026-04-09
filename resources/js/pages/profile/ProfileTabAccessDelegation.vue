<template>
    <div class="ppms-profile-access">
        <section class="ppms-profile-access-block ppms-profile-access-block--perm" :aria-labelledby="'acc-perm-' + uid">
            <h2 :id="'acc-perm-' + uid" class="ppms-profile-access-h2">{{ t('profile.accessSectionPermissions') }}</h2>
            <ProfileTabPermissions compact />
        </section>

        <section class="ppms-profile-access-block" :aria-labelledby="'acc-del-' + uid">
            <h2 :id="'acc-del-' + uid" class="ppms-profile-access-h2">{{ t('profile.accessSectionDelegation') }}</h2>
            <p class="ppms-profile-access-lead">{{ t('profile.delegationIntro') }}</p>
            <p v-if="canManageOthers" class="ppms-profile-access-lead ppms-profile-access-lead--admin">
                {{ t('profile.delegationIntroAdmin') }}
            </p>

            <div v-if="canManageOthers" class="ppms-profile-access-card ppms-profile-access-card--delegator">
                <div class="ppms-profile-access-row">
                    <label class="ppms-profile-access-h3 ppms-profile-access-label--heading" for="deleg-search">{{
                        t('profile.delegationDelegatorLabel')
                    }}</label>
                    <div ref="delegLookupEl" class="ppms-profile-access-lookup">
                        <input
                            id="deleg-search"
                            v-model="delegSearchQ"
                            type="search"
                            class="ppms-profile-access-input"
                            autocomplete="off"
                            :placeholder="t('profile.delegationDelegatorPlaceholder')"
                            :aria-expanded="delegPickCandidates.length > 0"
                            aria-controls="deleg-pick-list"
                            @input="onDelegatorSearchInput"
                            @keydown.escape.prevent="closeDelegPick"
                        />
                        <ul
                            v-if="delegPickCandidates.length"
                            id="deleg-pick-list"
                            class="ppms-profile-access-pick"
                            role="listbox"
                        >
                            <li v-for="u in delegPickCandidates" :key="u.id" role="option">
                                <button type="button" class="ppms-profile-access-pick-btn" @click="selectDelegatorUser(u)">
                                    <span class="ppms-profile-access-user">{{ u.name }}</span>
                                    <span class="ppms-profile-access-email">{{ u.email }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="ppms-profile-access-delegator-actions">
                        <p v-if="delegatorContext" class="ppms-profile-access-picked ppms-profile-access-picked--inline">
                            <strong>{{ delegatorContext.name }}</strong>
                            <span class="ppms-profile-access-email">· {{ delegatorContext.email }}</span>
                        </p>
                        <button
                            v-if="delegatorContext"
                            type="button"
                            class="ppms-pf-btn ppms-profile-access-btn-secondary"
                            @click="clearDelegatorContext"
                        >
                            {{ t('profile.delegationDelegatorSelf') }}
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="canManageOthers && delegatorContext"
                class="ppms-profile-access-banner ppms-profile-access-banner--context"
                role="status"
            >
                {{ t('profile.delegationContextBanner', { name: delegatorContext.name }) }}
            </div>

            <div v-if="delErr" class="ppms-profile-access-banner ppms-profile-access-banner--err" role="alert">
                {{ delErr }}
            </div>

            <div class="ppms-profile-access-card">
                <div class="ppms-profile-access-card-head">
                    <h3 class="ppms-profile-access-h3">{{ t('profile.delegationListTitle') }}</h3>
                </div>
                <div v-if="listLoading" class="ppms-profile-skel" role="status" :aria-label="t('common.loading')">
                    <div class="ppms-profile-skel-line ppms-profile-access-skel-a" />
                    <div class="ppms-profile-skel-line ppms-profile-access-skel-b" />
                </div>
                <div v-else-if="items.length === 0" class="ppms-profile-access-empty">{{ t('profile.delegationEmpty') }}</div>
                <div v-else class="ppms-profile-table-wrap ppms-profile-access-table-wrap">
                    <table class="ppms-profile-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ t('profile.delegationColUser') }}</th>
                                <th scope="col">{{ t('profile.delegationColScope') }}</th>
                                <th scope="col">{{ t('profile.delegationColExpires') }}</th>
                                <th scope="col">{{ t('profile.delegationColStatus') }}</th>
                                <th scope="col">{{ t('profile.delegationColAction') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in items"
                                :key="row.id"
                                :class="{ 'ppms-profile-access-tr--muted': row.is_expired }"
                            >
                                <td>
                                    <div class="ppms-profile-access-user">{{ row.delegatee?.name || '—' }}</div>
                                    <div class="ppms-profile-access-email">{{ row.delegatee?.email }}</div>
                                    <span v-if="row.delegatee?.role" class="ppms-profile-access-role">
                                        {{ roleLabel(row.delegatee.role) }}
                                    </span>
                                </td>
                                <td>{{ scopeLabel(row.scope) }}</td>
                                <td>{{ formatExpires(row.expires_at) }}</td>
                                <td>
                                    <span v-if="row.is_expired" class="ppms-profile-access-badge ppms-profile-access-badge--exp">{{
                                        t('profile.delegationExpired')
                                    }}</span>
                                    <span v-else class="ppms-profile-access-badge ppms-profile-access-badge--ok">{{
                                        t('profile.delegationActive')
                                    }}</span>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="ppms-pf-btn ppms-profile-access-revoke"
                                        :disabled="revokingId === row.id"
                                        @click="revoke(row)"
                                    >
                                        {{ t('profile.delegationRevoke') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ppms-profile-access-card ppms-profile-access-card--form">
                <h3 class="ppms-profile-access-h3">{{ t('profile.delegationAdd') }}</h3>
                <div class="ppms-profile-access-row">
                    <label class="ppms-profile-access-label" for="del-search">{{ t('profile.delegationDelegatee') }}</label>
                    <div ref="lookupEl" class="ppms-profile-access-lookup">
                        <input
                            id="del-search"
                            v-model="searchQ"
                            type="search"
                            class="ppms-profile-access-input"
                            autocomplete="off"
                            :placeholder="t('profile.delegationSearchPlaceholder')"
                            :aria-label="t('profile.delegationSearchPlaceholder')"
                            :aria-expanded="pickCandidates.length > 0"
                            aria-controls="del-pick-list"
                            aria-autocomplete="list"
                            @input="onSearchInput"
                            @keydown.escape.prevent="closePick"
                        />
                        <ul
                            v-if="pickCandidates.length"
                            id="del-pick-list"
                            class="ppms-profile-access-pick"
                            role="listbox"
                            :aria-label="t('profile.delegationSearchPlaceholder')"
                        >
                            <li v-for="u in pickCandidates" :key="u.id" role="option">
                                <button type="button" class="ppms-profile-access-pick-btn" @click="selectUser(u)">
                                    <span class="ppms-profile-access-user">{{ u.name }}</span>
                                    <span class="ppms-profile-access-email">{{ u.email }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <p v-if="picked" class="ppms-profile-access-picked">
                        <span>{{ picked.name }} · {{ picked.email }}</span>
                        <button type="button" class="ppms-profile-access-clear" @click="clearPick">{{ t('common.cancel') }}</button>
                    </p>
                </div>
                <div class="ppms-profile-access-row">
                    <label class="ppms-profile-access-label" for="del-scope">{{ t('profile.delegationScope') }}</label>
                    <select id="del-scope" v-model="formScope" class="ppms-profile-access-input">
                        <option v-for="s in scopeOptions" :key="s" :value="s">{{ scopeLabel(s) }}</option>
                    </select>
                </div>
                <div class="ppms-profile-access-row">
                    <label class="ppms-profile-access-label" for="del-exp">{{ t('profile.delegationExpires') }}</label>
                    <input id="del-exp" v-model="formExpires" type="date" class="ppms-profile-access-input" />
                </div>
                <div class="ppms-profile-access-actions">
                    <button
                        type="button"
                        class="ppms-pf-btn ppms-pf-btn--primary"
                        :disabled="!picked || saving"
                        :aria-busy="saving"
                        @click="submitDelegation"
                    >
                        {{ t('common.save') }}
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '../../ppmsUi';
import ProfileTabPermissions from './ProfileTabPermissions.vue';

const { t, locale } = useI18n();

const uid = `u${Math.random().toString(36).slice(2, 9)}`;

const listLoading = ref(true);
const delErr = ref('');
const items = ref([]);
const scopeOptions = ref(['supplier_contracts', 'projects', 'all']);
const revokingId = ref(null);
const saving = ref(false);
const currentUserId = ref(null);
const canManageOthers = ref(false);
const delegatorContext = ref(null);

const searchQ = ref('');
const pickCandidates = ref([]);
const picked = ref(null);
const formScope = ref('supplier_contracts');
const formExpires = ref('');

const delegSearchQ = ref('');
const delegPickCandidates = ref([]);

const lookupEl = ref(null);
const delegLookupEl = ref(null);
let searchTimer;
let delegSearchTimer;

function effectiveDelegatorId() {
    return delegatorContext.value?.id ?? currentUserId.value;
}

function roleLabel(role) {
    if (!role) {
        return '—';
    }
    const key = `layout.role.${role}`;
    const tr = t(key);
    return tr === key ? role : tr;
}

function scopeLabel(scope) {
    const key = `profile.delegationScopes.${scope}`;
    const tr = t(key);
    return tr === key ? scope : tr;
}

function formatExpires(iso) {
    if (!iso) {
        return t('profile.delegationNoExpiry');
    }
    try {
        const d = new Date(iso);
        return d.toLocaleDateString(locale.value === 'vi' ? 'vi-VN' : 'en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch {
        return '—';
    }
}

async function loadDelegations() {
    listLoading.value = true;
    delErr.value = '';
    try {
        const params = {};
        if (canManageOthers.value && delegatorContext.value) {
            params.delegator_id = delegatorContext.value.id;
        }
        const { data } = await axios.get('/api/me/delegations', { params });
        canManageOthers.value = !!data.can_manage_others;
        items.value = Array.isArray(data.items) ? data.items : [];
        if (Array.isArray(data.scopes) && data.scopes.length) {
            scopeOptions.value = data.scopes;
            if (!data.scopes.includes(formScope.value)) {
                formScope.value = data.scopes[0];
            }
        }
    } catch (e) {
        delErr.value = formatApiUserMessage(e, t('profile.delegationLoadError'));
    } finally {
        listLoading.value = false;
    }
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        const q = searchQ.value.trim();
        if (q.length < 2) {
            pickCandidates.value = [];

            return;
        }
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q } });
            const raw = Array.isArray(data) ? data : [];
            const ex = effectiveDelegatorId();
            pickCandidates.value = raw.filter((u) => u.id !== ex);
        } catch {
            pickCandidates.value = [];
        }
    }, 280);
}

function onDelegatorSearchInput() {
    clearTimeout(delegSearchTimer);
    delegSearchTimer = setTimeout(async () => {
        const q = delegSearchQ.value.trim();
        if (q.length < 2) {
            delegPickCandidates.value = [];

            return;
        }
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q } });
            delegPickCandidates.value = Array.isArray(data) ? data : [];
        } catch {
            delegPickCandidates.value = [];
        }
    }, 280);
}

function closePick() {
    pickCandidates.value = [];
}

function closeDelegPick() {
    delegPickCandidates.value = [];
}

function onDocClick(e) {
    const elTarget = e.target;
    if (lookupEl.value?.contains(elTarget)) {
        return;
    }
    if (delegLookupEl.value?.contains(elTarget)) {
        return;
    }
    closePick();
    closeDelegPick();
}

function selectDelegatorUser(u) {
    if (currentUserId.value != null && u.id === currentUserId.value) {
        delegatorContext.value = null;
    } else {
        delegatorContext.value = u;
    }
    delegSearchQ.value = '';
    delegPickCandidates.value = [];
    loadDelegations();
}

function clearDelegatorContext() {
    delegatorContext.value = null;
    delegSearchQ.value = '';
    delegPickCandidates.value = [];
    loadDelegations();
}

function selectUser(u) {
    picked.value = u;
    pickCandidates.value = [];
    searchQ.value = '';
}

function clearPick() {
    picked.value = null;
}

async function submitDelegation() {
    if (!picked.value) {
        return;
    }
    saving.value = true;
    try {
        const payload = {
            delegatee_id: picked.value.id,
            scope: formScope.value,
        };
        if (formExpires.value) {
            payload.expires_at = `${formExpires.value}T23:59:59`;
        }
        if (canManageOthers.value && delegatorContext.value) {
            payload.delegator_id = delegatorContext.value.id;
        }
        await axios.post('/api/me/delegations', payload);
        ppmsToastSuccess(t('profile.delegationSaved'));
        picked.value = null;
        formExpires.value = '';
        await loadDelegations();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
    } finally {
        saving.value = false;
    }
}

async function revoke(row) {
    const ok = await ppmsConfirm(t('profile.delegationRevokeConfirm'), {
        title: t('profile.delegationRevokeTitle'),
        destructive: true,
        confirmLabel: t('profile.delegationRevoke'),
        cancelLabel: t('common.cancel'),
    });
    if (!ok) {
        return;
    }
    revokingId.value = row.id;
    try {
        await axios.delete(`/api/me/delegations/${row.id}`);
        ppmsToastSuccess(t('profile.delegationRevoked'));
        await loadDelegations();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
    } finally {
        revokingId.value = null;
    }
}

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/user');
        currentUserId.value = data?.id ?? null;
    } catch {
        currentUserId.value = null;
    }
    document.addEventListener('click', onDocClick, true);
    await loadDelegations();
});

onUnmounted(() => {
    clearTimeout(searchTimer);
    clearTimeout(delegSearchTimer);
    document.removeEventListener('click', onDocClick, true);
});
</script>

<style scoped>
.ppms-profile-access {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}
.ppms-profile-access-block {
    min-width: 0;
}
.ppms-profile-access-block--perm {
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.35);
}
.ppms-profile-access-h2 {
    font-size: 1.05rem;
    font-weight: 600;
    margin: 0 0 0.75rem;
    color: var(--ppms-pf-fg, inherit);
}
.ppms-profile-access-h3 {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 0.75rem;
}
.ppms-profile-access-lead {
    font-size: 0.9rem;
    color: var(--ppms-pf-muted);
    margin: 0 0 1rem;
    max-width: none;
    line-height: 1.45;
}
.ppms-profile-access-lead--admin {
    margin-top: -0.35rem;
    padding: 0.5rem 0.65rem;
    border-radius: 8px;
    background: rgba(59, 130, 246, 0.06);
    border: 1px solid rgba(59, 130, 246, 0.2);
}
.ppms-profile-access-label--heading {
    display: block;
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: var(--ppms-pf-fg, inherit);
}
.ppms-profile-access-card--delegator {
    margin-bottom: 0.75rem;
}
.ppms-profile-access-delegator-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.75rem;
    margin-top: 0.5rem;
}
.ppms-profile-access-picked--inline {
    margin: 0;
}
.ppms-profile-access-btn-secondary {
    font-size: 0.85rem;
    padding: 0.35rem 0.65rem;
}
.ppms-profile-access-banner--context {
    background: rgba(59, 130, 246, 0.08);
    color: #1e40af;
    border: 1px solid rgba(59, 130, 246, 0.25);
}
.ppms-profile-access-banner {
    padding: 0.65rem 0.75rem;
    border-radius: 8px;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}
.ppms-profile-access-banner--err {
    background: rgba(185, 28, 28, 0.08);
    color: #991b1b;
    border: 1px solid rgba(185, 28, 28, 0.25);
}
.ppms-profile-access-card {
    border: 1px solid rgba(148, 163, 184, 0.4);
    border-radius: 10px;
    padding: 1rem 1rem 1.1rem;
    background: var(--ppms-pf-card-bg, rgba(255, 255, 255, 0.4));
}
.ppms-profile-access-card--form {
    margin-top: 1rem;
}
.ppms-profile-access-card-head {
    margin-bottom: 0.5rem;
}
.ppms-profile-access-empty {
    font-size: 0.9rem;
    color: var(--ppms-pf-muted);
}
.ppms-profile-access-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.ppms-profile-access-tr--muted {
    opacity: 0.72;
}
.ppms-profile-access-user {
    font-weight: 500;
}
.ppms-profile-access-email {
    font-size: 0.85rem;
    color: var(--ppms-pf-muted);
}
.ppms-profile-access-role {
    display: inline-block;
    margin-top: 0.25rem;
    font-size: 0.72rem;
    padding: 0.1rem 0.4rem;
    border-radius: 4px;
    background: rgba(100, 116, 139, 0.15);
    color: var(--ppms-pf-muted);
}
.ppms-profile-access-badge {
    font-size: 0.78rem;
    font-weight: 500;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
}
.ppms-profile-access-badge--ok {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
}
.ppms-profile-access-badge--exp {
    background: rgba(100, 116, 139, 0.2);
    color: #475569;
}
.ppms-profile-access-revoke:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.ppms-profile-access-row {
    margin-bottom: 0.75rem;
}
.ppms-profile-access-label {
    display: block;
    font-size: 0.85rem;
    margin-bottom: 0.35rem;
    color: var(--ppms-pf-muted);
}
.ppms-profile-access-input {
    width: 100%;
    max-width: 100%;
    padding: 0.45rem 0.5rem;
    border-radius: 6px;
    border: 1px solid rgba(148, 163, 184, 0.5);
    background: var(--ppms-pf-input-bg, #fff);
    color: inherit;
}
.ppms-profile-access-lookup {
    position: relative;
    max-width: 100%;
}
.ppms-profile-access-pick {
    list-style: none;
    margin: 0.25rem 0 0;
    padding: 0;
    border: 1px solid rgba(148, 163, 184, 0.45);
    border-radius: 6px;
    background: var(--ppms-pf-input-bg, #fff);
    max-height: 220px;
    overflow: auto;
    z-index: 4;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}
.ppms-profile-access-pick-btn {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.5rem 0.65rem;
    border: none;
    background: transparent;
    cursor: pointer;
    color: inherit;
    font: inherit;
}
.ppms-profile-access-pick-btn:hover,
.ppms-profile-access-pick-btn:focus-visible {
    background: rgba(59, 130, 246, 0.1);
    outline: none;
}
.ppms-profile-access-picked {
    font-size: 0.9rem;
    margin: 0.5rem 0 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
}
.ppms-profile-access-clear {
    font-size: 0.85rem;
    border: none;
    background: none;
    color: #2563eb;
    cursor: pointer;
    text-decoration: underline;
}
.ppms-profile-access-actions {
    margin-top: 0.75rem;
}
.ppms-profile-access-skel-a {
    width: 50%;
}
.ppms-profile-access-skel-b {
    width: 75%;
}
</style>
