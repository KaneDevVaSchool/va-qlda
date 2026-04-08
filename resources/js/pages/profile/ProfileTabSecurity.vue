<template>
    <div>
        <div v-if="loading" class="ppms-profile-skel" role="status">
            <div class="ppms-profile-skel-line" style="width: 45%" />
            <div class="ppms-profile-skel-line" style="width: 65%" />
        </div>
        <template v-else-if="err">{{ err }}</template>
        <template v-else>
            <p>
                <strong>{{ t('profile.securityRisk.' + (summary.risk === 'high' ? 'high' : summary.risk === 'medium' ? 'medium' : 'safe')) }}</strong>
            </p>
            <ul style="margin: 0 0 1rem 1rem; font-size: 0.9rem; color: var(--ppms-pf-muted)">
                <li>{{ t('profile.lastLogin') }}: {{ summary.last_login_at ? formatTime(summary.last_login_at) : '—' }}</li>
                <li>{{ t('profile.lastIp') }}: {{ summary.last_login_ip || '—' }}</li>
                <li>{{ t('profile.lastDevice') }}: {{ summary.last_login_device || '—' }}</li>
            </ul>

            <hr style="border: none; border-top: 1px solid var(--ppms-pf-border); margin: 1rem 0" />

            <h3 style="margin: 0 0 0.5rem; font-size: 1rem">{{ t('profile.changePassword') }}</h3>
            <form style="max-width: 400px" @submit.prevent="submitPw">
                <p>
                    <label>
                        {{ t('profile.currentPassword') }}<br />
                        <input v-model="pw.current" type="password" required autocomplete="current-password" style="width: 100%; padding: 0.4rem" />
                    </label>
                </p>
                <p>
                    <label>
                        {{ t('profile.newPassword') }}<br />
                        <input v-model="pw.next" type="password" required autocomplete="new-password" style="width: 100%; padding: 0.4rem" @input="meter" />
                    </label>
                    <div class="ppms-profile-meter" aria-hidden="true">
                        <div class="ppms-profile-meter-fill" :style="meterStyle" />
                    </div>
                    <small>{{ t('profile.passwordStrength') }}: {{ strengthLabel }}</small>
                </p>
                <p>
                    <label>
                        {{ t('profile.confirmPassword') }}<br />
                        <input v-model="pw.confirm" type="password" required autocomplete="new-password" style="width: 100%; padding: 0.4rem" />
                    </label>
                </p>
                <button type="submit" class="ppms-pf-btn ppms-pf-btn--primary" :disabled="saving">
                    {{ t('profile.changePassword') }}
                </button>
            </form>

            <p style="margin-top: 1.5rem; font-size: 0.85rem; color: var(--ppms-pf-muted)">{{ t('profile.twoFactorSoon') }}</p>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatApiUserMessage } from '../../bootstrap';
import { ppmsConfirm } from '../../ppmsUi';
import { ppmsToastSuccess } from '../../ppmsUi';

const { t } = useI18n();

const loading = ref(true);
const err = ref('');
const saving = ref(false);
const summary = ref({
    risk: 'safe',
    last_login_at: null,
    last_login_ip: null,
    last_login_device: null,
});
const pw = ref({ current: '', next: '', confirm: '' });
const strength = ref(0);

const strengthLabel = computed(() => {
    if (strength.value >= 4) {
        return 'Strong';
    }
    if (strength.value >= 2) {
        return 'Medium';
    }
    return 'Weak';
});

const meterStyle = computed(() => {
    const pct = Math.min(100, strength.value * 25);
    let bg = '#ef4444';
    if (strength.value >= 4) {
        bg = '#22c55e';
    } else if (strength.value >= 2) {
        bg = '#eab308';
    }
    return { width: pct + '%', background: bg };
});

function meter() {
    const s = pw.value.next || '';
    let score = 0;
    if (s.length >= 8) {
        score++;
    }
    if (s.length >= 12) {
        score++;
    }
    if (/[a-z]/.test(s) && /[A-Z]/.test(s)) {
        score++;
    }
    if (/\d/.test(s)) {
        score++;
    }
    if (/[^a-zA-Z0-9]/.test(s)) {
        score++;
    }
    strength.value = Math.min(4, score);
}

function formatTime(iso) {
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return iso;
    }
}

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/me/security');
        summary.value = data;
    } catch (e) {
        err.value = formatApiUserMessage(e, t('common.loading'));
    } finally {
        loading.value = false;
    }
});

async function submitPw() {
    if (pw.value.next !== pw.value.confirm) {
        window.alert('Mismatch');
        return;
    }
    if (!(await ppmsConfirm('Change password?', { title: t('profile.changePassword') }))) {
        return;
    }
    saving.value = true;
    try {
        await axios.put('/api/me/security/password', {
            current_password: pw.value.current,
            password: pw.value.next,
            password_confirmation: pw.value.confirm,
        });
        ppmsToastSuccess('OK');
        pw.value = { current: '', next: '', confirm: '' };
        strength.value = 0;
    } catch (e) {
        window.alert(formatApiUserMessage(e, 'Error'));
    } finally {
        saving.value = false;
    }
}
</script>
