<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\CouponStoreRequest;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class CouponController extends Controller
{
    private CouponService $service;

    /**
     * @param CouponService $service
     */
    public function __construct(CouponService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $paginate = $request->input('paginate', 10);
            $search = $request->input('search', '');
            $page = $request->input('page', 1);

            $data = $this->service->index($paginate, $page, $search);
            return $this->jsonResponse([
                'message' => __('List of records queried successfully'),
                'data' => $data
            ], HTTP_RESPONSE::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->jsonResponse([
                'message' => __('OPSS! An internal error has occurred. Try again later.'),
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }
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
     * @param string $coupon
     * @param int $eventId
     * @return JsonResponse
     */
    public function apply(string $coupon, int $eventId): JsonResponse
    {
        return $this->service->apply($coupon, $eventId);
    }

    /**
     * @param CouponStoreRequest $request
     * @return JsonResponse
     */
    public function store(CouponStoreRequest $request): JsonResponse
    {
        try {
            $data = $this->service->store($request->all());
            return $this->jsonResponse([
                'message' => json_decode($data->getContent())->message,
                'data' => json_decode($data->getContent())->data ?? []
            ], $data->status());
        } catch (\Throwable $th) {
            return $this->jsonResponse([
                'message' => __('OPSS! An internal error has occurred. Try again later.'),
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {

            $data = $this->service->update($id, $request->all());
            return $this->jsonResponse([
                'message' => json_decode($data->getContent())->message,
                'data' => json_decode($data->getContent())->data ?? []
            ], $data->status());
        } catch (\Throwable $th) {
            return $this->jsonResponse([
                'message' => __('OPSS! An internal error has occurred. Try again later.'),
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $data = $this->service->delete($id);
            return $this->jsonResponse([
                'message' => json_decode($data->getContent())->message,
            ], $data->status());
        } catch (\Throwable $th) {
            return $this->jsonResponse([
                'message' => __('OPSS! An internal error has occurred. Try again later.'),
            ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
