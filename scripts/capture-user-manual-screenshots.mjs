// scripts/capture-user-manual-screenshots.mjs
//
// Captures screenshots of the running UHMS app for the user manual.
//
// Usage:
//   npm run screenshots
//
// Environment variables:
//   UHMS_URL        - base URL of the running app (default: http://127.0.0.1:8000)
//   UHMS_EMAIL      - login email (default: admin@uhms.local)
//   UHMS_PASSWORD   - login password (default: password)
//   UHMS_HEADFUL    - set to "1" to run a headed browser
//   UHMS_SKIP_LOGIN - set to "1" to skip the login step
//
// The script:
//   - launches Chromium via Playwright
//   - performs an optional login
//   - visits each documented route and screenshots the full page
//   - writes PNGs to docs/assets/user-manual/ (overwrites existing images)
//   - logs but does not fail on missing routes (404 / redirect to login)

import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = resolve(__dirname, '..');
const outDir = resolve(repoRoot, 'docs', 'assets', 'user-manual');

const BASE_URL = (process.env.UHMS_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const EMAIL = process.env.UHMS_EMAIL || 'admin@uhms.local';
const PASSWORD = process.env.UHMS_PASSWORD || 'password';
const HEADFUL = process.env.UHMS_HEADFUL === '1';
const SKIP_LOGIN = process.env.UHMS_SKIP_LOGIN === '1';

const VIEWPORT = { width: 1440, height: 900 };

// Each entry tries `path` first; on failure falls through `aliases`.
// The `name` becomes the PNG filename saved in docs/assets/user-manual/.
// requireAuth: false means capture before logging in (login page itself).
const TARGETS = [
    // ── Authentication / Dashboard ──────────────────────────────────────────
    { name: '01-login',     path: '/login',              requireAuth: false },
    { name: '02-dashboard', path: '/admin/dashboard' },

    // ── Patients ─────────────────────────────────────────────────────────────
    { name: '03-patients',        path: '/admin/patients' },
    { name: '10-patients-create', path: '/admin/patients/create' },

    // ── Appointments ─────────────────────────────────────────────────────────
    { name: '11-appointments-list',              path: '/admin/appointments' },
    { name: '12-appointments-create',            path: '/admin/appointments/create' },
    { name: '70-appointments-two-week-calendar', path: '/admin/appointments/calendar' },

    // ── Visits / OPD ─────────────────────────────────────────────────────────
    { name: '05-visits-opd',    path: '/admin/visits' },
    { name: '13-visits-create', path: '/admin/visits/create' },

    // ── Queue ─────────────────────────────────────────────────────────────────
    { name: '14-queue-manage', path: '/admin/queue/manage' },
    { name: '15-queue-board',  path: '/admin/queue/board' },

    // ── Triage & Vitals ───────────────────────────────────────────────────────
    { name: '16-triage-index', path: '/admin/triage' },
    // 17-vitals: triage assess requires a visit ID; fall back to triage list
    { name: '17-vitals',       path: '/admin/triage', aliases: ['/admin/triage'] },

    // ── Consultations ─────────────────────────────────────────────────────────
    { name: '06-consultations',    path: '/admin/consultations' },
    { name: '18-medical-patterns', path: '/admin/patterns' },
    { name: '19-icd-codes',        path: '/admin/icd-codes' },

    // ── Procedures ────────────────────────────────────────────────────────────
    { name: '20-procedures-catalog',  path: '/admin/procedures' },
    { name: '21-procedures-schedule', path: '/admin/procedures/schedule' },

    // ── Ward / Inpatient ──────────────────────────────────────────────────────
    { name: '22-admissions', path: '/admin/admissions' },
    { name: '23-bed-map',    path: '/admin/bed-map' },
    { name: '24-wards',      path: '/admin/beds' },
    { name: '25-beds',       path: '/admin/beds' },

    // ── Pharmacy ──────────────────────────────────────────────────────────────
    { name: '26-prescriptions',  path: '/admin/prescriptions' },
    { name: '27-dispensing',     path: '/admin/pharmacy/dispensing' },
    { name: '28-drug-catalog',   path: '/admin/pharmacy/drugs' },
    { name: '29-drug-stock',     path: '/admin/store/stock/balances' },

    // ── Investigations ────────────────────────────────────────────────────────
    { name: '07-investigation-requests', path: '/admin/lab/requests' },
    { name: '30-investigation-results',  path: '/admin/lab/results' },
    { name: '31-test-catalog',           path: '/admin/investigation-catalogue',
      aliases: ['/admin/lab/tests'] },
    { name: '32-investigation-items',    path: '/admin/investigations/items' },
    { name: '33-investigation-stock',    path: '/admin/investigations/stock' },
    { name: '34-analyzers',              path: '/admin/analyzers' },

    // ── Billing ───────────────────────────────────────────────────────────────
    { name: '08-billing-invoices', path: '/admin/billing/invoices' },
    { name: '35-invoice-create',   path: '/admin/billing/invoices/create' },
    { name: '36-payments',         path: '/admin/billing/payments' },
    { name: '69-receive-payments', path: '/admin/billing/payments/receive' },
    { name: '37-services',         path: '/admin/services' },
    { name: '38-specialties',      path: '/admin/specialties' },

    // ── Claims & Insurance ────────────────────────────────────────────────────
    { name: '39-claims',              path: '/admin/claims' },
    { name: '40-claim-create',        path: '/admin/claims/create' },
    { name: '41-insurance-providers', path: '/admin/insurance-providers' },

    // ── Store & Procurement ───────────────────────────────────────────────────
    { name: '42-suppliers',       path: '/admin/store/suppliers' },
    { name: '43-purchase-orders', path: '/admin/store/purchase-orders' },
    { name: '44-po-create',       path: '/admin/store/purchase-orders/create' },
    { name: '45-stock-transfers', path: '/admin/store/transfers' },

    // ── Accounts & Finance ────────────────────────────────────────────────────
    { name: '46-account-categories', path: '/admin/accounts/categories' },
    { name: '47-expenses',           path: '/admin/accounts/expenses' },
    { name: '48-income',             path: '/admin/accounts/income' },
    { name: '49-daily-collection',   path: '/admin/accounts/daily-collection' },
    { name: '50-reconciliation',     path: '/admin/accounts/reconciliation' },
    { name: '51-handover',           path: '/admin/accounts/handover' },

    // ── HR & Payroll ──────────────────────────────────────────────────────────
    { name: '52-employees',  path: '/admin/hr/employees' },
    { name: '53-attendance', path: '/admin/hr/attendance' },
    { name: '54-leave',      path: '/admin/hr/leave' },
    { name: '55-payroll',    path: '/admin/hr/payroll' },

    // ── Reports ───────────────────────────────────────────────────────────────
    { name: '56-reports-income',             path: '/admin/reports/income' },
    { name: '57-reports-visits',             path: '/admin/reports/visits' },
    { name: '58-reports-consultation-stats', path: '/admin/reports/consultation-stats' },

    // ── Administration ────────────────────────────────────────────────────────
    { name: '59-users',        path: '/admin/users' },
    { name: '60-roles',        path: '/admin/roles' },
    { name: '61-departments',  path: '/admin/departments' },
    { name: '62-designations', path: '/admin/designations' },

    // ── Settings ──────────────────────────────────────────────────────────────
    { name: '63-settings-organization',    path: '/admin/settings/organization' },
    { name: '64-settings-invoice',         path: '/admin/settings/invoice' },
    { name: '65-settings-payment-methods', path: '/admin/settings/payment-methods' },
    { name: '66-activity-log',             path: '/admin/settings/activity-log' },
    { name: '09-modules',                  path: '/admin/modules' },

    // ── Notifications & Profile ───────────────────────────────────────────────
    { name: '67-notifications', path: '/admin/notifications' },
    { name: '68-profile',       path: '/admin/profile' },
];

function log(level, message) {
    const stamp = new Date().toISOString().slice(11, 19);
    console.log(`[${stamp}] ${level.padEnd(5)} ${message}`);
}

async function tryLogin(page) {
    if (SKIP_LOGIN) {
        log('info', 'UHMS_SKIP_LOGIN=1 — skipping login.');
        return false;
    }

    const loginUrl = `${BASE_URL}/login`;
    log('info', `Logging in at ${loginUrl} as ${EMAIL}`);

    try {
        await page.goto(loginUrl, { waitUntil: 'domcontentloaded', timeout: 20000 });
    } catch (err) {
        log('warn', `Could not open login page: ${err.message}`);
        return false;
    }

    const emailField = page.locator('input[type="email"], input[name="email"]').first();
    const passwordField = page.locator('input[type="password"], input[name="password"]').first();
    const submitButton = page.locator('button[type="submit"], input[type="submit"]').first();

    if (!(await emailField.count()) || !(await passwordField.count())) {
        log('warn', 'Login form fields not found — assuming app does not require auth or is already authenticated.');
        return false;
    }

    try {
        await emailField.fill(EMAIL);
        await passwordField.fill(PASSWORD);
        await Promise.all([
            page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {}),
            submitButton.click(),
        ]);
        const url = page.url();
        if (/\/login(\?|$)/.test(url)) {
            log('warn', `Still on login page after submit (url=${url}). Continuing without authenticated session.`);
            return false;
        }
        log('info', `Logged in. Landed on ${url}`);
        return true;
    } catch (err) {
        log('warn', `Login attempt failed: ${err.message}`);
        return false;
    }
}

async function captureTarget(page, target) {
    const candidates = [target.path, ...(target.aliases || [])];
    for (const candidate of candidates) {
        const url = `${BASE_URL}${candidate}`;
        try {
            const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
            // Wait for network to settle, but never block forever.
            await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

            const status = response ? response.status() : 0;
            const finalUrl = page.url();

            if (status >= 400) {
                log('warn', `${target.name}: ${candidate} returned HTTP ${status} — trying next candidate.`);
                continue;
            }
            if (target.requireAuth !== false && /\/login(\?|$)/.test(finalUrl) && !/\/login(\?|$)/.test(candidate)) {
                log('warn', `${target.name}: ${candidate} redirected to login — trying next candidate.`);
                continue;
            }

            const filePath = resolve(outDir, `${target.name}.png`);
            await page.screenshot({ path: filePath, fullPage: true });
            log('ok',   `${target.name}: captured from ${candidate} (HTTP ${status}) -> ${filePath}`);
            return true;
        } catch (err) {
            log('warn', `${target.name}: ${candidate} failed (${err.message}) — trying next candidate.`);
        }
    }
    log('skip', `${target.name}: no working route found among ${candidates.join(', ')}`);
    return false;
}

async function main() {
    await mkdir(outDir, { recursive: true });

    log('info', `Base URL:      ${BASE_URL}`);
    log('info', `Output folder: ${outDir}`);
    log('info', `Headful:       ${HEADFUL ? 'yes' : 'no'}`);

    const browser = await chromium.launch({ headless: !HEADFUL });
    const context = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 1 });
    const page = await context.newPage();

    let captured = 0;
    let skipped = 0;

    try {
        // Always capture /login first while unauthenticated.
        const loginTarget = TARGETS.find(t => t.name === '01-login');
        if (loginTarget) {
            const ok = await captureTarget(page, loginTarget);
            ok ? captured++ : skipped++;
        }

        await tryLogin(page);

        for (const target of TARGETS) {
            if (target.name === '01-login') continue;
            const ok = await captureTarget(page, target);
            ok ? captured++ : skipped++;
        }
    } finally {
        await context.close();
        await browser.close();
    }

    log('info', `Done. Captured: ${captured}, Skipped: ${skipped}.`);
    if (captured === 0) {
        process.exitCode = 1;
    }
}

main().catch(err => {
    console.error('Screenshot capture failed:', err);
    process.exit(1);
});
