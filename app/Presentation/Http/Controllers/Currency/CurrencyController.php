<?php

namespace App\Presentation\Http\Controllers\Currency;

use App\Application\Currency\UseCases\CreateCurrency;
use App\Application\Currency\UseCases\DeleteCurrency;
use App\Application\Currency\UseCases\GetCurrency;
use App\Application\Currency\UseCases\ListCurrencies;
use App\Application\Currency\UseCases\UpdateCurrency;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\Currency\CurrencyRequest;
use App\Presentation\Http\Resources\Currency\CurrencyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CurrencyController extends Controller
{
    /**
     * List every currency.
     */
    public function index(ListCurrencies $listCurrencies): AnonymousResourceCollection
    {
        return CurrencyResource::collection($listCurrencies->execute());
    }

    /**
     * Create a currency.
     */
    public function store(CurrencyRequest $request, CreateCurrency $createCurrency): JsonResponse
    {
        return CurrencyResource::make($createCurrency->execute($request->toData()))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a currency.
     */
    public function show(int $currency, GetCurrency $getCurrency): CurrencyResource
    {
        return CurrencyResource::make($getCurrency->execute(new CurrencyId($currency)));
    }

    /**
     * Replace every field of a currency.
     */
    public function update(CurrencyRequest $request, int $currency, UpdateCurrency $updateCurrency): CurrencyResource
    {
        return CurrencyResource::make($updateCurrency->execute(new CurrencyId($currency), $request->toData()));
    }

    /**
     * Delete a currency that no product uses.
     */
    public function destroy(int $currency, DeleteCurrency $deleteCurrency): Response
    {
        $deleteCurrency->execute(new CurrencyId($currency));

        return response()->noContent();
    }
}
