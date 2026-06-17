import { execFileSync } from 'node:child_process';
import { test, expect } from '@playwright/test';

const phpBinary = 'D:\\xampp3\\php\\php.exe';
const loginUrl = '/login';
const adminEmail = 'admin@uhms.local';
const adminPassword = 'password';

test.beforeAll(() => {
  execFileSync(
    phpBinary,
    [
      '-r',
      String.raw`
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$role = Spatie\Permission\Models\Role::firstOrCreate([
    'name' => 'Super Admin',
    'guard_name' => 'web',
]);

$user = App\Models\User::firstOrNew(['email' => 'admin@uhms.local']);
$user->forceFill([
    'first_name' => 'System',
    'last_name' => 'Administrator',
    'email' => 'admin@uhms.local',
    'phone' => '0200000000',
    'password' => Illuminate\Support\Facades\Hash::make('password'),
    'gender' => App\Enums\Gender::MALE,
    'status' => App\Enums\UserStatus::ACTIVE,
    'employee_id' => 'EMP-0001',
    'email_verified_at' => now(),
])->save();

$user->syncRoles([$role]);
`,
    ],
    { stdio: 'inherit' },
  );
});

test('admin can log in with valid credentials', async ({ page }) => {
  const emailField = page.locator('input[name="email"]');
  const passwordField = page.locator('input[name="password"]');
  const loginButton = page.getByRole('button', { name: 'Login' });

  await page.goto(loginUrl);

  await expect(emailField).toBeVisible();
  await expect(passwordField).toBeVisible();
  await expect(loginButton).toBeVisible();

  await emailField.fill(adminEmail);
  await passwordField.fill(adminPassword);
  await loginButton.click();

  await expect(page).toHaveURL(/\/admin\/dashboard$/);
});