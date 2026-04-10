import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import { i18n } from './i18n';
import router from './router';

createApp(App).use(router).use(i18n).mount('#app');

/** Firefox: không có full-overlay ::-webkit-calendar-picker-indicator — mở picker khi bấm vào ô. */
const ua = typeof navigator !== 'undefined' ? navigator.userAgent : '';
const isFirefox = /firefox/i.test(ua) && !/seamonkey/i.test(ua);
if (isFirefox) {
    document.addEventListener(
        'pointerdown',
        (e) => {
            const t = e.target;
            if (!(t instanceof HTMLInputElement)) {
                return;
            }
            if (!['date', 'datetime-local', 'month', 'time'].includes(t.type)) {
                return;
            }
            if (t.disabled || t.readOnly) {
                return;
            }
            if (typeof t.showPicker !== 'function') {
                return;
            }
            try {
                t.showPicker();
            } catch {
                /* NotAllowedError hoặc trình duyệt chưa hỗ trợ */
            }
        },
        true,
    );
}
