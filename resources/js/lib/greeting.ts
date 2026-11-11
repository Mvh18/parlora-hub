export type GreetingKey =
    | 'home.greeting.morning'
    | 'home.greeting.afternoon'
    | 'home.greeting.evening';

/**
 * The greeting for the given moment in the browser's own time zone:
 * morning 05:00–11:59, afternoon 12:00–17:59, evening otherwise.
 */
export function greetingKey(now: Date): GreetingKey {
    const hour = now.getHours();

    if (hour >= 5 && hour < 12) {
        return 'home.greeting.morning';
    }

    if (hour >= 12 && hour < 18) {
        return 'home.greeting.afternoon';
    }

    return 'home.greeting.evening';
}

/** The first word of a full name: "Alex Doe" → "Alex". */
export function firstName(fullName: string): string {
    return fullName.trim().split(/\s+/)[0] ?? '';
}
