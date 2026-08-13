<?php

namespace App\Models;

use App\Enums\CheckinStatus;
use App\Enums\InvitationStatus;
use App\Enums\RsvpStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $fillable = [
        'event_id',
        'first_name',
        'last_name',
        'full_name',
        'phone',
        'email',
        'gender',
        'category',
        'rsvp_status',
        'invitation_status',
        'checkin_status',
        'meal_preference',
        'dietary_restrictions',
        'accessibility_requirements',
        'plus_ones_allowed',
        'seat_number',
        'notes',
        'rsvp_responded_at',
        'checked_in_at',
        'archived_at',
        'rsvp_token',
    ];

    protected function casts(): array
    {
        return [
            'rsvp_status' => RsvpStatus::class,
            'invitation_status' => InvitationStatus::class,
            'checkin_status' => CheckinStatus::class,
            'plus_ones_allowed' => 'integer',
            'rsvp_responded_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Keep the split name and the denormalised full_name in step, whichever
        // side the caller set. Phase 3 code writes full_name; Phase 4 writes parts.
        static::saving(function (Guest $guest) {
            $parts = trim(implode(' ', array_filter([$guest->first_name, $guest->last_name])));

            if ($parts !== '' && ($guest->isDirty(['first_name', 'last_name']) || ! $guest->full_name)) {
                $guest->full_name = $parts;
            }

            if ((! $guest->first_name && ! $guest->last_name) && $guest->full_name) {
                $bits = preg_split('/\s+/', trim($guest->full_name), 2);
                $guest->first_name = $bits[0] ?? null;
                $guest->last_name = $bits[1] ?? null;
            }

            if (! $guest->rsvp_token) {
                $guest->rsvp_token = static::freshToken();
            }
        });
    }

    public static function freshToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (static::where('rsvp_token', $token)->exists());

        return $token;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
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
     * @return HasOne<QrCode, $this>
     */
    public function qrCode(): HasOne
    {
        return $this->hasOne(QrCode::class);
    }

    /**
     * @return HasOne<Checkin, $this>
     */
    public function checkin(): HasOne
    {
        return $this->hasOne(Checkin::class);
    }

    /**
     * @return HasMany<CommunicationLog, $this>
     */
    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class)->latest();
    }

    /**
     * @return HasOne<SeatingAssignment, $this>
     */
    public function seatingAssignment(): HasOne
    {
        return $this->hasOne(SeatingAssignment::class);
    }
}
