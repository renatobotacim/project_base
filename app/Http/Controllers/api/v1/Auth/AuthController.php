<?php

namespace App\Http\Controllers\api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthStoreRequest;
use App\Http\Requests\Auth\AuthRegisterRequest;
use App\Http\Requests\Auth\AuthUpdatePasswordRequest;
use App\Http\Requests\Auth\AuthUpdateRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
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
    public function permissions(Request $request): JsonResponse
    {
        $paginate = $request->input('paginate', 10);
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        return $this->service->permissions($paginate, $page, $search);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function permissionsAdmMaster(Request $request): JsonResponse
    {
        $paginate = $request->input('paginate', 10);
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        $status = $request->input('status', 'active');
        return $this->service->permissionsAdmMaster($paginate, $page, $search, $status);
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
    public function history(int $userId): JsonResponse
    {
        return $this->service->history($userId);
    }

    /**
     * @return JsonResponse
     */
    public function showSettings(): JsonResponse
    {
        return $this->service->showSettings();
    }

    /**
     * @return JsonResponse
     */
    public function logged(): JsonResponse
    {
        return $this->service->logged();
    }

    /**
     * @param AuthRegisterRequest $request
     * @return JsonResponse
     */
    public function register(AuthStoreRequest $request): JsonResponse
    {
        return $this->service->register($request->all());
    }

    /**
     * @param AuthStoreRequest $request
     * @return JsonResponse
     */
    public function store(AuthStoreRequest $request): JsonResponse
    {
        return $this->service->store($request->all());
    }

    /**
     * @param AuthUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(AuthUpdateRequest $request, int $id): JsonResponse
    {
        return $this->service->update($id, $request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function settings(Request $request): JsonResponse
    {
        return $this->service->settings($request->all());
    }

    /**
     * @param AuthUpdatePasswordRequest $request
     * @return JsonResponse
     */
    public function updatePassword(AuthUpdatePasswordRequest $request): JsonResponse
    {
        return $this->service->updatePassword($request->all());
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
