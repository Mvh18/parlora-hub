import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import Heading from '@/components/heading';

describe('Heading', () => {
    it('renders the title as a level-two heading', () => {
        render(<Heading title="Profile" />);

        expect(
            screen.getByRole('heading', { level: 2, name: 'Profile' }),
        ).toBeInTheDocument();
    });

    it('renders the description only when given', () => {
        const { rerender } = render(<Heading title="Profile" />);

        expect(screen.queryByText('Update your details')).toBeNull();

        rerender(<Heading title="Profile" description="Update your details" />);

        expect(screen.getByText('Update your details')).toBeInTheDocument();
    });
});
