/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.timeout = 60000;

const TOKEN_KEY = 'ppms_token';

export function getStoredToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function setAuthToken(token) {
    if (token) {
        localStorage.setItem(TOKEN_KEY, token);
        window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
        localStorage.removeItem(TOKEN_KEY);
        delete window.axios.defaults.headers.common.Authorization;
    }
}

const existing = getStoredToken();
if (existing) {
    window.axios.defaults.headers.common.Authorization = `Bearer ${existing}`;
}

/** Matches server-leaked SQL / PHP traces we never show to end users. */
const TECHNICAL_ERR_RE = /SQLSTATE|\(Connection:|SQL:|PDOException|Illuminate\\|vendor\\/i;

/**
 * User-safe API error text (Vietnamese). Prefer field validation messages; strip technical payloads.
 */
export function getApiErrorMessage(error, fallback = 'Đã xảy ra lỗi.') {
    if (!error?.response) {
        if (error?.code === 'ECONNABORTED' || error?.message?.includes?.('timeout')) {
            return 'Yêu cầu hết thời gian chờ. Kiểm tra mạng hoặc thử lại.';
        }
        if (error?.code === 'ERR_NETWORK' || error?.message === 'Network Error') {
            return 'Không kết nối được máy chủ. Kiểm tra mạng hoặc thử lại sau.';
        }
        return fallback;
    }

    const { data, status } = error.response;

    if (typeof data === 'string') {
        if (/<!DOCTYPE|<html/i.test(data)) {
            return 'Máy chủ trả về lỗi. Vui lòng thử lại sau.';
        }
        if (TECHNICAL_ERR_RE.test(data)) {
            return fallback;
        }
        const text = data.replace(/<[^>]*>/g, '').slice(0, 280).trim();
        return text || fallback;
    }

    if (data && typeof data === 'object') {
        if (data.errors && typeof data.errors === 'object') {
            const first = Object.values(data.errors)[0];
            if (Array.isArray(first) && typeof first[0] === 'string' && first[0].length) {
                const fieldMsg = first[0];
                if (!TECHNICAL_ERR_RE.test(fieldMsg)) {
                    return fieldMsg;
                }
            }
        }
        let msg = data.message;
        if (typeof msg === 'string' && TECHNICAL_ERR_RE.test(msg)) {
            msg = null;
        }
        if (typeof msg === 'string' && msg.length) {
            return msg;
        }
    }

    if (status === 401) {
        return 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn.';
    }
    if (status === 403) {
        return 'Bạn không có quyền thực hiện thao tác này.';
    }
    if (status === 404) {
        return 'Không tìm thấy dữ liệu hoặc đường dẫn.';
    }
    if (status === 422) {
        return 'Dữ liệu gửi lên chưa hợp lệ.';
    }
    if (status >= 500) {
        return 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau.';
    }
    return fallback;
}

export function getApiErrorHint(error) {
    const data = error?.response?.data;
    if (data && typeof data === 'object' && typeof data.hint === 'string' && data.hint.length) {
        if (!TECHNICAL_ERR_RE.test(data.hint)) {
            return data.hint;
        }
    }
    return '';
}

/** Message plus optional hint for inline UI. */
export function formatApiUserMessage(error, fallback) {
    const m = getApiErrorMessage(error, fallback);
    const h = getApiErrorHint(error);
    return h ? `${m} — ${h}` : m;
}

axios.interceptors.response.use(
    (r) => r,
    (error) => {
        const data = error.response?.data;
        if (data && typeof data === 'object' && typeof data.message === 'string') {
            if (TECHNICAL_ERR_RE.test(data.message)) {
                data.message = 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại hoặc liên hệ quản trị.';
                if (!data.hint) {
                    data.hint = 'Chi tiết kỹ thuật được ghi trong nhật ký máy chủ.';
                }
            }
        }
        return Promise.reject(error);
    },
);
