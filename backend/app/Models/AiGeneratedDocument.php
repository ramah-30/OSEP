<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGeneratedDocument extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'ai_template_id',
        'template_key',
        'category',
        'title',
        'format',
        'content',
        'inputs',
        'status',
        'model',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'inputs' => 'array',
            'meta' => 'array',
            'status' => DocumentStatus::class,
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
     * @return BelongsTo<AiTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AiTemplate::class, 'ai_template_id');
    }

    /**
     * Planner feedback on this document.
     *
     * @return HasMany<AiFeedback, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(AiFeedback::class, 'subject_id')->where('subject_type', 'document');
    }
}
