<template>
    <div class="ppms-auth-page login-shell">
        <VaSiteHeader />

        <div class="login-content">
            <div class="ppms-auth ppms-auth--fill login-auth-split">
                <div class="login-mascot-col">
                    <div class="login-mascot-anim" aria-hidden="true">
                        <VaMascotImg
                            img-class="ppms-mascot--login-split"
                            alt="Mascot chào mừng Vietnam America Schools"
                        />
                    </div>
                </div>

                <div class="login-form-col">
                    <div class="ppms-auth-card login-card">
                        <header class="login-card-head">
                            <h1 class="login-title">Đăng nhập</h1>
                            <p class="login-sub">PPMS — Quản lý dự án &amp; hiệu suất VA Schools</p>
                        </header>

                        <div v-if="error" class="login-alert" role="alert">
                            <span class="login-alert-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 8v4M12 16h.01" stroke-linecap="round" />
                                </svg>
                            </span>
                            <p class="login-alert-text">{{ error }}</p>
                        </div>

                        <form class="login-form" @submit.prevent="submit">
                            <div class="ppms-field login-field">
                                <div class="login-label-row">
                                    <label class="login-label-text" for="login-email">Email</label>
                                    <button
                                        type="button"
                                        class="login-tip"
                                        aria-describedby="login-tip-email"
                                        aria-label="Gợi ý về email đăng nhập"
                                    >
                                        <span class="login-tip-icon" aria-hidden="true">?</span>
                                        <span id="login-tip-email" class="login-tip-bubble" role="tooltip">
                                            Nhập email doanh nghiệp đã được kích hoạt trên hệ thống.
                                        </span>
                                    </button>
                                </div>
                                <input
                                    id="login-email"
                                    v-model.trim="email"
                                    type="email"
                                    name="email"
                                    required
                                    autocomplete="username"
                                    :disabled="loading"
                                    placeholder="ten.ban@va-schools.vn"
                                    class="login-input"
                                />
                            </div>

                            <div class="ppms-field login-field">
                                <div class="login-label-row">
                                    <label class="login-label-text" for="login-password">Mật khẩu</label>
                                    <button
                                        type="button"
                                        class="login-tip"
                                        aria-describedby="login-tip-password"
                                        aria-label="Gợi ý về mật khẩu"
                                    >
                                        <span class="login-tip-icon" aria-hidden="true">?</span>
                                        <span id="login-tip-password" class="login-tip-bubble" role="tooltip">
                                            Mật khẩu phân biệt chữ hoa, thường. Liên hệ IT nếu quên mật khẩu.
                                        </span>
                                    </button>
                                </div>
                                <div class="login-password-row">
                                    <input
                                        id="login-password"
                                        v-model="password"
                                        :type="passwordVisible ? 'text' : 'password'"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        :disabled="loading"
                                        placeholder="••••••••"
                                        class="login-password-input login-input"
                                    />
                                    <button
                                        type="button"
                                        class="login-password-toggle"
                                        :aria-label="passwordVisible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                                        :aria-pressed="passwordVisible"
                                        :disabled="loading"
                                        @click="passwordVisible = !passwordVisible"
                                    >
                                        <svg v-if="!passwordVisible" class="login-eye-svg" viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                fill="currentColor"
                                                d="M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0-7C7 9 2.73 12.11 1 16.5 2.73 20.89 7 24 12 24s9.27-3.11 11-7.5C21.27 12.11 17 9 12 9zm0 13a9.77 9.77 0 0 1-8.82-5.5C4.15 10.36 7.92 7 12 7s7.85 3.36 8.82 7.5A9.77 9.77 0 0 1 12 22z"
                                            />
                                        </svg>
                                        <svg v-else class="login-eye-svg" viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                fill="currentColor"
                                                d="M2 5.27 3.28 4 20 20.72 18.73 22l-3-3C14.74 20.53 13.39 21 12 21c-5 0-9.27-3.11-11-7.5 1.38-3.91 4.35-6.95 8.12-8.25L2 5.27zM12 9a3 3 0 0 1 3 3c0 .46-.11.9-.28 1.28L9.72 9.28c.38-.17.82-.28 1.28-.28zm0-2c-1.39 0-2.74.47-3.88 1.26L3.11 4.38A17.962 17.962 0 0 1 12 3c5 0 9.27 3.11 11 7.5-.64 1.82-1.64 3.44-2.89 4.82l-3.11-3.11C16.26 11.47 14.21 10 12 10c-.79 0-1.54.13-2.25.36L8.08 8.69A8.99 8.99 0 0 1 12 7z"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="ppms-btn-primary login-submit" :disabled="loading">
                                <span v-if="loading" class="login-spinner" aria-hidden="true" />
                                <span>{{ loading ? 'Đang đăng nhập…' : 'Đăng nhập' }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <VaSiteFooter :show-nav="false" />
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import VaMascotImg from '../components/VaMascotImg.vue';
import VaSiteFooter from '../components/VaSiteFooter.vue';
import VaSiteHeader from '../components/VaSiteHeader.vue';
import { formatApiUserMessage, setAuthToken } from '../bootstrap';

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);
const passwordVisible = ref(false);

const route = useRoute();
const router = useRouter();

watch([email, password], () => {
    error.value = '';
});

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        const { data } = await axios.post('/api/login', {
            email: email.value,
            password: password.value,
        });
        setAuthToken(data.token);
        const redirect = route.query.redirect || '/';
        router.push(typeof redirect === 'string' ? redirect : '/');
    } catch (e) {
        error.value = formatApiUserMessage(e, 'Đăng nhập thất bại.');
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.login-shell {
    font-family: var(--ppms-font, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif);
}

.login-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 0;
    width: 100%;
    padding: 1rem max(1rem, env(safe-area-inset-right)) 1.25rem max(1rem, env(safe-area-inset-left));
}

.login-auth-split {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1.75rem;
    width: 100%;
    max-width: 1040px;
    margin: 0 auto;
}

@media (min-width: 768px) {
    .login-auth-split {
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 2.75rem;
    }
}

.login-mascot-col {
    flex: 0 0 auto;
    display: flex;
    justify-content: center;
}

.login-mascot-anim {
    animation: login-mascot-float 4.2s ease-in-out infinite;
    filter: drop-shadow(0 10px 28px rgba(154, 0, 54, 0.18));
}

@keyframes login-mascot-float {
    0%,
    100% {
        transform: translateY(0) rotate(0deg);
    }
    35% {
        transform: translateY(-10px) rotate(-1.5deg);
    }
    70% {
        transform: translateY(-4px) rotate(1.2deg);
    }
}

.login-mascot-anim :deep(.ppms-mascot-img) {
    display: block;
    max-height: min(42vw, 15rem);
    width: auto;
    max-width: 12rem;
}

@media (min-width: 768px) {
    .login-mascot-anim :deep(.ppms-mascot-img) {
        max-height: min(52vh, 19rem);
        max-width: 14rem;
    }
}

.login-form-col {
    flex: 1 1 auto;
    width: 100%;
    max-width: 420px;
    min-width: 0;
}

.login-card {
    width: 100%;
    position: relative;
    overflow: visible;
    border-radius: 16px;
    padding: 2rem 1.85rem 2.1rem;
    border: 1px solid var(--ppms-border, #e8eaef);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.9) inset,
        0 20px 50px rgba(26, 29, 38, 0.08),
        0 4px 16px rgba(154, 0, 54, 0.06);
}

.login-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 1.25rem;
    right: 1.25rem;
    height: 3px;
    border-radius: 0 0 3px 3px;
    background: linear-gradient(90deg, var(--ppms-primary, #9a0036), #d4145a);
    pointer-events: none;
}

.login-card-head {
    text-align: center;
    margin-bottom: 1.5rem;
}

.login-title {
    margin: 0 0 0.4rem;
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--ppms-text, #1a1d26);
}

.login-sub {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--ppms-muted, #5c6370);
}

.login-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.75rem 0.9rem;
    margin-bottom: 1.1rem;
    border-radius: 10px;
    border: 1px solid rgba(220, 38, 38, 0.35);
    background: rgba(220, 38, 38, 0.07);
    color: #991b1b;
}

.login-alert-icon {
    flex-shrink: 0;
    margin-top: 0.05rem;
    color: var(--ppms-danger, #dc2626);
}

.login-alert-text {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.45;
}

.login-field {
    margin-bottom: 1.1rem;
}

.login-label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.4rem;
}

.login-label-text {
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    color: var(--ppms-text, #1a1d26);
}

/* Tooltip (?): bubble khi hover / focus */
.login-tip {
    position: relative;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.35rem;
    height: 1.35rem;
    padding: 0;
    border: none;
    border-radius: 50%;
    background: var(--ppms-primary-soft, rgba(154, 0, 54, 0.1));
    color: var(--ppms-primary, #9a0036);
    font-size: 0.7rem;
    font-weight: 800;
    line-height: 1;
    cursor: help;
    transition:
        background 0.2s ease,
        transform 0.15s ease;
}

.login-tip:hover,
.login-tip:focus-visible {
    background: rgba(154, 0, 54, 0.18);
    outline: none;
    transform: scale(1.06);
}

.login-tip-bubble {
    position: absolute;
    z-index: 60;
    bottom: calc(100% + 8px);
    right: 0;
    left: auto;
    width: max(12rem, 52vw);
    max-width: 16rem;
    padding: 0.55rem 0.65rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.4;
    text-align: left;
    color: var(--ppms-text, #1a1d26);
    background: var(--ppms-surface, #fff);
    border: 1px solid var(--ppms-border, #e8eaef);
    box-shadow: 0 8px 28px rgba(26, 29, 38, 0.12);
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transform: translateY(4px);
    transition:
        opacity 0.2s ease,
        visibility 0.2s ease,
        transform 0.2s ease;
}

.login-tip-bubble::after {
    content: '';
    position: absolute;
    top: 100%;
    right: 10px;
    border: 6px solid transparent;
    border-top-color: var(--ppms-border, #e8eaef);
}

.login-tip:hover .login-tip-bubble,
.login-tip:focus-visible .login-tip-bubble {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.login-input {
    width: 100%;
    min-height: 2.75rem;
    padding: 0.6rem 0.85rem !important;
    font-size: 0.9375rem !important;
    border-radius: 10px !important;
    border: 1px solid var(--ppms-border, #e8eaef) !important;
    background: var(--ppms-bg, #f8f9fb) !important;
    transition:
        border-color 0.22s ease,
        box-shadow 0.22s ease,
        background 0.22s ease;
}

.login-input::placeholder {
    color: var(--ppms-muted, #5c6370);
    opacity: 0.65;
}

.login-input:hover:not(:disabled) {
    border-color: rgba(154, 0, 54, 0.25) !important;
}

.login-input:focus {
    outline: none;
    border-color: var(--ppms-primary, #9a0036) !important;
    background: var(--ppms-surface, #fff) !important;
    box-shadow: 0 0 0 3px rgba(154, 0, 54, 0.14);
}

.login-password-row {
    position: relative;
    display: flex;
    align-items: stretch;
}

.login-password-input {
    flex: 1;
    padding-right: 2.75rem !important;
}

.login-password-toggle {
    position: absolute;
    right: 0.35rem;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    padding: 0;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: var(--ppms-muted, #5c6370);
    cursor: pointer;
    transition:
        background 0.2s ease,
        color 0.2s ease;
}

.login-password-toggle:hover:not(:disabled) {
    background: var(--ppms-primary-soft, rgba(154, 0, 54, 0.08));
    color: var(--ppms-primary, #9a0036);
}

.login-password-toggle:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.login-eye-svg {
    width: 1.25rem;
    height: 1.25rem;
    display: block;
}

.login-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    width: 100%;
    margin-top: 0.5rem;
    min-height: 3rem;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    border-radius: 10px !important;
    background: linear-gradient(135deg, #9a0036 0%, #c01048 50%, #9a0036 100%) !important;
    background-size: 200% auto;
    border: none !important;
    box-shadow: 0 4px 18px rgba(154, 0, 54, 0.3);
    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease,
        filter 0.18s ease;
}

.login-submit:hover:not(:disabled) {
    transform: scale(1.015);
    box-shadow: 0 6px 24px rgba(154, 0, 54, 0.38);
    filter: brightness(1.04);
}

.login-submit:active:not(:disabled) {
    transform: scale(0.99);
}

.login-submit:disabled {
    opacity: 0.88;
    transform: none;
    cursor: wait;
}

.login-spinner {
    width: 1.05rem;
    height: 1.05rem;
    flex-shrink: 0;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: login-spin 0.7s linear infinite;
}

@keyframes login-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
