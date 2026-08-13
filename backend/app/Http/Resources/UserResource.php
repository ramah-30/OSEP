<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'initials' => $this->initials,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => $this->country,
            'avatar_url' => $this->avatar_url,
            'account_type' => $this->account_type->value,
            'account_type_label' => $this->account_type->label(),
            'dashboard_path' => $this->account_type->dashboardPath(),
            'status' => $this->status->value,
            'email_verified' => $this->hasVerifiedEmail(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->all()),
            'permissions' => $this->whenLoaded('roles', fn () => $this->permissionNames()),
            'preferences' => [
                'locale' => $this->locale,
                'timezone' => $this->timezone,
                'theme' => $this->theme,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
