<?php

namespace App\Http\Controllers\api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class ResetPasswordController extends Controller
{

    private AuthService $service;

    /**
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->service = $authService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:8|confirmed',
            ]);

            $user = $this->service->showEmail($request->email);
            $password = $request->password;
            $status = Password::reset(
                $request->only('email', 'password', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));
                    $user->save();
                    event(new PasswordReset($user));
                }
            );

            return $this->jsonResponse(data: [
                'message' => $status === Password::PASSWORD_RESET ? _('Your password has been successfully reset.') : _($status),
            ], statusCode: $status === Password::PASSWORD_RESET ? HTTP_RESPONSE::HTTP_OK : HTTP_RESPONSE::HTTP_BAD_REQUEST);

        } catch (\Throwable $th) {
            return $this->jsonResponse([
                'message' => __('OPSS! An internal error has occurred. Try again later.'),
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
