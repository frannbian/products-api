<?php

namespace App\Presentation\Http\Concurrency;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * HTTP conditional requests built on the aggregate version: the ETag is the
 * version, If-None-Match saves bandwidth and If-Match prevents lost updates.
 */
final class EntityTag
{
    /**
     * Adds the ETag and turns the response into a 304 when the client already
     * has this version (If-None-Match).
     */
    public static function apply(JsonResponse $response, int $version, Request $request): JsonResponse
    {
        $response->setEtag((string) $version);
        $response->isNotModified($request);

        return $response;
    }

    /**
     * The version the client expects to modify, from If-Match. Null when the
     * header is missing or "*". Weak tags are accepted, lists are not.
     *
     * @throws PreconditionFailedHttpException when the header is not a version ETag
     */
    public static function expectedVersion(Request $request): ?int
    {
        $header = trim((string) $request->header('If-Match'));

        if ($header === '' || $header === '*') {
            return null;
        }

        if (! preg_match('/^(?:W\/)?"([1-9][0-9]{0,9})"$/', $header, $matches)) {
            throw new PreconditionFailedHttpException(__('http.invalid_if_match'));
        }

        return (int) $matches[1];
    }
}
