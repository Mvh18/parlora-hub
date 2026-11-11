/// <reference types="node" />
/**
 * Test support for the design system: reads the token stylesheet and
 * compiles the real `app.css` with Tailwind, without Vite.
 */
import { existsSync, readFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { compile } from 'tailwindcss';

export const root = process.cwd();
export const cssDir = resolve(root, 'resources/css');

export function read(path: string): string {
    return readFileSync(resolve(root, path), 'utf8');
}

function escape(text: string): string {
    return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * The declarations (property → value) of the first block whose selector
 * list is exactly `selector`, e.g. `:root`, `.dark` or `.bubble-bl`.
 */
export function declarations(
    css: string,
    selector: string,
): Map<string, string> {
    const source = css.replace(/\/\*[\s\S]*?\*\//g, '');
    const opening = new RegExp(`(^|[\\s}])${escape(selector)}\\s*\\{`);
    const match = opening.exec(source);

    if (match === null) {
        throw new Error(`No "${selector}" block found`);
    }

    let depth = 1;
    let index = match.index + match[0].length;
    const start = index;

    while (depth > 0 && index < source.length) {
        if (source[index] === '{') {
            depth++;
        } else if (source[index] === '}') {
            depth--;
        }

        index++;
    }

    const result = new Map<string, string>();
    const body = source.slice(start, index - 1);

    for (const declaration of body.matchAll(/([\w-]+)\s*:\s*([^;{}]+);/g)) {
        result.set(declaration[1], declaration[2].trim().replace(/\s+/g, ' '));
    }

    return result;
}

/** Only the custom properties of a block. */
export function customProperties(
    css: string,
    selector: string,
): Map<string, string> {
    return new Map(
        [...declarations(css, selector)].filter(([name]) =>
            name.startsWith('--'),
        ),
    );
}

/** Resolves `@import` ids the way Vite does for the files we import. */
function resolveStylesheet(id: string, base: string): string {
    if (id.startsWith('.') || id.startsWith('/')) {
        return resolve(base, id);
    }

    const parts = id.split('/');
    const name = id.startsWith('@') ? parts.slice(0, 2).join('/') : parts[0];
    const subpath = id.slice(name.length + 1);
    // pnpm links every direct dependency into the top-level node_modules.
    const dir = join(root, 'node_modules', name);

    if (subpath !== '') {
        return join(dir, subpath);
    }

    const manifest = JSON.parse(
        readFileSync(join(dir, 'package.json'), 'utf8'),
    ) as { style?: string; exports?: { '.'?: { style?: string } } };
    const style = manifest.exports?.['.']?.style ?? manifest.style;

    if (style === undefined) {
        throw new Error(`Package "${name}" declares no stylesheet`);
    }

    return join(dir, style);
}

/** Compiles `resources/css/app.css` and generates the given candidates. */
export async function buildAppCss(candidates: string[]): Promise<string> {
    const compiler = await compile(read('resources/css/app.css'), {
        base: cssDir,
        loadStylesheet: async (id, base) => {
            const path = resolveStylesheet(id, base);

            if (!existsSync(path)) {
                throw new Error(`Cannot resolve stylesheet "${id}"`);
            }

            return {
                path,
                base: dirname(path),
                content: readFileSync(path, 'utf8'),
            };
        },
    });

    return compiler.build(candidates);
}

/**
 * Replaces `var(--x)` references with the values compiled CSS declares for
 * them on `:root, :host` (Tailwind's theme layer).
 */
export function resolveVars(value: string, css: string): string {
    const theme = customProperties(css, ':root, :host');

    return value.replace(/var\((--[\w-]+)\)/g, (_reference, name: string) => {
        const resolved = theme.get(name);

        if (resolved === undefined) {
            throw new Error(`Theme variable ${name} is not declared`);
        }

        return resolveVars(resolved, css);
    });
}
