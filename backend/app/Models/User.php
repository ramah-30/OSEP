<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'account_type',
        'country',
        'avatar_url',
        'status',
        'account_claimed',
        'locale',
        'timezone',
        'theme',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'account_type' => AccountType::class,
            'status' => UserStatus::class,
            'account_claimed' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    /**
     * @return HasMany<AuthAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuthAuditLog::class);
    }

    /**
     * @return HasMany<Notification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    /**
     * @return HasOne<PlannerProfile, $this>
     */
    public function plannerProfile(): HasOne
    {
        return $this->hasOne(PlannerProfile::class);
    }

    /**
     * @return HasOne<ClientProfile, $this>
     */
    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    /**
     * @return HasOne<VendorProfile, $this>
     */
    public function vendorProfile(): HasOne
    {
        return $this->hasOne(VendorProfile::class);
    }

    /**
     * Events this planner owns.
     *
     * @return HasMany<Event, $this>
     */
    public function plannedEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'planner_id');
    }

    /**
     * The clients on this planner's roster (added standalone or picked up from
     * an event). This is what backs the planner's Clients list.
     *
     * @return BelongsToMany<User, $this>
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'planner_client', 'planner_id', 'client_id')->withTimestamps();
    }

    /**
     * Marketplace venue listings this (vendor) user owns.
     *
     * @return HasMany<MarketplaceVenue, $this>
     */
    public function ownedVenues(): HasMany
    {
        return $this->hasMany(MarketplaceVenue::class, 'owner_id');
    }

    /**
     * Saved vendor/venue shortlists this (planner) user has created.
     *
     * @return HasMany<SavedCollection, $this>
     */
    public function savedCollections(): HasMany
    {
        return $this->hasMany(SavedCollection::class, 'planner_id');
    }

    /**
     * The marketplace storefront relations for a vendor-role user.
     *
     * @return HasMany<VendorService, $this>
     */
    public function vendorServices(): HasMany
    {
        return $this->hasMany(VendorService::class, 'vendor_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<VendorPackage, $this>
     */
    public function vendorPackages(): HasMany
    {
        return $this->hasMany(VendorPackage::class, 'vendor_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<VendorPortfolio, $this>
     */
    public function vendorPortfolios(): HasMany
    {
        return $this->hasMany(VendorPortfolio::class, 'vendor_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<VendorAvailability, $this>
     */
    public function vendorAvailability(): HasMany
    {
        return $this->hasMany(VendorAvailability::class, 'vendor_id');
    }

    /**
     * Reviews left about this vendor.
     *
     * @return HasMany<Review, $this>
     */
    public function vendorReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'vendor_id')->latest();
    }

    /**
     * Reviews clients have left about this planner.
     *
     * @return HasMany<PlannerReview, $this>
     */
    public function plannerReviews(): HasMany
    {
        return $this->hasMany(PlannerReview::class, 'planner_id')->latest();
    }

    /**
     * The event this client owns (the platform models one live event per client
     * in Phase 2).
     *
     * @return HasOne<Event, $this>
     */
    public function clientEvent(): HasOne
    {
        return $this->hasOne(Event::class, 'client_id')->latest();
    }

    /**
     * Every event this client owns. A client can accumulate several once they
     * book more than one planner, so their workspace lists all of them.
     *
     * @return HasMany<Event, $this>
     */
    public function clientEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'client_id')->latest();
    }

    /**
     * Booking requests sent by this client to planners.
     *
     * @return HasMany<PlannerBookingRequest, $this>
     */
    public function bookingRequestsAsClient(): HasMany
    {
        return $this->hasMany(PlannerBookingRequest::class, 'client_id')->latest();
    }

    /**
     * Booking requests received by this planner from clients.
     *
     * @return HasMany<PlannerBookingRequest, $this>
     */
    public function bookingRequestsAsPlanner(): HasMany
    {
        return $this->hasMany(PlannerBookingRequest::class, 'planner_id')->latest();
    }

    // -----------------------------------------------------------------
    // Roles & permissions
    // -----------------------------------------------------------------

    public function hasRole(string $name): bool
    {
        return $this->roles->contains('name', $name);
    }

    /**
     * Attach a role without duplicating an existing assignment.
     */
    public function assignRole(string|Role $role): void
    {
        $role = $role instanceof Role ? $role : Role::where('name', $role)->firstOrFail();

        $this->roles()->syncWithoutDetaching([$role->id]);
        $this->unsetRelation('roles');
    }

    /**
     * Every distinct permission name granted through any of the user's roles.
     *
     * @return array<int, string>
     */
    public function permissionNames(): array
    {
        return $this->roles
            ->loadMissing('permissions')
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(string $name): bool
    {
        return in_array($name, $this->permissionNames(), true);
    }

    /**
     * @param  array<int, string>  $names
     */
    public function hasAnyPermission(array $names): bool
    {
        return (bool) array_intersect($names, $this->permissionNames());
    }

    /**
     * The profile row matching the user's account type, or null when none has
     * been created yet.
     */
    public function profile(): ?Model
    {
        return match ($this->account_type) {
            AccountType::EventPlanner => $this->plannerProfile,
            AccountType::Client => $this->clientProfile,
            AccountType::Vendor => $this->vendorProfile,
            default => null,
        };
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    // -----------------------------------------------------------------
    // Notifications — routed through the branded OSEP mail templates
    // -----------------------------------------------------------------

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
