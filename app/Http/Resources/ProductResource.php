<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'vendor' => [
                'id' => $this->vendor->id,
                'name' => $this->vendor->shop_name,
                'city' => $this->vendor->city,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'brand' => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ] : null,
            'price_range' => [
                'min' => $this->skus->min('price'),
                'max' => $this->skus->max('price'),
            ],
            'rating' => [
                'average' => $this->reviews_avg_rating ?? 0,
                'total' => $this->reviews_count ?? 0,
            ],
            'images' => $this->getMedia('images')->map(fn($media) => $media->getUrl()),
            'status' => $this->status,
            'featured' => $this->featured,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
