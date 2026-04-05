<template>
    <div class="ppms-page">
        <div class="ppms-page-toolbar">
            <button type="button" class="ppms-btn-primary" @click="showForm = true">
                + {{ t('projects.newProject') }}
            </button>
        </div>

        <div v-if="loading" class="ppms-loading-line" role="status">{{ t('common.loading') }}</div>
        <div v-else class="ppms-table-scroll">
            <table class="ppms-table">
                <thead>
                    <tr>
                        <th>{{ t('projects.colName') }}</th>
                        <th>{{ t('projects.colCustomer') }}</th>
                        <th>{{ t('projects.colSuppliers') }}</th>
                        <th>{{ t('projects.colType') }}</th>
                        <th>{{ t('projects.colPhase') }}</th>
                        <th>{{ t('projects.colStatus') }}</th>
                        <th>{{ t('projects.colProgress') }}</th>
                        <th>{{ t('projects.colOwner') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in projects" :key="p.id">
                        <td>
                            <router-link :to="'/projects/' + p.id">{{ p.name }}</router-link>
                        </td>
                        <td>
                            <span v-if="p.customer_name">{{ p.customer_name }}</span>
                            <span v-else class="ppms-muted">—</span>
                        </td>
                        <td>{{ suppliersLabel(p) }}</td>
                        <td>{{ t(`projects.type.${p.type}`) }}</td>
                        <td>{{ t(`projects.phase.${p.phase}`) }}</td>
                        <td>{{ t(`projects.status.${p.status}`) }}</td>
                        <td>{{ Number(p.progress).toFixed(1) }}%</td>
                        <td>{{ p.owner?.name }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="!loading && lastPage > 1" class="ppms-pagination ppms-mt">
            <span class="ppms-muted">{{ t('projects.pagination', { current: page, last: lastPage }) }}</span>
            <div class="ppms-pagination-actions">
                <button
                    type="button"
                    class="ppms-btn-ghost"
                    :disabled="page <= 1"
                    @click="goPage(page - 1)"
                >
                    {{ t('projects.prevPage') }}
                </button>
                <button
                    type="button"
                    class="ppms-btn-ghost"
                    :disabled="page >= lastPage"
                    @click="goPage(page + 1)"
                >
                    {{ t('projects.nextPage') }}
                </button>
            </div>
        </div>

        <div v-if="showForm" class="ppms-modal-backdrop" @click.self="showForm = false">
            <div class="ppms-modal">
                <h2>{{ t('projects.createModalTitle') }}</h2>
                <form @submit.prevent="createProject">
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldName') }} *</span>
                        <input v-model="form.name" required />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldType') }} *</span>
                        <select v-model="form.type" required>
                            <option value="maintenance">{{ t('projects.type.maintenance') }}</option>
                            <option value="delivery">{{ t('projects.type.delivery') }}</option>
                            <option value="rnd">{{ t('projects.type.rnd') }}</option>
                        </select>
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldOwnerId') }} *</span>
                        <input v-model.number="form.owner_id" type="number" required />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldDeadline') }}</span>
                        <input v-model="form.deadline" type="date" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldCustomerName') }}</span>
                        <input v-model="form.customer_name" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldCustomerEmail') }}</span>
                        <input v-model="form.customer_email" type="email" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldSuppliersHint') }}</span>
                        <textarea v-model="form.suppliers_text" rows="3" />
                    </label>
                    <label class="ppms-field">
                        <span>{{ t('projects.fieldDescription') }}</span>
                        <textarea v-model="form.description" rows="3" />
                    </label>
                    <p v-if="formError" class="ppms-error">{{ formError }}</p>
                    <div class="ppms-modal-actions">
                        <button type="button" class="ppms-btn-ghost" @click="showForm = false">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" class="ppms-btn-primary">{{ t('common.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '@/bootstrap';
import { ppmsConfirm, ppmsToastSuccess } from '@/ppmsUi';

const { t } = useI18n();

const projects = ref([]);
const loading = ref(true);
const showForm = ref(false);
const formError = ref('');
const page = ref(1);
const lastPage = ref(1);

const form = reactive({
    name: '',
    type: 'delivery',
    owner_id: null,
    deadline: '',
    description: '',
    customer_name: '',
    customer_email: '',
    suppliers_text: '',
});

function suppliersLabel(p) {
    const n = (p.suppliers || []).length;
    if (!n) {
        return t('projects.suppliersNone');
    }

    return t('projects.suppliersCount', { n });
}

function parseSuppliersPayload() {
    const lines = form.suppliers_text
        .split('\n')
        .map((s) => s.trim())
        .filter(Boolean);
    if (!lines.length) {
        return null;
    }

    return lines.map((name) => ({ name }));
}

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/projects', { params: { page: page.value } });
        if (Array.isArray(data.data)) {
            projects.value = data.data;
            lastPage.value = data.last_page || 1;
            page.value = data.current_page || 1;
        } else {
            projects.value = data;
            lastPage.value = 1;
        }
    } finally {
        loading.value = false;
    }
}

function goPage(p) {
    page.value = p;
}

watch(page, () => load());

async function createProject() {
    formError.value = '';
    if (!(await ppmsConfirm(t('projects.confirmCreate')))) {
        return;
    }
    try {
        const suppliers = parseSuppliersPayload();
        await axios.post('/api/projects', {
            name: form.name,
            type: form.type,
            owner_id: form.owner_id,
            deadline: form.deadline || null,
            description: form.description || null,
            customer_name: form.customer_name?.trim() || null,
            customer_email: form.customer_email?.trim() || null,
            suppliers,
        });
        showForm.value = false;
        form.suppliers_text = '';
        form.customer_name = '';
        form.customer_email = '';
        ppmsToastSuccess(t('projects.createOk'));
        page.value = 1;
        await load();
    } catch (e) {
        formError.value = formatApiUserMessage(e, t('projects.createErr'));
    }
}

onMounted(load);
</script>
