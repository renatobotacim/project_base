<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Services\AdmMasterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class AdmMasterController extends Controller
{
    private AdmMasterService $service;

    /**
     * @param AdmMasterService $service
     */
    public function __construct(AdmMasterService $service)
    {
        $this->service = $service;
    }

    /**
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        return $this->service->dashboard();
    }


}
