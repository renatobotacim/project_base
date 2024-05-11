<?php

namespace App\Http\Controllers\api\v1\Auth;

use App\Helpers\Log;
use App\Http\Controllers\Controller;
use App\Jobs\sendMail;
use App\Mail\RecoverPass;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;


class ForgotPasswordController extends Controller
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
    public function forgotPassword(Request $request): JsonResponse
    {

        $request->validate(['email' => 'required|email']);

        $user = $this->service->showEmail($request->email);

        if ($user) {

            $token = app('auth.password.broker')->createToken($user);

            // send mail for validade acount
            $data['link'] = env('APP_URL_FRONT') . 'reset-password/' . $token . "?email=" . $user->email;
            $data['userEmail'] = $user->email;
            $data['userName'] = $user->name;
            //sendMail::dispatch($data)->delay(now());
            $mail = new \App\Helpers\SendMail($data);
            $mail->sendRecoverPass();

            /*
            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => now()
            ]);
*/

            return $this->jsonResponse([
                'message' => __('A link to reset your password has been sent to your email.'),
            ], HTTP_RESPONSE::HTTP_OK);
        } else {
            return $this->jsonResponse([
                'message' => __('There was an error processing your request, please try again.'),
            ], HTTP_RESPONSE::HTTP_UNAUTHORIZED);
        }

        try {


        } catch (\Throwable $th) {
            return $this->jsonResponse([
                'message' => __('OPSS! An internal error has occurred. Try again later.'),
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
