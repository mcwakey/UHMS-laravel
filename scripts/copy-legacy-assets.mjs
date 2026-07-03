import { cpSync, existsSync, mkdirSync, readdirSync, rmSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');

const targets = [
    ['resources/css', 'public/build/css'],
    ['resources/scss', 'public/build/scss'],
    ['resources/img', 'public/build/img'],
    ['resources/js', 'public/build/js'],
    ['resources/plugins', 'public/build/plugins'],
];

for (const [source, destination] of targets) {
    const from = resolve(root, source);
    const to = resolve(root, destination);

    if (!existsSync(from)) {
        continue;
    }

    if (source === 'resources/plugins') {
        mkdirSync(to, { recursive: true });

        for (const entry of readdirSync(from, { withFileTypes: true })) {
            const sourceEntry = resolve(from, entry.name);
            const destinationEntry = resolve(to, entry.name);

            rmSync(destinationEntry, { recursive: true, force: true });
            cpSync(sourceEntry, destinationEntry, {
                recursive: true,
                force: true,
                errorOnExist: false,
            });
        }

        continue;
    }

    mkdirSync(to, { recursive: true });
    cpSync(from, to, {
        recursive: true,
        force: true,
        errorOnExist: false,
    });
}

console.log('Legacy assets copied to public/build.');
