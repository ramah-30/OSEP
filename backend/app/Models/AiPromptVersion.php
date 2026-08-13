<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptVersion extends Model
{
    protected $fillable = [
        'ai_prompt_template_id',
        'created_by',
        'version',
        'body',
        'note',
    ];

    /**
     * @return BelongsTo<AiPromptTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AiPromptTemplate::class, 'ai_prompt_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
