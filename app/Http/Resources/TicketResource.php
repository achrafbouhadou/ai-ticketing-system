<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => (string)$this->id,
            'subject'         => $this->subject,
            'body'            => $this->body,
            'status'          => $this->status?->value,
            'category'        => $this->category?->value,
            'category_source' => $this->category_source,
            'explanation'     => $this->explanation,
            'confidence'      => $this->confidence !== null ? (float)$this->confidence : null,
            'note'            => $this->note,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
