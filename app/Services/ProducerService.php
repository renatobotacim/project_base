<?php

namespace App\Services;

use App\Helpers\MetricsEvents;
use App\Jobs\sendMail;
use App\Mail\RegisterUser;
use App\Models\User;
use App\Repositories\ProducerRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;
use function Laravel\Prompts\table;
use Illuminate\Support\Str;

class ProducerService extends Service
{

    private ProducerRepositoryInterface $repository;
    private OwnerService $ownerService;
    private AuthService $authService;

    public function __construct(
        ProducerRepositoryInterface $repository,
        OwnerService                $ownerService,
        AuthService                 $authService
    )
    {
        $this->repository = $repository;
        $this->ownerService = $ownerService;
        $this->authService = $authService;
    }

    /**
     * [
     *  ADM MASTER - TELA DE LISTAGEM DE PRODUTORES
     * ]
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return object
     */
    public function index(int $paginate, int $page, string $search): object
    {
        try {

            $data = $this->repository->index($paginate, $page, $search);

            for ($x = 0; $x < count($data); $x++) {
                $data[$x]->tickets_count = DB::table('tickets')
                    ->leftJoin('batchs', 'batchs.id', '=', 'tickets.batch_id')
                    ->leftJoin('tickets_events', 'tickets_events.id', '=', 'batchs.ticket_event_id')
                    ->leftJoin('events', 'events.id', '=', 'tickets_events.event_id')
                    ->where('events.producer_id', $data[$x]->id)->count();
            }

            return $this->returnRequestSucess($data);

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
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
            $data = $this->repository->show($id);
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * List users according paramiter ID
     * @param int $id
     * @return JsonResponse
     */
    public function account(): JsonResponse
    {
        try {
            $data = $this->repository->show($this->myUser(self::GET_USER_PRODUCER));
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * List users according paramiter ID
     * @param int $id
     * @return object
     */
    public function dashboard(): object
    {

        try {

            $producerId = $this->myUser(self::GET_USER_PRODUCER);

            $events = DB::table('events')->where('producer_id', $producerId)->get();

            $tickets = DB::table('tickets')
                ->join('batchs', 'batchs.id', '=', 'tickets.batch_id')
                ->join('tickets_events', 'tickets_events.id', '=', 'batchs.ticket_event_id')
                ->join('events', 'events.id', '=', 'tickets_events.event_id')
                ->where('producer_id', $producerId)
                ->get();

            $ticketsEvent = [];
            $courtesies = 0;

            foreach ($tickets as $ticket) {
                if (!empty($ticket->courtesy)) {
                    $courtesies++;
                }

                if (array_key_exists($ticket->event_id, $ticketsEvent)) {
                    $ticketsEvent[$ticket->event_id]++;
                } else {
                    $ticketsEvent[$ticket->event_id] = 1;
                }

            }

            $activeEvents = [];
            foreach ($events as $event) {
                if ($event->date >= now()) {
                    $activeEvents[] = [
                        "id" => $event->id,
                        "event" => $event->event,
                        "name" => $event->name,
                        "tickets" => (array_key_exists($event->id, $ticketsEvent)) ? $ticketsEvent[$event->id] : 0
                    ];
                }
            }

            $sales = DB::table('sales')
                //->select('events.id as event_id', 'events.event', 'sales.id', 'sales.payment_type', 'sales.status', 'sales.value', 'sales.date', 'users.cpf',)
                ->select('sales.*')
                ->join('events', 'events.id', '=', 'sales.event_id')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->where('events.producer_id', $producerId)
                ->orderBy('sales.date', 'DESC')->get();

            $chartSalesAux = [];
            $salesPeakAll = [];

            foreach ($sales as $sale) {

                if ($sale->status == "CONFIRMED") {

                    if (!isset($chartSalesAux[date("Y", strtotime($sale->date)) . '-' . date("m", strtotime($sale->date))])) {
                        $chartSalesAux[date("Y", strtotime($sale->date)) . '-' . date("m", strtotime($sale->date))]['desktop'] = 0;
                        $chartSalesAux[date("Y", strtotime($sale->date)) . '-' . date("m", strtotime($sale->date))]['mobile'] = 0;
                    }

                    if ($sale->origin == "DESKTOP") {
                        $chartSalesAux[date("Y", strtotime($sale->date)) . '-' . date("m", strtotime($sale->date))]['desktop'] = ($chartSalesAux[date("Y", strtotime($sale->date)) . '-' . date("m", strtotime($sale->date))]['desktop'] + 1);
                    } else {
                        $chartSalesAux[date("Y", strtotime($sale->date)) . '-' . date("m", strtotime($sale->date))]['mobile'] = ($chartSalesAux[date("Y", strtotime($sale->date)) . '-' . date("m", strtotime($sale->date))]['mobile'] + 1);
                    }

                    if (array_key_exists(date("H", strtotime($sale->date)), $salesPeakAll)) {
                        $salesPeakAll[date("H", strtotime($sale->date))]++;
                    } else {
                        $salesPeakAll[date("H", strtotime($sale->date))] = 1;
                    }
                }
            }

            $max = 0;
            $count = 0;

            foreach ($salesPeakAll as $key => $x) {
                if ($x > $max) {
                    $max = $x;
                    $salesPeak = $key;
                }
                $count = $count + $x;
            }

            $percentSalesPeak = 0;
            if ($count > 0) {
                $percentSalesPeak = (100 * $max) / $count;
            }

            $chartSales = [];
            foreach ($chartSalesAux as $key => $x) {
                $chartSales[] = [
                    'desktop' => $x['desktop'],
                    'mobile' => $x['mobile'],
                    'date' => $key
                ];
            }

            $acess_mobile = 0;
            $acess_desktop = 0;
            $acess = DB::table('hits')
                ->join('events', 'events.id', '=', 'hits.event_id')
                ->where('producer_id', $producerId)->get();
            foreach ($acess as $a) {
                if ($a->origin == "MOBILE") {
                    $acess_mobile++;
                }
                if ($a->origin == "DESKTOP") {
                    $acess_desktop++;
                }
            }

            $data = array(
                "events" => count($events),
                "tickets" => count($tickets),
                "courtesies" => $courtesies,
                "chart_sales" => $chartSales,
                "sales_peak" => $salesPeak ?? 0,
                "percent_sales_peak" => $percentSalesPeak,
                "acess_mobile" => $acess_mobile,
                "acess_desktop" => $acess_desktop,
                "active_events" => $activeEvents
            );

            return $this->returnRequestSucess($data);


        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * List users according paramiter ID
     * @param int $id
     * @return JsonResponse
     */
    public function profile(int $id): JsonResponse
    {
        try {

            $producer = $this->repository->show($id);

            $events = DB::table('events')->select('id', 'event', 'banner')->where('producer_id', $producer->id)->orderBy('id', 'desc')->get();
            $permissions = DB::table('users')->select('name', 'office', 'status', 'email')->where('producer_id', $producer->id)->get();

            $data['producer'] = $producer;
            $data['events'] = count($events);
            $data['permissions'] = $permissions;
            $data['sales_value'] = 0;
            $data['rate_value'] = 0;
            $courtesies = 0;

            $tickets = DB::table('tickets')
                ->join('batchs', 'batchs.id', '=', 'tickets.batch_id')
                ->join('tickets_events', 'tickets_events.id', '=', 'batchs.ticket_event_id')
                ->join('events', 'events.id', '=', 'tickets_events.event_id')
                ->where('producer_id', $producer->id)
                ->get();

            $data['tickets'] = count($tickets);

            foreach ($tickets as $ticket) {
                $data['sales_value'] = $data['sales_value'] + $ticket->value;
                $data['rate_value'] = $data['rate_value'] + $ticket->rate;

                if (!empty($ticket->courtesy)) {
                    $courtesies++;
                }
            }

            $data['last_events'] = [];
            foreach ($events as $event) {
                if (count($data['last_events']) < 8) {
                    if (isset($ticket->banner)) {
                        $event->banner = Storage::disk('s3')->temporaryUrl($event->banner, now()->addMinutes(720));
                    }
                    $data['last_events'][] = $event;
                }
            }

            $data['courtesies'] = $courtesies;

            return $this->returnRequestSucess($data);

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }


    public function document(int $id, string $hash)
    {
        $producer = $this->repository->show($id);

        $link = '';
        foreach (($producer->documents ?? []) as $key => $file) {
            if ($file['hash'] == $hash) {
                $link = Storage::disk('s3')->temporaryUrl($file['path'], now()->addMinutes(60));
            }
        }

        return $this->returnRequestSucess(['link' => $link]);
    }

    /**
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     */
    public function store(array $data): JsonResponse
    {
        try {

            DB::beginTransaction();

            $dataUser = $data['owner'];

            if (isset($data['files']) && !empty($data['files'])) {
                $data['documents'] = [];
                foreach ($data['files'] as $key => $fileUp) {
                    $originalName = $fileUp->getClientOriginalName();
                    $name = $fileUp->hashName();
                    $file = new UploadService();

                    $cnpj = preg_replace("/[^0-9]/", "", $data['cnpj']);
                    $path = $file->uploadDocuments($fileUp, "producers/{$cnpj}/{$name}");

                    $hash = md5($name);
                    array_push($data['documents'], [
                        'hash' => $hash,
                        'name' => $originalName,
                        'path' => $path,
                        'drive' => 's3'
                    ]);
                }
            }


            $owner = $this->ownerService->store($data['owner']);

            if ($owner->status() != 200) {
                DB::rollBack();
            }

            $data['owner_id'] = json_decode($owner->content())->data->id;

            $model = $this->repository->store($data);

            //add user for Owner
            $dataUser['producer_id'] = $model->id;
            $dataUser['level'] = 2;
            $userOwner = $this->authService->register($dataUser);
            $userResponse = json_decode($userOwner->content())?->data?->id ?? null;

            if ($userResponse) {
                $userResponse = User::find($userResponse);
                $userResponse->permissions()->attach([1, 2, 3, 4, 5, 6, 13, 14, 15, 16, 17]);
            }

            if (!$model) {
                return $this->returnRequestWarning([], 'Não foi possível realizar o registro. Tente novamente', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                DB::rollBack();
            }

            DB::table('producers_balance')->insert(['producer_id' => $model->id]);

            DB::commit();

            // send mail for validade acount

            /* $dataEmail['type'] = 'notification';
             $dataEmail['userEmail'] = $data['owner']['email'];
             $dataEmail['userName'] = $data['owner']['name'];
             $dataEmail['title'] = 'Cadastro de Produtor';
             $dataEmail['content'] = "";
             sendMail::dispatch($dataEmail)->delay(now());
 */

            // send mail for validade acount
            $data['ownerName'] = $data['owner']['name'];
            $data['userEmail'] = $data['owner']['email'];
            $data['userName'] = $data['owner']['name'];
            $data['type'] = 'registerUser';
            $data['link'] = "https://app.ticketk.com.br";
            //sendMail::dispatch($data)->delay(now());
            $mail = new \App\Helpers\SendMail($data);
            $mail->sendRegisteProducer();

            return $this->returnRequestSucess($this->repository->show($model->id));

        } catch (\Exception $e) {
            return $this->returnRequestError(array($e));
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

        DB::beginTransaction();

        if ((isset($data['files']) && !empty($data['files'])) || (isset($data['removedFiles']) && !empty($data['removedFiles']))) {

            $model = $this->repository->show($id);
            $data['documents'] = [];

            if (isset($data['files']) && !empty($data['files'])) {
                foreach ($data['files'] as $key => $fileUp) {
                    $originalName = $fileUp->getClientOriginalName();
                    $name = $fileUp->hashName();
                    $file = new UploadService();

                    $cnpj = preg_replace("/[^0-9]/", "", $data['cnpj']);
                    $path = $file->uploadDocuments($fileUp, "producers/{$cnpj}/{$name}");

                    $hash = md5($name);
                    array_push($data['documents'], [
                        'hash' => $hash,
                        'name' => $originalName,
                        'path' => $path,
                        'drive' => 's3'
                    ]);
                }
            }

            array_push($data['documents'], ...($model->documents ?? []));

            if (isset($data['removedFiles']) && !empty($data['removedFiles'])) {
                $deleteArrayPaths = [];
                foreach ($model->documents ?? [] as $key => $file) {
                    if (in_array($file['hash'], $data['removedFiles'])) {
                        array_push($deleteArrayPaths, $file['path']);
                        unset($data['documents'][$key]);
                    }
                }
                if (!empty($deleteArrayPaths)) {
                    Storage::disk('s3')->delete($deleteArrayPaths);
                }
            }
        }

        $dataUser = $data['owner'];
        unset($dataUser['address']);

        $model = $this->repository->update($id, $data);

        $ownerOld = $this->ownerService->show($data['owner_id']);
        $owner = $this->ownerService->update($data['owner_id'], $data['owner']);

        if ($ownerOld['email'] != $data['owner']['email']) {


            // send mail for validade acount
            $data['ownerName'] = $data['owner']['name'];
            $data['userEmail'] = $data['owner']['email'];
            $data['userName'] = $data['owner']['name'];
            $data['link'] = "https://app.ticketk.com.br";
            $data['name'] = $data['owner']['name'];
            $data['email'] = $data['owner']['email'];
            //sendMail::dispatch($data)->delay(now());
            $mail = new \App\Helpers\SendMail($data);
            $mail->sendUpdateProducerOwner();

            //TODO - REMOVER PERMISSÃO DO USUÁRIO
            $userOld = $this->authService->showEmail($ownerOld['email']);

            $update = $this->authService->update($userOld['id'], [
                'permissions' => [],
                'level' => 1,
                'producer_id' => null
            ]);

            if (!json_decode($update->getContent())->data) {
                return $this->returnRequestWarning([], 'Não foi possível realizar o registro. Tente novamente', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                DB::rollBack();
            }

            //TODO - VERIFICAR NOVO USUAÁRIO
            $userNew = $this->authService->showEmail($data['owner']['email']);

            if ($userNew) {

                $dataUser['producer_id'] = $id;
                $dataUser['level'] = 2;
                $userOwner = $this->authService->update($userNew->id,$dataUser);
                $userResponse = json_decode($userOwner->content())?->data?->id ?? null;

                if ($userResponse) {
                    $userResponse = User::find($userResponse);
                    $userResponse->permissions()->attach([1, 2, 3, 4, 5, 6, 13, 14, 15, 16, 17]);
                }

            } else {
                //add user for Owner
                $dataUser['producer_id'] = $id;
                $dataUser['level'] = 2;
                $userOwner = $this->authService->register($dataUser);
                $userResponse = json_decode($userOwner->content())?->data?->id ?? null;

                if ($userResponse) {
                    $userResponse = User::find($userResponse);
                    $userResponse->permissions()->attach([1, 2, 3, 4, 5, 6, 13, 14, 15, 16, 17]);
                }
            }

        }

        if (!$model || !$owner) {
            return $this->returnRequestWarning([], 'Não foi possível realizar o registro. Tente novamente', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            DB::rollBack();
        }

        $model = $this->repository->show($id);

        DB::commit();

        return $this->returnRequestSucess($model);
        try {
        } catch (\Exception $e) {
            return $this->returnRequestError(array($e));
        }
    }

    /**
     * Method created to change the record with id passed as parameter.
     * @param int $id
     * @param array $data
     * @return JsonResponse
     */
    public function suspended(int $id, array $data): JsonResponse
    {

        try {

            DB::beginTransaction();

            $model = $this->repository->update($id, $data);

            if (!$model) {
                return $this->returnRequestWarning([], 'Não foi possível realizar o registro. Tente novamente', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                DB::rollBack();
            }

            DB::table('users')->where('producer_id', $id)->update([
                'producer_id' => null,
                'level' => null
            ]);

            $usersId = DB::table('users')->select('id')->where('producer_id', $id)->get();

            $aux = [];
            foreach ($usersId as $x) {
                array_push($aux, $x->id);
            }

            $usersId = DB::table('roles_has_users')->whereIn('user_id', $aux)->delete();

            DB::commit();


            return $this->returnRequestSucess([$model]);

        } catch (\Exception $e) {
            return $this->returnRequestError(array($e));
        }
    }

    /**
     * Method created to change the record with id passed as parameter.
     * @param array $data
     * @return JsonResponse
     */
    public function accountUpdate(array $data): JsonResponse
    {
        try {

            $user = $this->myUser(self::GET_USER_OBJECT);


            if (!$user->producer_id) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => __('You do not have permission to perform this functionality.'),
                    ], HTTP_RESPONSE::HTTP_UNAUTHORIZED
                );
            }

            $model = $this->repository->update($user->producer_id, $data);

            if (!$model) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => __('Unable to update record. Try again!'),
                        'data' => $data
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return response()->json(
                [
                    'message' => __('Data updated successfully!'),
                    'data' => $data
                ], HTTP_RESPONSE::HTTP_OK
            );

        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
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
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}
