import { existsSync, readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect, type Page } from '@playwright/test';

const supportDir = dirname(fileURLToPath(import.meta.url));
const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';

loadEnvFile(resolve(supportDir, '..', '.env'));
loadEnvFile(resolve(supportDir, '..', '..', '.env'));

export const baseURL = process.env.UHMS_BASE_URL ?? 'http://localhost:8000';

const loginPath = '/login';

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

const laravelRoot = findLaravelRoot(supportDir);

function loadEnvFile(path: string) {
  if (!existsSync(path)) {
    return;
  }

  for (const line of readFileSync(path, 'utf8').split(/\r?\n/)) {
    const trimmed = line.trim();

    if (!trimmed || trimmed.startsWith('#')) {
      continue;
    }

    const separatorIndex = trimmed.indexOf('=');

    if (separatorIndex === -1) {
      continue;
    }

    const key = trimmed.slice(0, separatorIndex).trim();
    const value = trimmed.slice(separatorIndex + 1).trim().replace(/^['"]|['"]$/g, '');

    if (key && process.env[key] === undefined) {
      process.env[key] = value;
    }
  }
}

export function requiredCredentials(emailEnv: string, passwordEnv: string) {
  const email = process.env[emailEnv];
  const password = process.env[passwordEnv];

  if (!email || !password) {
    throw new Error(`${emailEnv} and ${passwordEnv} must be set for UHMS E2E tests.`);
  }

  return { email, password };
}

export function url(path: string) {
  return new URL(path, baseURL).toString();
}

function clearLoginRateLimits(email: string) {
  execFileSync(
    phpBinary,
    [
      '-r',
      String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = getenv('UHMS_E2E_LOGIN_EMAIL') ?: '';
$ips = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];
$domains = ['', 'localhost', 'localhost:8000'];

foreach ($ips as $ipAddress) {
    Illuminate\Support\Facades\RateLimiter::clear(
        Illuminate\Support\Str::transliterate(Illuminate\Support\Str::lower($email) . '|' . $ipAddress)
    );

    foreach ($domains as $domain) {
        $routeThrottleKey = $domain . '|' . $ipAddress;
        Illuminate\Support\Facades\RateLimiter::clear($routeThrottleKey);
        Illuminate\Support\Facades\RateLimiter::clear(sha1($routeThrottleKey));
    }
}
`,
    ],
    {
      cwd: laravelRoot,
      env: {
        ...process.env,
        UHMS_E2E_LOGIN_EMAIL: email,
      },
      stdio: 'pipe',
    },
  );
}

export async function gotoLogin(page: Page) {
  await page.goto(url(loginPath), { waitUntil: 'commit' });

  try {
    await expect(page.locator('input[name="email"]')).toBeVisible();
  } catch (error) {
    if (new URL(page.url()).pathname !== loginPath) {
      return;
    }

    throw error;
  }
}

export async function submitLoginForm(page: Page) {
  const loginForm = page.locator('form').filter({ has: page.locator('input[name="email"]') }).first();

  await expect(loginForm).toHaveCount(1);
  await loginForm.evaluate((form) => {
    if (!(form instanceof HTMLFormElement)) {
      throw new Error('Login form was not an HTML form.');
    }

    form.requestSubmit();
  });
}

export async function loginAs(page: Page, emailEnv: string, passwordEnv: string) {
  const credentials = requiredCredentials(emailEnv, passwordEnv);
  clearLoginRateLimits(credentials.email);

  await gotoLogin(page);
  if (new URL(page.url()).pathname !== loginPath) {
    return;
  }

  await page.getByRole('textbox', { name: /email address/i }).fill(credentials.email);
  await page.locator('input[name="password"]').fill(credentials.password);
  await submitLoginForm(page);
  await expect.poll(() => page.url(), { timeout: 30_000 }).not.toMatch(/\/login(?:[?#].*)?$/);
}

export async function waitForBodyText(page: Page, pattern: RegExp, timeout = 30_000) {
  await expect
    .poll(
      async () => {
        try {
          return await page.evaluate(() => document.body?.innerText ?? '');
        } catch {
          return '';
        }
      },
      { timeout },
    )
    .toMatch(pattern);
}
