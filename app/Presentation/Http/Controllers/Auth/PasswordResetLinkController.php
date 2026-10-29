<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Application\Identity\UseCases\SendPasswordResetLink;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordResetLinkController extends Controller
{
    /**
     * Send a password reset link by email.
     *
     * Always responds the same, whether the email is registered or not, so it
     * can not be used to find out which emails have an account.
     *
     * @unauthenticated
     */
    public function store(ForgotPasswordRequest $request, SendPasswordResetLink $sendResetLink): JsonResponse
    {
        $sendResetLink->execute($request->email());

        // Same answer whether the email is registered or not: no user enumeration.
        return response()->json(['message' => __('passwords.sent_if_registered')]);
    }
}
