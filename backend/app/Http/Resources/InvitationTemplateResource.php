<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\InvitationTemplate
 */
class InvitationTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'subject' => $this->subject,
            'body' => $this->body,
            'logo_url' => $this->logo_url,
            'background_url' => $this->background_url,
            'theme' => $this->theme ?? [],
            'rsvp_deadline' => $this->rsvp_deadline?->toDateString(),
            'is_default' => $this->is_default,
            'is_owned' => $this->created_by !== null,
        ];
    }
}
