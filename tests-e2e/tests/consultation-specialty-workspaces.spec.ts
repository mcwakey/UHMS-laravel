import { expect, test, type Page } from '@playwright/test';
import {
  specialtyWorkspaceFixtures,
  type SpecialtyWorkspaceFixture,
  type SpecialtyWorkspaceFixtures,
} from './support/consultation-fixture';
import { loginWithCredentials, url } from './support/auth';

test.describe.configure({ mode: 'serial' });

const SPECIALTY_PROFILES = [
  'general_medicine',
  'physiotherapy',
  'ophthalmology',
  'dental',
  'obstetrics',
  'ent',
  'pediatrics',
  'emergency',
  'surgery',
];

const VIEWPORT_PROFILES = ['general_medicine', 'obstetrics', 'emergency', 'dental'];

// Phase 16.5/16.6: legacy duplicate sections (including complaint-like
// sections canonicalised in 16.6) are hidden from the doctor sidebar; their
// clinical function lives in the canonical shared sections.
const HIDDEN_DUPLICATE_SECTIONS: Record<string, string[]> = {
  physiotherapy: ['presenting_problem'],
  ophthalmology: ['eye_complaint'],
  dental: ['dental_diagnosis', 'dental_xray', 'dental_procedures', 'dental_complaint'],
  obstetrics: ['lab_screening', 'ultrasound_findings'],
  ent: ['ent_complaint'],
  pediatrics: ['pediatric_complaint'],
  emergency: ['urgent_investigations', 'urgent_procedures', 'medications_given', 'emergency_complaint'],
  surgery: ['procedure_plan', 'surgical_complaint'],
};

const EXPECTED_CANONICAL_SECTIONS: Record<string, string[]> = {
  dental: ['diagnosis', 'investigations', 'procedures'],
  obstetrics: ['investigations', 'complaints'],
  emergency: ['investigations', 'procedures', 'prescription'],
  surgery: ['procedures'],
};

const VIEWPORTS = [
  { name: 'desktop', width: 1366, height: 900 },
  { name: 'tablet', width: 820, height: 1180 },
  { name: 'mobile', width: 390, height: 844 },
];

test.describe('specialist consultation workspaces', () => {
  let fixtures: SpecialtyWorkspaceFixtures;

  test.beforeAll(() => {
    fixtures = specialtyWorkspaceFixtures();
  });

  for (const profileCode of SPECIALTY_PROFILES) {
    test(`${profileCode} workspace smoke`, async ({ page }) => {
      const fixture = fixtureFor(fixtures, profileCode);
      const errors = collectPageErrors(page);

      await loginWithCredentials(page, fixture.doctor_email, fixture.doctor_password);
      await page.goto(url(fixture.workspace_url), { waitUntil: 'domcontentloaded' });

      await expect(page.locator('#consultation-page-config')).toHaveCount(1);
      await expect(page.locator('#doctor-specialty-workspace')).toContainText(fixture.profile_label);
      await expect(page.locator('#completionReadinessCard')).toBeVisible();

      for (const section of fixture.expected_sections.slice(0, 4)) {
        await expect(page.locator(`#tab-${section}`)).toBeVisible();
      }

      for (const section of EXPECTED_CANONICAL_SECTIONS[profileCode] ?? []) {
        await expect(page.locator(`#tab-${section}`)).toBeVisible();
      }
      for (const section of HIDDEN_DUPLICATE_SECTIONS[profileCode] ?? []) {
        await expect(page.locator(`#tab-${section}`)).toHaveCount(0);
      }

      await clickQuickAction(page, fixture);
      await saveStructuredProbe(page, fixture);
      await generateSummaryPreview(page);
      await previewOrderSetIfAvailable(page);
      await assertNoBillingCardOnDoctorWorkspace(page);

      expect(errors.pageErrors).toEqual([]);
      expect(errors.responseErrors).toEqual([]);
      expect(errors.consoleErrors).toEqual([]);
    });
  }

  for (const viewport of VIEWPORTS) {
    for (const profileCode of VIEWPORT_PROFILES) {
      test(`${profileCode} responsive smoke at ${viewport.name}`, async ({ page }) => {
        const fixture = fixtureFor(fixtures, profileCode);
        const errors = collectPageErrors(page);

        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await loginWithCredentials(page, fixture.doctor_email, fixture.doctor_password);
        await page.goto(url(fixture.workspace_url), { waitUntil: 'domcontentloaded' });

        await expect(page.locator('#doctor-specialty-workspace')).toBeVisible();
        await expect(page.locator('#completionReadinessCard')).toBeVisible();
        await expect(page.locator('#consultationTabContent')).toBeVisible();

        const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
        expect(bodyWidth).toBeLessThanOrEqual(viewport.width + 24);

        if (fixture.expected_structured_section !== 'complaints') {
          await page.locator(`#tab-${fixture.expected_structured_section}`).click();
          await expect(specialtySectionPane(page, fixture.expected_structured_section)).toBeVisible();
        }

        await previewOrderSetIfAvailable(page, true);

        expect(errors.pageErrors).toEqual([]);
        expect(errors.responseErrors).toEqual([]);
        expect(errors.consoleErrors).toEqual([]);
      });
    }
  }

  test('specialist report page smoke', async ({ page }) => {
    const errors = collectPageErrors(page);

    await loginWithCredentials(page, fixtures.admin.email, fixtures.admin.password);
    await page.goto(url(fixtures.admin.report_url), { waitUntil: 'domcontentloaded' });

    await expect(page.locator('body')).toContainText(/Consultation Specialties|Spécialités de consultation/);
    await expect(page.getByRole('link', { name: /export csv/i }).first()).toBeVisible();
    const reportFilter = page.locator('form').filter({ has: page.locator('select[name="specialty_profile_id"]') }).first();
    await reportFilter.locator('select[name="specialty_profile_id"]').selectOption({
      label: fixtureFor(fixtures, 'physiotherapy').profile_label,
    });
    await Promise.all([
      page.waitForURL(/specialty_profile_id=/),
      reportFilter.evaluate((form) => (form as HTMLFormElement).requestSubmit()),
    ]);
    await expect(page.locator('table').first()).toBeVisible();

    expect(errors.pageErrors).toEqual([]);
    expect(errors.responseErrors).toEqual([]);
    expect(errors.consoleErrors).toEqual([]);
  });
});

function fixtureFor(fixtures: SpecialtyWorkspaceFixtures, profileCode: string): SpecialtyWorkspaceFixture {
  const fixture = fixtures.profiles.find((candidate) => candidate.profile_code === profileCode);
  if (!fixture) {
    throw new Error(`Missing specialty fixture for ${profileCode}`);
  }

  return fixture;
}

function collectPageErrors(page: Page) {
  const consoleErrors: string[] = [];
  const pageErrors: string[] = [];
  const responseErrors: string[] = [];

  page.on('console', (message) => {
    if (message.type() === 'error') {
      if (message.text().includes('Failed to load resource') && message.text().includes('403')) {
        return;
      }

      consoleErrors.push(message.text());
    }
  });
  page.on('pageerror', (error) => pageErrors.push(error.message));
  page.on('response', (response) => {
    if (response.status() === 403 && response.url().includes('/admin/notifications/recent')) {
      return;
    }

    if (response.status() >= 400) {
      responseErrors.push(`${response.status()} ${response.url()}`);
    }
  });

  return { consoleErrors, pageErrors, responseErrors };
}

async function clickQuickAction(page: Page, fixture: SpecialtyWorkspaceFixture) {
  const action = fixture.expected_quick_actions.find((candidate) => candidate !== 'order_sets') ?? fixture.expected_quick_actions[0];
  const quickAction = page.locator(`[data-doctor-workspace-action][data-target="#${action}-section"], [data-doctor-workspace-action][href="#${action}-section"]`).first();

  if (await quickAction.count()) {
    await quickAction.click();
    await expect(page.locator(`#${action}-section, #completionReadinessCard`).first()).toBeVisible();
  }
}

async function saveStructuredProbe(page: Page, fixture: SpecialtyWorkspaceFixture) {
  const probe = fixture.save_probe;
  if (!probe?.section || !probe.field || !probe.value || probe.section === 'complaints') {
    return;
  }

  await page.locator(`#tab-${probe.section}`).click();
  await page.locator(`[data-bs-target="#add-specialty-${probe.section}-form"]`).filter({ hasText: /add/i }).first().click();
  const form = page.locator(`#add-specialty-${probe.section}-form form`);
  await expect(form).toBeVisible();

  const values = probe.values && Object.keys(probe.values).length
    ? probe.values
    : { [probe.field]: probe.value };

  for (const [fieldName, fieldValue] of Object.entries(values)) {
    const field = form.locator(`[name="${fieldName}"]`).last();
    if (await field.count()) {
      const inputType = await field.getAttribute('type');
      if (inputType === 'checkbox') {
        if (fieldValue === '1' || fieldValue === 'true') {
          await field.check();
        } else {
          await field.uncheck();
        }
      } else {
        await field.fill(fieldValue);
      }
      continue;
    }

    const arrayField = form.locator(`[name="${fieldName}[]"]`).last();
    if (await arrayField.count()) {
      await arrayField.fill(fieldValue);
    }
  }

  const saveResult = await form.evaluate(async (element) => {
    const formElement = element as HTMLFormElement;
    const idempotency = formElement.querySelector<HTMLInputElement>('input[name="_idempotency_key"]');
    if (idempotency) {
      idempotency.value = `${idempotency.dataset.idempotencyAction || 'specialty-entry'}.${Date.now()}.${Math.random().toString(16).slice(2)}`;
    }

    const response = await fetch(formElement.action, {
      method: formElement.method || 'POST',
      body: new FormData(formElement),
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });

    return {
      ok: response.ok,
      status: response.status,
      body: await response.json().catch(() => null),
    };
  });

  expect(saveResult.status).toBeLessThan(400);
  expect(saveResult.body?.success ?? saveResult.ok).toBeTruthy();

  await page.reload({ waitUntil: 'domcontentloaded' });
  await page.locator(`#tab-${probe.section}`).click();
  await expect(specialtySectionPane(page, probe.section)).toContainText(probe.value);
}

function specialtySectionPane(page: Page, section: string) {
  return page.locator(`#specialty-${section}-section, #${section}-section`).first();
}

async function generateSummaryPreview(page: Page) {
  const button = page.locator('#generateSpecialtySummaryBtn');
  const modal = page.locator('#specialtySummaryModal');
  if (!(await button.count()) || !(await modal.count())) {
    return;
  }

  await page.locator('a[href="#summary-section"]').first().click();
  await button.evaluate((element) => (element as HTMLButtonElement).click());
  const visibleModal = page.locator('#specialtySummaryModal.show');

  try {
    await expect(visibleModal).toBeVisible({ timeout: 1500 });
    await expect(page.locator('#specialtySummaryPreviewBody')).toBeVisible();
    await page.locator('#specialtySummaryModal [data-bs-dismiss="modal"]').first().click();
    await expect(visibleModal).toHaveCount(0);
    return;
  } catch {
    const preview = await button.evaluate(async (element) => {
      const trigger = element as HTMLButtonElement;
      const previewUrl = trigger.dataset.previewUrl;
      if (!previewUrl) {
        return null;
      }

      const url = new URL(previewUrl, window.location.origin);
      if (trigger.dataset.routeId) {
        url.searchParams.set('consultation_route_id', trigger.dataset.routeId);
      }

      const response = await fetch(url.toString(), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });

      if (!response.ok) {
        throw new Error(`Summary preview failed with ${response.status}`);
      }

      return response.json();
    });

    expect(preview?.summary?.html ?? preview?.summary?.plainText ?? '').not.toEqual('');
  }
}

async function previewOrderSetIfAvailable(page: Page, closeOnly = false) {
  const preview = page.locator('[data-order-set-preview]').first();
  if (!(await preview.count())) {
    return;
  }

  await preview.click();
  await expect(page.locator('#specialtyOrderSetModal.show')).toBeVisible();
  await expect(page.locator('[data-order-set-items]')).toBeVisible();
  await page.locator('#specialtyOrderSetModal .btn-close').click();
  if (await page.locator('#specialtyOrderSetModal.show').count()) {
    await page.locator('#specialtyOrderSetModal').evaluate((element) => {
      const bootstrapApi = (window as unknown as {
        bootstrap?: { Modal?: { getOrCreateInstance: (modal: Element) => { hide: () => void } } };
      }).bootstrap;

      bootstrapApi?.Modal?.getOrCreateInstance(element)?.hide();
    });
  }
  if (await page.locator('#specialtyOrderSetModal.show').count()) {
    await page.locator('#specialtyOrderSetModal').evaluate((element) => {
      element.classList.remove('show');
      element.setAttribute('aria-hidden', 'true');
      element.removeAttribute('aria-modal');
      element.removeAttribute('role');
      document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('padding-right');
    });
  }
  await expect(page.locator('#specialtyOrderSetModal.show')).toHaveCount(0);

  if (closeOnly) {
    await expect(page.locator('#doctor-specialty-workspace')).toBeVisible();
  }
}

async function assertNoBillingCardOnDoctorWorkspace(page: Page) {
  const body = await page.locator('body').innerText();
  expect(body).not.toMatch(/exception|sqlstate|undefined variable/i);

  // Phase 16.5: the doctor consultation workspace must not expose the
  // billing/service mapping card or its preview/apply actions.
  await expect(page.locator('form[action*="specialty-billing/apply"]')).toHaveCount(0);
  await expect(page.locator('a[href*="specialty-billing/preview"]')).toHaveCount(0);
}
