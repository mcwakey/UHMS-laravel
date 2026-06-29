# Journey Notification Preferences (Phase 9.7)

Per-user, per-event control over journey handoff notifications, on top of the
existing per-module `NotificationPreference` (channels/quiet-hours).

## Storage — `journey_notification_preferences`

`user_id · event · in_app_enabled · email_enabled · sms_enabled · digest_enabled`
(unique per user+event). Rows are **optional** — defaults apply when absent.

## Service — `JourneyNotificationPreferenceService`

- `allows($user, $event, $channel='in_app')` — gates on the **global channel config**
  first (`journey.notifications.{in_app,email,sms}`), then the user's row, then the
  default. Per-request cache → one query per distinct user for a batch of recipients.
- `defaultsFor($user)` — full grid for the settings UI.
- `update($user, $preferences)` — upserts + audits (`JOURNEY_NOTIFICATION_PREFERENCES_UPDATED`).

The notification service filters every recipient through `allows()` before sending.

## Defaults (no row present)

| Event | In-app | Email | SMS |
|---|---|---|---|
| assigned, acknowledged, resolved, escalated, critical, stale_dismissed, unassigned_breached | on | off | off |
| claimed, unassigned_near_breach | off | off | off |

Email/SMS are **off unless globally enabled** in config — and even then only ride
existing configured providers (no new external infrastructure). Digest is a deferred
foundation (`journey.notification_digest`, disabled).

## Settings UI

`GET/PUT /admin/settings/journey-notifications` — any authenticated user manages their
own grid (event × in-app/email/sms). Globally-disabled channels render disabled.
EN/FR.

## Safety

Notifications never contain patient identifiers (cause + destination domain only); the
action link is the capability-filtered worklist. Preferences only ever *reduce* who is
notified — they cannot escalate visibility.
