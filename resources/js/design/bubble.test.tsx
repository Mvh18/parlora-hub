import { render } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import BrandMark from '@/components/brand-mark';
import { buildAppCss, declarations, resolveVars } from '@/test/stylesheets';

async function radiusOf(className: string): Promise<string> {
    const css = await buildAppCss([className]);
    const radius = declarations(css, `.${className}`).get('border-radius');

    expect(radius, `.${className} border-radius`).toBeDefined();

    return resolveVars(radius!, css);
}

describe('shape tokens', () => {
    it.each([
        ['bubble-bl', '24px 24px 24px 6px'],
        ['bubble-bl-sm', '16px 16px 16px 4px'],
        ['bubble-bl-xs', '12px 12px 12px 3px'],
        ['bubble-br', '18px 18px 4px 18px'],
    ])('maps %s to the speech-bubble radii', async (className, radii) => {
        expect(await radiusOf(className)).toBe(radii);
    });

    it.each([
        ['rounded-control', '10px'],
        ['rounded-button', '12px'],
        ['rounded-card', '20px'],
        ['rounded-pill', '999px'],
    ])('maps %s to its radius token', async (className, radius) => {
        expect(await radiusOf(className)).toBe(radius);
    });

    it('draws the brand mark as a speech bubble', async () => {
        const { container } = render(<BrandMark />);
        const mark = container.firstElementChild!;

        expect(mark).toHaveClass('bubble-bl-xs');
        expect(await radiusOf('bubble-bl-xs')).toBe('12px 12px 12px 3px');
    });
});
