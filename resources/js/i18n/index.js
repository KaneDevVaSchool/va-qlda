import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import vi from './locales/vi.json';

export const PPMS_LOCALE_KEY = 'ppms-locale';

function readStoredLocale() {
    if (typeof localStorage === 'undefined') {
        return null;
    }
    const s = localStorage.getItem(PPMS_LOCALE_KEY);
    return s === 'en' || s === 'vi' ? s : null;
}

export function getInitialLocale() {
    return readStoredLocale() || 'vi';
}

export function persistLocale(locale) {
    if (typeof localStorage === 'undefined') {
        return;
    }
    localStorage.setItem(PPMS_LOCALE_KEY, locale);
}

export const i18n = createI18n({
    legacy: false,
    locale: getInitialLocale(),
    fallbackLocale: 'vi',
    messages: { en, vi },
    globalInjection: true,
});
