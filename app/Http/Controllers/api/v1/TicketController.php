<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\TicketStoreRequest;
use App\Services\TicketService;
use http\Encoding\Stream\Inflate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class TicketController extends Controller
{
    private TicketService $service;

    /**
     * @param TicketService $service
     */
    public function __construct(TicketService $service)
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
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $data = $this->service->show($id);
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
     * @return JsonResponse
     */
    public function showByUsers(): JsonResponse
    {
        return $this->service->showByUsers();
    }

    /**
     * list this events on users tickets payment_id is currrent logged
     * @return JsonResponse
     */
    public function showEventsTicketsByUsers(): JsonResponse
    {
        return $this->service->showEventsTicketsByUsers();
    }

    /**
     * @param string $string
     * @return JsonResponse
     */
    public function showTicketsEventByUsers(string $string): JsonResponse
    {
        return $this->service->showTicketsEventByUsers($string);
    }

    /**
     * @param string $string
     * @return JsonResponse
     */
    public function showTicketsUser(): JsonResponse
    {
        return $this->service->showTicketsUser();
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
     * @return JsonResponse
     */
    public function courtesiesStore(Request $request): JsonResponse
    {
        return $this->service->courtesiesStore($request->all());
    }

    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {

        $data = $this->service->update($id, $request->all());
        return $this->jsonResponse([
            'message' => json_decode($data->getContent())->message,
            'data' => json_decode($data->getContent())->data ?? []
        ], $data->status());

        try {
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
    public function updateUsers(Request $request): JsonResponse
    {
        return $this->service->updateUsers($request->all());
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


    public function ticketPdf(string $ticket)
    {
        return $this->service->ticketPdf($ticket);
    }

    /**
     * @param string $event
     * @param string $ticket
     * @return JsonResponse
     */
    public function checkin(string $event, string $ticket): JsonResponse
    {
        return $this->service->checkin($event, $ticket);
    }
    /**
     * @param string $event
     * @param string $ticket
     * @return JsonResponse
     */
    public function validateCheckin(Request $request): JsonResponse
    {
        return $this->service->validateCheckin($request->all());
    }

    public function clearTickets(){
        return $this->service->clearTickets();
    }

}
