import { execFileSync } from 'node:child_process';
import { requiredCredentials } from './auth';

type E2EUser = {
  email: string;
  password: string;
  role: string | null;
  employee_id: string;
};

const phpBinary = process.env.UHMS_PHP_BINARY ?? 'php';
const employeeIdPrefix = 'E2E-PERM-';

const users: E2EUser[] = [
  credentialsFor('UHMS_RECEPTION_EMAIL', 'UHMS_RECEPTION_PASSWORD', 'Receptionist', `${employeeIdPrefix}RECEPTION`),
  credentialsFor('UHMS_CASHIER_EMAIL', 'UHMS_CASHIER_PASSWORD', 'Cashier', `${employeeIdPrefix}CASHIER`),
  credentialsFor('UHMS_DOCTOR_EMAIL', 'UHMS_DOCTOR_PASSWORD', 'Doctor', `${employeeIdPrefix}DOCTOR`),
  credentialsFor('UHMS_LIMITED_EMAIL', 'UHMS_LIMITED_PASSWORD', null, `${employeeIdPrefix}LIMITED`),
];

function credentialsFor(emailEnv: string, passwordEnv: string, role: string | null, employeeId: string): E2EUser {
  const credentials = requiredCredentials(emailEnv, passwordEnv);

  return {
    email: credentials.email,
    password: credentials.password,
    role,
    employee_id: employeeId,
  };
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
    $existing = App\Models\User::withTrashed()->where('email', $data['email'])->first();

    if ($existing && ! str_starts_with((string) $existing->employee_id, $prefix)) {
        continue;
    }

    if ($existing) {
        try { $existing->syncRoles([]); } catch (Throwable $e) {}
        $existing->forceDelete();
    }

    if ($data['role'] && ! Spatie\Permission\Models\Role::where('name', $data['role'])->exists()) {
        throw new RuntimeException("Required E2E role [{$data['role']}] does not exist. Run the role seeder before E2E tests.");
    }

    $user = new App\Models\User();
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
    foreach (App\Models\User::withTrashed()->where('email', $data['email'])->get() as $user) {
        if (! str_starts_with((string) $user->employee_id, $prefix)) {
            continue;
        }

        try { $user->syncRoles([]); } catch (Throwable $e) {}
        $user->forceDelete();
    }
}
`);
}
