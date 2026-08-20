<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class AiPromptTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'name',
        'category',
        'description',
        'variables',
        'current_version',
        'usage_count',
        'pinned',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'pinned' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AiPromptVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AiPromptVersion::class)->orderByDesc('version');
    }

    /**
     * The active body is always the highest version number — edits and rollbacks
     * both append a new top version.
     *
     * @return HasOne<AiPromptVersion, $this>
     */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(AiPromptVersion::class)->latestOfMany('version');
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
     * Parse the {{placeholder}} tokens out of a prompt body, in first-seen order.
     *
     * @return array<int, string>
     */
    public static function extractVariables(string $body): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Interpolate {{placeholders}} with the supplied values. Unfilled tokens are
     * left readable ("[client_name]") rather than blanked, so the intent survives.
     *
     * @param  array<string, mixed>  $values
     */
    public static function render(string $body, array $values): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($values) {
            $key = $m[1];
            $val = $values[$key] ?? null;

            return ($val === null || $val === '') ? '[' . Str::of($key)->replace('_', ' ') . ']' : (string) $val;
        }, $body);
    }
}
