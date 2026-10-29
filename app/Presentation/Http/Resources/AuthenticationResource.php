<?php

namespace App\Presentation\Http\Resources;

use App\Application\Identity\DTOs\AuthenticationResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property AuthenticationResult $resource
 */
class AuthenticationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource->user),
            ...(new AccessTokenResource($this->resource->token))->toArray($request),
        ];
    }
}
