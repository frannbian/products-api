<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Application\Identity\UseCases\ResetPassword;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Messages\PasswordResetMessage;
use App\Presentation\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

class NewPasswordController extends Controller
{
    /**
     * Handle an incoming new password request.
     */
    public function store(ResetPasswordRequest $request, ResetPassword $resetPassword): JsonResponse
    {
        $status = $resetPassword->execute($request->toData());

        return response()->json(['message' => PasswordResetMessage::for($status)]);
    }
}
