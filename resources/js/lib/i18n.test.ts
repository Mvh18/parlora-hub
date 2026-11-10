import { describe, expect, it } from 'vite-plus/test';
import { createTranslator, MissingTranslationError } from '@/lib/i18n';
import type { Translations } from '@/lib/i18n';

const translations: Translations = {
    home: {
        greeting: {
            afternoon: 'Good afternoon, :name',
        },
        streak: '{0} No streak yet|{1} :count day in a row|[2,*] :count days in a row',
    },
    auth: {
        welcome: 'Welcome back, :Name. Your role is :ROLE.',
        attempts: ':count attempt left|:count attempts left',
        prefix: ':user signed in as :username',
    },
};

const strict = createTranslator(translations, { strict: true });
const lenient = createTranslator(translations, { strict: false });

describe('t()', () => {
    it('looks up a nested key by its dotted path', () => {
        expect(strict.t('home.greeting.afternoon', { name: 'Alex' })).toBe(
            'Good afternoon, Alex',
        );
    });

    it('replaces placeholders in lower, capitalised and upper case', () => {
        expect(
            strict.t('auth.welcome', { name: 'alex', role: 'learner' }),
        ).toBe('Welcome back, Alex. Your role is LEARNER.');
    });

    it('replaces the longest placeholder first', () => {
        expect(
            strict.t('auth.prefix', { user: 'Alex', username: 'alex42' }),
        ).toBe('Alex signed in as alex42');
    });

    it('throws an error naming the missing key in strict mode', () => {
        expect(() => strict.t('home.missing')).toThrow(MissingTranslationError);
        expect(() => strict.t('home.missing')).toThrow('home.missing');
    });

    it('throws when the key points at a group instead of a string', () => {
        expect(() => strict.t('home.greeting')).toThrow('home.greeting');
    });

    it('falls back to the key outside strict mode', () => {
        expect(lenient.t('home.missing')).toBe('home.missing');
    });

    it('is strict by default while running tests', () => {
        expect(() => createTranslator(translations).t('nope')).toThrow('nope');
    });
});

describe('choice()', () => {
    it('picks explicit counts and ranges', () => {
        expect(strict.choice('home.streak', 0)).toBe('No streak yet');
        expect(strict.choice('home.streak', 1)).toBe('1 day in a row');
        expect(strict.choice('home.streak', 12)).toBe('12 days in a row');
    });

    it('uses the English singular and plural forms without conditions', () => {
        expect(strict.choice('auth.attempts', 1)).toBe('1 attempt left');
        expect(strict.choice('auth.attempts', 0)).toBe('0 attempts left');
        expect(strict.choice('auth.attempts', 3)).toBe('3 attempts left');
    });

    it('lets replacements override :count', () => {
        expect(strict.choice('auth.attempts', 3, { count: 'three' })).toBe(
            'three attempts left',
        );
    });

    it('throws for a missing key in strict mode', () => {
        expect(() => strict.choice('home.none', 2)).toThrow('home.none');
    });
});
