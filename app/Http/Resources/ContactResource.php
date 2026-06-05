<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'opted_in'   => $this->opted_in,
            'last_visit' => $this->last_visit?->toDateString(),
            'notes'      => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'tags'       => $this->whenLoaded('tags', fn() => $this->tags->map(fn($tag) => [
                'id'    => $tag->id,
                'name'  => $tag->name,
                'color' => $tag->color ?? null,
            ])),
            'activities' => $this->whenLoaded('activities'),
            'contact_notes' => $this->whenLoaded('notes'),
        ];
    }
}
