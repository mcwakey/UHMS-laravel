<?php

namespace App\Services\Maternity\Context;

use App\Data\Maternity\MaternityContextTargetDescriptor;
use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LogModule;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Phase 14R.5 — the link lifecycle every operational maternity bridge shares.
 *
 * Identical guarantees to the 14R.2 consultation bridge:
 *   - context type and every foreign key are DERIVED from the target model;
 *   - the source record's patient must own the maternity record (fail closed);
 *   - exactly one ACTIVE link per (source, context type);
 *   - history is never deleted, only retired via `active_slot = null`;
 *   - same-target link is idempotent; a different target requires an explicit
 *     relink with a reason;
 *   - the unique index is the final race guard, and a duplicate-key race
 *     resolves to either the idempotent winner or a clear domain error.
 *
 * Subclasses supply only the source side. No subclass creates a maternity
 * record or starts a maternity workflow.
 */
abstract class AbstractMaternityLinkService
{
    public function __construct(
        protected readonly ActivityLogService $activityLog,
        protected readonly MaternityContextTargetService $targets,
    ) {}

    /* ── Contract ──────────────────────────────────────────────────────── */

    /** @return class-string<Model> the link model */
    abstract protected function linkModel(): string;

    /** The link table's source foreign-key column. */
    abstract protected function sourceColumn(): string;

    /** The patient the source record belongs to (fail-closed ownership). */
    abstract protected function sourcePatientId(Model $source): ?int;

    abstract protected function logModule(): LogModule;

    /** Prefix for LINKED / RELINKED / UNLINKED activity events. */
    abstract protected function eventPrefix(): string;

    /**
     * Identifier-only activity context for the source record.
     *
     * @return array<string, mixed>
     */
    abstract protected function logContext(Model $source): array;

    /* ── Queries ───────────────────────────────────────────────────────── */

    /** Convenience passthrough so callers need not inject the target service. */
    public function contextTypeFor(Model $target): ?ConsultationMaternityContextType
    {
        return $this->targets->contextTypeFor($target);
    }

    public function getActiveLink(Model $source, ConsultationMaternityContextType $contextType): ?Model
    {
        return $this->baseQuery($source)
            ->forContextType($contextType)
            ->active()
            ->first();
    }

    /** @return Collection<int, Model> */
    public function getActiveLinks(Model $source): Collection
    {
        $model = $this->linkModel();

        return $this->baseQuery($source)
            ->active()
            ->with($model::targetRelations())
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, Model> */
    public function getHistoricalLinks(Model $source): Collection
    {
        return $this->baseQuery($source)->historical()->orderBy('id')->get();
    }

    /* ── Mutations ─────────────────────────────────────────────────────── */

    /**
     * Link a maternity record to the source. Idempotent for the same target;
     * a different active target throws relinkRequired().
     */
    public function link(
        Model $source,
        Model $target,
        User $actor,
        ConsultationMaternityLinkRole $role = ConsultationMaternityLinkRole::PRIMARY,
        ?string $reason = null,
        array $metadata = [],
    ): Model {
        $descriptor = $this->describe($source, $target);

        return DB::transaction(function () use ($source, $descriptor, $actor, $role, $reason, $metadata) {
            $existing = $this->baseQuery($source)
                ->forContextType($descriptor->contextType)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ((int) $existing->targetId() === $descriptor->targetId) {
                    return $existing;
                }

                throw MaternityLinkException::relinkRequired();
            }

            $link = $this->createActiveRow($source, $descriptor, $actor, $role, $reason, $metadata);

            $this->logLinkAction($this->eventPrefix().'_LINKED', $link, $actor, $source);

            return $link;
        });
    }

    /**
     * Replace the active link for a context type, keeping the old row as
     * history. A reason is mandatory.
     */
    public function relink(
        Model $source,
        Model $target,
        User $actor,
        string $reason,
        ConsultationMaternityLinkRole $role = ConsultationMaternityLinkRole::PRIMARY,
        array $metadata = [],
    ): Model {
        if (trim($reason) === '') {
            throw MaternityLinkException::reasonRequired();
        }

        $descriptor = $this->describe($source, $target);

        return DB::transaction(function () use ($source, $descriptor, $actor, $role, $reason, $metadata) {
            $existing = $this->baseQuery($source)
                ->forContextType($descriptor->contextType)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($existing && (int) $existing->targetId() === $descriptor->targetId) {
                return $existing;
            }

            $previousId = $existing?->id;
            $previousTargetId = $existing?->targetId();

            if ($existing) {
                $this->retireRow($existing, $actor, $reason);
            }

            $link = $this->createActiveRow($source, $descriptor, $actor, $role, $reason, $metadata);

            $this->logLinkAction(
                $this->eventPrefix().'_RELINKED',
                $link,
                $actor,
                $source,
                $reason,
                ['previous_link_id' => $previousId, 'previous_target_id' => $previousTargetId],
            );

            return $link;
        });
    }

    /** Retire an active link. The row becomes history; it is never deleted. */
    public function unlink(
        Model $source,
        ConsultationMaternityContextType $contextType,
        User $actor,
        string $reason,
    ): Model {
        if (trim($reason) === '') {
            throw MaternityLinkException::reasonRequired();
        }

        return DB::transaction(function () use ($source, $contextType, $actor, $reason) {
            $link = $this->baseQuery($source)
                ->forContextType($contextType)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $link) {
                throw MaternityLinkException::linkNotFound();
            }

            $this->retireRow($link, $actor, $reason);

            $this->logLinkAction(
                $this->eventPrefix().'_UNLINKED',
                $link->refresh(),
                $actor,
                $source,
                $reason,
            );

            return $link;
        });
    }

    /**
     * Idempotent handoff link: reuse the same-target active link, otherwise
     * create one. Never silently replaces a DIFFERENT active target — the
     * caller is told to relink explicitly.
     */
    public function linkForHandoff(
        Model $source,
        Model $target,
        User $actor,
        array $metadata = [],
    ): Model {
        return $this->link(
            $source,
            $target,
            $actor,
            ConsultationMaternityLinkRole::HANDOFF,
            null,
            $metadata,
        );
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    protected function describe(Model $source, Model $target): MaternityContextTargetDescriptor
    {
        $patientId = $this->sourcePatientId($source);

        if ($patientId === null || (int) $patientId === 0) {
            throw MaternityLinkException::invalidTarget();
        }

        try {
            return $this->targets->describe($target, (int) $patientId);
        } catch (MaternityContextTargetException $e) {
            throw MaternityLinkException::fromTargetFailure($e);
        }
    }

    protected function baseQuery(Model $source): Builder
    {
        $model = $this->linkModel();

        return $model::query()->where($this->sourceColumn(), $source->getKey());
    }

    protected function createActiveRow(
        Model $source,
        MaternityContextTargetDescriptor $descriptor,
        User $actor,
        ConsultationMaternityLinkRole $role,
        ?string $reason,
        array $metadata,
    ): Model {
        $model = $this->linkModel();

        try {
            return $model::create($descriptor->linkPayload() + [
                $this->sourceColumn() => $source->getKey(),
                'link_role' => $role->value,
                'linked_by' => $actor->id,
                'linked_at' => now(),
                'reason' => $reason,
                'metadata' => $metadata === [] ? null : $metadata,
                'active_slot' => $model::ACTIVE_SLOT,
            ] + $this->extraColumns($source, $metadata));
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            $winner = $this->baseQuery($source)
                ->forContextType($descriptor->contextType)
                ->active()
                ->first();

            if ($winner && (int) $winner->targetId() === $descriptor->targetId) {
                return $winner;
            }

            throw MaternityLinkException::relinkRequired();
        }
    }

    /**
     * Extra source-side columns a subclass wants stored (e.g. the originating
     * admission request). Never target-derived.
     *
     * @return array<string, mixed>
     */
    protected function extraColumns(Model $source, array $metadata): array
    {
        return [];
    }

    protected function retireRow(Model $link, User $actor, string $reason): void
    {
        $link->forceFill([
            'unlinked_by' => $actor->id,
            'unlinked_at' => now(),
            'active_slot' => null,
            'reason' => $reason,
        ])->save();
    }

    protected function isUniqueViolation(QueryException $e): bool
    {
        return in_array((string) $e->getCode(), ['23000', '23505'], true);
    }

    /**
     * Identifier-only audit trail. Never clinical notes, observations or
     * maternity measurements.
     */
    protected function logLinkAction(
        string $action,
        Model $link,
        User $actor,
        Model $source,
        ?string $reason = null,
        array $extra = [],
    ): void {
        $this->activityLog->log(
            $this->logModule(),
            $action,
            $this->logContext($source) + [
                'causer' => $actor,
                'metadata' => array_filter([
                    'context_type' => $link->context_type?->value,
                    'link_role' => $link->link_role?->value,
                    'target_record_id' => $link->targetId(),
                    'pregnancy_profile_id' => $link->pregnancy_profile_id,
                    'reason' => $reason,
                ] + $extra, fn ($value) => $value !== null),
            ],
            $link,
            $action,
        );
    }
}
