<template>
    <div class="ppms-profile-access">
        <header class="ppms-profile-access-hero">
            <h2 class="ppms-profile-access-page-title">{{ t('profile.accessTabTitle') }}</h2>
            <p class="ppms-profile-access-lead ppms-profile-access-lead--hero">{{ t('profile.accessTabLead') }}</p>
            <ol class="ppms-profile-access-steps ppms-profile-access-steps--cards">
                <li v-for="(stepKey, idx) in accessStepKeys" :key="stepKey" class="ppms-profile-access-step-card">
                    <span class="ppms-profile-access-step-num" aria-hidden="true">{{ idx + 1 }}</span>
                    <span class="ppms-profile-access-step-text">{{ t(`profile.${stepKey}`) }}</span>
                </li>
            </ol>
        </header>

        <div
            v-if="!rbacLoading && !canManageRbac"
            class="ppms-profile-access-banner ppms-profile-access-banner--readonly"
            role="region"
            :aria-labelledby="'acc-ro-' + uid"
        >
            <h3 :id="'acc-ro-' + uid" class="ppms-profile-access-banner-title">{{ t('profile.accessReadOnlyBannerTitle') }}</h3>
            <p class="ppms-profile-access-banner-summary">{{ t('profile.accessReadOnlyBannerSummary') }}</p>
            <details class="ppms-profile-access-details">
                <summary class="ppms-profile-access-details-summary">{{ t('profile.accessReadOnlyDetailsToggle') }}</summary>
                <p class="ppms-profile-access-banner-body">{{ t('profile.permissionAdminExplain', { role: roleLabel(myRbacRole), roles: permissionAdminRolesListed }) }}</p>
            </details>
        </div>

        <section class="ppms-profile-access-block" :aria-labelledby="'acc-role-' + uid">
            <h2 :id="'acc-role-' + uid" class="ppms-profile-access-h2">{{ t('profile.accessRoleTitle') }}</h2>
            <p class="ppms-profile-access-lead">{{ t('profile.accessRoleLead') }}</p>
            <div v-if="rbacLoading" class="ppms-profile-skel" role="status" :aria-label="t('common.loading')">
                <div class="ppms-profile-skel-line ppms-profile-access-skel-a" />
                <div class="ppms-profile-skel-line ppms-profile-access-skel-b" />
            </div>
            <div v-else class="ppms-profile-access-card">
                <div class="ppms-profile-access-role-row">
                    <span class="ppms-profile-role-badge" :class="roleBadgeClass(myRbacRole)">{{ roleLabel(myRbacRole) }}</span>
                </div>
                <p class="ppms-profile-access-note">{{ t('profile.accessRoleNoteDefault') }}</p>
                <p v-if="canManageRbac" class="ppms-profile-access-note ppms-profile-access-note--admin">
                    {{ t('profile.accessRoleAdminHint') }}
                </p>
                <div v-if="canManageRbac && roleOptions.length" class="ppms-profile-access-row">
                    <label class="ppms-profile-access-label" for="self-role">{{ t('profile.accessRoleChange') }}</label>
                    <select
                        id="self-role"
                        v-model="selfRoleDraft"
                        class="ppms-profile-access-input"
                        :disabled="roleSaving"
                        @change="saveSelfRole"
                    >
                        <option v-for="r in roleOptions" :key="r" :value="r">{{ roleLabel(r) }}</option>
                    </select>
                </div>
                <p v-else-if="!canManageRbac" class="ppms-profile-access-note">{{ t('profile.accessRoleUserHint') }}</p>
                <p v-else class="ppms-profile-access-note">{{ t('profile.accessRoleNoRoleOptions') }}</p>

                <template v-if="canManageOthers && delegatorContext && delegatorRoleReady">
                    <hr class="ppms-profile-access-hr" />
                    <p class="ppms-profile-access-subtitle">{{ t('profile.accessDelegatorRoleTitle', { name: delegatorContext.name }) }}</p>
                    <p class="ppms-profile-access-note">{{ t('profile.accessDelegatorRoleLead') }}</p>
                    <div v-if="canManageRbac && roleOptions.length" class="ppms-profile-access-row">
                        <label class="ppms-profile-access-label" for="delegator-role">{{ t('profile.accessRoleChange') }}</label>
                        <select
                            id="delegator-role"
                            v-model="delegatorRoleDraft"
                            class="ppms-profile-access-input"
                            :disabled="roleSaving"
                            @change="saveDelegatorRole"
                        >
                            <option v-for="r in roleOptions" :key="'d-' + r" :value="r">{{ roleLabel(r) }}</option>
                        </select>
                    </div>
                </template>
            </div>
        </section>

        <section class="ppms-profile-access-block ppms-profile-access-block--perm" :aria-labelledby="'acc-perm-' + uid">
            <h2 :id="'acc-perm-' + uid" class="ppms-profile-access-h2">{{ t('profile.accessSectionPermissions') }}</h2>
            <p class="ppms-profile-access-lead ppms-profile-access-lead--section">{{ t('profile.accessSectionPermHint') }}</p>
            <ProfileTabPermissions :key="permKey" compact />
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
                                    <span class="ppms-profile-access-role">{{ roleLabel(row.delegatee?.role) }}</span>
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
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '../../ppmsUi';
import ProfileTabPermissions from './ProfileTabPermissions.vue';

const emit = defineEmits(['refresh']);

const { t, locale } = useI18n();

const uid = `u${Math.random().toString(36).slice(2, 9)}`;

const accessStepKeys = ['accessStep1', 'accessStep2', 'accessStep3'];

const rbacLoading = ref(true);
const myRbacRole = ref('');
const roleOptions = ref([]);
const canManageRbac = ref(false);
const permissionAdminRoles = ref([]);
const permissionAdminRolesListed = computed(() =>
    permissionAdminRoles.value.map((r) => roleLabel(r)).join(', '),
);
const selfRoleDraft = ref('');
const delegatorRoleDraft = ref('');
const delegatorRoleReady = ref(false);
const roleSaving = ref(false);
const permKey = ref(0);

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

async function loadRbac() {
    rbacLoading.value = true;
    try {
        const { data } = await axios.get('/api/me/rbac');
        myRbacRole.value = data.role || '';
        roleOptions.value = Array.isArray(data.role_options) ? data.role_options : [];
        canManageRbac.value = !!data.can_manage;
        const raw = Array.isArray(data.permission_admin_roles) ? data.permission_admin_roles : ['admin'];
        permissionAdminRoles.value = raw.length ? raw : ['admin'];
        selfRoleDraft.value = myRbacRole.value || roleOptions.value[0] || '';
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('common.loading')));
    } finally {
        rbacLoading.value = false;
    }
}

async function saveSelfRole() {
    if (!currentUserId.value) {
        return;
    }
    roleSaving.value = true;
    try {
        await axios.patch(`/api/users/${currentUserId.value}/role`, { role: selfRoleDraft.value });
        myRbacRole.value = selfRoleDraft.value;
        ppmsToastSuccess(t('profile.accessRoleSaved'));
        emit('refresh');
        permKey.value += 1;
        await loadRbac();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
        selfRoleDraft.value = myRbacRole.value || selfRoleDraft.value;
    } finally {
        roleSaving.value = false;
    }
}

async function loadDelegatorRoleDetail() {
    delegatorRoleReady.value = false;
    if (!delegatorContext.value?.id) {
        return;
    }
    try {
        const { data } = await axios.get('/api/users/lookup', { params: { ids: delegatorContext.value.id } });
        const row = Array.isArray(data) && data[0];
        delegatorRoleDraft.value = (row && row.role) || roleOptions.value[0] || '';
        delegatorRoleReady.value = true;
    } catch {
        delegatorRoleReady.value = false;
    }
}

async function saveDelegatorRole() {
    if (!delegatorContext.value?.id) {
        return;
    }
    roleSaving.value = true;
    try {
        await axios.patch(`/api/users/${delegatorContext.value.id}/role`, { role: delegatorRoleDraft.value });
        ppmsToastSuccess(t('profile.accessRoleSaved'));
        emit('refresh');
        permKey.value += 1;
        await loadDelegatorRoleDetail();
        await loadDelegations();
    } catch (e) {
        ppmsToastError(formatApiUserMessage(e, t('profile.saveError')));
        await loadDelegatorRoleDetail();
    } finally {
        roleSaving.value = false;
    }
}

function effectiveDelegatorId() {
    return delegatorContext.value?.id ?? currentUserId.value;
}

function roleBadgeClass(role) {
    const r = (role || '').toLowerCase();
    if (!r) {
        return 'ppms-profile-role-badge--unassigned';
    }
    return `ppms-profile-role-badge--${r}`;
}

function roleLabel(role) {
    if (!role) {
        return t('layout.role.unassigned');
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

async function selectDelegatorUser(u) {
    if (currentUserId.value != null && u.id === currentUserId.value) {
        delegatorContext.value = null;
    } else {
        delegatorContext.value = u;
    }
    delegSearchQ.value = '';
    delegPickCandidates.value = [];
    await loadDelegations();
    if (delegatorContext.value) {
        await loadDelegatorRoleDetail();
    }
}

async function clearDelegatorContext() {
    delegatorContext.value = null;
    delegSearchQ.value = '';
    delegPickCandidates.value = [];
    delegatorRoleReady.value = false;
    await loadDelegations();
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
    await Promise.all([loadRbac(), loadDelegations()]);
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
.ppms-profile-access-hero {
    margin-bottom: 0.25rem;
}
.ppms-profile-access-page-title {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    letter-spacing: -0.02em;
    color: var(--ppms-pf-fg, inherit);
}
.ppms-profile-access-lead--hero {
    margin-bottom: 0.75rem;
}
.ppms-profile-access-steps {
    margin: 0;
    padding-left: 1.25rem;
    font-size: 0.88rem;
    color: var(--ppms-pf-muted);
    line-height: 1.55;
}
.ppms-profile-access-steps li {
    margin-bottom: 0.35rem;
}
.ppms-profile-access-steps--cards {
    list-style: none;
    padding: 0;
    display: grid;
    gap: 0.65rem;
    grid-template-columns: 1fr;
}
@media (min-width: 720px) {
    .ppms-profile-access-steps--cards {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }
}
.ppms-profile-access-step-card {
    display: flex;
    gap: 0.65rem;
    align-items: flex-start;
    padding: 0.65rem 0.75rem;
    border-radius: 10px;
    border: 1px solid rgba(148, 163, 184, 0.4);
    background: var(--ppms-pf-card-bg, rgba(255, 255, 255, 0.55));
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.ppms-profile-access-step-num {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.65rem;
    height: 1.65rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #1d4ed8;
    background: rgba(59, 130, 246, 0.12);
    border: 1px solid rgba(59, 130, 246, 0.22);
}
.ppms-profile-access-step-text {
    font-size: 0.86rem;
    line-height: 1.5;
    color: var(--ppms-pf-muted);
}
.ppms-profile-access-banner {
    margin-bottom: 1rem;
}
.ppms-profile-access-banner--readonly {
    padding: 0.85rem 1rem;
    border-radius: 10px;
    border: 1px solid rgba(148, 163, 184, 0.45);
    background: rgba(254, 243, 199, 0.35);
    border-left: 4px solid #d97706;
}
.ppms-profile-access-banner-title {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 0.4rem;
    color: var(--ppms-pf-fg, inherit);
}
.ppms-profile-access-banner-summary {
    font-size: 0.88rem;
    line-height: 1.5;
    margin: 0 0 0.5rem;
    color: var(--ppms-pf-fg, inherit);
}
.ppms-profile-access-details {
    margin: 0;
}
.ppms-profile-access-details-summary {
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    color: #b45309;
    list-style-position: outside;
}
.ppms-profile-access-details-summary:hover {
    text-decoration: underline;
}
.ppms-profile-access-details[open] .ppms-profile-access-details-summary {
    margin-bottom: 0.45rem;
}
.ppms-profile-access-banner-body {
    font-size: 0.86rem;
    line-height: 1.55;
    margin: 0;
    color: var(--ppms-pf-muted);
}
.ppms-profile-access-note {
    font-size: 0.85rem;
    color: var(--ppms-pf-muted);
    line-height: 1.45;
    margin: 0 0 0.75rem;
}
.ppms-profile-access-note--admin {
    padding: 0.45rem 0.6rem;
    border-radius: 8px;
    background: rgba(59, 130, 246, 0.06);
    border: 1px solid rgba(59, 130, 246, 0.2);
    color: #1e40af;
}
.ppms-profile-access-role-row {
    margin-bottom: 0.35rem;
}
.ppms-profile-access-hr {
    border: none;
    border-top: 1px solid rgba(148, 163, 184, 0.35);
    margin: 1rem 0;
}
.ppms-profile-access-subtitle {
    font-weight: 600;
    margin: 0 0 0.35rem;
    font-size: 0.95rem;
    color: var(--ppms-pf-fg, inherit);
}
.ppms-profile-access-lead--section {
    margin-top: -0.25rem;
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
    padding: 0.65rem 0.75rem;
    border-radius: 8px;
    font-size: 0.9rem;
    background: rgba(59, 130, 246, 0.08);
    color: #1e40af;
    border: 1px solid rgba(59, 130, 246, 0.25);
}
.ppms-profile-access-banner--err {
    padding: 0.65rem 0.75rem;
    border-radius: 8px;
    font-size: 0.9rem;
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
.ppms-profile-access-input:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
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
