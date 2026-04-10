<template>
    <div class="vm-user-picker" :class="{ 'vm-user-picker--disabled': disabled }">
        <label v-if="label" class="vm-field__label" :for="inputId">{{ label }}</label>
        <p v-if="hint" :id="hintId" class="vm-field__hint">{{ hint }}</p>
        <div class="vm-user-picker__wrap">
            <template v-if="selected">
                <div class="vm-user-picker__selected">
                    <span class="vm-user-picker__name">{{ selected.name }}</span>
                    <span class="vm-user-picker__email">{{ selected.email }}</span>
                    <button
                        type="button"
                        class="vm-user-picker__clear ppms-btn-ghost ppms-btn-sm"
                        :disabled="disabled"
                        :aria-label="clearAria"
                        @click="clear"
                    >
                        ×
                    </button>
                </div>
            </template>
            <template v-else>
                <input
                    :id="inputId"
                    v-model="query"
                    type="text"
                    class="ppms-input vm-kv__control"
                    :disabled="disabled"
                    :placeholder="placeholder"
                    :title="hint"
                    autocomplete="off"
                    :aria-describedby="hint ? hintId : undefined"
                    :aria-expanded="open"
                    aria-autocomplete="list"
                    :aria-controls="listId"
                    role="combobox"
                    @focus="onFocus"
                    @blur="onBlurDelayed"
                    @input="onQueryInput"
                    @keydown.down.prevent="moveHighlight(1)"
                    @keydown.up.prevent="moveHighlight(-1)"
                    @keydown.enter.prevent="pickHighlighted"
                    @keydown.escape="close"
                />
                <ul
                    v-if="open && (suggestions.length || loading)"
                    :id="listId"
                    class="vm-user-picker__list"
                    role="listbox"
                >
                    <li v-if="loading" class="vm-user-picker__hint">{{ t('common.loading') }}</li>
                    <li
                        v-for="(u, idx) in suggestions"
                        v-else
                        :key="u.id"
                        role="option"
                        class="vm-user-picker__opt"
                        :class="{ 'vm-user-picker__opt--hl': idx === highlight }"
                        :aria-selected="idx === highlight"
                        @mousedown.prevent="pick(u)"
                    >
                        <span class="vm-user-picker__opt-name">{{ u.name }}</span>
                        <span class="vm-user-picker__opt-meta">{{ u.email }} · {{ u.role }}</span>
                    </li>
                    <li v-if="!loading && queryTrim.length >= 2 && !suggestions.length" class="vm-user-picker__hint">
                        {{ t('vendors.userPickerNoResults') }}
                    </li>
                </ul>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        default: null,
        validator: (v) => v === null || v === undefined || typeof v === 'number',
    },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    inputId: { type: String, default: 'vm-user-picker' },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();

const query = ref('');
const selected = ref(null);
const suggestions = ref([]);
const loading = ref(false);
const open = ref(false);
const highlight = ref(0);

const hintId = computed(() => `${props.inputId}-hint`);
const listId = computed(() => `${props.inputId}-list`);
const queryTrim = computed(() => query.value.trim());
const clearAria = computed(() => t('vendors.userPickerClear'));

let searchTimer = null;

async function loadById(id) {
    if (!id) {
        selected.value = null;
        return;
    }
    try {
        const { data } = await axios.get('/api/users/lookup', { params: { ids: id } });
        const row = Array.isArray(data) ? data[0] : null;
        selected.value = row || null;
    } catch {
        selected.value = null;
    }
}

async function fetchSuggestions() {
    const q = queryTrim.value;
    if (q.length < 2) {
        suggestions.value = [];
        return;
    }
    loading.value = true;
    try {
        const { data } = await axios.get('/api/users/lookup', { params: { q } });
        suggestions.value = Array.isArray(data) ? data : [];
        highlight.value = 0;
    } catch {
        suggestions.value = [];
    } finally {
        loading.value = false;
    }
}

function onQueryInput() {
    open.value = true;
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        fetchSuggestions();
    }, 280);
}

function onFocus() {
    open.value = true;
    if (queryTrim.value.length >= 2) {
        fetchSuggestions();
    }
}

let blurTimer = null;
function onBlurDelayed() {
    if (blurTimer) clearTimeout(blurTimer);
    blurTimer = setTimeout(() => {
        open.value = false;
    }, 200);
}

function close() {
    open.value = false;
}

function pick(u) {
    selected.value = u;
    emit('update:modelValue', u.id);
    query.value = '';
    suggestions.value = [];
    open.value = false;
}

function clear() {
    selected.value = null;
    emit('update:modelValue', null);
    query.value = '';
    suggestions.value = [];
}

function moveHighlight(delta) {
    if (!suggestions.value.length) return;
    const n = suggestions.value.length;
    highlight.value = (highlight.value + delta + n) % n;
}

function pickHighlighted() {
    const u = suggestions.value[highlight.value];
    if (u) pick(u);
}

watch(
    () => props.modelValue,
    (id) => {
        if (id == null || id === '') {
            selected.value = null;
            return;
        }
        if (selected.value?.id === id) return;
        loadById(id);
    },
    { immediate: true }
);

onMounted(() => {
    if (props.modelValue) {
        loadById(props.modelValue);
    }
});
</script>

<style scoped>
.vm-user-picker {
    min-width: 0;
}
.vm-user-picker--disabled {
    opacity: 0.72;
    pointer-events: none;
}
.vm-user-picker__wrap {
    position: relative;
}
.vm-user-picker__selected {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem 0.75rem;
    padding: 0.5rem 0.65rem;
    border-radius: 8px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: var(--ppms-bg-subtle, #f8fafc);
}
.vm-user-picker__name {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--ppms-text, #0f172a);
}
.vm-user-picker__email {
    font-size: 0.8125rem;
    color: var(--ppms-muted, #64748b);
}
.vm-user-picker__clear {
    margin-left: auto;
    min-width: 2rem;
    font-size: 1.1rem;
    line-height: 1;
}
.vm-user-picker__list {
    position: absolute;
    z-index: 20;
    left: 0;
    right: 0;
    top: calc(100% + 4px);
    margin: 0;
    padding: 0.25rem 0;
    list-style: none;
    max-height: 14rem;
    overflow-y: auto;
    border-radius: 8px;
    border: 1px solid var(--ppms-border, #e2e8f0);
    background: #fff;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.1);
}
.vm-user-picker__opt {
    padding: 0.45rem 0.65rem;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}
.vm-user-picker__opt:hover,
.vm-user-picker__opt--hl {
    background: rgba(79, 70, 229, 0.08);
}
.vm-user-picker__opt-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--ppms-text, #0f172a);
}
.vm-user-picker__opt-meta {
    font-size: 0.75rem;
    color: var(--ppms-muted, #64748b);
}
.vm-user-picker__hint {
    padding: 0.5rem 0.65rem;
    font-size: 0.8125rem;
    color: var(--ppms-muted, #64748b);
}
</style>
