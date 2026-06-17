import { execFileSync } from 'node:child_process';
import { type Page } from '@playwright/test';
import { loginAs } from '../support/auth';

export type TestPatient = {
  firstName: string;
  lastName: string;
  fullName: string;
  dateOfBirth: string;
  gender: string;
  phone: string;
  email: string;
  profilePath: string;
  id: string;
};

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';

export async function createCashPatient(page: Page, prefix = 'E2EVisit'): Promise<TestPatient> {
  const runId = `${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}`;
  const data: Omit<TestPatient, 'id' | 'profilePath' | 'fullName'> & {
    address: string;
    city: string;
    region: string;
  } = {
    firstName: `${prefix}${runId}`,
    lastName: 'CashPatient',
    dateOfBirth: '1990-01-15',
    gender: 'male',
    phone: `020${String(Date.now()).slice(-7)}`,
    email: `e2e.patient.${runId}@example.test`,
    address: 'E2E cash patient address',
    city: 'Accra',
    region: 'Greater Accra',
  };

  const created = JSON.parse(
    execFileSync(
      phpBinary,
      [
        '-r',
        String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$data = json_decode(getenv('UHMS_E2E_PATIENT_JSON') ?: '{}', true, flags: JSON_THROW_ON_ERROR);

$patient = App\Models\Patient::create([
    'patient_number' => app(App\Services\PatientIdGeneratorService::class)->generate(),
    'first_name' => $data['firstName'],
    'last_name' => $data['lastName'],
    'date_of_birth' => $data['dateOfBirth'],
    'gender' => App\Enums\Gender::from($data['gender']),
    'phone' => $data['phone'],
    'email' => $data['email'],
    'address' => $data['address'],
    'city' => $data['city'],
    'region' => $data['region'],
    'status' => 'active',
    'is_active' => true,
]);

echo json_encode([
    'id' => (string) $patient->id,
    'fullName' => $patient->full_name,
    'profilePath' => route('admin.patients.show', $patient, false),
], JSON_THROW_ON_ERROR);
`,
      ],
      {
        cwd: process.cwd(),
        env: {
          ...process.env,
          UHMS_E2E_PATIENT_JSON: JSON.stringify(data),
        },
        stdio: 'pipe',
      },
    ).toString('utf8'),
  ) as Pick<TestPatient, 'id' | 'profilePath' | 'fullName'>;

  await loginAs(page, 'UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD');

  return {
    ...data,
    ...created,
  };
}
