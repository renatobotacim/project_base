<?php

namespace App\Services;

use App\Helpers\ExportDataCsV;
use App\Mail\RegisterUser;
use App\Models\Address;
use App\Models\Event;
use App\Repositories\EventRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use mysql_xdevapi\Collection;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;
use function Laravel\Prompts\table;
use function Termwind\renderUsing;

class EventService extends Service
{

    private EventRepositoryInterface $repository;

    public function __construct(
        EventRepositoryInterface $repository,
    )
    {
        $this->repository = $repository;
    }

    /**
     * @param int $paginate
     * @param int $page
     * @param array $params
     * @return JsonResponse
     */
    public function index(int $paginate, int $page, array $params): JsonResponse
    {
        try {

            $result = $this->repository->index($paginate, $page, $params);

            //$file = new UploadService();
            $cont = 0;
            foreach ($result as $item) {

                $result[$cont]->views = DB::table('hits')
                    ->where('event_id', $item->id)->count();
                $cont++;
            }

            return $this->returnRequestSucess($result);

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * MOSTRA OS EVENTOS PESQUISDOS NO
     * @param int $paginate
     * @param int $page
     * @param array $params
     * @return JsonResponse
     */
    public function search(int $paginate, int $page, array $params): JsonResponse
    {
        try {
            $result = $this->repository->search($paginate, $page, $params);
            return $this->returnRequestSucess($result);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * @param int $params
     * @return JsonResponse
     */
    public function panel(int $params): JsonResponse
    {
        // try {

        $result = $this->repository->panel($params);

        return $this->returnRequestSucess($result);

        // } catch (\Exception $e) {
        //     return $this->returnRequestError((array)$e);
        // }
    }

    /**
     * List users according paramiter ID
     * @param string $hash
     * @return JsonResponse
     */
    public function show(string $hash): JsonResponse
    {
        try {

            $id = null;
            $event = null;
            $slug = null;

            if (is_numeric($hash)) {
                $id = $hash;
            } else if (strpos($hash, '-')) {
                $slug = $hash;
            } else {
                $event = $hash;
            }

            $data = $this->repository->show($id, $event, $slug);
            // $file = new UploadService();
            // // $data->banner = $file->getFileHash($data->banner);

            return response()->json(
                [
                    'message' => 'Dados registrados com sucesso!',
                    'data' => $data
                ], HTTP_RESPONSE::HTTP_OK
            );

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * [PRODUCER] - route used to show event details
     * @param string $slug
     * @return JsonResponse
     */
    public function details(string $slug): JsonResponse
    {

        $data = $this->repository->show(null, null, $slug);

        $ticketsEvents = $data->ticketEvents;
        unset($data->coupons, $data->producer, $data->ticketEvents);

        $newTicketsEvents = [];

        foreach ($ticketsEvents as $t) {
            $bacthRetunr = [];

            foreach ($t->batchs as $b) {
                if (($b->amount_used + $b->amount_reserved) < $b->amount && $b->date_limit > now()) {
                    $bacthRetunr = $b;
                    break;
                }
            }


            unset($t->batchs);
            $t->batchs = [$bacthRetunr];

            if (!empty($bacthRetunr)) {
                $newTicketsEvents[] = $t;
            }

        }

        $aux = $data->toArray();
        $aux['ticket_events'] = $newTicketsEvents;

        return $this->returnRequestSucess($aux);

        try {
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * @param $paginate
     * @param $page
     * @param $search
     * @return JsonResponse
     */
    public function list($paginate, $page, $search): JsonResponse
    {
        try {

            $result = $this->repository->list($paginate, $page, $search, $this->myUser(self::GET_USER_PRODUCER));
            $file = new UploadService();
            $cont = 0;
            foreach ($result as $item) {
                if (!empty($item->banner)) {
                    //$result[$cont]->banner = $file->getFileHash($item->banner);
                }
                $cont++;
            }

            return $this->returnRequestSucess($result);

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * @param $paginate
     * @param $page
     * @param $search
     * @return JsonResponse
     */
    public function listActive(): JsonResponse
    {
        try {
            $result = $this->repository->listActive($this->myUser(self::GET_USER_PRODUCER));
            return $this->returnRequestSucess($result);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * [ADM MASTER]
     * MOSTRA OS EVENTOS QUE ESTÃO COM ALGUM TIPO DE DESTAQUE
     * @param int $paginate
     * @param int $page
     * @param array $params
     * @return JsonResponse
     */
    public function emphasis(int $paginate, int $page, array $params): JsonResponse
    {
        try {
            $data = [];

            $emphasisTypes = ['emphasis', 'banner', 'suggestion'];

            $data['indices'] = [];

            foreach ($emphasisTypes as $type) {
                $data['indices'][$type] = DB::table('events')
                    ->where('events.date', '>', now())
                    ->where('events.scheduling', '<', now())
                    ->where('events.emphasis_date_init', "<", now())
                    ->where('events.emphasis_date_finish', ">", now())
                    ->where('events.canceled', 0)
                    ->whereNotNull('events.emphasis_type')
                    ->whereJsonContains('events.emphasis_type', $type)
                    ->count();
            }

            $data['events'] = $this->repository->emphasis($paginate, $page, $params);
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * List users according paramiter ID
     * @param string $event
     * @return JsonResponse
     */
    public function dashboard(string $event): JsonResponse
    {

        try {

            $event = $this->repository->show(null, $event, null);

            $producerId = $this->myUser(self::GET_USER_PRODUCER);

            $allEvetns = DB::table('events')->select('event', 'name', 'date')->where('producer_id', $producerId)->get();

            $tickets = DB::table('tickets')
                ->select('tickets.*', 'sales.payment_type', 'sales.status as salesStatus', 'sales.value_real', 'sales.rates', 'batchs.value', 'batchs.rate', 'users.name as userName', 'users.email as userEmail', 'users.cpf as userCpf')
                ->join('batchs', 'batchs.id', '=', 'tickets.batch_id')
                ->join('tickets_events', 'tickets_events.id', '=', 'batchs.ticket_event_id')
                ->join('events', 'events.id', '=', 'tickets_events.event_id')
                ->join('users', 'users.id', '=', 'tickets.user_id')
                ->join('bags', 'bags.ticket_id', '=', 'tickets.id')
                ->join('sales', 'sales.bag', '=', 'bags.bag')
                ->where('events.id', $event->id)
                ->groupBy("tickets.id")
                ->get();

            $balance = 0;
            $lastTickets = [];
            $courtesies = [];

            foreach ($tickets as $t) {

                if ($t->salesStatus == 'CONFIRMED') {
                    $balance = $balance + $t->value;
                }
                $lastTickets[] = [
                    'created_at' => $t->created_at,
                    'ticket' => $t->ticket,
                    'status' => $t->status,
                    'email' => $t->userEmail,
                    'payment_type' => $t->payment_type,
                    'value' => $t->value,
                ];
                $courtesies[] = [
                    'created_at' => $t->created_at,
                    'ticket' => $t->ticket,
                    'rate' => $t->rate,
                    'user' => [
                        'name' => $t->userName,
                        'email' => $t->userEmail,
                        'cpf' => $t->userCpf,
                    ],
                    'payment' => DB::table('users')->select('name')->find($t->courtesy) ?? '',
                ];
            }
            $acess = DB::table('hits')->where('hits.event_id', $event->id)->get();
            $accessDay = 0;
            $chartAccess = [];
            foreach ($acess as $a) {
                if (date("Y-m-d", strtotime($a->moment)) == date("Y-m-d", strtotime(now()))) {
                    $accessDay++;
                }
                if (array_key_exists(date("Y-m-d", strtotime($a->moment)), $chartAccess)) {

                    if (array_key_exists($a->origin, $chartAccess[date("Y-m-d", strtotime($a->moment))])) {
                        $chartAccess[date("Y-m-d", strtotime($a->moment))][$a->origin]++;
                    } else {
                        $chartAccess[date("Y-m-d", strtotime($a->moment))][$a->origin] = 1;
                    }
                } else {
                    $chartAccess[date("Y-m-d", strtotime($a->moment))]['date'] = date("Y-m-d", strtotime($a->moment));
                    $chartAccess[date("Y-m-d", strtotime($a->moment))][$a->origin] = 1;
                }
            }

            $aux = $chartAccess;
            unset($chartAccess);
            $chartAccess = [];
            foreach ($aux as $x) {
                $chartAccess[] = $x;
            }

            $batchs = DB::table('batchs')
                ->select("batchs.*", 'tickets_events.*')
                ->join('tickets_events', 'tickets_events.id', '=', 'batchs.ticket_event_id')
                ->join('events', 'events.id', '=', 'tickets_events.event_id')
                ->where('events.id', $event->id)->get();

            $chartSalesType = [];
            foreach ($batchs as $b) {
                $aux['name'] = "{$b->name} - LOTE: " . $b->reference;
                $aux['qtd'] = $b->amount_used;
                $aux['total'] = $b->amount;
                $chartSalesType['tickets'][] = $aux;
            }

            $sales = DB::table('sales')
                ->select('sales.*')
                ->join('events', 'events.id', '=', 'sales.event_id')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->where('events.producer_id', $producerId)
                ->orderBy('sales.date', 'DESC')->get();


            $chatSalesPeriod = [];
            $chatPaymentTypePix = 0;
            $chatPaymentTypeCredit = 0;

            foreach ($sales as $sale) {
                if ($sale->status == "CONFIRMED") {
                    if (array_key_exists(date("m-Y", strtotime($sale->date)), $chatSalesPeriod)) {
                        $chatSalesPeriod[date("m-Y", strtotime($sale->date))]['value']++;
                    } else {
                        $chatSalesPeriod[date("m-Y", strtotime($sale->date))]['value'] = 1;
                        $chatSalesPeriod[date("m-Y", strtotime($sale->date))]['date'] = date("Y-m-d", strtotime($sale->date));
                    }
                    if ($sale->payment_type == "credit_card") {
                        $chatPaymentTypeCredit++;
                    }
                    if ($sale->payment_type == "pix") {
                        $chatPaymentTypePix++;
                    }
                }
            }

            $aux = $chatSalesPeriod;
            unset($chatSalesPeriod);
            $chatSalesPeriod = [];
            foreach ($aux as $x) {
                $chatSalesPeriod[] = $x;
            }

            $dashboard = $event;
            unset($event);

            $dashboard->ticket_sales = count($tickets);
            $dashboard->balance = $balance;
            $dashboard->access = count($acess);
            $dashboard->access_day = $accessDay;
            $dashboard->chart_sales_type = [$chartSalesType];
            $dashboard->chart_access = $chartAccess;
            $dashboard->chat_sales_period = $chatSalesPeriod;
            $dashboard->chat_payment_type = [
                'pix' => $chatPaymentTypePix,
                'credt_card' => $chatPaymentTypeCredit,
            ];
            $dashboard->last_tickets = $lastTickets;
            $dashboard->courtesies = $courtesies;

            $dashboard->sales_peak = 20;
            $dashboard->percent_sales_peak = '45%';
            $dashboard->events_list = $allEvetns;

            return $this->returnRequestSucess($dashboard);

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * List users according paramiter ID
     * @param string $event
     * @return JsonResponse
     */
    public function dashboardAdm(string $event): JsonResponse
    {

        try {

            $event = $this->repository->show(null, $event, null);
            $producerId = $this->myUser(self::GET_USER_PRODUCER);
            $tickets = DB::table('tickets')
                ->select('tickets.*', 'sales.payment_type', 'sales.status as salesStatus', 'batchs.value', 'batchs.rate', 'users.name as userName', 'users.email as userEmail', 'users.cpf as userCpf')
                ->join('batchs', 'batchs.id', '=', 'tickets.batch_id')
                ->join('tickets_events', 'tickets_events.id', '=', 'batchs.ticket_event_id')
                ->join('events', 'events.id', '=', 'tickets_events.event_id')
                ->join('users', 'users.id', '=', 'tickets.user_id')
                ->join('bags', 'bags.ticket_id', '=', 'tickets.id')
                ->join('sales', 'sales.bag', '=', 'bags.bag')
                ->where('events.id', $event->id)
                ->groupBy("tickets.id")
                ->get();

            $balance = 0;
            $salesTickets = 0;
            $courtesies = 0;
            $courtesies = 0;

            foreach ($tickets as $t) {
                if ($t->salesStatus == 'CONFIRMED') {
                    $salesTickets++;
                }
                if ($t->courtesy) {
                    $courtesies++;
                }
            }

            $event->salesTickets = $salesTickets;
            $event->courtesies = $courtesies;
            $event->tickets = $tickets;

            return $this->returnRequestSucess($event);


        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * [ADM MASTER]
     * @param string $event
     * @return JsonResponse
     */
    public function best(string $event): JsonResponse
    {

        $event = $this->repository->show(null, $event, null);
        $data['event'] = $event;

        $acess = DB::table('hits')->where('hits.event_id', $event->id)->get();

        $accessDay = 0;
        $chartAccess = [];

        foreach ($acess as $a) {
            if (date("Y-m-d", strtotime($a->moment)) == date("Y-m-d", strtotime(now()))) {
                $accessDay++;
            }
            if (array_key_exists(date("Y-m-d", strtotime($a->moment)), $chartAccess)) {
                $chartAccess[date("Y-m-d", strtotime($a->moment))]['hits']++;
            } else {
                $chartAccess[date("Y-m-d", strtotime($a->moment))]['date'] = date("Y-m-d", strtotime($a->moment));
                $chartAccess[date("Y-m-d", strtotime($a->moment))]['hits'] = 1;
            }
        }

        $aux = $chartAccess;
        unset($chartAccess);
        $chartAccess = [];
        foreach ($aux as $x) {
            $chartAccess[] = $x;
        }

        $data['history'] = $chartAccess;

        return $this->returnRequestSucess($data);
        try {
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * List users according paramiter ID
     * @param string $event
     * @return JsonResponse
     */
    public function donwloadSales(string $event): JsonResponse
    {

        try {

            $event = $this->repository->show(null, $event, null);
            $sales = DB::table('tickets')
                ->select('tickets.ticket', 'tickets.checking', 'tickets.status', 'tickets.discount', 'sales.payment_type', 'sales.date', 'sales.sale', 'sales.status as salesStatus', 'sales.value_real', 'sales.rates', 'batchs.value', 'batchs.rate', 'bags.bag', 'users.name as userName', 'users.email as userEmail', 'users.cpf as userCpf')
                ->join('batchs', 'batchs.id', '=', 'tickets.batch_id')
                ->join('tickets_events', 'tickets_events.id', '=', 'batchs.ticket_event_id')
                ->join('events', 'events.id', '=', 'tickets_events.event_id')
                ->join('users', 'users.id', '=', 'tickets.user_id')
                ->join('bags', 'bags.ticket_id', '=', 'tickets.id')
                ->join('sales', 'sales.bag', '=', 'bags.bag')
                ->where('events.id', $event->id)
                ->groupBy("tickets.id")
                ->get();


            $data = [];

            $paymentType = ['credit_card' => "CARTÃO", "pix" => "PIX"];
            $status = ['reserved' => "RESERVADO", "available" => "DISPONÍVEL", 'used' => 'USADO', "canceled" => "CANCELADO", "won" => "GANHADO"];
            foreach ($sales as $sale) {

                $aux['name'] = $sale->userName;
                $aux['userCpf'] = $sale->userCpf ?? '';
                $aux['email'] = $sale->userEmail ?? '';
                $aux['ticket'] = $sale->ticket;
                $aux['status'] = $status[$sale->status];
                $aux['checking'] = $sale->checking;
                $aux['date'] = $sale->date;
                $aux['payment_type'] = $paymentType[$sale->payment_type];
                $aux['value'] = "R$ " . $sale->value;
                $aux['discount'] = "R$ " . $sale->discount;

                $aux['sale'] = $sale->sale;
                $aux['bag'] = $sale->bag;

                $data[] = $aux;
            }

            $header = ['NOME', 'CPF', 'EMAIL', 'INGRESSO', 'STATUS', 'CHECKOUT', 'DATA COMPRA', 'TIPO', 'VALOR', 'DESCONTO', 'SALES', 'SACOLA'];

            $exp = new ExportDataCsV();
            $file = $exp->getExport($event->event, $header, $data);

            return $this->returnRequestSucess([
                'file' => $file
            ]);

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * List users according paramiter ID
     * @param int $mapId
     * @return JsonResponse
     */
    public function mapTickets(?int $mapId = null): JsonResponse
    {
        try {
            return response()->json(
                [
                    'message' => 'Dados registrados com sucesso!',
                    'data' => $this->repository->mapTickets($mapId, $this->myUser(self::GET_USER_PRODUCER))
                ], HTTP_RESPONSE::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     */
    public function store(array $data): JsonResponse
    {

        if (!$this->checkPermission(self::ROLES_CRIAR_NOVOS_EVENTOS, self::USER_LEVEL_PRODUCER)) {
            return $this->returnRequestWarning([], "You do not have permission to perform this functionality.", HTTP_RESPONSE::HTTP_UNAUTHORIZED);
        }
        DB::beginTransaction();

        $data['event'] = $this->generateHash('E', 14);
        $data['slug'] = $this->slugify($data['name']);
        $data['producer_id'] = $this->myUser(self::GET_USER_PRODUCER);


        if (isset($data['banner']) && !empty($data['banner'])) {
            $file = new UploadService();
            $data['banner'] = $file->uploadFile($data['banner'], $data['event']);
        }

        if (isset($data['emphasis_value'])) {
            $days = $this->countDays(date("Y-m-d"), $data['date']);
            $data['emphasis_rate'] = (float)($data['emphasis_value'] / $days) / 100;
        }

        $data['local'] = $this->slugify($data['name']);

        $model = $this->repository->store($data);

        if (isset($data['coupons'])) {
            $model->coupons()->createMany($data['coupons']);
        }

        $ticketEvents = $model->ticketEvents()->createMany($data['ticketsEvents']);
        $y = 0;

        //verificar a taxa de ingresso depois conforme cada produtor deseja.
        $rate = self::RATE_TICKET;
        $rateValueMin = self::RATE_VALUE_MIN;

        foreach ($ticketEvents as $x) {

            $dataBatch = collect($data['ticketsEvents'][$y++]['batchs']);

            $dataBatch = $dataBatch->map(function ($item, $key) use ($rate, $rateValueMin) {
                $value = (float)str_replace(['.', ','], ['', '.'], $item['value']);
                return [
                    ...$item,
                    'batch' => $this->generateHash('B', 14),
                    'rate' => ($value * $rate > $rateValueMin) ? ($value * $rate) : $rateValueMin,
                    'reference' => $key + 1
                ];
            })->toArray();

            $x->batchs()->createMany($dataBatch);
        }

        if (!$model) {
            DB::rollBack();
            return $this->returnRequestWarning($model, 'Não foi possível realizar o registro. Tente novamente', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::commit();

        $model = $this->repository->show($model->id);

        return $this->returnRequestSucess($model);
        try {
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnRequestError((array)$e);
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


        if (!$this->checkPermission(self::ROLES_EDITAR_EVENTOS_CADASTRADOS, self::USER_LEVEL_PRODUCER)) {
            return $this->returnRequestWarning(null, "You do not have permission to perform this functionality.", HTTP_RESPONSE::HTTP_UNAUTHORIZED);
        }

        $event = $this->repository->show($id);

        if ($event->date < Carbon::today()) {
            return $this->returnRequestWarning([], "You cannot edit an event that has already occurred.", HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (isset($data['emphasis_value'])) {
            $days = $this->countDays(date("Y-m-d"), $data['date']);
            $data['emphasis_rate'] = (float)($data['emphasis_value'] / $days) / 100;
        }

        unset($data['banner']);

        if (isset($data['banner_file'])) {
            $file = new UploadService();
            $data['banner'] = $file->uploadFile($data['banner_file'], $data['event']);
        }


        if ($event->scheduling > now()) {

            if (isset($data['name'])) {
                $data['slug'] = $this->slugify($data['name']);
            }
            $model = $this->repository->update($id, $data);
        } else {
            $coupons = collect($event->coupons)->pluck('id')->toArray();
            foreach ($data['coupons'] as $x) {
                if (isset($x['id'])) {
                    unset($x['created_at'], $x['updated_at'], $x['event_id']);
                    ($event->coupons->find($x['id']))->update($x);
                } else {
                    $event->coupons()->create($x);
                }
            }
            $model = $this->repository->update($id, ["description" => $data['description']]);
        }

        if (!$model) {
            return $this->returnRequestWarning([], _('Unable to update record. Try again!'), HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($event->scheduling > now()) {

            $event->coupons()->delete(collect($event->coupons)->pluck('id')->toArray());
            $event->coupons()->createMany($data['coupons'] ?? []);

            $event->ticketEvents()->delete(collect($event->ticketEvents)->pluck('id')->toArray());
            $ticketEvents = $event->ticketEvents()->createMany($data['ticketsEvents']);

            $y = 0;
            $rate = self::RATE_TICKET;
            $rateValueMin = self::RATE_VALUE_MIN;

            foreach ($ticketEvents as $x) {

                $dataBatch = collect($data['ticketsEvents'][$y++]['batchs']);

                $dataBatch = $dataBatch->map(function ($item, $key) use ($rate, $rateValueMin) {
                    $value = (float)str_replace(['.', ','], ['', '.'], $item['value']);
                    return [
                        ...$item,
                        'batch' => $this->generateHash('B', 14),
                        'rate' => ($value * $rate > $rateValueMin) ? ($value * $rate) : $rateValueMin,
                        'reference' => $key + 1
                    ];
                })->toArray();

                $x->batchs()->createMany($dataBatch);
            }
        }
        try {
            return $this->returnRequestSucess($this->repository->show($id));
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * Method created to change the record with id passed as parameter.
     * @param int $id
     * @param array $data
     * @return JsonResponse
     */
    public function canceled(int $id, array $data): JsonResponse
    {
        try {

            /**
             *******************************************************************************************************
             * CHECK PERMISSIONS
             *******************************************************************************************************
             */
            if ($this->myUser(self::GET_USER_LEVEL) == 3) {
                if (!$this->checkPermission(7, self::USER_LEVEL_ADMIN) && !$this->checkPermission(9, self::USER_LEVEL_ADMIN)) {
                    return $this->returnRequestWarning([], __('You do not have permission to perform this functionality.'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
                }
            }
            /**
             *******************************************************************************************************
             */

            //check data user
            $user = $this->myUser(self::GET_USER_OBJECT);
            if (!Hash::check($data['password'], $user->password)) {
                return $this->returnRequestWarning([], __('Desculpe, mas a senha informada é invalida!'), HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            $data['canceled'] = 1;
            $data['date_canceled'] = now();

            //TODO - CANCELAR OS INGRESSOS, ESTORNAR AS COMPRAS

            //TODO - CANCELAR TODAS AS COMPRAS FEITA PARA ESSE EVENTO, COM ESTODO NO DINHEIRO

            $data = $this->repository->update($id, $data);

            $event = $this->repository->show($id);

            return $this->returnRequestSucess($event);

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * Method created to change the record with id passed as parameter.
     * @param int $id
     * @param array $data
     * @return JsonResponse
     */
    public function promote(int $id, array $data): JsonResponse
    {
        try {

            /**
             *******************************************************************************************************
             * CHECK PERMISSIONS
             *******************************************************************************************************
             */
            if ($this->myUser(self::GET_USER_LEVEL) == 3) {
                if (!$this->checkPermission(7, self::USER_LEVEL_ADMIN) && !$this->checkPermission(9, self::USER_LEVEL_ADMIN)) {
                    return $this->returnRequestWarning([], __('You do not have permission to perform this functionality.'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
                }
            }
            /**
             *******************************************************************************************************
             */

            //check data user
            $user = $this->myUser(self::GET_USER_OBJECT);
            //     if (!Hash::check($data['password'], $user->password)) {
            //       return $this->returnRequestWarning([], __('Desculpe, mas a senha informada é invalida!'), HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            //     }

            //TODO - COMO VAI SER A REGRA DE NEGÓCIO DESSA PARADA

            if (isset($data['emphasis_type']) && count($data['emphasis_type']) == 0) {
                $data['emphasis_type'] = null;
            }
            //$data = $this->repository->update($id, $data);
            DB::table('events')->where('id', $id)->update($data);

            $event = $this->repository->show($id);

            return $this->returnRequestSucess($event);

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
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
            return $this->returnRequestError((array)$e);
        }
    }

}
