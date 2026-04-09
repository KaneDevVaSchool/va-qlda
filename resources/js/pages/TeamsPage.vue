<template>
    <div class="ppms-page">
        <section class="ppms-card">
            <div class="ppms-teams-head">
                <div>
                    <h2>{{ t('teams.title') }}</h2>
                    <p class="ppms-muted ppms-mt-sm">{{ t('teams.subtitle') }}</p>
                </div>
                <div class="ppms-teams-head-actions">
                    <router-link to="/kpi" class="ppms-btn-secondary ppms-btn-linkish">{{ t('teams.linkKpi') }}</router-link>
                    <button v-if="canCreate" type="button" class="ppms-btn-primary" @click="openCreateModal">
                        {{ t('teams.createBtn') }}
                    </button>
                </div>
            </div>

            <div class="ppms-teams-toolbar ppms-mt">
                <span class="ppms-teams-toolbar-label">{{ t('teams.viewMode') }}</span>
                <div class="ppms-teams-seg" role="group" :aria-label="t('teams.viewMode')">
                    <button
                        type="button"
                        class="ppms-teams-seg__btn"
                        :class="{ 'ppms-teams-seg__btn--on': viewMode === 'cards' }"
                        @click="viewMode = 'cards'"
                    >
                        {{ t('teams.viewCards') }}
                    </button>
                    <button
                        type="button"
                        class="ppms-teams-seg__btn"
                        :class="{ 'ppms-teams-seg__btn--on': viewMode === 'tree' }"
                        @click="viewMode = 'tree'"
                    >
                        {{ t('teams.viewTree') }}
                    </button>
                </div>
            </div>
        </section>

        <section class="ppms-card ppms-mt">
            <h3 class="ppms-h3">{{ t('teams.listSection') }}</h3>
            <div v-if="loading" class="ppms-loading-line ppms-mt-sm" role="status">{{ t('common.loading') }}</div>
            <p v-else-if="!teams.length" class="ppms-muted ppms-mt-sm">{{ t('teams.empty') }}</p>

            <div v-else-if="viewMode === 'cards'" class="ppms-teams-card-grid ppms-mt">
                <article
                    v-for="row in teams"
                    :key="row.id"
                    class="ppms-team-card"
                    :class="{ 'ppms-team-card--active': detail?.id === row.id }"
                >
                    <button type="button" class="ppms-team-card__main" @click="openTeam(row.id)">
                        <h4 class="ppms-team-card__title">{{ row.name }}</h4>
                        <p v-if="row.description" class="ppms-team-card__desc ppms-muted">{{ row.description }}</p>
                        <div class="ppms-team-card__meta">
                            <span>{{ t('teams.membersCount') }}: {{ row.members_count ?? 0 }}</span>
                            <span v-if="row.creator?.name"> · {{ row.creator.name }}</span>
                        </div>
                    </button>
                    <div v-if="row.can_manage" class="ppms-team-card__actions">
                        <button type="button" class="ppms-btn-ghost" @click.stop="confirmDelete(row)">
                            {{ t('common.delete') }}
                        </button>
                    </div>
                </article>
            </div>

            <div v-else class="ppms-team-forest ppms-mt">
                <div v-for="team in teams" :key="team.id" class="ppms-team-tree-block">
                    <div class="ppms-team-tree-block__head">
                        <button type="button" class="ppms-team-tree-block__title" @click="openTeam(team.id)">
                            {{ team.name }}
                        </button>
                        <span class="ppms-muted ppms-team-tree-block__count">{{ team.members_count ?? 0 }} {{ t('teams.membersShort') }}</span>
                    </div>
                    <div v-if="!team.members?.length" class="ppms-muted ppms-team-tree-block__empty">{{ t('teams.treeNoMembers') }}</div>
                    <div v-else class="ppms-team-tree">
                        <div v-for="node in treeLeaders(team)" :key="'l-' + node.id" class="ppms-team-tree__leader">
                            <span class="ppms-team-tree__badge">{{ t('teams.roleLeader') }}</span>
                            <span class="ppms-team-tree__name">{{ node.name }}</span>
                            <span v-if="node.pivot?.position" class="ppms-team-tree__pos ppms-muted">— {{ node.pivot.position }}</span>
                        </div>
                        <ul v-if="treeMembers(team).length" class="ppms-team-tree__members">
                            <li v-for="node in treeMembers(team)" :key="'m-' + node.id" class="ppms-team-tree__li">
                                <span class="ppms-team-tree__name">{{ node.name }}</span>
                                <span v-if="node.pivot?.position" class="ppms-team-tree__pos ppms-muted">— {{ node.pivot.position }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="detail" class="ppms-card ppms-mt">
            <div class="ppms-teams-detail-head">
                <h3 class="ppms-h3">{{ detail.name }}</h3>
                <p v-if="detail.description" class="ppms-muted ppms-mt-sm">{{ detail.description }}</p>
            </div>

            <h4 class="ppms-h4 ppms-mt">{{ t('teams.membersTitle') }}</h4>
            <p v-if="detail.can_manage" class="ppms-muted ppms-mt-sm ppms-team-hint">{{ t('teams.manageHint') }}</p>
            <div class="ppms-table-scroll ppms-mt-sm">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('teams.colName') }}</th>
                            <th>{{ t('teams.colEmail') }}</th>
                            <th>{{ t('teams.colPosition') }}</th>
                            <th>{{ t('teams.colRole') }}</th>
                            <th>{{ t('teams.colPermissions') }}</th>
                            <th v-if="detail.can_manage" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in detail.members || []" :key="m.id">
                            <td>{{ m.name }}</td>
                            <td>{{ m.email }}</td>
                            <td>{{ m.pivot?.position || '—' }}</td>
                            <td>{{ formatMemberRole(m) }}</td>
                            <td class="ppms-team-perm-cell">{{ formatPermissionsShort(m) }}</td>
                            <td v-if="detail.can_manage" class="ppms-td-actions">
                                <button type="button" class="ppms-btn-ghost" @click="openMemberModal(m)">
                                    {{ t('teams.editMember') }}
                                </button>
                                <button
                                    v-if="Number(m.id) !== Number(detail.created_by)"
                                    type="button"
                                    class="ppms-btn-ghost"
                                    @click="removeMember(m.id)"
                                >
                                    {{ t('teams.removeMember') }}
                                </button>
                                <span v-else class="ppms-muted">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="detail.can_manage" class="ppms-mt">
                <h4 class="ppms-h4">{{ t('teams.addMembers') }}</h4>
                <label class="ppms-field ppms-mt-sm">
                    <span>{{ t('teams.searchUser') }}</span>
                    <input
                        v-model="lookupQ"
                        type="search"
                        autocomplete="off"
                        :placeholder="t('teams.searchPlaceholder')"
                        @input="onLookupInput"
                    />
                </label>
                <ul v-if="lookupHits.length" class="ppms-lookup-list ppms-mt-sm">
                    <li v-for="u in lookupHits" :key="u.id">
                        <button type="button" class="ppms-link-btn" @click="addMember(u)">
                            {{ u.name }} — {{ u.email }}
                        </button>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Modal: tạo nhóm -->
        <div
            v-if="showCreateModal"
            class="ppms-modal-backdrop"
            role="presentation"
            @click.self="closeCreateModal"
        >
            <div class="ppms-modal ppms-modal--wide" role="dialog" aria-modal="true" aria-labelledby="teams-create-title" @click.stop>
                <h2 id="teams-create-title" class="ppms-modal-title">{{ t('teams.createSection') }}</h2>
                <form class="ppms-stack ppms-mt" @submit.prevent="createTeam">
                    <label class="ppms-field">
                        <span>{{ t('teams.name') }} *</span>
                        <input v-model="createForm.name" type="text" required maxlength="255" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('teams.description') }}</span>
                        <textarea v-model="createForm.description" rows="3" maxlength="5000" />
                    </label>
                    <p v-if="createErr" class="ppms-error">{{ createErr }}</p>
                    <div class="ppms-modal-actions">
                        <button type="button" class="ppms-btn-ghost ppms-modal-btn" @click="closeCreateModal">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" class="ppms-btn-primary ppms-modal-btn" :disabled="creating">
                            {{ t('teams.createBtn') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: chỉnh thành viên -->
        <div
            v-if="showMemberModal && memberEdit"
            class="ppms-modal-backdrop"
            role="presentation"
            @click.self="closeMemberModal"
        >
            <div class="ppms-modal ppms-modal--wide" role="dialog" aria-modal="true" aria-labelledby="teams-member-title" @click.stop>
                <h2 id="teams-member-title" class="ppms-modal-title">{{ t('teams.editMemberTitle') }}</h2>
                <p class="ppms-muted ppms-mt-sm">{{ memberEdit.name }} · {{ memberEdit.email }}</p>
                <form class="ppms-stack ppms-mt" @submit.prevent="saveMemberEdit">
                    <label class="ppms-field">
                        <span>{{ t('teams.colRole') }}</span>
                        <select v-model="memberEdit.role">
                            <option value="leader">{{ t('teams.roleLeader') }}</option>
                            <option value="member">{{ t('teams.roleMember') }}</option>
                        </select>
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('teams.colPosition') }}</span>
                        <input v-model="memberEdit.position" type="text" maxlength="255" :placeholder="t('teams.positionPlaceholder')" />
                    </label>
                    <fieldset class="ppms-team-perm-fieldset">
                        <legend class="ppms-field"><span>{{ t('teams.colPermissions') }}</span></legend>
                        <label v-for="key in TEAM_PERMISSION_KEYS" :key="key" class="ppms-team-perm-row">
                            <input v-model="memberEdit.permissions" type="checkbox" :value="key" />
                            <span>{{ permLabel(key) }}</span>
                        </label>
                    </fieldset>
                    <p v-if="memberEditErr" class="ppms-error">{{ memberEditErr }}</p>
                    <div class="ppms-modal-actions">
                        <button type="button" class="ppms-btn-ghost ppms-modal-btn" @click="closeMemberModal">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" class="ppms-btn-primary ppms-modal-btn" :disabled="memberSaving">
                            {{ t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';

const { t } = useI18n();

const TEAM_PERMISSION_KEYS = ['manage_members', 'edit_team_meta', 'view_team_kpi', 'assign_projects'];

const loading = ref(true);
const teams = ref([]);
const me = ref(null);
const detail = ref(null);
const viewMode = ref('cards');
const creating = ref(false);
const createErr = ref('');
const createForm = reactive({ name: '', description: '' });
const showCreateModal = ref(false);
const showMemberModal = ref(false);
const memberEdit = ref(null);
const memberEditErr = ref('');
const memberSaving = ref(false);

const lookupQ = ref('');
const lookupHits = ref([]);
let lookupTimer = null;

const canCreate = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));

function treeLeaders(team) {
    return (team.members || []).filter((m) => m.pivot?.role === 'leader');
}

function treeMembers(team) {
    return (team.members || []).filter((m) => m.pivot?.role !== 'leader');
}

function permLabel(key) {
    return t('teams.perm_' + key);
}

function formatMemberRole(m) {
    const r = m.pivot?.role;
    if (r === 'leader') {
        return t('teams.roleLeader');
    }
    return t('teams.roleMember');
}

function formatPermissionsShort(m) {
    const perms = m.pivot?.permissions;
    if (!perms || !Array.isArray(perms) || !perms.length) {
        return '—';
    }
    return perms.map((k) => permLabel(k)).join(', ');
}

function openCreateModal() {
    createErr.value = '';
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function openMemberModal(m) {
    memberEditErr.value = '';
    const perms = m.pivot?.permissions;
    memberEdit.value = {
        id: m.id,
        name: m.name,
        email: m.email,
        role: m.pivot?.role === 'leader' ? 'leader' : 'member',
        position: m.pivot?.position || '',
        permissions: Array.isArray(perms) ? [...perms] : [],
    };
    showMemberModal.value = true;
}

function closeMemberModal() {
    showMemberModal.value = false;
    memberEdit.value = null;
}

async function saveMemberEdit() {
    if (!detail.value || !memberEdit.value) {
        return;
    }
    memberEditErr.value = '';
    memberSaving.value = true;
    try {
        const { data } = await axios.patch(`/api/teams/${detail.value.id}/members/${memberEdit.value.id}`, {
            role: memberEdit.value.role,
            position: memberEdit.value.position?.trim() || null,
            permissions: memberEdit.value.permissions,
        });
        detail.value = data;
        ppmsToastSuccess(t('teams.memberUpdated'));
        closeMemberModal();
        await load();
    } catch (e) {
        memberEditErr.value = e.response?.data?.message || t('teams.memberUpdateError');
    } finally {
        memberSaving.value = false;
    }
}

async function load() {
    loading.value = true;
    try {
        const [u, list] = await Promise.all([
            axios.get('/api/user'),
            axios.get('/api/teams', { params: { include: 'members' } }),
        ]);
        me.value = u.data;
        teams.value = Array.isArray(list.data) ? list.data : [];
    } catch (e) {
        ppmsToastError(t('teams.loadError'));
    } finally {
        loading.value = false;
    }
}

async function openTeam(id) {
    try {
        const { data } = await axios.get(`/api/teams/${id}`);
        detail.value = data;
    } catch (e) {
        ppmsToastError(t('teams.loadDetailError'));
    }
}

async function createTeam() {
    createErr.value = '';
    creating.value = true;
    try {
        const { data: created } = await axios.post('/api/teams', {
            name: createForm.name.trim(),
            description: createForm.description?.trim() || null,
        });
        createForm.name = '';
        createForm.description = '';
        ppmsToastSuccess(t('teams.created'));
        closeCreateModal();
        await load();
        if (created?.id) {
            await openTeam(created.id);
        }
    } catch (e) {
        createErr.value = e.response?.data?.message || t('teams.createError');
    } finally {
        creating.value = false;
    }
}

async function confirmDelete(teamRow) {
    if (!(await ppmsConfirm(t('teams.deleteConfirm', { name: teamRow.name })))) {
        return;
    }
    try {
        await axios.delete(`/api/teams/${teamRow.id}`);
        ppmsToastSuccess(t('teams.deleted'));
        if (detail.value?.id === teamRow.id) {
            detail.value = null;
        }
        await load();
    } catch (e) {
        ppmsToastError(t('teams.deleteError'));
    }
}

function onLookupInput() {
    clearTimeout(lookupTimer);
    const q = lookupQ.value.trim();
    if (q.length < 2) {
        lookupHits.value = [];

        return;
    }
    lookupTimer = setTimeout(async () => {
        try {
            const { data } = await axios.get('/api/users/lookup', { params: { q } });
            const existing = new Set((detail.value?.members || []).map((m) => m.id));
            lookupHits.value = (Array.isArray(data) ? data : []).filter((u) => !existing.has(u.id)).slice(0, 20);
        } catch {
            lookupHits.value = [];
        }
    }, 300);
}

async function addMember(u) {
    if (!detail.value) {
        return;
    }
    try {
        await axios.post(`/api/teams/${detail.value.id}/members`, { user_ids: [u.id] });
        lookupQ.value = '';
        lookupHits.value = [];
        ppmsToastSuccess(t('teams.memberAdded'));
        await openTeam(detail.value.id);
        await load();
    } catch (e) {
        ppmsToastError(t('teams.memberAddError'));
    }
}

async function removeMember(userId) {
    if (!detail.value) {
        return;
    }
    if (!(await ppmsConfirm(t('teams.removeConfirm')))) {
        return;
    }
    try {
        await axios.delete(`/api/teams/${detail.value.id}/members/${userId}`);
        ppmsToastSuccess(t('teams.memberRemoved'));
        await openTeam(detail.value.id);
        await load();
    } catch (e) {
        ppmsToastError(t('teams.memberRemoveError'));
    }
}

function syncModalBodyClass() {
    if (showCreateModal.value || showMemberModal.value) {
        document.body.classList.add('ppms-modal-open');
    } else {
        document.body.classList.remove('ppms-modal-open');
    }
}

watch([showCreateModal, showMemberModal], syncModalBodyClass);

onMounted(() => {
    load();
    syncModalBodyClass();
});

onUnmounted(() => {
    document.body.classList.remove('ppms-modal-open');
});
</script>

<style scoped>
.ppms-teams-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}
.ppms-teams-head-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}
.ppms-btn-linkish {
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.ppms-h3 {
    font-size: 1.1rem;
    margin: 0 0 0.5rem;
}
.ppms-h4 {
    font-size: 1rem;
    margin: 0;
}
.ppms-teams-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}
.ppms-teams-toolbar-label {
    font-size: 0.9rem;
    color: var(--ppms-muted, #666);
}
.ppms-teams-seg {
    display: inline-flex;
    border: 1px solid var(--ppms-border, #ccc);
    border-radius: 8px;
    overflow: hidden;
}
.ppms-teams-seg__btn {
    border: none;
    background: transparent;
    padding: 0.4rem 0.9rem;
    font: inherit;
    cursor: pointer;
    color: inherit;
}
.ppms-teams-seg__btn--on {
    background: rgba(0, 0, 0, 0.06);
    font-weight: 600;
}
.ppms-teams-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}
.ppms-team-card {
    border: 1px solid var(--ppms-border, #ddd);
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 120px;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}
.ppms-team-card--active {
    border-color: var(--ppms-accent, #2563eb);
    box-shadow: 0 0 0 1px var(--ppms-accent, #2563eb);
}
.ppms-team-card__main {
    flex: 1;
    text-align: left;
    border: none;
    background: transparent;
    padding: 1rem;
    cursor: pointer;
    font: inherit;
    color: inherit;
}
.ppms-team-card__main:hover {
    background: rgba(0, 0, 0, 0.03);
}
.ppms-team-card__title {
    margin: 0 0 0.35rem;
    font-size: 1.05rem;
}
.ppms-team-card__desc {
    margin: 0 0 0.5rem;
    font-size: 0.875rem;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.ppms-team-card__meta {
    font-size: 0.8rem;
    color: var(--ppms-muted, #666);
}
.ppms-team-card__actions {
    padding: 0 1rem 1rem;
}
.ppms-team-forest {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.ppms-team-tree-block {
    border: 1px solid var(--ppms-border, #e5e5e5);
    border-radius: 10px;
    padding: 1rem 1rem 1rem 1.25rem;
    background: rgba(0, 0, 0, 0.02);
}
.ppms-team-tree-block__head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.5rem 1rem;
    margin-bottom: 0.75rem;
}
.ppms-team-tree-block__title {
    border: none;
    background: none;
    padding: 0;
    font: inherit;
    font-weight: 600;
    font-size: 1.05rem;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 2px;
    color: inherit;
}
.ppms-team-tree-block__count {
    font-size: 0.85rem;
}
.ppms-team-tree-block__empty {
    font-size: 0.9rem;
}
.ppms-team-tree__leader {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem 0.5rem;
    padding: 0.5rem 0.65rem;
    margin-bottom: 0.5rem;
    background: rgba(0, 0, 0, 0.04);
    border-radius: 8px;
    border-left: 3px solid var(--ppms-accent, #2563eb);
}
.ppms-team-tree__badge {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ppms-accent, #2563eb);
}
.ppms-team-tree__members {
    list-style: none;
    margin: 0;
    padding: 0 0 0 1rem;
    border-left: 2px solid var(--ppms-border, #ddd);
}
.ppms-team-tree__li {
    padding: 0.35rem 0;
    position: relative;
}
.ppms-team-tree__li::before {
    content: '';
    position: absolute;
    left: -1rem;
    top: 50%;
    width: 0.65rem;
    height: 1px;
    background: var(--ppms-border, #ddd);
}
.ppms-team-tree__name {
    font-weight: 500;
}
.ppms-team-perm-cell {
    font-size: 0.85rem;
    max-width: 220px;
}
.ppms-team-hint {
    font-size: 0.875rem;
}
.ppms-team-perm-fieldset {
    border: none;
    padding: 0;
    margin: 0;
}
.ppms-team-perm-row {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    cursor: pointer;
}
.ppms-team-perm-row input {
    margin-top: 0.2rem;
}
.ppms-link-btn {
    background: none;
    border: none;
    padding: 0;
    color: inherit;
    text-decoration: underline;
    cursor: pointer;
    font: inherit;
    text-align: left;
}
.ppms-td-actions {
    white-space: nowrap;
}
.ppms-btn-ghost {
    background: transparent;
    border: 1px solid var(--ppms-border, #ccc);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    font: inherit;
    margin-right: 0.35rem;
}
.ppms-lookup-list {
    list-style: none;
    margin: 0;
    padding: 0;
    border: 1px solid var(--ppms-border, #ddd);
    border-radius: 6px;
    max-height: 200px;
    overflow: auto;
}
.ppms-lookup-list li {
    border-bottom: 1px solid var(--ppms-border, #eee);
}
.ppms-lookup-list li:last-child {
    border-bottom: none;
}
.ppms-lookup-list .ppms-link-btn {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    text-decoration: none;
}
.ppms-lookup-list .ppms-link-btn:hover {
    background: rgba(0, 0, 0, 0.04);
}
.ppms-teams-detail-head .ppms-h3 {
    margin-bottom: 0;
}
</style>
