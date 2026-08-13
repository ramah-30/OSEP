<?php

namespace App\Models;

use App\Enums\TemplateType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationTemplate extends Model
{
    protected $fillable = [
        'created_by',
        'name',
        'type',
        'subject',
        'body',
        'logo_url',
        'background_url',
        'theme',
        'rsvp_deadline',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'type' => TemplateType::class,
            'theme' => 'array',
            'rsvp_deadline' => 'date',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
