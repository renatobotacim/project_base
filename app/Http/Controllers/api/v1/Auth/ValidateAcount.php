<?php

namespace App\Http\Controllers\api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class ValidateAcount extends Controller
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
     * @param $token
     * @return RedirectResponse
     */
    public function validadeAcount($token)
    {
        try {
            if ($token) {
                $userId = explode('_', base64_decode($token));
                $userId = $userId[1];
                $user = $this->service->show($userId);
                if ($user) {
                    $this->service->update($userId, ['email_verified_at' => date("Y-m-d H:i:s")]);
                    return Redirect::away('https://app.ticketk.com.br?validate=true');
                } else {
                    return Redirect::away('https://app.ticketk.com.br?validate=invalid');
                }
            } else {
                return Redirect::away('https://app.ticketk.com.br?validate=bad');
            }
        } catch (\Throwable $th) {
            return Redirect::away('https://app.ticketk.com.br?validate=false');
        }
    }

}
