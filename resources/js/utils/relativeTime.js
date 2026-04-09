/**
 * Relative time for display (e.g. "5 minutes ago" / "5 phút trước") using Intl.RelativeTimeFormat.
 * @param {string|Date} isoOrDate
 * @param {string} locale IETF language tag (e.g. 'vi', 'en')
 */
export function formatRelativeTime(isoOrDate, locale = 'vi') {
    const date = typeof isoOrDate === 'string' ? new Date(isoOrDate) : isoOrDate;
    if (Number.isNaN(date.getTime())) {
        return '';
    }
    const now = Date.now();
    const diffSec = Math.round((date.getTime() - now) / 1000);
    const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
    const abs = Math.abs(diffSec);
    const sign = diffSec < 0 ? -1 : 1;
    if (abs < 60) {
        return rtf.format(sign * diffSec, 'second');
    }
    if (abs < 3600) {
        return rtf.format(sign * Math.round(diffSec / 60), 'minute');
    }
    if (abs < 86400) {
        return rtf.format(sign * Math.round(diffSec / 3600), 'hour');
    }
    if (abs < 86400 * 7) {
        return rtf.format(sign * Math.round(diffSec / 86400), 'day');
    }
    if (abs < 86400 * 30) {
        return rtf.format(sign * Math.round(diffSec / (86400 * 7)), 'week');
    }
    if (abs < 86400 * 365) {
        return rtf.format(sign * Math.round(diffSec / (86400 * 30)), 'month');
    }
    return rtf.format(sign * Math.round(diffSec / (86400 * 365)), 'year');
}
