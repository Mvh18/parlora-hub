import { renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { useTrans } from '@/hooks/use-trans';

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: {
            locale: 'en',
            translations: {
                home: { greeting: { afternoon: 'Good afternoon, :name' } },
            },
        },
    }),
}));

describe('useTrans()', () => {
    it('translates with the shared locale', () => {
        const { result } = renderHook(() => useTrans());

        expect(result.current.locale).toBe('en');
        expect(
            result.current.t('home.greeting.afternoon', { name: 'Alex' }),
        ).toBe('Good afternoon, Alex');
    });

    it('throws on a missing key while running tests', () => {
        const { result } = renderHook(() => useTrans());

        expect(() => result.current.t('home.greeting.evening')).toThrow(
            'home.greeting.evening',
        );
    });
});
