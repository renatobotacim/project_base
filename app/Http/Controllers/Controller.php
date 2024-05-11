<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;


/**
 * @OA\Server(url="http://localhost/api"),
 * @OA\Info(title="teste", version="0.0.1")
 */

class Controller extends BaseController
{

    use AuthorizesRequests, ValidatesRequests;

    public function jsonResponse($data, $statusCode = ResponseAlias::HTTP_OK)
    {
        return response()->json($data, $statusCode);
    }
}
