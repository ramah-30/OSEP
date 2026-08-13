<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTask extends Model
{
    protected $fillable = [
        'event_id',
        'assigned_to',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'position',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'status' => TaskStatus::class,
            'due_date' => 'date',
            'position' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id')->latest();
    }

    /**
     * Tasks this one waits on.
     *
     * @return BelongsToMany<EventTask, $this>
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            EventTask::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id',
        )->withTimestamps();
    }
}
