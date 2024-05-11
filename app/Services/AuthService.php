<?php

namespace App\Services;

use App\Helpers\Payment;
use App\Jobs\sendMail;
use App\Mail\RegisterUser;
use App\Models\Address;
use App\Models\Sale;
use App\Models\Ticket;
use App\Repositories\AuthRepositoryInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class AuthService extends Service
{

    private AuthRepositoryInterface $repository;

    /**
     * @param AuthRepositoryInterface $repository
     */
    public function __construct(
        AuthRepositoryInterface $repository,
    )
    {
        $this->repository = $repository;
    }

    /**
     * show list records for model
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return JsonResponse
     */
    public function index(int $paginate, int $page, string $search): JsonResponse
    {
        try {
            $data = $this->repository->index($paginate, $page, $search);
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * show list users in producer
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return JsonResponse
     */
    public function permissions(int $paginate, int $page, string $search): JsonResponse
    {
        try {
            $producerId = $this->myUser(self::GET_USER_ID);
            $data = $this->repository->permissions($paginate, $page, $producerId, $search);
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }


    /**
     * @param int $paginate
     * @param int $page
     * @param string|null $search
     * @param string $status
     * @return JsonResponse
     */
    public function permissionsAdmMaster(int $paginate, int $page, string $search = null, string $status): JsonResponse
    {
        try {
            $data = $this->repository->permissionsAdmMaster($paginate, $page, $search, $status);
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @param int $id
     * @return object
     */
    public function show(int $id): JsonResponse
    {

        $data = $this->repository->show($id);
        return $this->returnRequestSucess($data);

        try {
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @param int $id
     * @return object
     */
    public function history(int $id): JsonResponse
    {

        $data = $this->repository->show($id);

        $ticket = new Ticket();

        $modelSales = new Sale();
        $sales = DB::table('sales')
            ->select('sales.status', 'sales.payment_type', 'events.id as event_id', 'events.event', 'events.name', 'events.date as event_date', 'events.banner')
            ->join('events', 'events.id', '=', 'sales.event_id')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->groupBy('events.id')
            ->orderBy('sales.date', 'DESC')->get();
        $newsales = [];
        foreach ($sales as $x) {
            if (isset($x->banner)) {
                $x->banner = Storage::disk('s3')->temporaryUrl($x->banner, now()->addMinutes(720));
            }
            $newsales[] = $x;
        }
        $data->events = $newsales;
        try {
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @return JsonResponse
     */
    public function showSettings(): JsonResponse
    {
        try {
            return response()->json(
                [
                    'message' => __('Record queried successfully'),
                    'data' => $this->repository->show($this->myUser(self::GET_USER_ID)),
                ], HTTP_RESPONSE::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }


    /**
     * List users according paramiter ID
     * @return JsonResponse
     */
    public function logged(): JsonResponse
    {
        try {
            $user = $this->myUser(self::GET_USER_OBJECT);
            $user->permissions;
            unset($user->producer);
            unset($user->producer);
            return $this->returnRequestSucess($user);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @param string $email
     * @return object|null
     */
    public function showEmail(string $email): object|null
    {
        try {
            return $this->repository->showEmail($email);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @param string $cpf
     * @return object|null
     */
    public function showCpf(string $cpf): object|null
    {
        try {
            return $this->repository->showCpf($cpf);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @param array $data
     * @return object|null
     */
    public function checkUser(array $data): object|null
    {
        try {

            $data['cpf'] = $this->cleanString(1, $data['cpf']);

            //check email
            $user = $this->showEmail($data['email']);
            if (!$user) {
                //check CPF
                $user = $this->showCpf($data['cpf']);

                if (!$user) {
                    $result = $this->register($data);
                    $user = json_decode($result->getContent())->data;
                    unset($result);
                }
            }

            if ($user->email != $data['email']) {
                $errors[] = "Com base nos dados informados, foi encontrado um registro correspondente, porém, os dados informados no campo de email está divergente dos demais dados. Verifique os seus dados, para ver se não houve nenhum erro de digitação";
            }

            if ($user->cpf != $data['cpf']) {
                $errors[] = "Com base nos dados informados, foi encontrado um registro correspondente, porém, os dados informados no campo de cpf está divergente dos demais dados. Verifique os seus dados, para ver se não houve nenhum erro de digitação";
            }

            if (isset($errors)) {
                return $this->returnRequestWarning($errors, 'Discrepancies were found in the data provided, check the data and try again.', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            return $this->returnRequestSucess($user, 'User verified successfully.');

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @param array $data
     * @return object|null
     */
    public function validate(array $data): object|null
    {
        try {

            $data['cpf'] = $this->cleanString(1, $data['cpf']);

            //check email
            $user = $this->showEmail($data['email']);
            if (!$user) {
                //check CPF
                $user = $this->showCpf($data['cpf']);
                if (!$user) {
                    $result = $this->register($data);
                    $user = json_decode($result->getContent())->data;
                    unset($result);
                }
            }

            if (isset($data['payment']) && $data['payment']) {

                //add customer payment
                if (empty($user->customer_id)) {
                    $dataCustomer = [
                        'name' => $data['name'],
                        'cpfCnpj' => $data['cpf'],
                        'email' => $data['email'],
                        'mobilePhone' => '',
                        'address' => '',
                        'addressNumber' => '',
                        'complement' => '',
                        'province' => '',
                        'postalCode' => '',
                        'groupName' => 'TICKETK',
                        'company' => 'TALENTS',
                        'notificationDisabled' => true,
                        'externalReference' => $user->id,
                    ];

                    $payment = new Payment();
                    $customer = $payment->setCustomer($dataCustomer);

                    $this->update($user->id, ['customer_id' => $customer->id]);
                    $user->customer_id = $customer->id;
                }
            }

            $userUpdate = [];

            if (empty($user->cpf)) {
                $userUpdate['cpf'] = $data["cpf"];
            }

            if (empty($user->name)) {
                $userUpdate['name'] = $data["name"];
            }

            if (empty($user->customer_id)) {
                // TODO CHAMAR AASAS PARA PODER REGISTRAR O CLIENTE
                //  $userUpdate['customer_id'] = 'cus_000005766308';
            }

            if (!empty($userUpdate)) {
                $this->update($user->id, $userUpdate);
            }

            unset($userUpdate);

            if ($user->email != $data['email']) {
                $errors[] = "Com base nos dados informados, foi encontrado um registro correspondente, porém, os dados informados no campo de email está divergente dos demais dados. Verifique os seus dados, para ver se não houve nenhum erro de digitação";
            }

            if ($user->cpf != $data['cpf']) {
                $errors[] = "Com base nos dados informados, foi encontrado um registro correspondente, porém, os dados informados no campo de cpf está divergente dos demais dados. Verifique os seus dados, para ver se não houve nenhum erro de digitação";
            }

            if (isset($errors)) {
                return response()->json(
                    [
                        'message' => __('Discrepancies were found in the data provided, check the data and try again.'),
                        'erros' => $errors
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return $this->returnRequestSucess($user);

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     */
    public function register(array $data): JsonResponse
    {

        try {
            $sendPass = false;
            if (!isset($data['password'])) {
                $pass = $this->generateHash('P', 7);
                $sendPass = true;
            } else {
                $pass = $data['password'];
            }

            $data['password'] = Hash::make($pass);

            if (isset($data['cpf'])) {
                $data['cpf'] = $this->cleanString(1, $data['cpf']);
            }

            $model = $this->repository->store($data);

            if (isset($data['address'])) {

                $address = $model->address()->create($data['address']);
                $model->address_id = $address->id;
                $model->save();

                $model->address = $address;

                unset($address);
            }

            if (!$model) {
                return $this->returnRequestWarning($data, 'Não foi possível realizar o registro. Tente novamente', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            // send mail for validade acount
            $data['token'] = env('APP_URL') . 'api/validate-acount/' . base64_encode(Hash::make(rand(100000000, 999999999)) . "_{$model->id}");
            $data['pass'] = $pass;
            $data['sendPass'] = $sendPass;
            $data['userEmail'] = $data['email'];
            $data['userName'] = $data['name'];
            $data['type'] = 'registerUser';
            $data['link'] = "https://app.ticketk.com.br";
            //sendMail::dispatch($data)->delay(now());
            $mail = new \App\Helpers\SendMail($data);
            $mail->sendRegisterUser();

            return $this->returnRequestSucess($model);

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     */
    public function store(array $data): JsonResponse
    {

        try {

            $sendPass = false;
            if (!isset($data['password'])) {
                $pass = $this->generateHash('P', 7);
                $sendPass = true;
            } else {
                $pass = $data['password'];
            }

            $data['password'] = Hash::make($pass);

            if (isset($data['permissions'])) {
                $data['status'] = 'active';
                $data['level'] = $data['level'] ?? 2;
            }

            if ($data['level'] == 2) {
                $data['producer_id'] = $this->myUser(self::GET_USER_PRODUCER);
                $data['link'] = "https://app.ticketk.com.br";
            }

            if ($data['level'] == 3) {
                $data['link'] = "https://adm.ticketk.com.br";
            }

            DB::beginTransaction();

            $user = $this->showEmail($data['email']);

            if ($user) {

                /**    if ($user->level != self::USER_LEVEL_USER) {
                 * return response()->json(
                 * [
                 * 'status' => false,
                 * 'message' => __('Usuário não está disponível para cadastro! Consulte os administradores do sistema'),
                 * ], HTTP_RESPONSE::HTTP_UNAUTHORIZED
                 * );
                 * }*/

                if (!empty($user->level)) {
                    return $this->returnRequestWarning([], __('Usuário não está disponível para cadastro! Consulte os administradores do sistema'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
                }

                $model = $this->repository->update($user->id, $data);

                if ($model) {
                    $model = $this->repository->show($user->id);
                }

            } else {
                $model = $this->repository->store($data);
            }

            if (isset($data['permissions'])) {

                /**
                 *******************************************************************************************************
                 * CHECK PERMISSIONS
                 *******************************************************************************************************
                 */
                if ($this->myUser(self::GET_USER_LEVEL) == 2) {
                    if (!$this->checkPermission(self::ROLES_ACESSAR_PERMISSOES, self::USER_LEVEL_PRODUCER)) {
                        DB::rollBack();
                        return $this->returnRequestWarning([], __('You do not have permission to perform this functionality.'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
                    }
                }
                if ($this->myUser(self::GET_USER_LEVEL) == 3) {
                    if (!$this->checkPermission(7, self::USER_LEVEL_ADMIN) && !$this->checkPermission(11, self::USER_LEVEL_ADMIN)) {
                        DB::rollBack();
                        return $this->returnRequestWarning([], __('You do not have permission to perform this functionality.'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
                    }
                }
                /**
                 *******************************************************************************************************
                 */

                $result = $this->repository->storePermissions($model, $data['permissions']);

                $model = $model->toArray();
                $model['permissions'] = $data['permissions'];
                $model = (object)$model;

                if (!$result) {
                    DB::rollBack();
                    return response()->json(
                        [
                            'status' => false,
                            'message' => __('Unable to update record. Try again!'),
                            'data' => $data
                        ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
                unset($result);
            }

            DB::commit();

            if (!$model) {
                return response()->json(
                    [
                        'status' => false,
                        'message' => 'Não foi possível realizar o registro. Tente novamente',
                        'data' => $data
                    ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // send mail for validade acount
            $data['token'] = env('APP_URL') . 'api/validate-acount/' . base64_encode(Hash::make(rand(100000000, 999999999)) . "_{$model->id}");
            $data['pass'] = $pass;
            $data['sendPass'] = $sendPass;
            $data['userEmail'] = $data['email'];
            $data['userName'] = $data['name'];
            $data['type'] = 'registerUser';

            //  sendMail::dispatch($data)->delay(now());
            $mail = new \App\Helpers\SendMail($data);
            $mail->sendRegisterUser();

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
     * @param array $data
     * @return JsonResponse
     */
    public function settings(array $data): JsonResponse
    {
        //password cannot be changed
        if (isset($data['permissions'])) {
            unset($data['permissions']);
        }
        return $this->update($this->myUser(self::GET_USER_ID), $data);
    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    public function updatePassword(array $data): JsonResponse
    {
        $pass = Hash::make($data['new_password'] ?? $this->generateHash('P', 7));
        $update = $this->repository->update($this->myUser(self::GET_USER_ID), [
            'password' => $pass
        ]);
        return response()->json(
            [
                'message' => __('Senha atualizada com sucesso.'),
                'data' => $update
            ], HTTP_RESPONSE::HTTP_OK
        );
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

            //email cannot be changed
            if (isset($data['email'])) {
                unset($data['email']);
            }

            //password cannot be changed
            if (isset($data['password'])) {
                unset($data['password']);
            }

            DB::beginTransaction();
            $model = $this->repository->update($id, $data);

            if (!$model) {
                DB::rollBack();
                return response()->json(
                    ['status' => false,
                        'message' => __('Unable to update record. Try again!'),
                        'data' => $data], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                );
            }


            $model = $this->repository->show($id);

            if (isset($data['address'])) {

                if (isset($data['address']['id'])) {
                    $model->address()->update($data['address']);
                } else {
                    $address = $model->address()->create($data['address']);
                    $model->address_id = $address->id;
                    $model->save();
                    unset($address);
                }

                if (!$model) {
                    DB::rollBack();
                    return response()->json(
                        [
                            'status' => false,
                            'message' => __('Unable to update record. Try again!'),
                            'data' => $this->repository->show($id)
                        ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                    );
                }

                unset($address);
            }

            if (isset($data['permissions'])) {

                /**
                 *******************************************************************************************************
                 * CHECK PERMISSIONS
                 *******************************************************************************************************
                 */
                if ($this->myUser(self::GET_USER_LEVEL) == 2) {
                    if (!$this->checkPermission(self::ROLES_ACESSAR_PERMISSOES, self::USER_LEVEL_PRODUCER)) {
                        DB::rollBack();
                        return $this->returnRequestWarning([], __('You do not have permission to perform this functionality.'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
                    }
                }
                if ($this->myUser(self::GET_USER_LEVEL) == 3) {
                    if (!$this->checkPermission(7, self::USER_LEVEL_ADMIN) && !$this->checkPermission(11, self::USER_LEVEL_ADMIN)) {
                        DB::rollBack();
                        return $this->returnRequestWarning([], __('You do not have permission to perform this functionality.'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
                    }
                }
                /**
                 *******************************************************************************************************
                 */

                $user = $this->repository->show($id);
                $result = $this->repository->storePermissions($user, $data['permissions']);

                if (!$result) {
                    DB::rollBack();
                    return response()->json(
                        [
                            'status' => false,
                            'message' => __('Unable to update record. Try again!'),
                            'data' => $data
                        ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
                unset($result);
            }

            DB::commit();

            $level = ['','','PRODUTOR', "ADMINISTRADOR"];
            // send mail for validade acount
            $data['userEmail'] = $model->email;
            $data['userName'] = $model->name;
            $data['title'] = 'Atualização de Perfil de Acesso';
            $data['content'] = "Olá {$model->name}, <br><br>Seu cadastro foi atualizado e agora você possui um nível de permissão de {$level[$model->level]}.<br><br>Att. Equipe ticketK";

            //  sendMail::dispatch($data)->delay(now());
            $mail = new \App\Helpers\SendMail($data);
            $mail->sendNotification();

            return response()->json(
                [
                    'message' => __('Data updated successfully!'),
                    'data' => $model
                ], HTTP_RESPONSE::HTTP_OK
            );

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

            /**
             *******************************************************************************************************
             * CHECK PERMISSIONS
             *******************************************************************************************************
             */
            if ($this->myUser(self::GET_USER_LEVEL) == 2) {
                if (!$this->checkPermission(self::ROLES_ACESSAR_PERMISSOES, self::USER_LEVEL_PRODUCER)) {
                    DB::rollBack();
                    return $this->returnRequestWarning([], __('You do not have permission to perform this functionality.'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
                }
            }
            if ($this->myUser(self::GET_USER_LEVEL) == 3) {
                if (!$this->checkPermission(7, self::USER_LEVEL_ADMIN) && !$this->checkPermission(11, self::USER_LEVEL_ADMIN)) {
                    DB::rollBack();
                    return $this->returnRequestWarning([], __('You do not have permission to perform this functionality.'), HTTP_RESPONSE::HTTP_UNAUTHORIZED);
                }
            }
            /**
             *******************************************************************************************************
             */
            DB::beginTransaction();

            $user = $this->repository->show($id);

            $data = [
                "producer_id" => null,
                "permissions" => [],
                "level" => 1
            ];

            //update level user
            $model = $this->repository->update($user->id, $data);

            //remove roles this user
            $result = $this->repository->storePermissions($user, $data['permissions']);

            DB::commit();

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

}
