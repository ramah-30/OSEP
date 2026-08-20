<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\MilestoneStatus;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Observers\EventObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    protected static function boot(): void
    {
        parent::boot();
        self::observe(EventObserver::class);
    }
    protected $fillable = [
        'planner_id',
        'client_id',
        'event_code',
        'title',
        'event_type',
        'event_category',
        'event_date',
        'start_time',
        'end_time',
        'venue',
        'location',
        'expected_guests',
        'description',
        'theme',
        'priority',
        'internal_notes',
        'status',
        'source',
        'progress',
        'budget_total',
        'budget_spent',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'status' => EventStatus::class,
            'priority' => Priority::class,
            'progress' => 'integer',
            'expected_guests' => 'integer',
            'budget_total' => 'decimal:2',
            'budget_spent' => 'decimal:2',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /**
     * @return BelongsTo<User, $this>
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * @return HasMany<EventMilestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(EventMilestone::class)->orderBy('position');
    }

    /**
     * @return HasMany<EventTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(EventTask::class)->orderBy('position');
    }

    /**
     * @return HasMany<Guest, $this>
     */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * The full venue record. Named `venueDetail` because the events table also
     * carries a quick `venue` name string, which would shadow a `venue` relation.
     *
     * @return HasOne<Venue, $this>
     */
    public function venueDetail(): HasOne
    {
        return $this->hasOne(Venue::class);
    }

    /**
     * @return HasMany<VendorAssignment, $this>
     */
    public function vendorAssignments(): HasMany
    {
        return $this->hasMany(VendorAssignment::class);
    }

    /**
     * @return HasMany<BudgetItem, $this>
     */
    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /**
     * @return HasMany<Approval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class)->latest();
    }

    /**
     * @return HasMany<EventDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EventDocument::class)->latest();
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }

    /**
     * The subset of the activity feed the client is allowed to see — the
     * milestone moments (a vendor confirming, a contract signed, a quotation
     * sent) that power the client's "Updates" timeline.
     *
     * @return HasMany<ActivityLog, $this>
     */
    public function clientUpdates(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->where('visible_to_client', true)->latest();
    }

    /**
     * @return HasMany<VenueLayout, $this>
     */
    public function venueLayouts(): HasMany
    {
        return $this->hasMany(VenueLayout::class)->latest('updated_at');
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class)->latest();
    }

    /**
     * @return HasMany<RsvpResponse, $this>
     */
    public function rsvpResponses(): HasMany
    {
        return $this->hasMany(RsvpResponse::class)->latest('responded_at');
    }

    /**
     * @return HasMany<MealOption, $this>
     */
    public function mealOptions(): HasMany
    {
        return $this->hasMany(MealOption::class)->orderBy('sort');
    }

    /**
     * @return HasMany<QrCode, $this>
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    /**
     * @return HasMany<Checkin, $this>
     */
    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class)->latest('checked_in_at');
    }

    /**
     * @return HasMany<CommunicationLog, $this>
     */
    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class)->latest();
    }

    /**
     * @return HasOne<Budget, $this>
     */
    public function budget(): HasOne
    {
        return $this->hasOne(Budget::class);
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class)->latest();
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest();
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    // -----------------------------------------------------------------
    // Derived figures
    // -----------------------------------------------------------------

    public function budgetRemaining(): string
    {
        return bcsub((string) $this->budget_total, (string) $this->budget_spent, 2);
    }

    /**
     * Re-derive `budget_spent` from the actual costs of the budget line items and
     * persist it. This mirrors the planner-facing "Actual spend" figure (the sum
     * of every line's actual cost, regardless of status), so the client budget
     * overview — which reads the stored figure — stays in step with what the
     * planner sees as they break the budget down.
     */
    public function recalculateBudgetSpent(): void
    {
        $spent = $this->budgetItems()->sum('actual_cost');

        $this->forceFill(['budget_spent' => $spent])->save();
    }

    /**
     * Re-derive planning `progress` from completed milestones (falling back to
     * tasks when there are no milestones yet), and persist it.
     */
    public function recalculateProgress(): void
    {
        $milestones = $this->milestones()->count();

        if ($milestones > 0) {
            $done = $this->milestones()->where('status', MilestoneStatus::Completed->value)->count();
            $this->forceFill(['progress' => (int) round($done / $milestones * 100)])->save();

            return;
        }

        $tasks = $this->tasks()->count();

        if ($tasks > 0) {
            $done = $this->tasks()->where('status', TaskStatus::Completed->value)->count();
            $this->forceFill(['progress' => (int) round($done / $tasks * 100)])->save();
        }
    }

    /**
     * @return HasOne<PlannerBookingRequest, $this>
     */
    public function bookingRequest(): HasOne
    {
        return $this->hasOne(PlannerBookingRequest::class);
    }

    /**
     * Allocate the next EVT-YYYY-###### code. Called on create.
     */
    public static function nextCode(): string
    {
        $year = now()->year;
        $prefix = "EVT-{$year}-";

        // Derive the next sequence from the highest existing code for the year,
        // not a row count — counting collides once any event is deleted. The
        // codes are zero-padded, so ordering lexicographically == numerically.
        $last = static::where('event_code', 'like', $prefix.'%')
            ->orderByDesc('event_code')
            ->value('event_code');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('EVT-%d-%06d', $year, $next);
    }
}
