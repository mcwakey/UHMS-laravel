import { execFileSync } from 'node:child_process';
import { requiredCredentials } from './auth';

type E2EUser = {
  email: string;
  password: string;
  role: string | null;
  employee_id: string;
  permissions?: string[];
};

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';
const employeeIdPrefix = 'E2E-PERM-';

const users: E2EUser[] = [
  credentialsFor('UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD', 'Receptionist', `${employeeIdPrefix}RECEPTION`, [
    'patients.view',
    'patients.create',
    'patients.edit',
    'visits.view',
    'visits.create',
    'visits.edit',
    'emergency.board.view',
    'emergency.case.create',
    'emergency.case.view',
  ]),
  credentialsFor('UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD', 'Cashier', `${employeeIdPrefix}CASHIER`, [
    'invoices.view',
    'payments.view',
    'payments.create',
    'accounts.cashier',
  ]),
  ...optionalCredentialsFor('UHMS_ACCOUNTANT_EMAIL', 'UHMS_ACCOUNTANT_PASSWORD', 'Accountant', `${employeeIdPrefix}ACCOUNTANT`, [
    'accounting.dashboard.view',
    'accounting.journals.view',
    'accounting.reports.trial_balance',
    'accounting.reports.general_ledger',
    'accounting.reports.cashbook',
    'accounting.reports.revenue_by_department',
    'accounting.settings.view',
    'accounting.failed_postings.view',
    'accounting.posting.view',
  ]),
  ...optionalCredentialsFor('UHMS_LAB_EMAIL', 'UHMS_LAB_PASSWORD', 'Lab Technician', `${employeeIdPrefix}LAB`, [
    'lab.requests.view',
    'lab.results.view',
    'lab.results.create',
    'lab.results.verify',
    'lab.tests.manage',
    'investigation.catalogue.view',
  ]),
  credentialsFor('UHMS_DOCTOR_EMAIL', 'UHMS_DOCTOR_PASSWORD', 'Doctor', `${employeeIdPrefix}DOCTOR`, [
    'patients.view',
    'visits.view',
    'emergency.board.view',
    'emergency.case.view',
    'consultations.view',
    'consultations.create',
    'consultation.request_lab',
    'consultation.view_results',
    'lab.requests.create',
    'lab.results.view',
  ]),
  credentialsFor('UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD', null, `${employeeIdPrefix}LIMITED`),
];

function credentialsFor(
  emailEnv: string,
  passwordEnv: string,
  role: string | null,
  employeeId: string,
  permissions: string[] = [],
): E2EUser {
  const credentials = requiredCredentials(emailEnv, passwordEnv);

  return {
    email: credentials.email,
    password: credentials.password,
    role,
    employee_id: employeeId,
    permissions,
  };
}

function optionalCredentialsFor(
  emailEnv: string,
  passwordEnv: string,
  role: string | null,
  employeeId: string,
  permissions: string[] = [],
): E2EUser[] {
  if (!process.env[emailEnv] && !process.env[passwordEnv]) {
    return [];
  }

  return [credentialsFor(emailEnv, passwordEnv, role, employeeId, permissions)];
}

function runPhp(script: string) {
  execFileSync(phpBinary, ['-r', script], {
    cwd: process.cwd(),
    env: {
      ...process.env,
      UHMS_E2E_USERS_JSON: JSON.stringify(users),
      UHMS_E2E_EMPLOYEE_PREFIX: employeeIdPrefix,
    },
    stdio: 'pipe',
  });
}

export function ensurePermissionE2EUsers() {
  runPhp(String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = json_decode(getenv('UHMS_E2E_USERS_JSON') ?: '[]', true, flags: JSON_THROW_ON_ERROR);
$prefix = getenv('UHMS_E2E_EMPLOYEE_PREFIX') ?: 'E2E-PERM-';

foreach ($users as $data) {
    foreach (['127.0.0.1', '::1'] as $ipAddress) {
        Illuminate\Support\Facades\RateLimiter::clear(
            Illuminate\Support\Str::transliterate(Illuminate\Support\Str::lower($data['email']) . '|' . $ipAddress)
        );
    }

    $existing = App\Models\User::withTrashed()->where('email', $data['email'])->first();

    if ($existing && ! str_starts_with((string) $existing->employee_id, $prefix)) {
        continue;
    }

    if ($data['role'] && ! Spatie\Permission\Models\Role::where('name', $data['role'])->exists()) {
        throw new RuntimeException("Required E2E role [{$data['role']}] does not exist. Run the role seeder before E2E tests.");
    }

    $permissions = $data['permissions'] ?? [];

    if ($permissions) {
        $existingPermissions = Spatie\Permission\Models\Permission::whereIn('name', $permissions)->pluck('name')->all();
        $missingPermissions = array_values(array_diff($permissions, $existingPermissions));

        if ($missingPermissions) {
            throw new RuntimeException('Required E2E permissions do not exist: ' . implode(', ', $missingPermissions));
        }
    }

    $user = $existing ?: new App\Models\User();
    if (method_exists($user, 'trashed') && $user->trashed()) {
        $user->restore();
    }

    $user->forceFill([
        'first_name' => 'E2E',
        'last_name' => $data['role'] ?: 'Limited',
        'email' => $data['email'],
        'phone' => '0200000000',
        'password' => Illuminate\Support\Facades\Hash::make($data['password']),
        'gender' => App\Enums\Gender::MALE,
        'status' => App\Enums\UserStatus::ACTIVE,
        'employee_id' => $data['employee_id'],
        'email_verified_at' => now(),
    ])->save();

    $data['role'] ? $user->syncRoles([$data['role']]) : $user->syncRoles([]);

    if ($permissions) {
        $user->givePermissionTo($permissions);
    }
}
`);
}

export function cleanupPermissionE2EUsers() {
  runPhp(String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = json_decode(getenv('UHMS_E2E_USERS_JSON') ?: '[]', true, flags: JSON_THROW_ON_ERROR);
$prefix = getenv('UHMS_E2E_EMPLOYEE_PREFIX') ?: 'E2E-PERM-';

foreach ($users as $data) {
    foreach (['127.0.0.1', '::1'] as $ipAddress) {
        Illuminate\Support\Facades\RateLimiter::clear(
            Illuminate\Support\Str::transliterate(Illuminate\Support\Str::lower($data['email']) . '|' . $ipAddress)
        );
    }

    foreach (App\Models\User::withTrashed()->where('email', $data['email'])->get() as $user) {
        if (! str_starts_with((string) $user->employee_id, $prefix)) {
            continue;
        }

        try { $user->syncRoles([]); $user->syncPermissions([]); } catch (Throwable $e) {}
        try { $user->forceDelete(); } catch (Throwable $e) {}
    }
}
`);
}
