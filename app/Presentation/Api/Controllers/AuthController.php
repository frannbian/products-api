<?php

namespace App\Presentation\Api\Controllers;

use App\Application\Identity\UseCases\LoginUser;
use App\Application\Identity\UseCases\LogoutUser;
use App\Application\Identity\UseCases\RegisterUser;
use App\Http\Controllers\Controller;
use App\Presentation\Api\Request\LoginRequest;
use App\Presentation\Api\Request\RegisterRequest;
use App\Presentation\Api\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthController extends Controller
{
    public function register(
        RegisterRequest $request,
        RegisterUser $useCase
    ): JsonResponse {
        $result = $useCase->execute($request->validated());

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'user' => new UserResource($result['user']),
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(
        LoginRequest $request,
        LoginUser $useCase
    ): JsonResponse {
        $result = $useCase->execute(
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'message' => 'Inicio de sesión correcto.',
            'user' => new UserResource($result['user']),
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(
        Request $request,
        LogoutUser $useCase
    ): JsonResponse {
        $useCase->execute($request->user());

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function me(Request $request): JsonResource
    {
        return new UserResource($request->user());
    }
}