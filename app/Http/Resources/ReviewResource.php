<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'rating'=>$this->rating,
            'comment'=>$this->comment,
            'reviewer' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'seller' => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
            ],
            'createdAt'=>$this->created_at,
            'updatedAt'=>$this->updated_at,
        ];
    }
}
