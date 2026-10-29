<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Application\Identity\UseCases\SendPasswordResetLink;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Messages\PasswordResetMessage;
use App\Presentation\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;

class PasswordResetLinkController extends Controller
{
    /**
     * Send a password reset link by email.
     *
     * @unauthenticated
     */
    public function store(ForgotPasswordRequest $request, SendPasswordResetLink $sendResetLink): JsonResponse
    {
        $status = $sendResetLink->execute($request->email());

        return response()->json(['message' => PasswordResetMessage::for($status)]);
    }
}
