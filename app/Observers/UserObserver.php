<?php

namespace App\Observers;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\User;
use App\Services\ActivityLogService;

class UserObserver
{
    public function __construct(protected ActivityLogService $logger) {}

    public function created(User $user): void
    {
        $this->logger->log(LogModule::USERS, 'USER_CREATED', [
            'metadata' => ['email' => $user->email],
        ], $user, 'User account created');
    }

    public function updated(User $user): void
    {
        $changed = $user->getChanges();
        $original = $user->getOriginal();

        $watched = ['status', 'email', 'department_id'];
        foreach ($watched as $field) {
            if (array_key_exists($field, $changed)) {
                $newVal = $changed[$field];
                $oldVal = $original[$field] ?? null;
                $newStr = is_object($newVal) ? ($newVal->value ?? (string) $newVal) : (string) $newVal;
                $oldStr = is_object($oldVal) ? ($oldVal->value ?? (string) $oldVal) : (string) $oldVal;
                $sev = $field === 'status' && $newStr === 'inactive'
                    ? LogSeverity::WARNING
                    : LogSeverity::NOTICE;
                $this->logger->log(LogModule::USERS, 'USER_FIELD_CHANGED', [
                    'severity' => $sev,
                    'metadata' => ['field' => $field, 'from' => $oldStr, 'to' => $newStr],
                ], $user, "User {$field} changed");
            }
        }

        if (array_key_exists('password', $changed)) {
            $this->logger->logSecurity('PASSWORD_CHANGED', [
                'severity' => LogSeverity::SECURITY,
                'metadata' => ['user_id' => $user->id],
            ], $user);
        }
    }

    public function deleted(User $user): void
    {
        $this->logger->log(LogModule::USERS, 'USER_DELETED', [
            'severity' => LogSeverity::WARNING,
            'metadata' => ['email' => $user->email],
        ], $user, 'User account deleted');
    }
}
