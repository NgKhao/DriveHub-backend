<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status,
            'reporter' => [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'email' => $this->reporter->email,
                'phone' => $this->reporter->phone,
                'role' => $this->reporter->role,
            ],
            'reportedUser' => [
                'id' => $this->reportedUser->id,
                'name' => $this->reportedUser->name,
                'email' => $this->reportedUser->email,
                'phone' => $this->reportedUser->phone,
                'role' => $this->reportedUser->role,
            ],
            'post' => $this->when($this->post_id, function () {
                return [
                    'id' => $this->post->id,
                    'title' => $this->post->title,
                    'status' => $this->post->status,
                ];
            }),
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
        ];
    }
}
