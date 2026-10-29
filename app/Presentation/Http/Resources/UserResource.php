<?php

namespace App\Presentation\Http\Resources;

use App\Domain\Identity\Entities\User;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property User $resource
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->value(),
            'name' => $this->resource->name()->value(),
            'email' => $this->resource->email()->value(),
            'email_verified_at' => $this->formatDate($this->resource->emailVerifiedAt()),
            'created_at' => $this->formatDate($this->resource->createdAt()),
        ];
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        return $date ? Carbon::instance($date)->toJSON() : null;
    }
}
