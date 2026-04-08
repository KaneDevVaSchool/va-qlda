/**
 * Cùng logic với cột "Người tham gia" trên ProjectList (owner → executor → follower → stakeholder email).
 */
import { initials as nameInitials } from './projectDetailFormatters';

export function participantStakeholderEmails(p) {
    const emails = p.stakeholder_emails;

    return Array.isArray(emails) ? emails.filter(Boolean) : [];
}

export function initialsFromEmail(email) {
    const local = String(email).split('@')[0].trim();
    if (!local) {
        return '?';
    }
    const parts = local.split(/[._+-]+/).filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }

    return local.length >= 2 ? local.slice(0, 2).toUpperCase() : local.charAt(0).toUpperCase();
}

export function displayNameFromEmail(email) {
    const local = String(email).split('@')[0].trim();

    return local.replace(/[._+-]+/g, ' ').trim() || String(email);
}

/**
 * @returns {Array<{ kind: 'owner'|'executor'|'follower'|'stakeholder', user?: object, email?: string, colorSeed: string, initials: string }>}
 */
export function buildParticipantSlots(p) {
    const slots = [];
    const seenUserIds = new Set();
    const knownEmails = new Set();

    if (p.owner) {
        const seed = p.owner.name || p.owner.email || '?';
        seenUserIds.add(p.owner.id);
        if (p.owner.email) {
            knownEmails.add(String(p.owner.email).toLowerCase());
        }
        slots.push({
            kind: 'owner',
            user: p.owner,
            colorSeed: seed,
            initials: nameInitials(p.owner.name),
        });
    }

    const pushUser = (u, kind) => {
        if (!u || seenUserIds.has(u.id)) {
            return;
        }
        seenUserIds.add(u.id);
        if (u.email) {
            knownEmails.add(String(u.email).toLowerCase());
        }
        const seed = u.name || u.email || '?';
        slots.push({
            kind,
            user: u,
            colorSeed: seed,
            initials: nameInitials(u.name),
        });
    };

    for (const u of p.executor_users || []) {
        pushUser(u, 'executor');
    }
    for (const u of p.follower_users || []) {
        pushUser(u, 'follower');
    }

    for (const em of participantStakeholderEmails(p)) {
        const low = String(em).toLowerCase();
        if (knownEmails.has(low)) {
            continue;
        }
        knownEmails.add(low);
        slots.push({
            kind: 'stakeholder',
            email: em,
            colorSeed: em,
            initials: initialsFromEmail(em),
        });
    }

    return slots;
}
