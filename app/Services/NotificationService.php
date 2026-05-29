<?php

namespace App\Services;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Models\Department;
use App\Models\NotificationDigestQueue;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\DatabaseNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

/**
 * Central API for sending in-app database notifications.
 *
 * Workflow code should depend on this service rather than instantiating
 * notification classes directly. Handles recipient resolution, dedupe,
 * priority/module normalisation, and safe failure logging.
 */
class NotificationService
{
    public const DEFAULT_DEDUPE_MINUTES = 15;

    public function notifyUser(?User $user, array $payload, ?int $dedupeMinutes = null): bool
    {
        if (! $user || ! $user->exists) {
            return false;
        }

        $payload = $this->normalisePayload($payload);
        $dedupeMinutes = $dedupeMinutes ?? (int) config('notifications.dedupe_minutes', self::DEFAULT_DEDUPE_MINUTES);

        if ($dedupeMinutes > 0 && $this->hasRecentDuplicate($user, $payload, $dedupeMinutes)) {
            return false;
        }

        // Per-user channel preferences (DB/broadcast/mail/sms). Defaults to database only.
        $preference = $this->resolvePreference($user, $payload['module']);
        $channels = $preference?->channels ?: ['database'];
        $payload['_channels'] = $channels;

        // Quiet-hours digest: defer non-urgent notifications.
        if (
            $preference
            && $preference->digest_enabled
            && $payload['priority'] !== NotificationPriority::URGENT->value
            && $payload['priority'] !== NotificationPriority::CRITICAL->value
            && $this->inQuietHours($preference)
        ) {
            return $this->enqueueDigest($user, $payload, $preference);
        }

        try {
            $user->notify(new DatabaseNotification($payload));
            return true;
        } catch (\Throwable $e) {
            Log::warning('NotificationService.notifyUser failed', [
                'user_id' => $user->id,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function notifyUsers(iterable $users, array $payload, ?int $dedupeMinutes = null): int
    {
        $sent = 0;
        foreach ($users as $user) {
            if ($this->notifyUser($user, $payload, $dedupeMinutes)) {
                $sent++;
            }
        }
        return $sent;
    }

    public function notifyRole(string|array $roles, array $payload, ?int $dedupeMinutes = null): int
    {
        $roles = (array) $roles;
        if (empty($roles)) {
            return 0;
        }

        $users = User::query()
            ->role($roles)
            ->where('status', 'active')
            ->get();

        return $this->notifyUsers($users, $payload, $dedupeMinutes);
    }

    public function notifyPermission(string $permission, array $payload, ?int $dedupeMinutes = null): int
    {
        $perm = Permission::query()->where('name', $permission)->first();
        if (! $perm) {
            return 0;
        }

        $users = User::query()
            ->where('status', 'active')
            ->where(function ($query) use ($perm) {
                $query->whereHas('permissions', fn ($q) => $q->where('permissions.id', $perm->id))
                    ->orWhereHas('roles.permissions', fn ($q) => $q->where('permissions.id', $perm->id));
            })
            ->get();

        return $this->notifyUsers($users, $payload, $dedupeMinutes);
    }

    public function notifyDepartment(int|Department|null $department, array $payload, ?int $dedupeMinutes = null): int
    {
        $departmentId = $department instanceof Department ? $department->id : $department;
        if (! $departmentId) {
            return 0;
        }

        $users = User::query()
            ->where('status', 'active')
            ->where('department_id', $departmentId)
            ->get();

        return $this->notifyUsers($users, $payload, $dedupeMinutes);
    }

    /* ── Reads ─────────────────────────────────────────────────── */

    public function unreadCount(User $user): int
    {
        return (int) $user->unreadNotifications()->count();
    }

    public function latest(User $user, int $limit = 10): EloquentCollection
    {
        return $user->notifications()->latest()->take($limit)->get();
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->whereKey($notificationId)->first();
        if (! $notification) {
            return false;
        }
        $notification->markAsRead();
        return true;
    }

    public function markAllAsRead(User $user): int
    {
        return (int) $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /* ── Internals ─────────────────────────────────────────────── */

    protected function normalisePayload(array $payload): array
    {
        $module = $payload['module'] ?? NotificationModule::SYSTEM;
        if ($module instanceof NotificationModule) {
            $module = $module->value;
        }
        $priority = $payload['priority'] ?? NotificationPriority::NORMAL;
        if ($priority instanceof NotificationPriority) {
            $priority = $priority->value;
        }

        $payload['module'] = NotificationModule::tryFrom((string) $module)?->value ?? NotificationModule::SYSTEM->value;
        $payload['priority'] = NotificationPriority::tryFrom((string) $priority)?->value ?? NotificationPriority::NORMAL->value;
        $payload['title'] = $payload['title'] ?? null;
        $payload['message'] = $payload['message'] ?? ($payload['title'] ?? 'Notification');
        $payload['action_url'] = $payload['action_url'] ?? ($payload['url'] ?? null);
        $payload['url'] = $payload['action_url'] ?? '#';
        $payload['source_type'] = $payload['source_type'] ?? null;
        $payload['source_id'] = $payload['source_id'] ?? null;
        $payload['dedupe_key'] = $payload['dedupe_key'] ?? $this->buildDedupeKey($payload);

        return $payload;
    }

    protected function buildDedupeKey(array $payload): ?string
    {
        if (! empty($payload['source_type']) && isset($payload['source_id'])) {
            return sprintf('%s:%s:%s', $payload['module'], $payload['source_type'], $payload['source_id']);
        }
        return null;
    }

    protected function hasRecentDuplicate(User $user, array $payload, int $minutes): bool
    {
        if (empty($payload['dedupe_key'])) {
            return false;
        }

        $since = Carbon::now()->subMinutes($minutes);
        $needle = '"dedupe_key":' . json_encode($payload['dedupe_key']);

        return $user->notifications()
            ->where('created_at', '>=', $since)
            ->where('data', 'like', '%' . $needle . '%')
            ->exists();
    }

    protected function resolvePreference(User $user, string $module): ?NotificationPreference
    {
        if (! Schema::hasTable('notification_preferences')) {
            return null;
        }
        try {
            return NotificationPreference::forUser((int) $user->id, $module);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function inQuietHours(NotificationPreference $pref): bool
    {
        if (! $pref->quiet_hours_start || ! $pref->quiet_hours_end) {
            return false;
        }
        $now = now()->format('H:i:s');
        $start = $pref->quiet_hours_start;
        $end = $pref->quiet_hours_end;
        return $start < $end
            ? ($now >= $start && $now < $end)
            : ($now >= $start || $now < $end);
    }

    protected function enqueueDigest(User $user, array $payload, NotificationPreference $pref): bool
    {
        if (! Schema::hasTable('notification_digest_queue')) {
            return false;
        }
        try {
            $now = now();
            $scheduled = $now->copy()->setTimeFromTimeString($pref->quiet_hours_end);
            if ($scheduled <= $now) {
                $scheduled->addDay();
            }
            NotificationDigestQueue::create([
                'user_id' => $user->id,
                'payload' => $payload,
                'scheduled_for' => $scheduled,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('NotificationService.enqueueDigest failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
