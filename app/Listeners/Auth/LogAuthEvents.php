<?php

namespace App\Listeners\Auth;

use App\Enums\LogSeverity;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

class LogAuthEvents
{
    public function __construct(protected ActivityLogService $logger)
    {
    }

    public function handleLogin(Login $event): void
    {
        $this->logger->logSecurity('LOGIN', [
            'severity' => LogSeverity::SECURITY,
            'metadata' => [
                'guard' => $event->guard,
                'user_id' => $event->user?->getAuthIdentifier(),
                'email' => $event->user?->email ?? null,
            ],
        ], $event->user instanceof \Illuminate\Database\Eloquent\Model ? $event->user : null);
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }
        $this->logger->logSecurity('LOGOUT', [
            'severity' => LogSeverity::INFO,
            'metadata' => [
                'guard' => $event->guard,
                'user_id' => $event->user?->getAuthIdentifier(),
            ],
        ], $event->user instanceof \Illuminate\Database\Eloquent\Model ? $event->user : null);
    }

    public function handleFailed(Failed $event): void
    {
        $this->logger->logSecurity('FAILED_LOGIN', [
            'severity' => LogSeverity::WARNING,
            'metadata' => [
                'guard' => $event->guard,
                'email' => $event->credentials['email'] ?? null,
            ],
        ]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->logger->logSecurity('PASSWORD_CHANGED', [
            'severity' => LogSeverity::SECURITY,
            'metadata' => [
                'user_id' => $event->user?->getAuthIdentifier(),
            ],
        ], $event->user instanceof \Illuminate\Database\Eloquent\Model ? $event->user : null);
    }
}
