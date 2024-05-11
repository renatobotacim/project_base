<?php

namespace App\Services;

use App\Helpers\Events;
use App\Http\Controllers\api\v1\CommonController;
use App\Repositories\AuthRepositoryInterface;
use App\Repositories\TicketRepositoryInterface;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;
use function Laravel\Prompts\table;
use function PHPUnit\Framework\assertFileMatchesFormatFile;

class TicketService extends Service
{

    private TicketRepositoryInterface $repository;
    private AuthService $authService;

    public function __construct(
        TicketRepositoryInterface $repository,
        AuthService               $authService
    )
    {
        $this->repository = $repository;
        $this->authService = $authService;
    }

    /**
     * show list records for model
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return object
     */
    public function index(int $paginate, int $page, string $search): object
    {
        try {
            return $this->repository->index($paginate, $page, $search);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @param int $id
     * @return object
     */
    public function show(int $id): object
    {
        try {
            return $this->repository->show($id);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @param int $userId
     * @return JsonResponse
     */
    public function showByUsers(): JsonResponse
    {
        try {
            return $this->repository->showByUsers($this->myUser(self::GET_USER_ID));
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @return JsonResponse
     */
    public function showEventsTicketsByUsers(): JsonResponse
    {

        try {

            $ticketsEvents = $this->repository->showByUsers($this->myUser(self::GET_USER_ID));

            $events = [];
            $eventsId = [];

            foreach ($ticketsEvents as $event) {
                if (!in_array($event->batch->ticketEvent->event->id, $eventsId)) {
                    unset($event->batch->ticketEvent->event->description);
                    if (isset($event->bags[0]->sales[0]->status) && $event->bags[0]->sales[0]->status == 'CONFIRMED') {
                        $eventsId[] = $event->batch->ticketEvent->event->id;
                        $events[] = $event->batch->ticketEvent->event;
                    }
                }
            }

            return $this->returnRequestSucess($events);

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * List users according paramiter ID
     * @return JsonResponse
     */
    // public function showTicketsEventByUsers($string): JsonResponse
    // {

    //     $event = new Events();
    //     $eventData = $event->showEvent($string);

    //     $data = [];

    //     $data['event'] = $event->showEvent($string);
    //     $producer = $data['event']->producer->id;
    //     unset($data['event']->producer);
    //     $data['event']->producer = ['id' => $producer];

    //     //precisa do batch_id ( listo os tickets desse batch)
    //     //$data['tickets'] = $this->repository->showByEvent($event->show->id, $this->myUser(self::GET_USER_ID));

    //     //lista as compras do usuário logado para o evento
    //     $sales = $event->showSales($eventData->id, $this->myUser(self::GET_USER_ID));


    //     foreach ($sales as $sale){
    //         $sale->tickets = $this->repository->showByEvent($eventData->id, $this->myUser(self::GET_USER_ID));
    //         $data['sales'][] = $sale;
    //     }

    //     return $this->returnRequestSucess($data);
    //     /**
    //      * $ticketsEvents = $this->repository->showByUsers($this->myUser(self::GET_USER_ID));
    //      *
    //      * dd($ticketsEvents);
    //      * return $this->returnRequestSucess($event);
    //      *
    //      *
    //      * foreach ($event->ticket_events as $t) {
    //      * }
    //      *
    //      *
    //      * $this->repository->show();
    //      *
    //      *
    //      * $events = [];
    //      * $eventsId = [];
    //      *
    //      * foreach ($ticketsEvents as $event) {
    //      * if (!in_array($event->batch->ticketEvent->event->id, $eventsId)) {
    //      * $eventsId[] = $event->batch->ticketEvent->event->id;
    //      * unset($event->batch->ticketEvent->event->description);
    //      * $events[] = $event->batch->ticketEvent->event;
    //      * }
    //      * }
    //      *
    //      * return $this->returnRequestSucess($events);
    //      **/
    //     try {
    //     } catch (\Exception $e) {
    //         return $this->returnRequestError((array)$e);
    //     }
    // }


    /**
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     */

    public function showTicketsEventByUsers($string): JsonResponse
    {
        $data = [];
        $event = new Events();
        $eventData = $event->showEvent($string);
        $data['event'] = $eventData;

        unset($data['event']['producer']);
        unset($data['event']['ticketEvents']);
        unset($data['event']['coupons']);
        unset($data['event']['map']);

        $user = auth()->user();

        $tickets = DB::table('tickets')->select('tickets.*')
            ->join('batchs as bt', 'tickets.batch_id', '=', 'bt.id')
            ->join('tickets_events as te', 'bt.ticket_event_id', '=', 'te.id')
            ->join('events as ev', 'te.event_id', '=', 'ev.id')
            ->where('ev.event', $string)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('payment_id', $user->id);
            })
            ->orderByDesc('tickets.created_at')
            ->get();


        $ticketsIds = $tickets->pluck('id');

        $bags = DB::table('bags')
            ->whereIn('ticket_id', $ticketsIds)
            ->orderByDesc('created_at')
            ->get();

        $bagsHash = $bags->pluck('bag');

        $sales = DB::table('sales')
            ->whereIn('bag', $bagsHash)
            //->whereIn('status', ['CONFIRMED', 'canceled'])
            ->orderByDesc('created_at')
            ->get();

        $data['sales'] = $sales->map(function ($sale) use ($tickets, $bags) {
            $ticketData = $tickets->filter(function ($ticket) use ($sale, $bags) {
                $userTicket = DB::table('users')->select('id', 'name', 'cpf', 'email')
                    ->where('id', $ticket->user_id)->first();

                $ticket->user = $userTicket;

                $eventTicketBatch = DB::table('batchs')
                    ->select('batchs.reference as batch_reference', 'te.name as ticket_event_name', 'sc.name as sector_name')
                    ->join('tickets_events as te', 'batchs.ticket_event_id', '=', 'te.id')
                    ->leftJoin('sectors as sc', 'te.sector_id', '=', 'sc.id')
                    ->where('batchs.id', $ticket->batch_id)
                    ->first();

                $ticket->event_ticket_batch = $eventTicketBatch;

                return $bags->contains(function ($bag) use ($ticket, $sale) {
                    return $bag->ticket_id == $ticket->id && $bag->bag == $sale->bag;
                });
            })->mapWithKeys(function ($ticket) {
                return [$ticket->id => $ticket];
            })->values();

            $userPayment = DB::table('users')->select('id', 'name', 'email')
                ->where('id', $sale->user_id)->first();
            $sale->payment_user = $userPayment;
            $sale->tickets = $ticketData->toArray();
            return $sale;
        });

        return $this->returnRequestSucess($data);
    }

    public function showTicketsUser(): JsonResponse
    {
        $user = auth()->user();

        $tickets = DB::table('tickets')->select('id','ticket','batch_id','user_id')
        ->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('payment_id', $user->id);
        })->where('status', 'available')
        ->orderByDesc('tickets.created_at')
        ->get();

        $ticketData = $tickets->map(function ($ticket) {
            $userTicket = DB::table('users')->select('name', 'cpf')
                ->where('id', $ticket->user_id)->first();

            $ticket->user = $userTicket;

            $eventTicketBatch = DB::table('batchs')
                ->select('batchs.reference as batch_reference', 'te.name as ticket_event_name', 'sc.name as sector_name', 'te.event_id')
                ->join('tickets_events as te', 'batchs.ticket_event_id', '=', 'te.id')
                ->leftJoin('sectors as sc', 'te.sector_id', '=', 'sc.id')
                ->where('batchs.id', $ticket->batch_id)
                ->first();

            $ticket->event_ticket_batch = $eventTicketBatch;

            return $ticket;
        })->mapWithKeys(function ($ticket) {
            return [$ticket->id => $ticket];
        })->values();

        $ticketData = $ticketData->groupBy('event_ticket_batch.event_id');

        $events = DB::table('events')->select('events.name', 'events.id',
        'events.date', 'events.classification',
        'ad.name as address_name',
        'ad.road as address_road', 'ad.number as address_number',
        'ad.district as address_district', 'ct.name as city_name',
        'ct.uf as city_uf','ct.state as city_state')
        ->join('address as ad', 'ad.id', '=', 'events.address_id')
        ->join('cities as ct', 'ct.id', '=', 'ad.city_id')
        ->whereIn('events.id', $ticketData->keys())
        ->get();

        $ticketData = $ticketData->toArray();

        $margin = Carbon::today()->subDays(1);

        $events = $events->filter(function ($event) use ($margin) {
            $eventDate = Carbon::parse($event->date);
            return $eventDate->isAfter($margin);
        });

        $events = collect($events)->map(function ($event) use ($ticketData) {
            $event->tickets = $ticketData[$event->id] ?? [];
            foreach ($event->tickets as $key => $ticket) {
                $ticket->qrcode = 'data:image/svg+xml;base64,'.base64_encode(QrCode::size(300)->format('svg')->generate($ticket->ticket));
            }
            return $event;
        });

        return $this->returnRequestSucess($events);
    }

    public function store(array $data): JsonResponse
    {
        try {

            $model = $this->repository->store($data);

            if (!$model) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Não foi possível realizar o registro. Tente novamente',
                        'data' => $data
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return response()->json(
                [
                    'message' => 'Dados registrados com sucesso!',
                    'data' => $model
                ], HTTP_RESPONSE::HTTP_OK
            );

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     */
    public function courtesiesStore(array $data): JsonResponse
    {

        try {

            $user = $this->authService->showEmail($data['email']);
            if (!$user) {
                $user = json_decode($this->authService->register($data)->content())->data;
            }

            $event = new Events();
            $event->showEvent($data['event']);

            if ($event->show->courtesies_used >= $event->show->courtesies) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => __('The limit of amenities available for this event has been reached!'),
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $data['ticket'] = $this->generateHash("T", 14);
            $data['user_id'] = $user->id;
            $data['status'] = 'available';
            $data['edited'] = 1;
            $data['payment_id'] = $this->myUser(self::GET_USER_ID);
            $data['courtesy'] = $data['payment_id'];

            // gerar ingresso
            $model = $this->repository->store($data);

            //ABATER DO QUANTITATIVO DO EVENTO.
            $event->generateCortesies();

            /**
             * REGISTRAR UMA VENDA PARA A CORTESIA
             */
            $dataSales['date']=now();
            $dataSales['created_at']=now();
            $dataSales['updated_at']=now();
            $dataSales['user_id']=$this->myUser(self::GET_USER_ID);
            $dataSales['value']=0;
            $dataSales['rates']=0;
            $dataSales['amount']=1;
            $dataSales['payment_type']='courtesy';
            $dataSales['status']='CONFIRMED';
            $dataSales['sale']= $this->generateHash('S',14);

            $dataSales['value_final']= 0;
            $dataSales['event_id']= $event->show->id;
            DB::table('sales')->insert($dataSales);

            $dataBag['bag'] = $dataSales['bag'];
            $dataBag['ticket_id'] = $model->id;
            $dataBag['expires_at'] = date('Y-m-d H:i:s', strtotime(now(). ' +10 minutes'));;
            DB::table('bag')->insert($dataBag);

            if (!$model) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => __('Unable to register. Try again'),
                        'data' => $data
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $myUser = $this->myUser(self::GET_USER_OBJECT);
            $model['payment'] = $myUser->name;
            $model->user->email;

            return response()->json(
                [
                    'message' => __('Data registered successfully!'),
                    'data' => $model
                ], HTTP_RESPONSE::HTTP_OK
            );

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }


    /**
     * Method created to change the record with id passed as parameter.
     * @param int $id
     * @param array $data
     * @return JsonResponse
     */
    public function update(int $id, array $data): JsonResponse
    {
        try {
            if (isset($data['user'])) {

                $check = $this->authService->checkUser($data['user']);

                if ($check->status() != 200) {
                    return $check;
                }

                $user = json_decode($check->getContent())->data;
                $data['user_id'] = $user->id;

            }


            $model = $this->repository->update($id, $data);

            if (!$model) {
                return $this->returnRequestWarning($data, 'Unable to update record. Try again!', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            return $this->returnRequestSucess($data);


        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }


    /**
     * Method created to change the record with id passed as parameter.
     * @param int $id
     * @param array $data
     * @return JsonResponse
     */
    public function updateUsers(array $data): JsonResponse
    {

        try {

            $check = $this->authService->checkUser($data);

            if ($check->status() != 200) {
                return $check;
            }

            $user = json_decode($check->getContent())->data;

            $ticket = $this->repository->show($data['ticket_id']);
            if (!$ticket) {
                return $this->returnRequestWarning($data, 'Evento não Localizado', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            $model = $this->repository->update($data['ticket_id'], [
                'user_id' => $user->id,
                'edited' => 1
            ]);

            if (!$model) {
                return $this->returnRequestWarning($data, 'Unable to update record. Try again!', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            return $this->returnRequestSucess($data, "Seu ingresso foi atualizado com sucesso! Agora, a pessoa que foi vinculada ao ingresso, já poderá usá-lo");

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }


    /**
     * Method created to delete the record with id passed as parameter.
     * @param int $id
     * @return JsonResponse
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $model = $this->repository->delete($id);

            if (!$model) {
                return response()->json(
                    [
                        'message' => __('Unable to delete record. Try again!'),
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return response()->json(
                [
                    'message' => __('Data delete successfully!'),
                ], HTTP_RESPONSE::HTTP_OK
            );

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }


    public function ticketPdf(string $ticket)
    {

        try {

            $ticket = $this->repository->show(null, $ticket);
            $batch = $ticket->batch;
            $user = $ticket->user;
            $ticketEvents = $ticket->batch->ticketEvent;
            $event = $ticket->batch->ticketEvent->event;
            $address = $ticket->batch->ticketEvent->event->address;
            $city = $ticket->batch->ticketEvent->event->address->city;
            $sector = $ticket->batch->ticketEvent->sector;

            $qrcodeEncod = 'data:image/png;base64, ' . base64_encode(QrCode::size(250)->generate($ticket->ticket));

            $carbonDate = Carbon::parse($event->date);
            $carbonDate->locale('pt_BR');
            $formattedDate = $carbonDate->isoFormat('DD MMMM, YYYY - HH[h]');

            $sectorName = $sector ? '- ' . $sector?->name : '';

            $pdf = Pdf::loadView('pdf.ticket', [
                'dateFull' => $formattedDate,
                'name' => $event->name,
                'address' => "{$address->name} :: {$address->road}, {$address->number}, {$address->district} - {$city->name} - {$city->uf}",
                'batch' => (object)['name' => "{$ticketEvents->name} {$sectorName}", 'value' => "{$batch->reference}º LOTE - VALOR R$: {$batch->value}"],
                'user' => (object)['name' => $user->name, 'cpf' => $user->cpf],
                'saleDate' => "data",
                'ticket' => $ticket->ticket,
                'qrcodeEncod' => $qrcodeEncod
            ]);
            return base64_encode($pdf->download('ticket.pdf'));

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * @param string $event
     * @param string $ticket
     * @return JsonResponse
     */
    public function checkin(string $event, string $ticket): JsonResponse
    {

        try {

            if (!$this->checkPermission(self::ROLES_VALIDACAO_DE_INGRESSOS, self::USER_LEVEL_PRODUCER)) {
                return $this->returnRequestWarning([], __('You do not have permission to perform this functionality.'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
            }

            $ticket = $this->repository->show(null, $ticket);

            if (!$ticket) {
                return $this->returnRequestWarning([], "Ingresso não localizado!", HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            $ev = DB::table('events')
                ->select('events.id', 'events.event')
                ->join('tickets_events', 'tickets_events.event_id', '=', 'events.id')
                ->join('batchs', 'batchs.ticket_event_id', '=', 'tickets_events.id')
                ->join('tickets', 'tickets.batch_id', '=', 'batchs.id')
                ->where('tickets.ticket', $ticket->ticket)
                ->groupBy('events.id')
                ->first();

            if ($ev->event != $event) {
                return $this->returnRequestWarning([], "Esse ingresso não pertence ao evento informado!", HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($ticket->status != 'available') {
                return $this->returnRequestWarning([], "Este ingresso já foi utilizado e não está mais disponível!", HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            $checkout = DB::table('tickets_checkout')->insertGetId([
                'ticket' => $ticket->ticket,
                'event' => $event,
                'checkin' => now(),
                'user_id' => $this->myUser(self::GET_USER_ID),
            ]);

            $user = DB::table('users')->find($ticket->user_id);
            $aux['name'] = $user->name;
            $aux['cpf'] = $user->cpf;
            $aux['email'] = $user->email;
            $aux['ticket'] = $ticket->ticket;
            $aux['event'] = $event;
            $aux['checkout'] = $checkout;
            $aux['status'] = $ticket->status;

            unset($user);

            return $this->returnRequestSucess($aux, "Ingresso está diponível para uso! Clique em VALIDAR para processar o ingresso");

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * @param string $event
     * @param string $ticket
     * @return JsonResponse
     */
    public function validateCheckin(array $data): JsonResponse
    {

        try {

            if (!$this->checkPermission(self::ROLES_VALIDACAO_DE_INGRESSOS, self::USER_LEVEL_PRODUCER)) {
                return $this->returnRequestWarning([], __('You do not have permission to perform this functionality.'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
            }

            $ticket = $this->repository->show(null, $data['ticket']);

            DB::table('tickets_checkout')->where('id', $data['checkout'])->where('event', $data['event'])->update([
                'validate' => now()
            ]);

            $update = $this->repository->update($ticket->id, ['status' => 'used']);

            if (!$update) {
                return $this->returnRequestWarning([], "Ocorreu algum erro ao verificar o ingresso!", HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            return $this->returnRequestSucess([], "Ingresso validado! Acesso Autorizado");

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * @param string $event
     * @param string $ticket
     * @return JsonResponse
     */
    public function clearTickets(): JsonResponse
    {
        try {
            $now = now();
            $tickets = $this->repository->listTicketsClear($now);
            $ticketsQtd = [];
            foreach ($tickets as $ticket) {
                if (array_key_exists($ticket->batch_id, $ticketsQtd)) {
                    $ticketsQtd[$ticket->batch_id]['qtd']++;
                } else {
                    $ticketsQtd[$ticket->batch_id]['id'] = $ticket->batch_id;
                    $ticketsQtd[$ticket->batch_id]['qtd'] = 1;
                }
            }
            $del = $this->repository->clearTickets($now);
            foreach ($ticketsQtd as $x) {
                DB::table('batchs')->where('id', $x['id'])->decrement('amount_reserved', $x['qtd']);
            }

            return $this->returnRequestSucess([true]);

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

}
