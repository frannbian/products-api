<?php

namespace App\Presentation\Http\Controllers\Currency;

use App\Application\Currency\UseCases\CreateCurrency;
use App\Application\Currency\UseCases\DeleteCurrency;
use App\Application\Currency\UseCases\GetCurrency;
use App\Application\Currency\UseCases\ListCurrencies;
use App\Application\Currency\UseCases\UpdateCurrency;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Presentation\Http\Concurrency\EntityTag;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Pagination\PaginatorFactory;
use App\Presentation\Http\Requests\Currency\CurrencyRequest;
use App\Presentation\Http\Requests\PaginatedRequest;
use App\Presentation\Http\Resources\Currency\CurrencyResource;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\IgnoreResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;

class CurrencyController extends Controller
{
    /**
     * List currencies, paginated.
     */
    public function index(PaginatedRequest $request, ListCurrencies $listCurrencies): AnonymousResourceCollection
    {
        $page = $listCurrencies->execute($request->page(), $request->perPage());

        return CurrencyResource::collection(PaginatorFactory::fromPage($page, $request));
    }

    /**
     * Create a currency.
     *
     * @status 201
     */
    #[HeaderParameter('Idempotency-Key', 'Unique key (1-255 characters) to retry the request safely: a retry with the same key and body replays the first response instead of repeating it.', required: false, type: 'string')]
    #[Response(201, 'Currency created', type: CurrencyResource::class)]
    #[Header('ETag', 'Version of the currency, to use in If-Match and If-None-Match.', type: 'string', status: 201)]
    #[IgnoreResponse(200)]
    public function store(CurrencyRequest $request, CreateCurrency $createCurrency): JsonResponse
    {
        $created = $createCurrency->execute($request->toData());

        return EntityTag::apply(CurrencyResource::make($created)->response()->setStatusCode(201), $created->version(), $request);
    }

    /**
     * Get a currency.
     *
     * The ETag header holds the currency version, usable in If-None-Match
     * and If-Match.
     */
    #[HeaderParameter('If-None-Match', 'ETag you already have: responds 304 without a body when the currency did not change.', required: false, type: 'string')]
    #[Header('ETag', 'Version of the currency, to use in If-Match and If-None-Match.', type: 'string')]
    #[Response(304, 'Not modified: the version in If-None-Match is the current one')]
    public function show(Request $request, int $currency, GetCurrency $getCurrency): JsonResponse
    {
        $found = $getCurrency->execute(new CurrencyId($currency));

        return EntityTag::apply(CurrencyResource::make($found)->response(), $found->version(), $request);
    }

    /**
     * Replace every field of a currency.
     *
     * Send the ETag you read in If-Match: a different version responds 412.
     */
    #[HeaderParameter('If-Match', 'ETag you read: the update only succeeds if the currency still has that version (412 otherwise).', required: false, type: 'string')]
    #[Header('ETag', 'New version of the currency.', type: 'string')]
    public function update(CurrencyRequest $request, int $currency, UpdateCurrency $updateCurrency): JsonResponse
    {
        $updated = $updateCurrency->execute(
            new CurrencyId($currency),
            $request->toData(),
            EntityTag::expectedVersion($request),
        );

        return EntityTag::apply(CurrencyResource::make($updated)->response(), $updated->version(), $request);
    }

    /**
     * Delete a currency that no product uses.
     */
    public function destroy(int $currency, DeleteCurrency $deleteCurrency): HttpResponse
    {
        $deleteCurrency->execute(new CurrencyId($currency));

        return response()->noContent();
    }
}
