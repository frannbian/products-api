<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Application\Catalog\UseCases\GetProductImport;
use App\Application\Catalog\UseCases\StartProductImport;
use App\Domain\Catalog\Entities\ProductImport;
use App\Domain\Catalog\ValueObjects\ImportId;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\Catalog\ImportProductsRequest;
use App\Presentation\Http\Resources\Catalog\ProductImportResource;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\IgnoreResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductImportController extends Controller
{
    /** Seconds a client should wait before polling the import status again. */
    private const POLL_AFTER_SECONDS = 2;

    /**
     * Import up to 1000 products in the background.
     *
     * Every row is validated right away; the products are created by a queue
     * worker. Responds 202 Accepted with the import, whose status is at the
     * URL in the Location header.
     */
    #[HeaderParameter('Idempotency-Key', 'Unique key (1-255 characters) to retry the request safely: a retry with the same key and body replays the first response instead of repeating it.', required: false, type: 'string')]
    #[Response(202, 'Import accepted, processing in the background', type: ProductImportResource::class)]
    #[Header('Location', 'URL to poll for the import status.', type: 'string', status: 202)]
    #[Header('Retry-After', 'Seconds to wait before polling.', type: 'integer', status: 202)]
    #[IgnoreResponse(200)]
    public function store(ImportProductsRequest $request, StartProductImport $startImport): JsonResponse
    {
        $import = $startImport->execute((int) $request->user()->getAuthIdentifier(), $request->rows());

        return $this->withPollingHeaders(
            ProductImportResource::make($import)->response()->setStatusCode(202),
            $import,
        )->header('Location', route('imports.show', $import->id()->value()));
    }

    /**
     * Get the status of an import.
     *
     * While it is pending or processing, Retry-After says when to poll again.
     */
    #[Header('Retry-After', 'Present while the import is not finished: seconds to wait before polling again.', type: 'integer')]
    public function show(Request $request, string $import, GetProductImport $getImport): JsonResponse
    {
        $found = $getImport->execute(new ImportId($import), (int) $request->user()->getAuthIdentifier());

        return $this->withPollingHeaders(ProductImportResource::make($found)->response(), $found);
    }

    private function withPollingHeaders(JsonResponse $response, ProductImport $import): JsonResponse
    {
        return $import->isFinished() ? $response : $response->header('Retry-After', (string) self::POLL_AFTER_SECONDS);
    }
}
