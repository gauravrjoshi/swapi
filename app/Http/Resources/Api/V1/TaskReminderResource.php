<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskReminderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'times' => $this->times,
            'timezone' => $this->timezone,
            'last_triggered_at' => $this->last_triggered_at?->toIso8601String(),
        ];
    }
}
