<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Producer\ProducerStoreRequest;
use App\Http\Requests\Producer\ProducerUpdateRequest;
use App\Services\ProducerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class ProducerController extends Controller
{
    private ProducerService $service;

    /**
     * @param ProducerService $service
     */
    public function __construct(ProducerService $service)
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
     * @param int $userId
     * @return JsonResponse
     */
    public function show(int $userId): JsonResponse
    {
        return $this->service->show($userId);
    }


    /**
     * @param int $userId
     * @return JsonResponse
     */
    public function account(): JsonResponse
    {
        return $this->service->account();

    }

    /**
     * @param int $userId
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        return $this->service->dashboard();
    }

    /**
     * @param int $userId
     * @return JsonResponse
     */
    public function profile(int $userId): JsonResponse
    {
        return $this->service->profile($userId);
    }

    public function document(int $id, string $hash): JsonResponse
    {
        return $this->service->document($id, $hash);
    }


    /**
     * @param ProducerStoreRequest $request
     * @return JsonResponse
     */
    public function store(ProducerStoreRequest $request): JsonResponse
    {
        return $this->service->store($request->all());
    }

    /**
     * @param ProducerUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(ProducerUpdateRequest $request, int $id): JsonResponse
    {
        return $this->service->update($id, $request->all());
    }

    /**
     * @param ProducerUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function suspended(Request $request, int $id): JsonResponse
    {
        return $this->service->suspended($id, $request->all());
    }

    /**
     * @param ProducerUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function accountUpdate(ProducerUpdateRequest $request): JsonResponse
    {
        return $this->service->accountUpdate($request->all());
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
