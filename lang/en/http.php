<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Language Lines
    |--------------------------------------------------------------------------
    */

    'stale_version' => 'The resource was modified since you read it. Get the latest version and try again.',
    'invalid_if_match' => 'The If-Match header must be an ETag returned by the API.',
    'concurrency_conflict' => 'The resource was modified by another request at the same time. Try again.',
    'constraint_conflict' => 'The request conflicts with the current state of the data.',
    'service_unavailable' => 'The service is temporarily unavailable. Try again later.',

    'idempotency_key_invalid' => 'The Idempotency-Key header must have between 1 and 255 characters.',
    'idempotency_key_reused' => 'The Idempotency-Key was already used with a different request.',
    'idempotency_key_in_progress' => 'A request with this Idempotency-Key is still being processed.',

];
