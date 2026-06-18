import { existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

function findLaravelRoot(startDir: string): string {
  let dir = resolve(startDir);

  while (true) {
    if (
      existsSync(resolve(dir, 'artisan')) &&
      existsSync(resolve(dir, 'vendor', 'autoload.php')) &&
      existsSync(resolve(dir, 'bootstrap', 'app.php'))
    ) {
      return dir;
    }

    const parent = resolve(dir, '..');

    if (parent === dir) {
      throw new Error(`Could not find Laravel root from ${startDir}.`);
    }

    dir = parent;
  }
}

export const laravelRoot = findLaravelRoot(dirname(fileURLToPath(import.meta.url)));
