<template>
    <div>
        <div v-if="loading" class="ppms-profile-skel" role="status">
            <div class="ppms-profile-skel-line" style="width: 50%" />
            <div class="ppms-profile-skel-line" style="width: 75%" />
        </div>
        <template v-else>
            <div class="ppms-profile-header-meta" style="margin-bottom: 1rem">
                <span>{{ t('profile.profileCompleteness') }}: <strong>{{ profile?.profile_completeness ?? 0 }}%</strong></span>
                <span v-if="profile?.profile_updated_at"
                    >{{ t('profile.lastUpdated') }}:
                    {{ formatTime(profile.profile_updated_at) }}</span
                >
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: flex-start">
                <div style="flex: 0 0 200px">
                    <div
                        class="ppms-profile-drop"
                        :class="{ 'ppms-profile-drop--active': dragOver }"
                        @dragover.prevent="dragOver = true"
                        @dragleave="dragOver = false"
                        @drop.prevent="onDrop"
                        @click="fileRef?.click()"
                    >
                        <p style="margin: 0; font-size: 0.9rem">{{ t('profile.avatarDrop') }}</p>
                        <input
                            ref="fileRef"
                            type="file"
                            accept="image/*"
                            hidden
                            @change="onPickFile"
                        />
                    </div>
                    <p style="font-size: 0.8rem; color: var(--ppms-pf-muted, #64748b); margin-top: 0.5rem">
                        {{ t('profile.avatarCropHint') }}
                    </p>
                </div>

                <div style="flex: 1 1 260px">
                    <div v-if="cropSrc" class="ppms-profile-crop-host">
                        <img ref="imgRef" :src="cropSrc" alt="" style="max-width: 100%; display: block" />
                    </div>
                    <div v-if="cropSrc" style="margin-top: 0.75rem">
                        <button type="button" class="ppms-pf-btn ppms-pf-btn--primary" @click="applyCrop">
                            {{ t('profile.applyCrop') }}
                        </button>
                    </div>
                </div>
            </div>

            <div style="margin-top: 1.5rem">
                <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.75rem">
                    <button
                        v-if="!editing"
                        type="button"
                        class="ppms-pf-btn ppms-pf-btn--primary"
                        @click="editing = true"
                    >
                        {{ t('profile.edit') }}
                    </button>
                    <template v-else>
                        <button type="button" class="ppms-pf-btn ppms-pf-btn--primary" :disabled="saving" @click="save">
                            {{ t('profile.save') }}
                        </button>
                        <button type="button" class="ppms-pf-btn" :disabled="saving" @click="cancelEdit">
                            {{ t('profile.cancel') }}
                        </button>
                    </template>
                </div>

                <p style="font-size: 0.85rem; color: var(--ppms-pf-muted)">{{ t('profile.emailReadonly') }}</p>
                <p>
                    <strong>Email:</strong>
                    {{ form.email }}
                </p>
                <p>
                    <label>
                        <strong>{{ t('profile.phone') }}</strong><br />
                        <input
                            v-model="form.phone"
                            type="text"
                            :disabled="!editing"
                            style="width: 100%; max-width: 360px; padding: 0.4rem 0.5rem"
                        />
                    </label>
                </p>
                <p>
                    <label>
                        <strong>{{ t('profile.address') }}</strong><br />
                        <textarea
                            v-model="form.address"
                            rows="3"
                            :disabled="!editing"
                            style="width: 100%; max-width: 480px; padding: 0.4rem 0.5rem"
                        />
                    </label>
                </p>
            </div>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';
import { ppmsToastSuccess } from '../../ppmsUi';

const emit = defineEmits(['refresh']);

const { t } = useI18n();

const loading = ref(true);
const editing = ref(false);
const saving = ref(false);
const profile = ref(null);
const form = ref({ name: '', email: '', phone: '', address: '' });
const fileRef = ref(null);
const imgRef = ref(null);
const cropSrc = ref('');
const dragOver = ref(false);
let cropper = null;
let pickedFile = null;

function formatTime(iso) {
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return iso;
    }
}

function destroyCropper() {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
}

function onPickFile(e) {
    const f = e.target.files?.[0];
    if (f) {
        startCrop(f);
    }
}

function onDrop(e) {
    dragOver.value = false;
    const f = e.dataTransfer?.files?.[0];
    if (f && f.type.startsWith('image/')) {
        startCrop(f);
    }
}

function startCrop(file) {
    if (cropSrc.value) {
        URL.revokeObjectURL(cropSrc.value);
    }
    pickedFile = file;
    destroyCropper();
    cropSrc.value = URL.createObjectURL(file);
    nextTick(() => {
        destroyCropper();
        if (imgRef.value) {
            cropper = new Cropper(imgRef.value, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 0.92,
            });
        }
    });
}

async function applyCrop() {
    if (!cropper) {
        return;
    }
    const canvas = cropper.getCroppedCanvas({ width: 512, height: 512 });
    canvas.toBlob(async (blob) => {
        if (!blob) {
            return;
        }
        const fd = new FormData();
        fd.append('avatar', blob, pickedFile?.name || 'avatar.jpg');
        try {
            await axios.post('/api/me/profile/avatar', fd);
            ppmsToastSuccess('OK');
            cropSrc.value = '';
            destroyCropper();
            await load();
            emit('refresh');
        } catch (e) {
            window.alert(formatApiUserMessage(e, 'Upload failed'));
        }
    }, 'image/jpeg', 0.92);
}

function cancelEdit() {
    editing.value = false;
    const p = profile.value;
    if (p) {
        form.value = {
            name: p.name || '',
            email: p.email || '',
            phone: p.phone || '',
            address: p.address || '',
        };
    }
}

async function save() {
    saving.value = true;
    try {
        await axios.patch('/api/me/profile', {
            name: form.value.name,
            phone: form.value.phone || null,
            address: form.value.address || null,
        });
        ppmsToastSuccess('OK');
        editing.value = false;
        await load();
        emit('refresh');
    } catch (e) {
        window.alert(formatApiUserMessage(e, 'Error'));
    } finally {
        saving.value = false;
    }
}

async function load() {
    const { data } = await axios.get('/api/me/profile');
    profile.value = data;
    form.value = {
        name: data.name || '',
        email: data.email || '',
        phone: data.phone || '',
        address: data.address || '',
    };
}

onMounted(async () => {
    try {
        await load();
    } catch (e) {
        window.alert(formatApiUserMessage(e, t('common.loading')));
    } finally {
        loading.value = false;
    }
});

onBeforeUnmount(() => {
    destroyCropper();
    if (cropSrc.value) {
        URL.revokeObjectURL(cropSrc.value);
    }
});
</script>
