<script setup>
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    modelValue: { type: String, default: '' },
    inputId: { type: String, required: true },
    placeholder: { type: String, default: '' },
    title: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

function splitToTags(text) {
    if (text == null || text === undefined) {
        return [];
    }
    const s = String(text).trim();
    if (!s) {
        return [];
    }
    return s
        .split(/[\n,;|]+/)
        .map((x) => x.trim())
        .filter(Boolean);
}

function serializeTags(arr) {
    return arr.join('\n');
}

const tags = ref([]);
const input = ref('');

function syncFromModel() {
    tags.value = splitToTags(props.modelValue);
}

watch(
    () => props.modelValue,
    () => {
        syncFromModel();
    },
    { immediate: true },
);

function emitModel() {
    emit('update:modelValue', serializeTags(tags.value));
}

function addTag() {
    const raw = input.value.trim();
    if (!raw) {
        return;
    }
    if (!tags.value.includes(raw)) {
        tags.value.push(raw);
    }
    input.value = '';
    emitModel();
}

function removeTag(i) {
    tags.value.splice(i, 1);
    emitModel();
}

function onKeydown(e) {
    if (props.disabled) {
        return;
    }
    if (e.key === 'Enter') {
        e.preventDefault();
        addTag();
        return;
    }
    if (e.key === 'Backspace' && !input.value && tags.value.length) {
        tags.value.pop();
        emitModel();
    }
}

const flushPending = () => {
    addTag();
};

defineExpose({ flushPending });

function onPaste(e) {
    const text = e.clipboardData?.getData('text/plain') ?? '';
    if (!text.includes(',') && !text.includes(';') && !text.includes('|') && !text.includes('\n')) {
        return;
    }
    e.preventDefault();
    const extra = splitToTags(text);
    for (const x of extra) {
        if (!tags.value.includes(x)) {
            tags.value.push(x);
        }
    }
    emitModel();
}
</script>

<template>
    <div class="vm-tag-input" :class="{ 'vm-tag-input--disabled': disabled }">
        <div class="vm-tag-input__box" :title="title">
            <ul v-if="tags.length" class="vm-tag-input__tags" aria-label="tags">
                <li v-for="(tag, i) in tags" :key="'tag-' + i + '-' + tag" class="vm-tag-input__tag">
                    <span class="vm-tag-input__tag-text">{{ tag }}</span>
                    <button
                        v-if="!disabled"
                        type="button"
                        class="vm-tag-input__tag-remove"
                        :aria-label="t('vendors.tagRemove')"
                        @click="removeTag(i)"
                    >
                        ×
                    </button>
                </li>
            </ul>
            <input
                :id="inputId"
                v-model="input"
                type="text"
                class="vm-tag-input__field"
                :placeholder="tags.length ? '' : placeholder"
                :disabled="disabled"
                autocomplete="off"
                @keydown="onKeydown"
                @paste="onPaste"
                @blur="addTag"
            />
        </div>
    </div>
</template>

<style scoped>
.vm-tag-input {
    width: 100%;
}
.vm-tag-input--disabled .vm-tag-input__box {
    opacity: 0.75;
    pointer-events: none;
}
.vm-tag-input__box {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
    min-height: 2.75rem;
    padding: 0.4rem 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}
.vm-tag-input__box:focus-within {
    border-color: rgba(79, 70, 229, 0.45);
    box-shadow:
        0 0 0 1px rgba(79, 70, 229, 0.15),
        0 0 0 4px rgba(79, 70, 229, 0.1);
}
.vm-tag-input__tags {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: center;
}
.vm-tag-input__tag {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    max-width: 100%;
    padding: 0.2rem 0.35rem 0.2rem 0.55rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.35;
    color: #1e40af;
    background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
    border: 1px solid #bfdbfe;
    border-radius: 8px;
}
.vm-tag-input__tag-text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.vm-tag-input__tag-remove {
    flex-shrink: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1.1rem;
    line-height: 1;
    padding: 0 0.2rem;
    color: #64748b;
    border-radius: 4px;
}
.vm-tag-input__tag-remove:hover {
    background: rgba(30, 64, 175, 0.12);
    color: #1e3a8a;
}
.vm-tag-input__field {
    flex: 1 1 8rem;
    min-width: 6rem;
    border: none;
    outline: none;
    background: transparent;
    font: inherit;
    font-size: 0.9375rem;
    padding: 0.25rem 0.35rem;
    color: #0f172a;
}
.vm-tag-input__field::placeholder {
    color: #94a3b8;
}
.vm-tag-input__field:disabled {
    cursor: not-allowed;
}
</style>
