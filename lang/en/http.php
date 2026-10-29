<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Language Lines
    |--------------------------------------------------------------------------
    */

    'lock_timeout' => 'The resource is being modified by another request. Try again.',
    'constraint_conflict' => 'The request conflicts with the current state of the data.',
    'service_unavailable' => 'The service is temporarily unavailable. Try again later.',

    'idempotency_key_invalid' => 'The Idempotency-Key header must have between 1 and 255 characters.',
    'idempotency_key_reused' => 'The Idempotency-Key was already used with a different request.',
    'idempotency_key_in_progress' => 'A request with this Idempotency-Key is still being processed.',

];
