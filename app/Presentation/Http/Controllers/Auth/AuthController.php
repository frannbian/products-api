<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Application\Identity\UseCases\GetUser;
use App\Application\Identity\UseCases\LoginUser;
use App\Application\Identity\UseCases\LogoutUser;
use App\Application\Identity\UseCases\RefreshToken;
use App\Application\Identity\UseCases\RegisterUser;
use App\Domain\Identity\ValueObjects\UserId;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\Auth\LoginRequest;
use App\Presentation\Http\Requests\Auth\RegisterRequest;
use App\Presentation\Http\Resources\AccessTokenResource;
use App\Presentation\Http\Resources\AuthenticationResource;
use App\Presentation\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUser $register): JsonResponse
    {
        $result = $register->execute($request->toData());

        return response()->json([
            'message' => __('auth.registered'),
            ...AuthenticationResource::make($result)->resolve($request),
        ], 201);
    }

    public function login(LoginRequest $request, LoginUser $login): JsonResponse
    {
        $result = $request->authenticate($login);

        return response()->json([
            'message' => __('auth.logged_in'),
            ...AuthenticationResource::make($result)->resolve($request),
        ]);
    }

    public function logout(LogoutUser $logout): JsonResponse
    {
        $logout->execute();

        return response()->json(['message' => __('auth.logged_out')]);
    }

    public function refresh(Request $request, RefreshToken $refresh): JsonResponse
    {
        return response()->json([
            'message' => __('auth.token_refreshed'),
            ...AccessTokenResource::make($refresh->execute())->resolve($request),
        ]);
    }

    public function me(Request $request, GetUser $getUser): UserResource
    {
        return UserResource::make(
            $getUser->execute(new UserId($request->user()->getAuthIdentifier()))
        );
    }
}
