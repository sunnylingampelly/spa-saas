const APP_TIMEZONE = 'Asia/Kolkata';

/**
 * Single source of truth for date display formatting across the app: DD-MM-YYYY
 * in Asia/Kolkata, per the India-only requirement. Always rendered in the app's
 * fixed timezone — NOT the viewer's browser/OS timezone — so a spa owner checking
 * the schedule from a different timezone still sees the spa's actual local time.
 * Every page/component should format dates through this instead of reimplementing it.
 */
export function formatDate(value, { withTime = false } = {}) {
    if (!value) return '';

    const date = value instanceof Date ? value : new Date(value);

    if (Number.isNaN(date.getTime())) return '';

    const parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: APP_TIMEZONE,
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit', hour12: false } : {}),
    })
        .formatToParts(date)
        .reduce((acc, part) => ({ ...acc, [part.type]: part.value }), {});

    const datePart = `${parts.day}-${parts.month}-${parts.year}`;

    return withTime ? `${datePart} ${parts.hour}:${parts.minute}` : datePart;
}

export function formatTime(value) {
    if (!value) return '';

    const date = value instanceof Date ? value : new Date(value);

    if (Number.isNaN(date.getTime())) return '';

    return new Intl.DateTimeFormat('en-IN', {
        timeZone: APP_TIMEZONE,
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    }).format(date);
}

export function useDateFormat() {
    return { formatDate, formatTime };
}
