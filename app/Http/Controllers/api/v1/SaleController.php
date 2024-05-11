<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\SaleStoreRequest;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class SaleController extends Controller
{
    private SaleService $service;

    /**
     * @param SaleService $service
     */
    public function __construct(SaleService $service)
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
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        return $this->service->show($id);
    }

    /**
     * @param string $sale
     * @return JsonResponse
     */
    public function verify(string $sale): JsonResponse
    {
        return $this->service->verify($sale);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function payment(Request $request): JsonResponse
    {
        return $this->service->payment($request->all());
    }

    /**
     * [MASTER]
     * MOSTRA AS MOVIMENTAÇÕES FINANCEIRA DO PRODUTRO LOGADO
     * @param Request $request
     * @return JsonResponse
     */
    public function moviment(Request $request): JsonResponse
    {
        $paginate = $request->input('paginate', 10);
        $page = $request->input('page', 1);

        $params = [
            'search' => $request->input('search', null),
            'event' => $request->input('event', null),
            'payment' => $request->input('payment', null),
            'status' => $request->input('status', null),
            'dateStart' => $request->input('dateStart', null),
            'dateEnd' => $request->input('dateEnd', null)
        ];

        return $this->service->moviment($paginate, $page, $params);
    }

    /**
     * [MASTER]
     * MOSTRA AS MOVIMENTAÇÕES FINANCEIRA DO PRODUTRO LOGADO
     * @param Request $request
     * @return JsonResponse
     */
    public function movimentDonwload(Request $request): JsonResponse
    {
        $paginate = $request->input('paginate', 10);
        $page = $request->input('page', 1);

        $params = [
            'search' => $request->input('search', null),
            'event' => $request->input('event', null),
            'payment' => $request->input('payment', null),
            'status' => $request->input('status', null),
            'dateStart' => $request->input('dateStart', null),
            'dateEnd' => $request->input('dateEnd', null)
        ];

        return $this->service->movimentDonwload($paginate, $page, $params);
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
     * @param string $sales
     * @return JsonResponse
     */
    public function checkPayment(string $sales): JsonResponse
    {
        return $this->service->checkPayment($sales);
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
     * @param Request $request
     * @return JsonResponse
     */
    public function receivedPayment(Request $request): JsonResponse
    {
        return $this->service->receivedPayment($request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function refundSales(Request $request): JsonResponse
    {
        return $this->service->refundSales($request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticateRefund(Request $request): JsonResponse
    {
        return $this->service->authenticateRefund($request->all());
    }

}
