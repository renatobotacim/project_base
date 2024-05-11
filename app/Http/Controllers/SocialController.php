<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use App\Models\SocialiteProviderUser;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class SocialController extends Controller
{

    /**
     * @param string $provider
     * @return JsonResponse
     */
    public function RedirectToProvider(string $provider): JsonResponse
    {
        return response()->json([
            'message' => __('Login redirect link'),
            'data' => [
                'redirectUrl' => Socialite::driver($provider)->redirect()->getTargetUrl()
            ]
        ], HTTP_RESPONSE::HTTP_OK);
    }


    /**
     * @param $provider
     * @return Application|JsonResponse|Redirector|\Illuminate\Contracts\Foundation\Application|RedirectResponse
     */
    public function hadleProviderCallback($provider): Application|JsonResponse|Redirector|\Illuminate\Contracts\Foundation\Application|RedirectResponse
    {
        try {
            $access_token = request()->input('access_token', null);

            $providerUser = $access_token ? Socialite::driver($provider)->userFromToken($access_token)
                : Socialite::driver($provider)->stateless()->user();

            $socialiteProviderUserTable = SocialiteProviderUser::where(
                [
                    ['provider_id', '=', $providerUser->getId()],
                    ['provider', '=', $provider],
                ]
            )->first();


            if (!$socialiteProviderUserTable) {
                $user = User::where('email', $providerUser->getEmail())->first();
                if($user){

                    // $user = User::firstOrCreate([
                    //     'email' => $providerUser->getEmail(),
                    // ], [
                    //     'name' => $providerUser->getName() ?? $providerUser->getNickname() ?? ('user_' . uniqid() . rand(0, 999)),
                    // ]);

                    SocialiteProviderUser::create([
                        'provider_id' => $providerUser->getId(),
                        'provider' => $provider,
                        'user_id' => $user->id
                    ]);
                }else{
                    if($access_token){
                        return response()->json([
                            'noexist' => true
                        ]);
                    }
                    $clientUrlLogin = env('CLIENT_URL') . '/register?social=1';
                    return redirect($clientUrlLogin);
                }
            } else {
                $user = $socialiteProviderUserTable->user;
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            if ($user->status == 'active') {

                $user->permissions;

                if ($access_token) {
                    return response()->json([
                        'user' => $user,
                        'token' => $token
                    ]);
                } else {
                    $clientUrlLogin = env('CLIENT_URL') . '/login?token=' . $token;
                    return redirect($clientUrlLogin);
                }

            } else {
                return $this->jsonResponse([
                    'message' => __('Acesso não autorizado.'),
                ], HTTP_RESPONSE::HTTP_UNAUTHORIZED);
            }

        } catch (\Throwable $th) {
            return response()->json([
                'message' => __('Error when logging in. Try again!' . $th->getMessage())
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

}
