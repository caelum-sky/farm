<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => optional($this->email_verified_at)->toISOString(),
            'farm_name' => $this->farm_name,
            'profile_picture' => $this->profile_picture,
            'bio' => $this->bio,
            'phone' => $this->phone,
            'phone_verified_at' => optional($this->phone_verified_at)->toISOString(),
            'location' => $this->location,
            'address' => $this->address,
            'gender' => $this->gender,
            'birthdate' => optional($this->birthdate)->toDateString(),
            'role' => $this->role,
            'status' => $this->status,
            'kyc_status' => $this->kyc_status,
            'risk_score' => $this->risk_score,
            'theme' => $this->theme,
            'profile_visibility' => $this->profile_visibility,
            'share_location' => $this->share_location,
            'organization_id' => $this->organization_id,
        ];
    }
}
