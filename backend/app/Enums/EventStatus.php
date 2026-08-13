<?php

namespace App\Enums;

/**
 * The event lifecycle from the Phase 3 spec:
 * Draft → Planning → Client Approval → Execution → Completed → Archived.
 * Cancelled sits outside the happy path but any event can land there.
 */
enum EventStatus: string
{
    case Draft = 'draft';
    case Planning = 'planning';
    case ClientApproval = 'client_approval';
    case Execution = 'execution';
    case Completed = 'completed';
    case Archived = 'archived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Planning => 'Planning',
            self::ClientApproval => 'Client approval',
            self::Execution => 'Execution',
            self::Completed => 'Completed',
            self::Archived => 'Archived',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * The ordered lifecycle stages a planner can step an event through. Cancelled
     * is reachable from anywhere and is therefore not part of the linear flow.
     *
     * @return array<int, self>
     */
    public static function pipeline(): array
    {
        return [
            self::Draft,
            self::Planning,
            self::ClientApproval,
            self::Execution,
            self::Completed,
            self::Archived,
        ];
    }

    /**
     * Whether a move from this status to $target is allowed. Planners may advance
     * or step back one stage along the pipeline, jump to Cancelled, or restore a
     * cancelled event back to Planning.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($target === $this) {
            return false;
        }

        if ($target === self::Cancelled) {
            return true;
        }

        if ($this === self::Cancelled) {
            return $target === self::Planning;
        }

        $pipeline = self::pipeline();
        $from = array_search($this, $pipeline, true);
        $to = array_search($target, $pipeline, true);

        return $from !== false && $to !== false && abs($to - $from) === 1;
    }
}
