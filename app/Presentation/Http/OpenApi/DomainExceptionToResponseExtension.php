<?php

namespace App\Presentation\Http\OpenApi;

use App\Application\Identity\Exceptions\InvalidVerificationLink;
use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Currency\Exceptions\CurrencyInUse;
use App\Domain\Currency\Exceptions\CurrencyNotFound;
use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types as OpenApiTypes;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Support\Str;

/**
 * Documents the domain exceptions that DomainExceptionRenderer turns into
 * HTTP errors other than 422 (those are documented as validation errors).
 */
class DomainExceptionToResponseExtension extends ExceptionToResponseExtension
{
    /**
     * @var array<class-string, array{int, string}>
     */
    private const RESPONSES = [
        ProductNotFound::class => [404, 'The product does not exist'],
        CurrencyNotFound::class => [404, 'The currency does not exist'],
        CurrencyInUse::class => [409, 'The currency is used by products'],
        InvalidVerificationLink::class => [403, 'The verification link is invalid'],
    ];

    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType && $this->responseFor($type) !== null;
    }

    public function toResponse(Type $type): ?Response
    {
        [$status, $description] = $this->responseFor($type);

        $body = (new OpenApiTypes\ObjectType)
            ->addProperty('message', (new OpenApiTypes\StringType)->setDescription('Error overview.'))
            ->setRequired(['message']);

        return Response::make($status)
            ->setDescription($description)
            ->setContent('application/json', Schema::fromType($body));
    }

    public function reference(ObjectType $type): Reference
    {
        return new Reference('responses', Str::afterLast($type->name, '\\'), $this->components);
    }

    /**
     * @return array{int, string}|null
     */
    private function responseFor(ObjectType $type): ?array
    {
        foreach (self::RESPONSES as $exception => $response) {
            if ($type->isInstanceOf($exception)) {
                return $response;
            }
        }

        return null;
    }
}
