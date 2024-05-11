<?php

namespace App\Services;

use App\Mail\RegisterUser;
use App\Mail\SendCode;
use App\Repositories\WithdrawalRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class WithdrawalService extends Service
{

    private WithdrawalRepositoryInterface $repository;

    public function __construct(
        WithdrawalRepositoryInterface $repository,
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
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * show list records for model
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return JsonResponse
     */
    public function history(int $paginate, int $page, string $search): JsonResponse
    {
        try {

            $data = [];

            //TODO VERIFICAR SALDO E DADOS DO PRODUTOR
            $aux = $this->repository->showBalance($this->myUser(self::GET_USER_PRODUCER));
            $data['producer'] = $aux;

            //TODO LISTAR HISTÓRICO DE SAQUES
            $data['history'] = $this->repository->history($paginate, $page, $search, $this->myUser(self::GET_USER_PRODUCER));

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
    public function show(int $id): JsonResponse
    {
        try {

            $data = $this->repository->show($id);
            $aux = $this->repository->showBalance($data->producer_id);

            $data->balance = $aux->balance;
            $data->balance_block = $aux->balance_block;

            $data->bank = $aux->bank;
            $data->agency = $aux->agency;
            $data->account = $aux->account;
            $data->onwer_account = $aux->onwer_account;

            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * List users according paramiter ID
     * @return object
     */
    public function generateCode(): object
    {

        try {

            $data = [
                'code' => $this->generateHash("V", 5),
                'generate' => now()
            ];

            $generate = $this->repository->generateCode($data);

            if (!$generate) {
                return $this->returnRequestWarning([], "Desculpe, não foi possivel gerar o código da solicitação!", HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            //TODO ENVIAR CÓDIGO POR EMAIL
            $aux = $this->repository->showBalance($this->myUser(self::GET_USER_PRODUCER));

            $data['userEmail'] = $aux->email;
            $data['userName'] = $aux->ownerName;
            $mail = new \App\Helpers\SendMail($data);
            $mail->sendCode();

            return $this->returnRequestSucess([], 'Código de segurança gerando com sucesso! acesse sua conta de email para pegar o código!');

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

        try {

            //TODO VERIFICAR CODIGO
            $validateCode = $this->repository->validateCode($data['token']);
            if (!$validateCode) {
                return $this->returnRequestWarning([], "Desculpe, mas o código informado é inválido!", HTTP_RESPONSE::HTTP_UNAUTHORIZED);
            }

            //TODO VERIFICAR SALDO
            $balance = $this->repository->showBalance($this->myUser(self::GET_USER_PRODUCER));
            if (!$balance->balance > $data['value']) {
                return $this->returnRequestWarning([], "Desculpe, mas o código informado é inválido!", HTTP_RESPONSE::HTTP_UNAUTHORIZED);
            }

            //TODO REGISTRA SOLICITAÇÃO
            $data['producer_id'] = $this->myUser(self::GET_USER_PRODUCER);
            $data['user_request_id'] = $this->myUser(self::GET_USER_ID);
            $data['request'] = now();
            $data['acount'] = '1asdasd';

            $model = $this->repository->store($data);

            if (!$model) {
                return $this->returnRequestWarning([], 'Não foi possível realizar sua solicitação. Tente novamente', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            return $this->returnRequestSucess($model);

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
    public function update(int $id, array $data): JsonResponse
    {
        try {

            $withdrawal = $this->repository->show($id);

            if ($withdrawal->status == 'pending') {

                $user = $this->myUser(self::GET_USER_OBJECT);

                if (!Hash::check($data['password'], $user->password)) {
                    return $this->returnRequestWarning([], __('Desculpe, mas a senha informada é invalida!'), HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                }

                $data['user_payment_id'] = $user->id;
                $data['payment'] = now();

                $model = $this->repository->update($id, $data);

                if (!$model) {
                    return $this->returnRequestWarning([], __('Unable to update record. Try again!'), HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                }

                return $this->returnRequestSucess($data);

            } else {
                return $this->returnRequestWarning([null], __('Essa requisição já foi processada!'), HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

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
    public function liberedValues(): JsonResponse
    {
        try {

            $sales = DB::table('producers_history_money')
                ->whereDate('block', '<', now())
                ->whereNull('libered')
                ->get();

            foreach ($sales as $x) {

                DB::beginTransaction();

                if ($x->type == 'C') {
                    DB::table('producers_history_money')->where('id', $x->id)->update([
                        'libered' => 1,
                        'libered_date' => now()
                    ]);

                    DB::table('producers_balance')->where('id', $x->producer_id)->increment('balance', $x->value);
                    DB::table('producers_balance')->where('id', $x->producer_id)->decrement('balance_block', $x->value);
                }

                if ($x->type == 'D') {
                    DB::table('producers_history_money')->where('id', $x->id)->update([
                        'libered' => 1,
                        'libered_date' => now()
                    ]);

                    DB::table('producers_balance')->where('id', $x->producer_id)->decrement('balance_block', $x->value);
                }


                DB::commit();
            }
            return $this->returnRequestSucess([true]);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

}
