#!/usr/bin/env node
/**
 * Inertia bridge anchor opt-out codemod.
 *
 * Adds `data-no-inertia` to any <a> tag that has `target="_blank"` or
 * `download` and does NOT already have `data-no-inertia`.
 *
 * The Inertia bridge already auto-skips these (see BladePage.vue), but the
 * explicit attribute is a guardrail in case the heuristic changes.
 *
 * Run from project root:
 *     node scripts/codemod-inertia-anchor-optouts.mjs
 *     node scripts/codemod-inertia-anchor-optouts.mjs --dry-run
 */

import { readFile, writeFile, readdir, stat } from 'node:fs/promises';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const ROOT = resolve(__dirname, '..');
const VIEWS_DIR = join(ROOT, 'resources', 'views');
const DRY = process.argv.includes('--dry-run');

async function* walk(dir) {
    for (const entry of await readdir(dir, { withFileTypes: true })) {
        const full = join(dir, entry.name);
        if (entry.isDirectory()) {
            yield* walk(full);
        } else if (entry.isFile() && entry.name.endsWith('.blade.php')) {
            yield full;
        }
    }
}

// Match <a ...> opening tags only; we look for target=_blank or `download`
// attribute that has no `data-no-inertia` sibling.
const ANCHOR_RE = /<a\b([^>]*?)>/gi;

function needsTag(attrs) {
    if (/\bdata-no-inertia\b/i.test(attrs)) return false;
    const hasBlank = /\btarget\s*=\s*["']_blank["']/i.test(attrs);
    const hasDownload = /\bdownload(?=[\s=>"'/])/i.test(attrs);
    return hasBlank || hasDownload;
}

function injectTag(attrs) {
    // Insert before final whitespace/slash. Preserve leading space.
    return ` data-no-inertia${attrs}`;
}

let totalFiles = 0;
let touchedFiles = 0;
let totalAnchors = 0;

for await (const file of walk(VIEWS_DIR)) {
    totalFiles++;
    const src = await readFile(file, 'utf8');
    let count = 0;
    const out = src.replace(ANCHOR_RE, (m, attrs) => {
        if (!needsTag(attrs)) return m;
        count++;
        return `<a${injectTag(attrs)}>`;
    });
    if (count > 0) {
        touchedFiles++;
        totalAnchors += count;
        const rel = file.slice(ROOT.length + 1).replace(/\\/g, '/');
        console.log(`${DRY ? '[dry] ' : ''}${rel}  +${count}`);
        if (!DRY) await writeFile(file, out, 'utf8');
    }
}

console.log(`\nFiles scanned: ${totalFiles}`);
console.log(`Files ${DRY ? 'would-be-changed' : 'changed'}: ${touchedFiles}`);
console.log(`Anchors tagged: ${totalAnchors}`);
