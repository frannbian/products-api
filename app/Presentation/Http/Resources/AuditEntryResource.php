<?php

namespace App\Presentation\Http\Resources;

use App\Application\Shared\DTOs\AuditEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property AuditEntry $resource
 */
class AuditEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'event_id' => $this->resource->eventId,
            'event' => $this->resource->event,
            'actor_id' => $this->resource->actorId,
            'payload' => $this->resource->payload,
            'occurred_at' => Carbon::instance($this->resource->occurredAt)->toJSON(),
        ];
    }
}
