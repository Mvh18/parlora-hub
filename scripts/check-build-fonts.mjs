/*
 * Built-fonts check.
 *
 * Run after `pnpm build`. Fails when the built assets reference a
 * third-party font host, or when a brand font family is missing from the
 * bundle: learners' browsers must load fonts only from the application.
 * The Vitest design tests cover the sources; this covers what ships.
 */
import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';

const buildDir = process.argv[2] ?? 'public/build';
const thirdPartyHosts =
    /fonts\.googleapis\.com|fonts\.gstatic\.com|fonts\.bunny\.net|use\.typekit\.net/;
const families = ['bricolage-grotesque', 'figtree'];

if (!existsSync(buildDir)) {
    console.error(`${buildDir} does not exist; run pnpm build first.`);
    process.exit(1);
}

function files(directory) {
    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const path = join(directory, entry.name);

        return entry.isDirectory() ? files(path) : [path];
    });
}

const built = files(buildDir);
const problems = [];

for (const file of built.filter((path) => /\.(css|js|json|html)$/.test(path))) {
    if (thirdPartyHosts.test(readFileSync(file, 'utf8'))) {
        problems.push(`${file} references a third-party font host`);
    }
}

for (const family of families) {
    if (
        !built.some((path) => path.includes(family) && path.endsWith('.woff2'))
    ) {
        problems.push(`no ${family} woff2 file in ${buildDir}`);
    }
}

if (problems.length > 0) {
    console.error(problems.join('\n'));
    process.exit(1);
}

console.log(`Fonts OK: ${families.join(', ')} served from ${buildDir}.`);
