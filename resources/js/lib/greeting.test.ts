import { describe, expect, it } from 'vite-plus/test';
import { firstName, greetingKey } from '@/lib/greeting';

const at = (hour: number, minute = 0) => new Date(2026, 0, 15, hour, minute);

describe('greetingKey()', () => {
    it.each([
        [5, 0, 'home.greeting.morning'],
        [11, 59, 'home.greeting.morning'],
        [12, 0, 'home.greeting.afternoon'],
        [15, 0, 'home.greeting.afternoon'],
        [17, 59, 'home.greeting.afternoon'],
        [18, 0, 'home.greeting.evening'],
        [23, 30, 'home.greeting.evening'],
        [4, 59, 'home.greeting.evening'],
    ])('at %i:%i greets with %s', (hour, minute, key) => {
        expect(greetingKey(at(hour, minute))).toBe(key);
    });
});

describe('firstName()', () => {
    it.each([
        ['Alex Doe', 'Alex'],
        ['  Alex   Doe ', 'Alex'],
        ['Alex', 'Alex'],
    ])('reads the first name of "%s"', (name, first) => {
        expect(firstName(name)).toBe(first);
    });
});
