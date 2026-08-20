<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAutomationRule extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'name',
        'trigger_type',
        'threshold',
        'action_type',
        'action_config',
        'enabled',
        'last_evaluated_at',
        'last_fired_at',
    ];

    protected function casts(): array
    {
        return [
            'threshold' => 'float',
            'action_config' => 'array',
            'enabled' => 'boolean',
            'last_evaluated_at' => 'datetime',
            'last_fired_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<AiAutomationRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(AiAutomationRun::class);
    }
}
