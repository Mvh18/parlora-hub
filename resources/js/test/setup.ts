import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach } from 'vite-plus/test';
import { mediaQuery } from '@/test/media';

// useIsMobile() reads matchMedia when its module loads.
if (typeof window !== 'undefined' && !window.matchMedia) {
    window.matchMedia = (query: string) =>
        ({
            get matches() {
                return mediaQuery.matches;
            },
            media: query,
            onchange: null,
            addEventListener: () => {},
            removeEventListener: () => {},
            addListener: () => {},
            removeListener: () => {},
            dispatchEvent: () => false,
        }) as MediaQueryList;
}

afterEach(() => {
    cleanup();
    mediaQuery.matches = false;
});
