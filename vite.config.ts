import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig, lazyPlugins } from 'vite-plus';

// Under Vitest only the plugins that transform source files load: the
// Laravel plugin refuses to serve when CI=true and Wayfinder shells out to
// `php artisan`, and JS tests must not need either.
const testing = Boolean(process.env.VITEST);
const inDocker = process.env.HUB_DOCKER === 'true';
const devPort = Number(process.env.VITE_PORT ?? 5173);

export default defineConfig({
    plugins: lazyPlugins(() => [
        ...(testing
            ? []
            : [
                  laravel({
                      input: ['resources/css/app.css', 'resources/js/app.tsx'],
                      refresh: true,
                  }),
              ]),
        inertia(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
        ...(testing
            ? []
            : [
                  wayfinder({
                      formVariants: true,
                  }),
              ]),
    ]),
    // The `@/` alias from tsconfig.json, also for tests without the Laravel
    // plugin.
    resolve: {
        tsconfigPaths: true,
    },
    // In the Compose `app` service (HUB_DOCKER=true) Vite listens on every
    // interface of the container, the browser reaches it through the port
    // published on 127.0.0.1, and file changes are polled because events do
    // not cross the Windows bind mount (ADR 0002).
    server: {
        host: inDocker ? '0.0.0.0' : undefined,
        port: devPort,
        strictPort: true,
        hmr: inDocker ? { host: '127.0.0.1' } : undefined,
        watch: {
            usePolling: inDocker,
            interval: 100,
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.{ts,tsx}'],
        setupFiles: ['resources/js/test/setup.ts'],
    },
    lint: {
        // Oxlint ports of typescript-eslint and eslint-plugin-react(-hooks).
        plugins: ['typescript', 'react', 'oxc', 'unicorn'],
        rules: {
            'react/rules-of-hooks': 'error',
            'react/exhaustive-deps': 'error',
            'typescript/no-explicit-any': 'error',
            'typescript/no-floating-promises': 'error',
            // UI copy comes from lang/<locale>/*.php through useTrans(); only
            // punctuation and symbols may be written in JSX.
            'react/jsx-no-literals': [
                'error',
                {
                    allowedStrings: [
                        '/',
                        '|',
                        '·',
                        '•',
                        '—',
                        '–',
                        '-',
                        ':',
                        ',',
                        '.',
                        '(',
                        ')',
                        '+',
                        '×',
                        '%',
                        '#',
                        '&',
                        '*',
                        '…',
                    ],
                },
            ],
        },
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        // Match .editorconfig: data and prose files use two-space indents.
        overrides: [
            {
                files: ['*.{json,yml,yaml,md}'],
                options: { tabWidth: 2 },
            },
        ],
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
