<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Services\HitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class HitController extends Controller
{
    private HitService $service;

    /**
     * @param HitService $service
     */
    public function __construct(HitService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search', '');
        return $this->service->index($search ?? '');
    }

    /**
     * @param int $userId
     * @return JsonResponse
     */
    public function show(int $userId): JsonResponse
    {
        try {
            $data = $this->service->show($userId);
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
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        return $this->service->store($request->all());
    }

}
