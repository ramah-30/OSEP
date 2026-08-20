<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'category',
        'name',
        'description',
        'icon',
        'output_format',
        'body_template',
        'variables',
        'requires_event',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'requires_event' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
