<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Application\Identity\UseCases\VerifyEmail;
use App\Domain\Identity\ValueObjects\UserId;
use App\Presentation\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the email as verified from the signed link sent by email. The link
     * is opened in a browser, so it is authorized by its signature, not a token.
     */
    public function __invoke(int $id, string $hash, VerifyEmail $verifyEmail): RedirectResponse
    {
        $verifyEmail->execute(new UserId($id), $hash);

        return redirect()->away(
            config('app.frontend_url').'/dashboard?verified=1'
        );
    }
}
