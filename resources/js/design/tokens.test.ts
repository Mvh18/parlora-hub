import { describe, expect, it } from 'vite-plus/test';
import { customProperties, declarations, read } from '@/test/stylesheets';

// Hex case is irrelevant (the formatter lowercases it): compare in uppercase.
function upper(theme: Map<string, string>): Map<string, string> {
    return new Map(
        [...theme].map(([name, value]) => [name, value.toUpperCase()]),
    );
}

const tokens = read('resources/css/tokens.css');
const light = upper(customProperties(tokens, ':root'));
const dark = upper(customProperties(tokens, '.dark'));

const hexColour = /^#[0-9A-F]{6}([0-9A-F]{2})?$/;

function colours(theme: Map<string, string>): string[] {
    return [...theme]
        .filter(([, value]) => value.startsWith('#'))
        .map(([name]) => name);
}

function luminance(hex: string): number {
    const [r, g, b] = [1, 3, 5].map((offset) => {
        const channel = parseInt(hex.slice(offset, offset + 2), 16) / 255;

        return channel <= 0.04045
            ? channel / 12.92
            : ((channel + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function contrast(foreground: string, background: string): number {
    const [lighter, darker] = [
        luminance(foreground),
        luminance(background),
    ].sort((a, b) => b - a);

    return (lighter + 0.05) / (darker + 0.05);
}

// WCAG 2.2 AA: 4.5:1 for body text, 3:1 for large text and UI parts
// (focus rings, field borders, icons, bars on inverse surfaces).
const TEXT = 4.5;
const UI = 3;

/** Every foreground/background pair the design system declares. */
const pairs: [foreground: string, background: string, minimum: number][] = [
    ['--ink', '--canvas', TEXT],
    ['--ink', '--surface', TEXT],
    ['--ink', '--accent-soft', TEXT],
    ['--ink', '--accent-tint', TEXT],
    ['--ink-secondary', '--canvas', TEXT],
    ['--ink-secondary', '--surface', TEXT],
    ['--ink-muted', '--canvas', TEXT],
    ['--ink-muted', '--surface', TEXT],
    ['--ink-muted', '--accent-soft', TEXT],
    ['--ink-subtle', '--canvas', TEXT],
    ['--ink-subtle', '--surface', TEXT],
    ['--accent', '--canvas', TEXT],
    ['--accent', '--surface', TEXT],
    ['--accent', '--accent-soft', TEXT],
    ['--accent-strong', '--surface', TEXT],
    ['--accent-strong', '--accent-soft', TEXT],
    ['--on-accent', '--accent', TEXT],
    ['--on-accent', '--accent-strong', TEXT],
    ['--accent-strong', '--accent-tint', TEXT],
    ['--on-inverse', '--inverse', TEXT],
    ['--on-inverse-muted', '--inverse', TEXT],
    ['--accent-on-inverse', '--inverse', UI],
    ['--success', '--surface', TEXT],
    ['--success', '--canvas', TEXT],
    ['--success-text', '--success-soft', TEXT],
    ['--warning', '--surface', UI],
    ['--warning-text', '--warning-soft', TEXT],
    ['--danger', '--surface', TEXT],
    ['--danger', '--canvas', TEXT],
    ['--on-danger', '--danger', TEXT],
    ['--field-border', '--surface', UI],
    ['--field-border', '--canvas', UI],
    ['--ring', '--surface', UI],
    ['--ring', '--canvas', UI],
];

describe('design tokens', () => {
    it('defines the light theme with the values of the mockups', () => {
        expect(Object.fromEntries(light)).toMatchObject({
            '--canvas': '#F3F4F9',
            '--surface': '#FFFFFF',
            '--border': '#E4E5EE',
            '--divider': '#EDEEF4',
            '--control-border': '#D5D7E3',
            '--ink': '#231942',
            '--ink-secondary': '#3E3A5C',
            '--ink-muted': '#5B5874',
            '--ink-subtle': '#6E6A86',
            '--accent': '#3550E6',
            '--accent-strong': '#2238B8',
            '--accent-soft': '#EEF0FD',
            '--accent-tint': '#E3E7FD',
            '--accent-muted': '#B9C3FA',
            '--accent-on-inverse': '#8B9BFF',
            '--on-inverse-muted': '#CFCBE8',
            '--success': '#0F766E',
            '--success-soft': '#E6F4F2',
            '--success-text': '#0B5E57',
            '--warning': '#B45309',
            '--warning-soft': '#FDF0E1',
            '--warning-text': '#92400E',
            '--danger': '#C2410C',
        });
    });

    it('writes every colour as a six- or eight-digit hex value', () => {
        for (const theme of [light, dark]) {
            for (const name of colours(theme)) {
                expect(theme.get(name), name).toMatch(hexColour);
            }
        }
    });

    it('gives the dark theme exactly the colours of the light theme', () => {
        expect(colours(dark).sort()).toEqual(colours(light).sort());
    });

    it('declares every token used by a contrast pair', () => {
        for (const [foreground, background] of pairs) {
            for (const name of [foreground, background]) {
                expect(light.has(name), `${name} (light)`).toBe(true);
                expect(dark.has(name), `${name} (dark)`).toBe(true);
            }
        }
    });

    it.each([
        ['light', light],
        ['dark', dark],
    ])('meets WCAG 2.2 AA for every declared pair in %s', (_name, theme) => {
        const failures = pairs
            .map(([foreground, background, minimum]) => ({
                pair: `${foreground} on ${background}`,
                ratio: contrast(theme.get(foreground)!, theme.get(background)!),
                minimum,
            }))
            .filter(({ ratio, minimum }) => ratio < minimum)
            .map(
                ({ pair, ratio, minimum }) =>
                    `${pair}: ${ratio.toFixed(2)} < ${minimum}`,
            );

        expect(failures).toEqual([]);
    });

    it('paints the page background before CSS loads with the canvas tokens', () => {
        const view = read('resources/views/app.blade.php');

        expect(
            declarations(view, 'html').get('background-color')?.toUpperCase(),
        ).toBe(light.get('--canvas'));
        expect(
            declarations(view, 'html.dark')
                .get('background-color')
                ?.toUpperCase(),
        ).toBe(dark.get('--canvas'));
    });
});
