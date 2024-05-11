<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Common\CommonStoreRequest;
use App\Services\CommonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class CommonController extends Controller
{
    private CommonService $service;

    /**
     * @param CommonService $service
     */
    public function __construct(CommonService $service)
    {
        $this->service = $service;
    }

    /**
     * @return JsonResponse
     */
    public function showOptions(): JsonResponse
    {
        try {
            $data = $this->service->showOptions();
            return $this->jsonResponse([
                'message' => __('Record queried successfully'),
                'data' => $data
            ], HTTP_RESPONSE::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->jsonResponse([
                'message' => __('OPSS! An internal error has occurred. Try again later.'),
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
         */
    public function generateQrCode(Request $request)
    {
        $size = $request->input('size', 10);
        $data = $request->input('data', null);
        return $this->service->generateQrCode($size, $data);
    }

    /**
     * @return JsonResponse
     */
    public function getAddressCep($zipcode): JsonResponse
    {
        return $this->service->getAddressCep($zipcode);
    }

    /**
     * @return JsonResponse
     */
    public function versionApp(): JsonResponse
    {
        return $this->service->versionApp();
    }


    /**
     * @return JsonResponse
     */
    public function genereateLogSendgrid(Request $request): JsonResponse
    {
        return $this->service->genereateLogSendgrid($request->all());
    }




}
