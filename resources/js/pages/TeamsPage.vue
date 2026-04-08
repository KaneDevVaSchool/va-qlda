<template>
    <div class="ppms-page">
        <section class="ppms-card">
            <div class="ppms-teams-head">
                <div>
                    <h2>{{ t('teams.title') }}</h2>
                    <p class="ppms-muted ppms-mt-sm">{{ t('teams.subtitle') }}</p>
                </div>
                <router-link to="/kpi" class="ppms-btn-secondary ppms-btn-linkish">{{ t('teams.linkKpi') }}</router-link>
            </div>

            <form v-if="canCreate" class="ppms-stack ppms-mt" @submit.prevent="createTeam">
                <h3 class="ppms-h3">{{ t('teams.createSection') }}</h3>
                <div class="ppms-teams-create-grid">
                    <label class="ppms-field">
                        <span>{{ t('teams.name') }} *</span>
                        <input v-model="createForm.name" type="text" required maxlength="255" />
                    </label>
                    <label class="ppms-field ppms-field-span2">
                        <span>{{ t('teams.description') }}</span>
                        <textarea v-model="createForm.description" rows="2" maxlength="5000" />
                    </label>
                </div>
                <p v-if="createErr" class="ppms-error">{{ createErr }}</p>
                <button type="submit" class="ppms-btn-primary" :disabled="creating">{{ t('teams.createBtn') }}</button>
            </form>
        </section>

        <section class="ppms-card ppms-mt">
            <h3 class="ppms-h3">{{ t('teams.listSection') }}</h3>
            <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
            <p v-else-if="!teams.length" class="ppms-muted ppms-mt-sm">{{ t('teams.empty') }}</p>
            <div v-else class="ppms-table-scroll ppms-mt-sm">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('teams.name') }}</th>
                            <th>{{ t('teams.membersCount') }}</th>
                            <th>{{ t('teams.creator') }}</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in teams" :key="row.id">
                            <td>
                                <button type="button" class="ppms-link-btn" @click="openTeam(row.id)">{{ row.name }}</button>
                            </td>
                            <td>{{ row.members_count ?? 0 }}</td>
                            <td>{{ row.creator?.name ?? '—' }}</td>
                            <td class="ppms-td-actions">
                                <button
                                    v-if="row.can_manage"
                                    type="button"
                                    class="ppms-btn-ghost"
                                    @click="confirmDelete(row)"
                                >
                                    {{ t('common.delete') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="detail" class="ppms-card ppms-mt">
            <div class="ppms-teams-detail-head">
                <h3 class="ppms-h3">{{ detail.name }}</h3>
                <p v-if="detail.description" class="ppms-muted ppms-mt-sm">{{ detail.description }}</p>
            </div>

            <h4 class="ppms-h4 ppms-mt">{{ t('teams.membersTitle') }}</h4>
            <div class="ppms-table-scroll ppms-mt-sm">
                <table class="ppms-table">
                    <thead>
                        <tr>
                            <th>{{ t('teams.colName') }}</th>
                            <th>{{ t('teams.colEmail') }}</th>
                            <th>{{ t('teams.colRole') }}</th>
                            <th v-if="detail.can_manage" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in detail.members || []" :key="m.id">
                            <td>{{ m.name }}</td>
                            <td>{{ m.email }}</td>
                            <td>{{ formatMemberRole(m) }}</td>
                            <td v-if="detail.can_manage">
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
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { ppmsConfirm, ppmsToastError, ppmsToastSuccess } from '@/ppmsUi';

const { t } = useI18n();

const loading = ref(true);
const teams = ref([]);
const me = ref(null);
const detail = ref(null);
const creating = ref(false);
const createErr = ref('');
const createForm = reactive({ name: '', description: '' });

const lookupQ = ref('');
const lookupHits = ref([]);
let lookupTimer = null;

const canCreate = computed(() => ['admin', 'pm', 'tl'].includes(me.value?.role));

function formatMemberRole(m) {
    const r = m.pivot?.role;
    if (r === 'leader') {
        return t('teams.roleLeader');
    }
    return t('teams.roleMember');
}

async function load() {
    loading.value = true;
    try {
        const [u, list] = await Promise.all([axios.get('/api/user'), axios.get('/api/teams')]);
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

onMounted(load);
</script>

<style scoped>
.ppms-teams-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
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
.ppms-teams-create-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.ppms-field-span2 {
    grid-column: 1 / -1;
}
@media (max-width: 640px) {
    .ppms-teams-create-grid {
        grid-template-columns: 1fr;
    }
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
