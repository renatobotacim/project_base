<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Withdrawals\WithdrawalsStoreRequest;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class WithdrawalController extends Controller
{
    private WithdrawalService $service;

    /**
     * @param WithdrawalService $service
     */
    public function __construct(WithdrawalService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $paginate = $request->input('paginate', 10);
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        return $this->service->index($paginate, $page, $search);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $paginate = $request->input('paginate', 10);
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        return $this->service->history($paginate, $page, $search);
    }

    /**
     * @param int $userId
     * @return JsonResponse
     */
    public function show(int $userId): JsonResponse
    {
        return $this->service->show($userId);
    }

    /**
     * @return JsonResponse
     */
    public function generateCode(): JsonResponse
    {
        return $this->service->generateCode();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        return $this->service->store($request->all());
    }

    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        return $this->service->update($id, $request->all());
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        return $this->service->delete($id);
    }

    /**
     * @return JsonResponse
     */
    public function liberedValues(): JsonResponse
    {
        return $this->service->liberedValues();
    }

}
