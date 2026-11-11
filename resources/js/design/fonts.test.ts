/// <reference types="node" />
import { readdirSync, readFileSync } from 'node:fs';
import { join, relative } from 'node:path';
import { describe, expect, it } from 'vite-plus/test';
import { buildAppCss, declarations, root } from '@/test/stylesheets';

const thirdPartyFontHosts =
    /fonts\.googleapis\.com|fonts\.gstatic\.com|fonts\.bunny\.net|use\.typekit\.net/;

function files(directory: string): string[] {
    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const path = join(directory, entry.name);

        return entry.isDirectory() ? files(path) : [path];
    });
}

describe('brand fonts', () => {
    it('never references a third-party font host in the sources', () => {
        const sources = [
            ...files(join(root, 'resources')),
            join(root, 'vite.config.ts'),
        ];
        const offenders = sources
            .filter((file) =>
                thirdPartyFontHosts.test(readFileSync(file, 'utf8')),
            )
            .map((file) => relative(root, file));

        expect(offenders).toEqual([]);
    });

    it('bundles both families as local woff2 files', async () => {
        const css = await buildAppCss([]);
        const faces = [...css.matchAll(/@font-face\s*\{[^}]*\}/g)].map(
            ([face]) => face,
        );

        for (const family of [
            'Bricolage Grotesque Variable',
            'Figtree Variable',
        ]) {
            const familyFaces = faces.filter((face) => face.includes(family));

            expect(familyFaces.length, family).toBeGreaterThan(0);

            for (const face of familyFaces) {
                expect(face).toMatch(/url\(\.\/files\/[\w-]+\.woff2\)/);
            }
        }
    });

    it('uses Figtree for text and Bricolage Grotesque for display', async () => {
        const css = await buildAppCss(['font-sans', 'font-display']);

        expect(declarations(css, '.font-sans').get('font-family')).toBe(
            'var(--font-sans)',
        );
        expect(css).toMatch(/--font-sans:\s*'Figtree Variable'/);
        expect(css).toMatch(/--font-display:\s*'Bricolage Grotesque Variable'/);
    });
});
