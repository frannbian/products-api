<?php

namespace App\Presentation\Http\Resources;

use App\Application\Identity\DTOs\AccessToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property AccessToken $resource
 */
class AccessTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource->token,
            'token_type' => $this->resource->type,
            'expires_in' => $this->resource->expiresIn,
        ];
    }
}
