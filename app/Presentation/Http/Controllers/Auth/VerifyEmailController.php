<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Application\Identity\UseCases\VerifyEmail;
use App\Domain\Identity\ValueObjects\UserId;
use App\Presentation\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the email as verified from the signed link sent by email. The link
     * is opened in a browser, so it is authorized by its signature, not a token.
     *
     * @unauthenticated
     */
    public function __invoke(int $id, string $hash, VerifyEmail $verifyEmail): RedirectResponse
    {
        $verifyEmail->execute(new UserId($id), $hash);

        // Symfony's RedirectResponse instead of Laravel's: same response, but
        // Laravel's one is Responsable and sends Scramble into infinite recursion.
        return new RedirectResponse(config('app.frontend_url').'/dashboard?verified=1');
    }
}
