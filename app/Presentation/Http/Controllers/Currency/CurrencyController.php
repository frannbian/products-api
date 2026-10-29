<?php

namespace App\Presentation\Http\Controllers\Currency;

use App\Application\Currency\UseCases\CreateCurrency;
use App\Application\Currency\UseCases\DeleteCurrency;
use App\Application\Currency\UseCases\GetCurrency;
use App\Application\Currency\UseCases\ListCurrencies;
use App\Application\Currency\UseCases\UpdateCurrency;
use App\Domain\Currency\ValueObjects\CurrencyId;
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
     */
    #[HeaderParameter('Idempotency-Key', 'Unique key (1-255 characters) to retry the request safely: a retry with the same key and body replays the first response instead of repeating it.', required: false, type: 'string')]
    #[Response(201, 'Currency created', type: CurrencyResource::class)]
    #[IgnoreResponse(200)]
    public function store(CurrencyRequest $request, CreateCurrency $createCurrency): JsonResponse
    {
        return CurrencyResource::make($createCurrency->execute($request->toData()))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a currency.
     *
     * Send the ETag back in If-None-Match to get a 304 when nothing changed.
     */
    #[HeaderParameter('If-None-Match', 'ETag you already have: responds 304 without a body when the currency did not change.', required: false, type: 'string')]
    #[Header('ETag', 'Fingerprint of the response, to send back in If-None-Match.', type: 'string')]
    #[Response(304, 'Not modified: the ETag in If-None-Match is still current')]
    public function show(int $currency, GetCurrency $getCurrency): CurrencyResource
    {
        return CurrencyResource::make($getCurrency->execute(new CurrencyId($currency)));
    }

    /**
     * Replace every field of a currency.
     *
     * Concurrent updates of the same currency are applied one after the other.
     */
    public function update(CurrencyRequest $request, int $currency, UpdateCurrency $updateCurrency): CurrencyResource
    {
        return CurrencyResource::make($updateCurrency->execute(new CurrencyId($currency), $request->toData()));
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
