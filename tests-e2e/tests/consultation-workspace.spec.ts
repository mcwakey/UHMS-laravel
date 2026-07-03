import { expect, test, type Page } from '@playwright/test';
import { consultationWorkspaceFixture, type ConsultationWorkspaceFixture } from './support/consultation-fixture';
import { loginWithCredentials, url } from './support/auth';

test.describe.configure({ mode: 'serial' });

test.describe('consultation workspace smoke', () => {
  let fixture: ConsultationWorkspaceFixture;

  test.beforeAll(() => {
    fixture = consultationWorkspaceFixture();
  });

  test('loads without console errors and keeps delegated controls alive', async ({ page }) => {
    const consoleErrors: string[] = [];
    const pageErrors: string[] = [];
    const responseErrors: string[] = [];

    page.on('console', (message) => {
      if (message.type() === 'error') {
        if (message.text().includes('409 (Conflict)')) {
          return;
        }

        consoleErrors.push(message.text());
      }
    });
    page.on('pageerror', (error) => pageErrors.push(error.message));
    page.on('response', (response) => {
      if (response.status() >= 400) {
        if (response.status() === 409 && response.url().includes('/prescriptions')) {
          return;
        }

        responseErrors.push(`${response.status()} ${response.url()}`);
      }
    });

    await loginWithCredentials(page, fixture.email, fixture.password);
    await page.goto(url(fixture.consultation_url), { waitUntil: 'domcontentloaded' });

    await expect(page.locator('#consultation-page-config')).toHaveCount(1);
    await expect(page.locator('body')).toContainText(/Consultation|Prescriptions|Procedures/);

    const config = await page.locator('#consultation-page-config').textContent();
    expect(JSON.parse(config || '{}')).toMatchObject({
      visitId: fixture.visit_id,
      currentRouteId: fixture.consultation_route_id,
    });

    await expect(page.locator('input[name="consultation_route_id"]').first()).toHaveValue(String(fixture.consultation_route_id));
    await expect(page.locator('form[data-route-context-required="true"] input[name="_idempotency_key"]').first()).toHaveValue(/\S+/);
    await expect(page.locator('#completionReadinessCard')).toBeVisible();

    await exerciseProcedureDepartmentSelector(page, fixture);
    await exerciseLabRequestModalValidation(page, fixture);
    await exercisePrescriptionRows(page);
    await exercisePrescriptionSafetyWarning(page, fixture);
    await exerciseSectionRefresh(page);

    expect(pageErrors).toEqual([]);
    expect(responseErrors).toEqual([]);
    expect(consoleErrors).toEqual([]);
  });
});

async function exerciseProcedureDepartmentSelector(page: Page, fixture: ConsultationWorkspaceFixture) {
  await page.locator('#tab-procedures').click();
  await page.getByRole('button', { name: /request procedure/i }).click();

  const servicesResponse = page.waitForResponse((response) => {
    return response.url().includes(`/admin/theatre/departments/${fixture.procedure_department_id}/services`)
      && response.status() === 200;
  });

  await page.locator('#procedureDeptSelect').selectOption(String(fixture.procedure_department_id));
  await servicesResponse;

  const serviceSelect = page.locator('#procedureServiceSelect');
  await expect(serviceSelect).toBeEnabled();
  await expect(serviceSelect.locator(`option[value="${fixture.procedure_service_id}"]`)).toHaveCount(1);
}

async function exerciseLabRequestModalValidation(page: Page, fixture: ConsultationWorkspaceFixture) {
  await page.evaluate(() => {
    const modal = document.getElementById('investigationModal');
    if (!modal || !window.bootstrap?.Modal) {
      throw new Error('Investigation modal or Bootstrap modal helper is unavailable.');
    }

    window.bootstrap.Modal.getOrCreateInstance(modal).show();
  });

  await expect(page.locator('#investigationModal.show')).toBeVisible();
  const labForm = page.locator('#labRequestForm');
  await expect(labForm.locator('input[name="consultation_route_id"]')).toHaveValue(String(fixture.consultation_route_id));

  await expect.poll(() => labForm.evaluate((form) => (form as HTMLFormElement).checkValidity())).toBe(false);
  await page.locator('#labReqDeptSelect').selectOption(String(fixture.lab_department_id));
  await expect(page.locator('#labReqItemsContainer')).toBeVisible();

  await page.locator('#labRequestForm [data-bs-dismiss="modal"]').click();
  await expect(page.locator('#investigationModal.show')).toHaveCount(0);
}

async function exercisePrescriptionRows(page: Page) {
  await page.locator('#tab-prescriptions').click();
  await page.locator('#prescriptions-section [data-bs-target="#addPrescriptionForm"]').click();

  const rows = page.locator('#prescriptionItems .prescription-item');
  await expect(rows).toHaveCount(1);

  await page.locator('[data-consultation-action="add-prescription-item"]').click();
  await expect(rows).toHaveCount(2);

  await rows.nth(1).locator('[data-consultation-action="remove-prescription-row"]').click();
  await expect(rows).toHaveCount(1);
}

async function exercisePrescriptionSafetyWarning(page: Page, fixture: ConsultationWorkspaceFixture) {
  await page.locator('#tab-prescriptions').click();
  const addButton = page.locator('#prescriptions-section [data-bs-target="#addPrescriptionForm"]');
  if (!(await page.locator('#addPrescriptionForm').isVisible())) {
    await addButton.click();
  }

  const form = page.locator('#prescriptionForm');
  await form.locator('select[name="items[0][drug_id]"]').selectOption(String(fixture.drug_id));
  await form.locator('input[name="items[0][drug_name]"]').evaluate((input, value) => {
    (input as HTMLInputElement).value = String(value);
  }, 'E2E Amoxicillin');
  await form.locator('input[name="items[0][dosage]"]').fill('500mg');
  await form.locator('select[name="items[0][frequency]"]').selectOption('BD');
  await form.locator('input[name="items[0][duration]"]').fill('5 days');
  await form.locator('input[name="items[0][quantity]"]').fill('10');
  await form.locator('select[name="items[0][route]"]').selectOption('oral');

  const warningResponse = page.waitForResponse((response) => (
    response.url().includes('/prescriptions') && response.status() === 409
  ));
  await form.locator('button[type="submit"]').click();
  await warningResponse;

  await expect(form.locator('[data-prescription-safety-panel]')).toBeVisible();
  await expect(form.locator('[data-prescription-safety-warnings]')).toContainText(/allergy|conflict/i);
  await expect(form.locator('textarea[name="safety_override_reason"]')).toBeVisible();
}

async function exerciseSectionRefresh(page: Page) {
  await page.evaluate(async () => {
    if (!window.UHMSConsultation?.sectionRefresh) {
      throw new Error('Consultation section refresh API is unavailable.');
    }

    await window.UHMSConsultation.sectionRefresh.refresh('prescriptions');
  });

  await page.locator('#tab-prescriptions').click();
  if (!(await page.locator('#addPrescriptionForm').isVisible())) {
    await page.locator('#prescriptions-section [data-bs-target="#addPrescriptionForm"]').click();
  }
  await expect(page.locator('[data-consultation-action="add-prescription-item"]')).toBeVisible();
}
