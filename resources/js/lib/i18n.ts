/*
 * Translations shared by HandleInertiaRequests from lang/<locale>/*.php.
 *
 * Keys are dotted paths, the first segment being the file (group):
 * "auth.login.title" reads lang/en/auth.php → ['login' => ['title' => …]].
 * Lines use Laravel's syntax: ":name" placeholders (":Name" and ":NAME"
 * change the case of the value) and "|"-separated plural forms with optional
 * "{n}" or "[from,to]" conditions, as in trans_choice().
 *
 * A missing key throws in development and tests, so a typo fails loudly; a
 * production build shows the key instead of breaking the page.
 */

export type Translations = { [key: string]: string | Translations };
export type Replacements = Record<string, string | number>;

export type Translator = {
    t: (key: string, replacements?: Replacements) => string;
    choice: (key: string, count: number, replacements?: Replacements) => string;
};

export class MissingTranslationError extends Error {
    constructor(key: string) {
        super(`Missing translation key "${key}".`);
        this.name = 'MissingTranslationError';
    }
}

const strictByDefault = import.meta.env.DEV || import.meta.env.MODE === 'test';

export function createTranslator(
    translations: Translations,
    { strict = strictByDefault }: { strict?: boolean } = {},
): Translator {
    const line = (key: string): string | null => {
        const found = lookup(translations, key);

        if (found !== null) {
            return found;
        }

        if (strict) {
            throw new MissingTranslationError(key);
        }

        return null;
    };

    return {
        t: (key, replacements = {}) => {
            const found = line(key);

            return found === null ? key : replace(found, replacements);
        },
        choice: (key, count, replacements = {}) => {
            const found = line(key);

            return found === null
                ? key
                : replace(selectPlural(found, count), {
                      count,
                      ...replacements,
                  });
        },
    };
}

function lookup(translations: Translations, key: string): string | null {
    let node: string | Translations | undefined = translations;

    for (const segment of key.split('.')) {
        if (typeof node !== 'object') {
            return null;
        }

        node = node[segment];
    }

    return typeof node === 'string' ? node : null;
}

function capitalise(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

function replace(line: string, replacements: Replacements): string {
    // Longest first, so ":username" is not consumed by ":user".
    const keys = Object.keys(replacements).sort((a, b) => b.length - a.length);

    return keys.reduce((result, key) => {
        const value = String(replacements[key]);

        return result
            .replaceAll(`:${capitalise(key)}`, capitalise(value))
            .replaceAll(`:${key.toUpperCase()}`, value.toUpperCase())
            .replaceAll(`:${key}`, value);
    }, line);
}

const condition = /^[{[]([^{}[\]]*)[}\]]\s*([\s\S]*)$/;

function matches(rule: string, count: number): boolean {
    if (!rule.includes(',')) {
        return Number(rule.trim()) === count;
    }

    const [from, to] = rule.split(',').map((part) => part.trim());
    const above = from === '*' || count >= Number(from);
    const below = to === '*' || count <= Number(to);

    return above && below;
}

function selectPlural(line: string, count: number): string {
    const segments = line.split('|');

    for (const segment of segments) {
        const parsed = condition.exec(segment.trim());

        if (parsed && matches(parsed[1], count)) {
            return parsed[2];
        }
    }

    const forms = segments.map((segment) =>
        segment.trim().replace(condition, '$2'),
    );

    // English: the first form for exactly one, the second for anything else.
    const index = count === 1 ? 0 : 1;

    return forms[index] ?? forms[0];
}
