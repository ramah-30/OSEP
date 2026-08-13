<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A 1:1 conversation between two users, stored with a canonical pair ordering
 * (user_one_id is always the smaller id). Use Conversation::between() to fetch
 * or create the single thread for any pair.
 */
class Conversation extends Model
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * @return HasMany<DirectMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(DirectMessage::class)->orderBy('created_at');
    }

    /**
     * @return HasOne<DirectMessage, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(DirectMessage::class)->latestOfMany();
    }

    /** The participant who is not the given user. */
    public function otherParticipant(int $userId): ?User
    {
        return $this->user_one_id === $userId ? $this->userTwo : $this->userOne;
    }

    public function hasParticipant(int $userId): bool
    {
        return $this->user_one_id === $userId || $this->user_two_id === $userId;
    }

    /**
     * Fetch (or create) the single conversation for an unordered pair of users.
     */
    public static function between(int $a, int $b): self
    {
        [$one, $two] = $a < $b ? [$a, $b] : [$b, $a];

        return static::firstOrCreate(['user_one_id' => $one, 'user_two_id' => $two]);
    }

    /**
     * @param  Builder<Conversation>  $query
     * @return Builder<Conversation>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(fn ($q) => $q
            ->where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId));
    }
}
