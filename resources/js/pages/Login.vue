<template>
    <div class="ppms-auth-page login-shell login-shell--va">
        <VaSiteHeader />

        <div class="login-content">
            <header class="login-hero">
                <img
                    class="login-hero-dragon"
                    src="/images/logo/logo-footer.png"
                    width="112"
                    height="112"
                    alt=""
                    decoding="async"
                    loading="eager"
                />
                <h1 class="login-hero-title">{{ t('login.heroTitle') }}</h1>
                <p class="login-hero-slogan">{{ t('login.heroSlogan') }}</p>
            </header>

            <div class="ppms-auth ppms-auth--fill login-stage">
                <div class="ppms-auth-card login-card">
                    <header
                        class="login-card-head login-card-head--center"
                        :class="{ 'login-card-head--with-tabs': googleTabVisible && !mfaStep }"
                    >
                        <h2 class="login-title">{{ t('login.title') }}</h2>
                        <p
                            v-if="!mfaStep && loginMode === 'google' && googleTabVisible"
                            class="login-sub login-sub--lead"
                        >
                            {{ t('login.cardLeadGoogle') }}
                        </p>
                        <p v-else-if="!mfaStep && loginMode === 'email'" class="login-sub login-sub--lead">
                            {{ t('login.subtitle') }}
                        </p>
                    </header>

                    <div
                        v-if="googleTabVisible && !mfaStep"
                        class="login-method-tabs"
                        role="tablist"
                        :aria-label="t('login.tabListLabel')"
                    >
                        <button
                            type="button"
                            role="tab"
                            class="login-method-tab"
                            :class="{ 'is-active': loginMode === 'google' }"
                            :aria-selected="loginMode === 'google'"
                            @click="loginMode = 'google'"
                        >
                            <svg class="login-method-tab-g" viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    fill="#4285F4"
                                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                />
                                <path
                                    fill="#34A853"
                                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                />
                                <path
                                    fill="#FBBC05"
                                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                />
                                <path
                                    fill="#EA4335"
                                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                />
                            </svg>
                            <span>{{ t('login.methodTabGoogle') }}</span>
                        </button>
                        <button
                            type="button"
                            role="tab"
                            class="login-method-tab"
                            :class="{ 'is-active': loginMode === 'email' }"
                            :aria-selected="loginMode === 'email'"
                            @click="loginMode = 'email'"
                        >
                            <span>{{ t('login.methodTabEmailPw') }}</span>
                        </button>
                    </div>

                        <div v-if="bannerError" class="login-alert" role="alert">
                            <span class="login-alert-icon" aria-hidden="true">
                                <svg
                                    viewBox="0 0 24 24"
                                    width="20"
                                    height="20"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 8v4M12 16h.01" stroke-linecap="round" />
                                </svg>
                            </span>
                            <p class="login-alert-text">{{ bannerError }}</p>
                        </div>

                        <div v-if="mfaStep" class="login-mfa-block">
                            <h2 class="login-mfa-title">{{ t('login.mfaTitle') }}</h2>
                            <p class="login-mfa-desc">{{ t('login.mfaDesc') }}</p>
                            <div class="ppms-field login-field">
                                <label class="login-label-text" for="login-mfa-code">{{ t('login.mfaCodeLabel') }}</label>
                                <input
                                    id="login-mfa-code"
                                    v-model="mfaCode"
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    maxlength="6"
                                    autocomplete="one-time-code"
                                    class="login-input login-mfa-input"
                                    :disabled="loading"
                                    :aria-invalid="mfaCodeError ? 'true' : 'false'"
                                    @input="mfaCodeError = ''"
                                />
                                <p v-if="mfaCodeError" class="login-field-msg login-field-msg--err" role="alert">
                                    {{ mfaCodeError }}
                                </p>
                            </div>
                            <label class="login-remember">
                                <input v-model="rememberMe" type="checkbox" class="login-remember-input" />
                                <span class="login-remember-text">{{ t('login.rememberMe') }}</span>
                            </label>
                            <p class="login-remember-hint">{{ t('login.rememberHint') }}</p>
                            <button
                                type="button"
                                class="ppms-btn-primary login-submit"
                                :disabled="loading || mfaCode.trim().length < 6"
                                @click="submitMfa"
                            >
                                <span v-if="loading" class="login-spinner" aria-hidden="true" />
                                <span>{{ loading ? t('login.mfaSubmitting') : t('login.mfaVerify') }}</span>
                            </button>
                            <button type="button" class="login-mfa-back" @click="resetMfaFlow">
                                {{ t('login.mfaBack') }}
                            </button>
                        </div>

                        <template v-else>
                            <div v-if="googleTabVisible && loginMode === 'google'" class="login-google-first">
                                <button
                                    type="button"
                                    class="login-google-glyph"
                                    :disabled="googleLoading || !securityAcknowledged"
                                    :aria-label="t('login.googleBtn')"
                                    @click="startGoogleLogin"
                                >
                                    <span v-if="googleLoading" class="login-spinner login-spinner--dark" aria-hidden="true" />
                                    <svg v-else class="login-google-glyph-svg" viewBox="0 0 24 24" aria-hidden="true">
                                        <path
                                            fill="#4285F4"
                                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                        />
                                        <path
                                            fill="#34A853"
                                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                        />
                                        <path
                                            fill="#FBBC05"
                                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                        />
                                        <path
                                            fill="#EA4335"
                                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                        />
                                    </svg>
                                </button>
                                <label class="login-ack-inline" for="login-security-ack-google">
                                    <input
                                        id="login-security-ack-google"
                                        v-model="securityAcknowledged"
                                        type="checkbox"
                                        name="security_ack_google"
                                        class="login-ack-input"
                                        :aria-invalid="ackError ? 'true' : 'false'"
                                    />
                                    <span>{{ t('login.ackShort') }}</span>
                                </label>
                                <p v-if="ackError" class="login-field-msg login-field-msg--err login-field-msg--ack" role="alert">
                                    {{ ackError }}
                                </p>
                            </div>

                            <template v-else>
                                <form class="login-form" @submit.prevent="submit">
                                    <div class="login-form-grid">
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
                                                :class="{ 'login-input--invalid': !!emailError }"
                                                :aria-invalid="emailError ? 'true' : 'false'"
                                                @input="emailError = ''"
                                            />
                                            <p v-if="emailError" class="login-field-msg login-field-msg--err" role="alert">
                                                {{ emailError }}
                                            </p>
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
                                                    :class="{ 'login-input--invalid': !!passwordError }"
                                                    :aria-invalid="passwordError ? 'true' : 'false'"
                                                    @input="passwordError = ''"
                                                />
                                                <button
                                                    type="button"
                                                    class="login-password-toggle"
                                                    :aria-label="passwordVisible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                                                    :aria-pressed="passwordVisible"
                                                    :disabled="loading"
                                                    @click="passwordVisible = !passwordVisible"
                                                >
                                                    <svg
                                                        v-if="!passwordVisible"
                                                        class="login-eye-svg"
                                                        viewBox="0 0 24 24"
                                                        aria-hidden="true"
                                                    >
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
                                            <p v-if="passwordError" class="login-field-msg login-field-msg--err" role="alert">
                                                {{ passwordError }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="login-form-compliance">
                                        <div class="login-ack-compact">
                                            <label class="login-ack-label" for="login-security-ack">
                                                <input
                                                    id="login-security-ack"
                                                    v-model="securityAcknowledged"
                                                    type="checkbox"
                                                    name="security_ack"
                                                    class="login-ack-input"
                                                    :aria-invalid="ackError ? 'true' : 'false'"
                                                    :aria-describedby="ackError ? 'login-ack-hint login-ack-err' : 'login-ack-hint'"
                                                />
                                                <span class="login-ack-text">
                                                    <span class="login-ack-line">
                                                        {{ t('login.ackLabel') }}
                                                        <a
                                                            class="login-ack-learn-link"
                                                            href="https://intercertvietnam.com/chung-nhan-iso-270012022/"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            :title="t('login.learnMoreSecurity')"
                                                            :aria-label="t('login.learnMoreSecurity')"
                                                            @click.stop
                                                            >{{ t('login.learnMoreShort') }}</a
                                                        >
                                                    </span>
                                                    <span id="login-ack-hint" class="login-ack-hint">{{ t('login.ackHint') }}</span>
                                                </span>
                                            </label>
                                            <p v-if="ackError" id="login-ack-err" class="login-field-msg login-field-msg--err" role="alert">
                                                {{ ackError }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="login-form-footer">
                                        <label class="login-remember">
                                            <input v-model="rememberMe" type="checkbox" class="login-remember-input" />
                                            <span class="login-remember-text">{{ t('login.rememberMe') }}</span>
                                        </label>
                                        <p class="login-remember-hint login-remember-hint--form">{{ t('login.rememberHint') }}</p>

                                        <button
                                            type="submit"
                                            class="ppms-btn-primary login-submit"
                                            :disabled="loading || !securityAcknowledged"
                                        >
                                            <span v-if="loading" class="login-spinner" aria-hidden="true" />
                                            <span>{{ loading ? t('login.submitting') : t('login.submit') }}</span>
                                        </button>
                                    </div>
                                </form>
                            </template>
                        </template>
                    </div>
                </div>
        </div>

        <VaSiteFooter class="login-shell-footer" :show-nav="false" />
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import VaSiteFooter from '../components/VaSiteFooter.vue';
import VaSiteHeader from '../components/VaSiteHeader.vue';
import { formatApiUserMessage, setAuthToken } from '../bootstrap';

const { t } = useI18n();

/** 'google' = màn hình tròn G (mặc định nếu bật OAuth); 'email' = form email + ISO/PCI */
const loginMode = ref('email');
const googleConfigured = ref(false);
const googleConfigLoaded = ref(false);
const googleLoading = ref(false);

const googleTabVisible = computed(() => googleConfigLoaded.value && googleConfigured.value);

const email = ref('');
const password = ref('');
const bannerError = ref('');
const emailError = ref('');
const passwordError = ref('');
const ackError = ref('');
const mfaCodeError = ref('');
const loading = ref(false);
const passwordVisible = ref(false);
const securityAcknowledged = ref(false);
const rememberMe = ref(true);
const mfaStep = ref(false);
const mfaChallenge = ref('');
const mfaCode = ref('');

const route = useRoute();
const router = useRouter();

const GOOGLE_ERROR_I18N = {
    oauth_denied: 'googleDenied',
    oauth_failed: 'googleFailed',
    no_email: 'googleNoEmail',
    no_account: 'googleNoAccount',
    locked: 'googleLocked',
    account_mismatch: 'googleAccountMismatch',
    domain_not_allowed: 'googleDomainNotAllowed',
};

function googleErrorMessage(code) {
    const suffix = GOOGLE_ERROR_I18N[code];
    return suffix ? t(`login.${suffix}`) : '';
}

watch(email, () => {
    emailError.value = '';
});

watch(password, () => {
    passwordError.value = '';
});

watch(loginMode, (mode) => {
    bannerError.value = '';
    if (mode !== 'email') {
        resetMfaFlow();
    }
});

watch(securityAcknowledged, (v) => {
    if (v) {
        ackError.value = '';
    }
});

watch(googleTabVisible, (visible) => {
    if (!visible) {
        loginMode.value = 'email';
    }
});

function applyGoogleQueryError() {
    const code = route.query.google_error;
    if (typeof code === 'string' && code in GOOGLE_ERROR_I18N) {
        bannerError.value = googleErrorMessage(code);
        if (googleTabVisible.value) {
            loginMode.value = 'google';
        }
    }
}

function resetMfaFlow() {
    mfaStep.value = false;
    mfaChallenge.value = '';
    mfaCode.value = '';
    mfaCodeError.value = '';
}

function clearLoginFormErrors() {
    bannerError.value = '';
    emailError.value = '';
    passwordError.value = '';
    ackError.value = '';
}

function applyLoginAxiosError(e) {
    const status = e.response?.status;
    const data = e.response?.data;
    if (status === 429) {
        emailError.value = t('login.rateLimitHint');
        return;
    }
    if (status === 422 && data?.errors && typeof data.errors === 'object') {
        const er = data.errors;
        if (Array.isArray(er.email) && er.email[0]) {
            emailError.value = er.email[0];
        }
        if (Array.isArray(er.password) && er.password[0]) {
            passwordError.value = er.password[0];
        }
        if (!emailError.value && !passwordError.value && typeof data.message === 'string' && data.message.length) {
            bannerError.value = data.message;
        }
        return;
    }
    bannerError.value = formatApiUserMessage(e, t('login.loginFailed'));
}

async function processGoogleExchange() {
    const ex = route.query.google_exchange;
    if (typeof ex !== 'string' || ex.length !== 48) {
        return;
    }
    const redirectTarget =
        typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
            ? route.query.redirect
            : '/';
    bannerError.value = '';
    loading.value = true;
    try {
        const { data } = await axios.post('/api/auth/google/exchange', { exchange: ex });
        setAuthToken(data.token, { remember: true });
        await router.replace({ path: '/login' });
        await router.push(redirectTarget);
    } catch (e) {
        bannerError.value = formatApiUserMessage(e, t('login.googleExchangeErr'));
        loginMode.value = 'google';
        const q = { ...route.query };
        delete q.google_exchange;
        await router.replace({ path: '/login', query: q });
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/auth/google/config');
        googleConfigured.value = !!data.enabled;
    } catch {
        googleConfigured.value = false;
    } finally {
        googleConfigLoaded.value = true;
    }
    if (googleConfigured.value) {
        loginMode.value = 'google';
    }
    applyGoogleQueryError();
    await processGoogleExchange();
});

async function startGoogleLogin() {
    bannerError.value = '';
    if (!securityAcknowledged.value) {
        ackError.value = t('login.securityRequired');
        return;
    }
    googleLoading.value = true;
    try {
        const { data } = await axios.get('/api/auth/google/redirect');
        if (data?.url) {
            window.location.assign(data.url);
        }
    } catch (e) {
        bannerError.value = formatApiUserMessage(e, t('login.googleStartErr'));
    } finally {
        googleLoading.value = false;
    }
}

async function submitMfa() {
    mfaCodeError.value = '';
    bannerError.value = '';
    loading.value = true;
    try {
        const { data } = await axios.post('/api/login/mfa', {
            challenge: mfaChallenge.value,
            code: mfaCode.value.trim(),
            remember: rememberMe.value,
        });
        setAuthToken(data.token, { remember: rememberMe.value });
        const redirect = route.query.redirect || '/';
        router.push(typeof redirect === 'string' ? redirect : '/');
    } catch (e) {
        const status = e.response?.status;
        const er = e.response?.data?.errors;
        if (status === 422 && er?.code?.[0]) {
            mfaCodeError.value = er.code[0];
        } else if (status === 429) {
            mfaCodeError.value = t('login.rateLimitHint');
        } else {
            bannerError.value = formatApiUserMessage(e, t('login.loginFailed'));
        }
    } finally {
        loading.value = false;
    }
}

async function submit() {
    clearLoginFormErrors();
    if (!securityAcknowledged.value) {
        ackError.value = t('login.securityRequired');
        return;
    }
    loading.value = true;
    try {
        const { data } = await axios.post('/api/login', {
            email: email.value,
            password: password.value,
            remember: rememberMe.value,
        });
        if (data.mfa_required && data.challenge) {
            mfaChallenge.value = data.challenge;
            mfaStep.value = true;
            mfaCode.value = '';
            return;
        }
        if (data.token) {
            setAuthToken(data.token, { remember: rememberMe.value });
            const redirect = route.query.redirect || '/';
            router.push(typeof redirect === 'string' ? redirect : '/');
        }
    } catch (e) {
        applyLoginAxiosError(e);
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.login-shell {
    font-family: var(--ppms-font, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif);
}

.login-shell--va .login-content {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 0;
    width: 100%;
    padding: 0 max(1rem, env(safe-area-inset-right)) 0 max(1rem, env(safe-area-inset-left));
}

.login-hero {
    text-align: center;
    padding: clamp(1.25rem, 4vh, 2.25rem) 1rem 1rem;
    max-width: 28rem;
}

.login-hero-dragon {
    display: block;
    margin: 0 auto 1rem;
    width: clamp(4.5rem, 14vw, 5.75rem);
    height: auto;
    filter: brightness(0) invert(1);
}

.login-hero-title {
    margin: 0 0 0.4rem;
    font-size: clamp(0.7rem, 2.1vw, 0.82rem);
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #fff;
    line-height: 1.3;
}

.login-hero-slogan {
    margin: 0;
    font-size: clamp(0.65rem, 1.85vw, 0.76rem);
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.45;
}

.login-stage {
    flex: 1 1 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 0;
    padding: 0.5rem 1rem 1.5rem;
}

.login-card-head--center {
    text-align: center;
}

.login-card-head--with-tabs {
    margin-bottom: 0.65rem;
}

.login-method-tabs {
    display: flex;
    gap: 0.2rem;
    width: 100%;
    max-width: 100%;
    margin: 0 auto 1.05rem;
    padding: 0.22rem;
    border-radius: 12px;
    background: var(--ppms-bg, #f8f9fb);
    border: 1px solid var(--ppms-border, #e8eaef);
    box-sizing: border-box;
}

.login-method-tab {
    flex: 1;
    min-width: 0;
    min-height: 2.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.4rem 0.5rem;
    border: none;
    border-radius: 10px;
    font-size: var(--login-fs-14, 0.875rem);
    font-weight: 700;
    font-family: inherit;
    line-height: 1.25;
    color: var(--ppms-muted, #5c6370);
    background: transparent;
    cursor: pointer;
    transition:
        background 0.18s ease,
        color 0.18s ease,
        box-shadow 0.18s ease;
}

.login-method-tab-g {
    width: 1.1rem;
    height: 1.1rem;
    flex-shrink: 0;
}

.login-method-tab.is-active {
    color: var(--ppms-text, #1a1d26);
    background: var(--ppms-surface, #fff);
    box-shadow: 0 1px 4px rgba(26, 29, 38, 0.08);
}

.login-method-tab:hover:not(.is-active) {
    color: var(--ppms-text, #1a1d26);
    background: rgba(255, 255, 255, 0.72);
}

.login-method-tab:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px var(--ppms-surface, #fff), 0 0 0 4px rgba(154, 0, 54, 0.32);
}

@media (max-width: 399.98px) {
    .login-method-tab {
        font-size: 0.78rem;
        padding-left: 0.35rem;
        padding-right: 0.35rem;
    }
}

.login-sub--lead {
    text-align: center;
    line-height: 1.5;
    font-size: var(--login-fs-14, 0.875rem);
    max-width: min(100%, 40rem);
    margin-left: auto;
    margin-right: auto;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.login-form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem 1.35rem;
    margin-bottom: 0.25rem;
    align-items: start;
}

@media (min-width: 640px) {
    .login-form-grid {
        grid-template-columns: 1fr 1fr;
        gap: 1.05rem 1.5rem;
    }
}

.login-form-grid .login-field {
    margin-bottom: 0;
}

.login-form-compliance {
    margin-top: 0.25rem;
    padding-top: 1.1rem;
    border-top: 1px solid rgba(232, 234, 239, 0.9);
}

.login-form-compliance .login-ack-compact {
    margin-bottom: 0;
}

.login-form-footer {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    margin-top: 0.15rem;
    padding-top: 1.15rem;
    border-top: 1px solid rgba(232, 234, 239, 0.9);
}

.login-form-footer .login-remember {
    margin: 0;
}

.login-form-footer .login-remember-hint--form {
    margin-bottom: 0.35rem;
}

.login-form-footer .login-submit {
    margin-top: 0.4rem;
}

.login-google-first {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 0.35rem 0 0.15rem;
}

.login-google-glyph {
    width: 4.35rem;
    height: 4.35rem;
    border-radius: 50%;
    border: none;
    background: #fff;
    box-shadow:
        0 4px 20px rgba(0, 0, 0, 0.1),
        0 1px 0 rgba(255, 255, 255, 0.9) inset;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition:
        transform 0.15s ease,
        box-shadow 0.2s ease;
}

.login-google-glyph:hover:not(:disabled) {
    transform: scale(1.04);
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.14);
}

.login-google-glyph:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.login-google-glyph-svg {
    width: 1.9rem;
    height: 1.9rem;
}

.login-ack-inline {
    display: flex;
    align-items: flex-start;
    gap: 0.45rem;
    font-size: var(--login-fs-12, 0.75rem);
    color: var(--ppms-muted, #5c6370);
    text-align: left;
    max-width: 19rem;
    line-height: 1.4;
}

.login-field-msg--ack {
    text-align: center;
    max-width: 19rem;
}

.login-shell--va :deep(.login-shell-footer.va-site-footer) {
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.22) 0%, rgba(0, 0, 0, 0.38) 100%);
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: none;
}

.login-shell--va :deep(.va-site-footer-school) {
    color: rgba(255, 255, 255, 0.55);
}

.login-shell--va :deep(.va-site-footer-product),
.login-shell--va :deep(.va-site-footer-tagline) {
    color: rgba(255, 255, 255, 0.92);
}

.login-shell--va :deep(.va-site-footer-nav a) {
    color: rgba(255, 255, 255, 0.88);
}

.login-shell--va :deep(.va-site-footer-rule) {
    background: rgba(255, 255, 255, 0.1);
}

.login-shell--va :deep(.va-site-footer-bottom) {
    color: rgba(255, 255, 255, 0.5);
}

@media (max-width: 639.98px) {
    .login-stage {
        align-items: flex-start;
        padding-top: 0.25rem;
    }
}

.login-stage .login-card {
    width: 100%;
    max-width: min(680px, 100%);
    margin-left: auto;
    margin-right: auto;
}

@media (min-width: 640px) {
    .login-stage .login-card {
        max-width: min(720px, 100%);
    }
}

.login-card {
    --login-fs-12: 0.75rem;
    --login-fs-14: 0.875rem;
    --login-fs-16: 1rem;
    --login-fs-20: 1.25rem;
    --login-fs-24: 1.5rem;
    position: relative;
    z-index: 1;
    width: 100%;
    overflow: visible;
    border-radius: 16px;
    padding: 2rem 1.9rem 2.15rem;
    border: 1px solid rgba(232, 234, 239, 0.95);
    background: linear-gradient(165deg, #ffffff 0%, #fafbfc 42%, #f4f5f8 100%);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.95) inset,
        0 24px 56px rgba(26, 29, 38, 0.1),
        0 6px 20px rgba(154, 0, 54, 0.07);
}

@media (max-width: 639.98px) {
    .login-card {
        padding: 1.35rem 1.15rem 1.5rem;
        border-radius: 14px;
    }
}

@media (min-width: 640px) {
    .login-stage .login-card {
        padding-left: 2.25rem;
        padding-right: 2.25rem;
    }
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

.login-tabs {
    display: flex;
    gap: 0.35rem;
    margin: 0 0 1rem;
    padding: 0.2rem;
    border-radius: 12px;
    background: var(--ppms-bg, #f8f9fb);
    border: 1px solid var(--ppms-border, #e8eaef);
}

.login-tab {
    flex: 1;
    min-height: 2.5rem;
    padding: 0.35rem 0.75rem;
    border: none;
    border-radius: 10px;
    font-size: var(--login-fs-14, 0.875rem);
    font-weight: 700;
    color: var(--ppms-muted, #5c6370);
    background: transparent;
    cursor: pointer;
    font-family: inherit;
    transition:
        background 0.2s ease,
        color 0.2s ease,
        box-shadow 0.2s ease,
        transform 0.12s ease;
}

.login-tab:hover:not(:disabled) {
    color: var(--ppms-text, #1a1d26);
}

.login-tab:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px var(--ppms-surface, #fff), 0 0 0 4px rgba(154, 0, 54, 0.35);
}

.login-tab:active:not(:disabled) {
    transform: scale(0.98);
}

.login-tab:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.login-tab.is-active {
    color: var(--ppms-primary, #9a0036);
    background: var(--ppms-surface, #fff);
    box-shadow: 0 1px 4px rgba(26, 29, 38, 0.08);
}

.login-ack-compact {
    margin: 0 0 1rem;
}

.login-ack-label {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    margin: 0;
    cursor: pointer;
    font-size: var(--login-fs-14, 0.875rem);
    line-height: 1.45;
    color: var(--ppms-text, #1a1d26);
}

.login-ack-input {
    flex-shrink: 0;
    width: 1.1rem;
    height: 1.1rem;
    margin-top: 0.12rem;
    accent-color: var(--ppms-primary, #9a0036);
    cursor: pointer;
}

.login-ack-text {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    min-width: 0;
}

.login-ack-line {
    font-weight: 600;
    line-height: 1.55;
}

.login-ack-learn-link {
    margin-left: 0.35rem;
    font-weight: 700;
    font-size: var(--login-fs-14, 0.875rem);
    color: var(--ppms-primary, #9a0036);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.login-ack-learn-link:hover {
    color: #7a0028;
}

.login-ack-learn-link:focus-visible {
    outline: none;
    border-radius: 4px;
    box-shadow: 0 0 0 2px var(--ppms-surface, #fff), 0 0 0 4px rgba(154, 0, 54, 0.35);
}

.login-ack-hint {
    font-size: var(--login-fs-12, 0.75rem);
    font-weight: 600;
    color: var(--ppms-muted, #5c6370);
}

.login-field-msg {
    margin: 0.35rem 0 0;
    font-size: var(--login-fs-12, 0.75rem);
    line-height: 1.4;
    font-weight: 600;
}

.login-field-msg--err {
    color: #991b1b;
}

.login-mfa-block {
    margin-bottom: 0.5rem;
}

.login-mfa-title {
    margin: 0 0 0.5rem;
    font-size: var(--login-fs-20, 1.25rem);
    font-weight: 800;
    color: var(--ppms-text, #1a1d26);
}

.login-mfa-desc {
    margin: 0 0 1rem;
    font-size: var(--login-fs-14, 0.875rem);
    line-height: 1.5;
    color: var(--ppms-muted, #5c6370);
}

.login-mfa-input {
    letter-spacing: 0.35em;
    font-weight: 800;
    font-size: var(--login-fs-20, 1.25rem) !important;
    text-align: center;
}

.login-mfa-back {
    display: block;
    width: 100%;
    margin-top: 0.75rem;
    padding: 0.5rem;
    border: none;
    background: transparent;
    font-size: var(--login-fs-14, 0.875rem);
    font-weight: 700;
    color: var(--ppms-primary, #9a0036);
    cursor: pointer;
    font-family: inherit;
    border-radius: 8px;
    transition: background 0.18s ease;
}

.login-mfa-back:hover {
    background: var(--ppms-primary-soft, rgba(154, 0, 54, 0.08));
}

.login-mfa-back:focus-visible {
    outline: none;
    box-shadow: var(--ppms-focus, 0 0 0 2px rgba(154, 0, 54, 0.35));
}

.login-remember {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin: 0.35rem 0 0.15rem;
    cursor: pointer;
    font-size: var(--login-fs-14, 0.875rem);
    font-weight: 600;
    color: var(--ppms-text, #1a1d26);
}

.login-remember-input {
    flex-shrink: 0;
    width: 1.05rem;
    height: 1.05rem;
    margin-top: 0.1rem;
    accent-color: var(--ppms-primary, #9a0036);
    cursor: pointer;
}

.login-remember-hint {
    margin: 0 0 0.85rem;
    font-size: var(--login-fs-12, 0.75rem);
    color: var(--ppms-muted, #5c6370);
    line-height: 1.4;
}

.login-remember-hint--form {
    margin-top: 0;
    margin-bottom: 1rem;
}

.login-google-panel {
    margin-bottom: 0.25rem;
}

.login-google-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.65rem;
    width: 100%;
    min-height: 3rem;
    padding: 0 1rem;
    border-radius: 10px;
    border: 1px solid var(--ppms-border, #e8eaef);
    background: var(--ppms-surface, #fff);
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--ppms-text, #1a1d26);
    cursor: pointer;
    font-family: inherit;
    box-shadow: 0 2px 8px rgba(26, 29, 38, 0.06);
    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        transform 0.15s ease;
}

.login-google-btn:hover:not(:disabled) {
    border-color: rgba(66, 133, 244, 0.45);
    box-shadow: 0 4px 14px rgba(66, 133, 244, 0.15);
}

.login-google-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px var(--ppms-surface, #fff), 0 0 0 4px rgba(66, 133, 244, 0.45);
}

.login-google-btn:active:not(:disabled) {
    transform: scale(0.99);
}

.login-google-btn:disabled {
    opacity: 0.85;
    cursor: wait;
}

.login-google-icon {
    width: 1.35rem;
    height: 1.35rem;
    flex-shrink: 0;
}

.login-spinner--dark {
    border-color: rgba(26, 29, 38, 0.2);
    border-top-color: var(--ppms-primary, #9a0036);
}

.login-card-head {
    text-align: center;
    margin-bottom: 1.5rem;
}

.login-title {
    margin: 0 0 0.4rem;
    font-size: var(--login-fs-24, 1.5rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--ppms-text, #1a1d26);
}

.login-sub {
    margin: 0;
    font-size: var(--login-fs-14, 0.875rem);
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
    font-size: var(--login-fs-14, 0.875rem);
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

.login-input--invalid {
    border-color: rgba(220, 38, 38, 0.55) !important;
    background: rgba(254, 242, 242, 0.65) !important;
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
