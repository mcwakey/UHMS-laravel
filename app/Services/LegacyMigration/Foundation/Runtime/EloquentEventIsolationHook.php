<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

use App\Models\LegacyMigration\ProtectedFoundationModel;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

final class EloquentEventIsolationHook implements SubsystemIsolationHook
{
    private ?DenyingEventDispatcher $dispatcher = null;

    private ?Dispatcher $protectedDelegate = null;

    public function __construct(private readonly ApplicationIsolationState $state) {}

    public function proofReference(): string
    {
        return 'laravel-event-facade-and-eloquent-dispatcher';
    }

    public function capture(): array
    {
        $model = Model::getEventDispatcher();
        $this->protectedDelegate = $model instanceof Dispatcher ? $model : null;

        return ['facade' => Event::getFacadeRoot(), 'model' => $model];
    }

    public function isolate(): void
    {
        $this->dispatcher = new DenyingEventDispatcher($this->state, $this->protectedDelegate);
        Event::swap($this->dispatcher);
        Model::setEventDispatcher($this->dispatcher);
        $this->state->activate(ProhibitedSubsystem::LaravelEvents);
    }

    public function isolated(): bool
    {
        return $this->dispatcher !== null
            && Event::getFacadeRoot() === $this->dispatcher
            && Model::getEventDispatcher() === $this->dispatcher;
    }

    public function restore(mixed $state): void
    {
        if (! is_array($state) || ! array_key_exists('facade', $state) || ! array_key_exists('model', $state)) {
            throw new RuntimeIsolationException('FOUNDATION_EVENT_RESTORE_INVALID', 'The event restoration state is invalid.');
        }
        Event::swap($state['facade']);
        $state['model'] === null ? Model::unsetEventDispatcher() : Model::setEventDispatcher($state['model']);
        $this->dispatcher = null;
        $this->protectedDelegate = null;
        $this->state->deactivate(ProhibitedSubsystem::LaravelEvents);
    }
}

final class DenyingEventDispatcher implements Dispatcher
{
    public function __construct(
        private readonly ApplicationIsolationState $state,
        private readonly ?Dispatcher $protectedDelegate = null,
    ) {}

    public function listen($events, $listener = null)
    {
        $this->state->deny(ProhibitedSubsystem::LaravelEvents);
    }

    public function hasListeners($eventName)
    {
        return true;
    }

    public function subscribe($subscriber)
    {
        $this->state->deny(ProhibitedSubsystem::LaravelEvents);
    }

    public function until($event, $payload = [])
    {
        if ($this->protectedEvent($payload)) {
            return $this->protectedDelegate?->until($event, $payload);
        }
        $this->state->deny(ProhibitedSubsystem::LaravelEvents);
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        if ($this->protectedEvent($payload)) {
            return $this->protectedDelegate?->dispatch($event, $payload, $halt);
        }
        $this->state->deny(ProhibitedSubsystem::LaravelEvents);
    }

    public function push($event, $payload = [])
    {
        $this->state->deny(ProhibitedSubsystem::LaravelEvents);
    }

    public function flush($event)
    {
        $this->state->deny(ProhibitedSubsystem::LaravelEvents);
    }

    public function forget($event)
    {
        $this->state->deny(ProhibitedSubsystem::LaravelEvents);
    }

    public function forgetPushed()
    {
        $this->state->deny(ProhibitedSubsystem::LaravelEvents);
    }

    private function protectedEvent(mixed $payload): bool
    {
        if ($this->protectedDelegate === null) {
            return false;
        }
        foreach (is_array($payload) ? $payload : [$payload] as $item) {
            if ($item instanceof ProtectedFoundationModel) {
                return true;
            }
        }

        return false;
    }
}
