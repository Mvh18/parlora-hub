import { render, screen } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import Home from '@/pages/home';

vi.mock('@inertiajs/react', () => import('@/test/inertia'));

describe('Home', () => {
    beforeEach(() => {
        vi.useFakeTimers({ toFake: ['Date'] });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('greets the learner by first name for the local time of day', () => {
        vi.setSystemTime(new Date(2026, 0, 15, 15, 0));

        render(<Home />);

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Good afternoon, Alex',
            }),
        ).toBeInTheDocument();
    });

    it('says good morning before noon', () => {
        vi.setSystemTime(new Date(2026, 0, 15, 8, 0));

        render(<Home />);

        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
            'Good morning, Alex',
        );
    });

    it('shows the empty state and no progress figures', () => {
        vi.setSystemTime(new Date(2026, 0, 15, 15, 0));

        const { container } = render(<Home />);

        expect(
            screen.getByRole('region', {
                name: 'Your lessons are on their way',
            }),
        ).toBeInTheDocument();
        expect(screen.queryByRole('progressbar')).toBeNull();
        expect(screen.queryByRole('meter')).toBeNull();
        expect(container.textContent).not.toMatch(/\d/);
        expect(container.textContent).not.toMatch(/level|streak|minutes/i);
    });
});
