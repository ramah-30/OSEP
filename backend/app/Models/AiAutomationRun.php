<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAutomationRun extends Model
{
    protected $fillable = [
        'ai_automation_rule_id',
        'user_id',
        'event_id',
        'summary',
        'action_type',
        'result_type',
        'result_id',
    ];

    /**
     * @return BelongsTo<AiAutomationRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AiAutomationRule::class, 'ai_automation_rule_id');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
