<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Application\Identity\UseCases\SendEmailVerificationNotification;
use App\Domain\Identity\ValueObjects\UserId;
use App\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request, SendEmailVerificationNotification $sendNotification): JsonResponse
    {
        $sent = $sendNotification->execute(new UserId($request->user()->getAuthIdentifier()));

        return response()->json([
            'message' => $sent ? __('auth.verification_link_sent') : __('auth.email_already_verified'),
        ]);
    }
}
