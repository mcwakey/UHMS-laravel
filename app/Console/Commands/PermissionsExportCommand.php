<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Export the (user, role, permission) attestation matrix to a CSV.
 *
 *   php artisan permissions:export
 *   php artisan permissions:export --output=path/to/file.csv
 *
 * Default path: storage/reports/attestation/permissions-YYYY-MM-DD.csv
 *
 * Schedule quarterly and email the result to the compliance officer.
 */
class PermissionsExportCommand extends Command
{
    protected $signature = 'permissions:export {--output= : Override the destination path}';

    protected $description = 'Export users / roles / direct-permissions matrix to CSV for attestation.';

    public function handle(): int
    {
        $output = (string) $this->option('output');
        if ($output === '') {
            $dir = storage_path('reports' . DIRECTORY_SEPARATOR . 'attestation');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $output = $dir . DIRECTORY_SEPARATOR
                    . 'permissions-' . Carbon::now()->format('Y-m-d') . '.csv';
        }

        $fp = fopen($output, 'w');
        if ($fp === false) {
            $this->error("Cannot open {$output} for writing.");
            return self::FAILURE;
        }

        fputcsv($fp, [
            'user_id',
            'name',
            'email',
            'is_active',
            'roles',
            'direct_permissions',
            'effective_permissions',
            'created_at',
        ]);

        $count = 0;
        User::with('roles', 'permissions')
            ->orderBy('id')
            ->chunk(200, function ($users) use ($fp, &$count) {
                foreach ($users as $user) {
                    $roleNames    = $user->roles->pluck('name')->all();
                    $directPerms  = $user->permissions->pluck('name')->all();
                    $allPerms     = $user->getAllPermissions()->pluck('name')->all();
                    sort($roleNames);
                    sort($directPerms);
                    sort($allPerms);

                    fputcsv($fp, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $this->resolveIsActive($user) ? 'yes' : 'no',
                        implode('|', $roleNames),
                        implode('|', $directPerms),
                        implode('|', $allPerms),
                        $user->created_at?->toIso8601String() ?? '',
                    ]);
                    $count++;
                }
            });

        fclose($fp);

        $this->info("Wrote {$count} user row(s) to {$output}");
        return self::SUCCESS;
    }

    private function resolveIsActive(User $user): bool
    {
        foreach (['is_active', 'active', 'enabled'] as $col) {
            if (array_key_exists($col, $user->getAttributes())) {
                return (bool) $user->{$col};
            }
        }
        return true;
    }
}
