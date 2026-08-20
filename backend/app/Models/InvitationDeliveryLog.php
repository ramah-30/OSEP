<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationDeliveryLog extends Model
{
    protected $fillable = [
        'invitation_id',
        'status',
        'channel',
        'detail',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
