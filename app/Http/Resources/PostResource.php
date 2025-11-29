<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $images = is_array($this->images) ? $this->images : [];

        return [
            'postId'        => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'price'         => (float) $this->price,
            'status'        => $this->status,  //pending,approved,rejected
            'location'      => $this->location,
            'phoneContact'  => $this->phone_contact,
            'images'        => $images,
            'carDetail'  => [
                'brand'          => $this->brand,
                'model'         => $this->model,
                'year'          => (int) $this->year,
                'mileage'       => (int) $this->mileage,
                'transmission'  => $this->transmission,
                'color'         => $this->color,
                'condition'     => $this->condition,
                'fuelType'      => $this->fuel_type,
            ],
            'createdAt'     => $this->created_at->toISOString(),
            'updatedAt'     => $this->updated_at->toISOString(),
        ];
    }
}
