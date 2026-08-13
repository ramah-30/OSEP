<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedItem extends Model
{
    use BelongsToProvider;

    protected $fillable = [
        'collection_id',
        'vendor_id',
        'venue_id',
        'note',
    ];

    /**
     * @return BelongsTo<SavedCollection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(SavedCollection::class, 'collection_id');
    }
}
