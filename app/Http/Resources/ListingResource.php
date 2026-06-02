<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'transaction_type' => $this->transaction_type,
            'type_label' => $this->typeLabel(),
            'price_label' => $this->priceLabel(),
            'price' => $this->price,
            'rent_rate' => $this->rent_rate,
            'deposit_amount' => $this->deposit_amount,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'available_quantity' => $this->availableQuantity(),
            'condition' => $this->condition,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'geo_hash' => $this->geo_hash,
            'harvest_date' => optional($this->harvest_date)->toDateString(),
            'description' => $this->description,
            'image_url' => $this->image_url,
            'is_featured' => $this->is_featured,
            'moderation_status' => $this->moderation_status,
            'lifecycle_status' => $this->lifecycle_status,
            'published_at' => optional($this->published_at)->toISOString(),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($media): array => [
                'id' => $media->id,
                'type' => $media->type,
                'url' => $media->url ?: $media->path,
                'alt_text' => $media->alt_text,
                'moderation_status' => $media->moderation_status,
            ])),
            'reviews_count' => $this->reviews_count ?? null,
            'rating_average' => isset($this->reviews_avg_rating) ? round((float) $this->reviews_avg_rating, 2) : null,
        ];
    }
}
