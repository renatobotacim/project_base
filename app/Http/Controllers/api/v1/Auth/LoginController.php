<?php

namespace App\Http\Controllers\api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthLoginRequest as AuthLoginRequestAlias;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class LoginController extends Controller
{

    /**
     * @param AuthLoginRequestAlias $request
     * @return JsonResponse
     */
    public function authenticate(AuthLoginRequestAlias $request): JsonResponse
    {
        try {

            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                if ($user->status == 'active') {
                    $user->permissions;
                    $token = $user->createToken('auth_token')->plainTextToken;
                    return $this->jsonResponse([
                        'message' => __('Authenticated successfully.'),
                        'data' => [
                            'access_token' => $token,
                            'token_type' => 'Bearer',
                            'user' => $user,
                        ],
                    ]);
                } else {
                    return $this->jsonResponse([
                        'message' => __('Acesso não autorizado.'),
                    ], HTTP_RESPONSE::HTTP_UNAUTHORIZED);
                }
            }
            return $this->jsonResponse([
                'message' => __('Invalid credentials.'),
            ], HTTP_RESPONSE::HTTP_UNAUTHORIZED);
        } catch (\Throwable $th) {
            return $this->jsonResponse([
                'message' => __('OPSS! An internal error has occurred. Try again later.'),
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {

            auth()->user()->currentAccessToken()->delete();

            return $this->jsonResponse([
                'message' => __('Logout performed successfully'),
            ], HTTP_RESPONSE::HTTP_OK);

        } catch (\Throwable $th) {
            return $this->jsonResponse([
                'message' => __('OPSS! An internal error has occurred. Try again later.'),
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
