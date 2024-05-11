<?php

namespace App\Services;

use App\Helpers\Events;
use App\Helpers\ExportDataCsV;
use App\Helpers\Log;
use App\Helpers\Payment;
use App\Jobs\sendMail;
use App\Models\Event;
use App\Repositories\SaleRepositoryInterface;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;
use function Laravel\Prompts\table;

class SaleService extends Service
{

    private SaleRepositoryInterface $repository;
    private TicketService $ticketService;
    private AuthService $authService;
    private CouponService $couponService;

    public function __construct(
        SaleRepositoryInterface $repository,
        TicketService           $ticketService,
        AuthService             $authService,
        CouponService           $couponService
    )
    {
        $this->repository = $repository;
        $this->ticketService = $ticketService;
        $this->authService = $authService;
        $this->couponService = $couponService;
    }

    /**
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
     * List users according paramiter ID
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $data = $this->repository->show($id);
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * List users according paramiter ID
     * @param string $sale
     * @return JsonResponse
     */
    public function verify(string $sale): JsonResponse
    {
        try {
            $data = $this->repository->show(null, $sale);
            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     */
    public function payment(array $data): JsonResponse
    {
        try {

            $log = new Log();
            $log->createLog(self::LOG_INFO, 0, "INICIANDO PROCESSO DE VENDA");

            $erros = [];
            $ticketsIds = [];

            //TODO -- VERIFICAR DADOS DO USUÁRIO PAGADOR
            $log->createLog(self::LOG_INFO, 0, "INICIANDO VERIFICAÇÃO DO USUÁRIO PAGADOR");
            if (!isset($data['payment_user'])) {
                return $this->returnRequestWarning([], 'Não foi possível realizar sua compra pois não foi localizado os dados do responsável pelo pagamento dos tickets. Tente novamente', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }
            $userPayment = $this->authService->validate([
                "name" => $data["payment_user"]["name"],
                "email" => $data["payment_user"]["email"],
                "cpf" => $data["payment_user"]["cpf"],
                "payment" => true
            ]);
            $log->createLog(self::LOG_INFO, 0, "USUÁRIO PAGADOR: " . json_encode($userPayment));

            if ($userPayment->status() != 200) {
                return $userPayment;
            }

            $log->createLog(self::LOG_INFO, 0, "INICIANDO SOMA DO VALOR DA VENDA");
            //TODO  -- SOMAR VALOR DA COMPRA.
            $valueSales = 0;
            $valueRates = 0;
            foreach ($data['ticket_users'] as $ticket) {
                $ticketsIds[] = $ticket['ticket_id'];
                $dataTicket = $this->ticketService->show($ticket['ticket_id']);
                $valueSales = $valueSales + $dataTicket->batch->value;
                $valueRates = $valueRates + $dataTicket->batch->rate;
            }

            DB::beginTransaction();

            //TODO -- PROCESSAR CUPOM DE DESCONTO
            $log->createLog(self::LOG_INFO, 0, "PROCESSANDO CUMPOM DE DESCONTO");
            $valueDesc = 0;
            $valueSalesFinal = $valueSales + $valueRates;
            if (isset($data['coupon'])) {
                $coupon = $this->couponService->execute($data['coupon'], $data['event_id']);
                if ($coupon->getStatusCode() == HTTP_RESPONSE::HTTP_OK) {
                    $coupon = json_decode($coupon->getContent())->data;
                    $valueDesc = $valueSales * ($coupon->dedution / 100);
                    $valueSalesFinal = ($valueSales - $valueDesc) + $valueRates;
                } else {
                    $erros['coupon'] = json_decode($coupon->getContent())->message;
                }
            }


            //TODO -- REGISTRAR VENDA (SALES)
            $log->createLog(self::LOG_INFO, 0, "REGISTRANDO SALES");
            $sales = null;
            $sales = $this->store([
                "sale" => $this->generateHash("S", 14),
                "date" => now(),
                "user_id" => json_decode($userPayment->content())->data->id,
                "value" => $valueSales,
                "rates" => $valueRates,
                "discount" => $valueDesc,
                "amount" => count($data['ticket_users']),
                "payment_type" => $data['payment_type'],
                "bag" => $data['bag'],
                "value_final" => $valueSalesFinal,
                "event_id" => $data['event_id'],
            ]);

            $log->createLog(self::LOG_INFO, 0, "SALES REGISTRADO: " . json_encode($sales));

            $log->createLog(self::LOG_INFO, 0, "FAZENDO A ATUALIZAÇÃO DOS USUÁRIOS DO INGRESSO");
            //TODO FAZER A ATUALIZAÇÃO DOS USUÁRIOS DO INGRESSO
            foreach ($data['ticket_users'] as $x) {
                //VERIFICAR SE O USUÁRIO EXISTE
                $user = $this->authService->validate($x);
                $user = json_decode($user->getContent())->data;
                //update
                $model = $this->ticketService->update($x['ticket_id'], [
                    'user_id' => $user->id,
                    'payment_id' => json_decode($userPayment->content())->data->id,
                ]);
                //gera mesnagens de erro
                if ($model->getStatusCode() != 200) {
                    $erros[] = [
                        'message' => json_decode($model->getContent())->message,
                        'data' => json_decode($model->getContent())->data
                    ];
                }
            }

            $log->createLog(self::LOG_INFO, 0, " LIBERANDO OS INGRESSOS NA BATCH QUE FICARAM INVÁLIDOS PRESENTES NA BAG");
            //TODO -- LIBERA OS INGRESSOS NA BATCH QUE FICARAM INVÁLIDOS PRESENTES NA BAG
            $ticketsSales = $this->repository->listTickts(json_decode($sales->getContent())->data->sale, false);
            foreach ($ticketsSales as $t) {
                if (!in_array($t->ticket_id, $ticketsIds)) {
                    DB::table('batchs')->where('id', $t->batch_id)->decrement('amount_reserved', 1);
                    DB::table('bags')->where('ticket_id', $t->ticket_id)->delete();
                    $this->ticketService->delete($t->ticket_id);
                }
            }

            //TODO -- PROCESSAR PAGAMENTO
            $log->createLog(self::LOG_INFO, 0, "SEPERANDO OS DADOS PARA PROCESSAR PAGAMENTO");
            $dataPayment = [
                'saleData' => json_decode($sales->getContent())->data,
                "billingType" => $data['payment_type'],
                "customer" => json_decode($userPayment->getContent())->data->customer_id,
                "value" => $valueSalesFinal,
                "description" => json_decode($sales->getContent())->data->sale ?? 'teste',
                "externalReference" => json_decode($sales->getContent())->data->id,
            ];

            if (strtoupper($data['payment_type']) == 'CREDIT_CARD') {
                $dataPayment['creditCard'] = $data['payment_data']['creditCard'];
                $dataPayment['creditCardHolderInfo'] = $data['payment_data']['creditCardHolderInfo'];
                $dataPayment['remoteIp'] = $data['payment_data']['remoteIp'];
            }

            $sales = $this->processPaymet($dataPayment);

            if ($sales->status() != 200) {
                $log->createLog(self::LOG_ERROR, 0, "PAGAMENTO NÃO PROCESSADO PROCESSADO");
                DB::rollBack();
                return $sales;
            }

            $log->createLog(self::LOG_INFO, 0, "PAGAMENTO PROCESSADO");

            $log->createLog(self::LOG_INFO, 0, "ENVIANDO EMAIL DA COMPRA");
            // send mail for validade acount
            $event = DB::table('events')->select('events.name')->find($data['event_id']);
            $amount = count($data['ticket_users']);

            $dataEmail['userEmail'] = $data["payment_user"]["email"];
            $dataEmail['userName'] = $data["payment_user"]["name"];
            $dataEmail['title'] = 'Confirmação de Compra - ' . $event->name;
            $dataEmail['content'] = "Prezado(a) {$user->name},
<br>Agradecemos por escolher a TICKET-K para adquirir seu ingresso para o evento {$event->name}. Estamos empolgados por tê-lo(a) como parte desse momento especial e queremos garantir que sua experiência seja memorável.
<br><b>Detalhes do Ingresso:</b>
<br>Quantidade: {$amount}<br>Valor Total: {$valueSalesFinal}
<br><br>Agradecemos por confiar na Ticketk para sua experiência de compra de ingressos. Desejamos que aproveite o evento ao máximo e estamos à disposição para qualquer assistência necessária.";
            $mail = new \App\Helpers\SendMail($dataEmail);
            $mail->sendNotification();

            $sales = json_decode($sales->content())->data;


            //TODO LIBERA INGRESSOS DA VENDA

            $this->availableTickets($sales->sale);

            $event = new Event();
            $aux = $event->select('producer_id')->find($data['event_id']);
            $log->createLog(self::LOG_INFO, 0, "INICIANDO HISTÓRICO DE MOVIMENTAÇÃO");
            DB::table('producers_history_money')->insert([
                'producer_id' => $aux->producer_id,
                'type' => 'C',
                'value' => $sales->value,
                'block' => date('Y-m-d H:i:s', strtotime("+0 days", strtotime(now()))),
                'sales_id' => $sales->id
            ]);

            unset($event, $aux);

            if (strtoupper($sales->payment_type) == 'PIX') {
                $msg = 'Sua compra foi registrada com sucesso! Para efetivar o pagamento, leia o QRCode para finalizar o pagamento e fazer a liberação dos seus ingressos';
            }

            if (strtoupper($sales->payment_type) == 'CREDIT_CARD') {
                $msg = 'Sua compra foi registrada com sucesso e seu pagamento foi processado com sucesso! seus ingressos já estão disponíveis.';
            }

            $log->createLog(self::LOG_INFO, 0, "PROCESSO DE VENDA FINALIZADO. MSG DE RETORNO: " . $msg);

            DB::commit();
            return $this->returnRequestSucess($sales, $msg ?? '');

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * @param int $paginate
     * @param int $page
     * @param array $search
     * @return JsonResponse
     */
    public function moviment(int $paginate, int $page, array $params): JsonResponse
    {
        try {
            $data = $this->repository->moviment($paginate, $page, $params, $this->myUser(self::GET_USER_PRODUCER));

            $newData = [];

            foreach ($data as $x) {
                $aux = $x;
                $aux['value'] = $x->value;
                $newData[] = $aux;
            }

            return $this->returnRequestSucess($data);
        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }

    /**
     * @param int $paginate
     * @param int $page
     * @param array $search
     */
    public function movimentDonwload(int $paginate, int $page, array $params)
    {

        try {

            $data = $this->repository->moviment(100000000, 1, $params, $this->myUser(self::GET_USER_PRODUCER));

            $newData = [];

            $paymentType = ['credit_card' => "CARTÃO", "pix" => "PIX", "courtesy" => "CORTESIA"];
            $status = ['pending' => "PENDENTE", "paid" => "PAGO", 'CONFIRMED' => 'CONFIRMADO', "canceled" => "CANCELADO", "partial" => "PAGAMENTO PARCIAL", "refund_requested" => "ESTORNADO"];
            foreach ($data as $x) {
                $aux['event'] = mb_convert_encoding($x->event, 'ISO-8859-1', 'UTF-8');
                $aux['payment_type'] = mb_convert_encoding($paymentType[$x->payment_type], 'ISO-8859-1', 'UTF-8');

                if ($x->status == 'CONFIRMED') {
                    $value = ($x->value_real - $x->rates);
                }

                if ($x->status == 'refund_requested') {
                    $value = $x->value;
                }

                $value = max($value, 0);
                $aux['value'] = mb_convert_encoding("R$ " . $value, 'ISO-8859-1', 'UTF-8');
                $aux['cpf'] = mb_convert_encoding($x->cpf, 'ISO-8859-1', 'UTF-8');
                $aux['date'] = mb_convert_encoding($x->date, 'ISO-8859-1', 'UTF-8');
                $aux['status'] = mb_convert_encoding($status[$x->status], 'ISO-8859-1', 'UTF-8');
                $newData[] = $aux;
            }

            $header = ['EVENTO', 'FORMA PAGAMENTO', 'VALOR', 'CPF', 'DATA', 'STATUS'];

            $exp = new ExportDataCsV();
            $file = $exp->getExport("relatorio_movimentacao_" . date('Y-m-d'), $header, $newData);

            return $this->returnRequestSucess([
                'file' => $file
            ]);

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

            $model = $this->repository->store($data);

            if (!$model) {
                return $this->returnRequestWarning($data, 'Não foi possível realizar o registro. Tente novamente', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            return $this->returnRequestSucess($model);

        } catch (\Exception $e) {
            return $this->returnRequestError($e);
        }
    }


    /**
     * @param string $sale
     * @return JsonResponse
     */
    public function checkPayment(string $sale): JsonResponse
    {

        $sale = $this->repository->show(null, $sale);

        if (strtoupper($sale->status != "CONFIRMED")) {
            return $this->returnRequestWarning($sale, "Desculpe, mas não identificamos o seu pagamento de sua compra em nosso sistema!");
        }

        $available = $this->availableTickets($sale->sale);

        return $this->returnRequestSucess($sale, 'Seus ingressos estão disponíveis para uso!');

    }

    /**
     * @param string $sale
     * @return JsonResponse
     */
    public function availableTickets(string $sale): JsonResponse
    {
        try {

            $log = new Log();
            $log->createLog(self::LOG_INFO, 0, "INICIANDO A VERIFICAÇÃO PARA LIBERAÇÃO DOS INGRESSOS");
            $log->createLog(self::LOG_INFO, 0, "Sales verificado: {$sale}");

            $tickets = $this->repository->listTickts($sale, true);

            $log->createLog(self::LOG_INFO, 0, "Foram encontrados " . count($tickets) . " ingressos para liberação!");
            $erros = [];

            foreach ($tickets as $x) {

                DB::table('batchs')->where('id', $x->batch_id)->decrement('amount_reserved', 1);
                DB::table('batchs')->where('id', $x->batch_id)->increment('amount_used', 1);

                //TODO FAZER A ATUALIZAÇÃO DO STATUS DO INGRESSO
                $model = $this->ticketService->update($x->ticket_id, [
                    'status' => 'available'
                ]);

                $log->createLog(self::LOG_INFO, 0, "Ingresso {$x->ticket_id} atualizado!");

                $log->createLog(self::LOG_INFO, 0, "Enviando link do ingresso para o participante!");
                $dataEmail['userEmail'] = $x->email;
                $dataEmail['userName'] = $x->name;
                $dataEmail['title'] = 'Seu ingresso está disponível!';
                $dataEmail['content'] = "Prezado(a) {$x->name},
    <br>Agradecemos por escolher a TICKET-K para adquirir seu ingresso para o evento {teste}. Segue abaixo o link para você baixar o seu ingresso!
    <br><b>Link: </b> https://app.ticketk.com.br/myticket/{$x->ticket}
    <br><br>Agradecemos por confiar na Ticketk para sua experiência de compra de ingressos. Desejamos que aproveite o evento ao máximo e estamos à disposição para qualquer assistência necessária.";
                $mail = new \App\Helpers\SendMail($dataEmail);
                $mail->sendNotification();

                if ($model->getStatusCode() != 200) {
                    $erros[] = [json_decode($model->getContent())->data, json_decode($model->getContent())->message];
                }
            }


            if (!empty($erros)) {
                $log->createLog(self::LOG_ERROR, 0, "Não foi possível fazer a liberação de todos os ingressos! Verifique os erros apresentados: " . json_encode($erros));
                return $this->returnRequestWarning($erros, "Não foi possível fazer a liberação de todos os ingressos! Verifique os erros apresentados e tente novamente");
            }

            $log->createLog(self::LOG_INFO, 0, "Os infressos foram liberados com sucesso!");
            $log->createLog(self::LOG_INFO, 0, "VERIFICAÇÃO PARA LIBERAÇÃO DOS INGRESSOS FINALIZADA");

            return $this->returnRequestSucess([], 'Os infressos foram liberados com sucesso!');

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
            $model = $this->repository->update($id, $data);

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

    /**
     * @param array $data
     * @return JsonResponse
     */
    public function processPaymet(array $data): JsonResponse
    {

        $log = new Log();
        $log->createLog(self::LOG_INFO, 3, "INICIANDO PROCESSAMENTO DE PAGAMENTO");

        $data["callback"] = [
            "successUrl" => env("SITE"),
        ];
        $data["dueDate"] = date('Y-m-d');

        $dataPayment = $data['salesData'];
        unset($dataPayment['salesData']);


        $payment = new Payment();
        $log->createLog(self::LOG_INFO, 3, "CRIANDO INSTANCIA COM GATWAY DE PAGAMETNO");
        $log->createLog(self::LOG_INFO, 3, json_encode($dataPayment));
        $response = $payment->setPayment($dataPayment);

        if (!$response) {
            $log->createLog(self::LOG_ERROR, 3, 'Não foi possível registrar o pagamento junto ao gatway! [' . json_encode($response) . ']');
            return $this->returnRequestWarning([], 'Não foi possível registrar o pagamento junto ao gatway!', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }

        $log->createLog(self::LOG_INFO, 3, 'Pagamento registrado no gatway ' . json_encode($response));

        $sales = $this->repository->update($data['saleData']->id, [
            'invoice' => $response->invoiceNumber,
            'invoice_url' => $response->invoiceUrl,
            'payment_id' => $response->id,
            'status' => $response->status,
            'payment_date' => (strtoupper($response->status) == "CONFIRMED") ? date('Y-m-d H:i:s') : null,
            'value_real' => $response->netValue,
        ]);


        if (!$sales) {
            $log->createLog(self::LOG_ERROR, 3, 'Não foi possível realizar o processamento de seu pagamento! Verifique os seus dados e tente novamente');
            return $this->returnRequestWarning([], 'Não foi possível realizar o processamento de seu pagamento! Verifique os seus dados e tente novamente!', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }

        $log->createLog(self::LOG_INFO, 3, 'Sales atualziado');

        $sales = $this->repository->show($data['saleData']->id);

        if (strtoupper($data['billingType']) == 'PIX') {
            $Qrcode = $payment->getPixQrCode($response->id);
            $sales->QrCode = $Qrcode->encodedImage;
            $sales->code = $Qrcode->payload;
        }

        if (strtoupper($data['billingType']) == 'CREDIT_CARD') {
            if (strtoupper($response->status) != "CONFIRMED") {
                //TODO ERRO AO PAGAR COM CARTÃO
            }
        }

        $log->createLog(self::LOG_INFO, 3, "FINALIZANDO PROCESSAMENTO DE PAGAMENTO");

        return $this->returnRequestSucess($sales);

    }

    /**
     * receive return the webhook do gatway payment
     * @param array $data
     * @return JsonResponse
     */
    public function receivedPayment(array $data): JsonResponse
    {


        $log = new Log();
        $log->createLog(self::LOG_INFO, 1, "Hebhook de cobrança recebido, iniciando processamento do pagamento {$data['payment']['id']}");

        if ($data['event'] == "PAYMENT_RECEIVED") {
            $sale = $this->repository->show(null, null, $data['payment']['id']);

            if (!$sale) {
                $log->createLog(self::LOG_ERROR, 1, "Não foi localizado cobrança para esse pagamento! -  {$data['payment']['id']}");
                //return $this->returnRequestWarning([], "Não foi localizado cobrança para esse pagamento! -  {$data['payment']['id']}", HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                return $this->returnRequestSucess([true]);
            }

            $status = ($data['payment']['status'] == 'RECEIVED') ? 'CONFIRMED' : $data['payment']['status'];
            if (isset($sale->value_final) && $data['payment']['value'] < $sale->value_final) {
                $status = "partial";
                $log->createLog(self::LOG_WARNING, 1, "O valor recebido é diferente do valor da cobrança");
            }

            $dataUpdate = ['status' => $status, 'payment_date' => now()];

            $update = $this->repository->update($sale->id, $dataUpdate);

            if (!$update) {
                // return $this->returnRequestWarning($dataUpdate, 'Não foi possível atualizar o sales', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                return $this->returnRequestSucess([true]);
            }

            $user = DB::table('users')->find($sale->user_id);
            $event = DB::table('events')->select('events.name')->find($sale->event_id);

            $ticketEvent = DB::table('tickets')->select('te.*', 'bt.*')
                ->join('batchs as bt', 'tickets.batch_id', '=', 'bt.id')
                ->join('tickets_events as te', 'bt.ticket_event_id', '=', 'te.id')
                ->join('events as ev', 'te.event_id', '=', 'ev.id')
                ->where('ev.id', $sale->event_id)
                ->where('payment_id', $user->id)
                ->get();

            $typeTicket = '';
            foreach ($ticketEvent as $x) {
                $typeTicket .= "{$x->name} - {$x->reference}º LOTE  ";
            }

            // send mail for validade acount
            $dataEmail['userEmail'] = $user->email;
            $dataEmail['userName'] = $user->name;
            $dataEmail['title'] = 'Confirmação de Compra - ' . $event->name;
            $dataEmail['content'] = "Prezado(a) {$user->name},
<br>Agradecemos por escolher a TICKET-K para adquirir seu ingresso para o evento {$event->name}. Estamos empolgados por tê-lo(a) como parte desse momento especial e queremos garantir que sua experiência seja memorável.
<br><b>Detalhes do Ingresso:</b>
<br>Tipo de Ingresso: {$typeTicket}
<br>Quantidade: {$sale->amount}
<br>Valor Total: {$sale->value_final}
<br><br>Agradecemos por confiar na Ticketk para sua experiência de compra de ingressos. Desejamos que aproveite o evento ao máximo e estamos à disposição para qualquer assistência necessária.";
            $mail = new \App\Helpers\SendMail($dataEmail);
            $mail->sendNotification();

            $log->createLog(self::LOG_SUCCESS, 1, "Cobrança atualizada com sucesso! Data: " . json_encode($sale));
            //return $this->returnRequestSucess($dataUpdate);
            return $this->returnRequestSucess([true]);
        }

        $log->createLog(self::LOG_ERROR, 1, "Não foi possível realizar a verificação do pagamento {$data['payment']['id']}");
        //return $this->returnRequestWarning([], '', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        return $this->returnRequestSucess([true]);


        try {
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }


    /**
     * receive return the webhook do gatway payment
     * @param array $data
     * @return JsonResponse
     */
    public function refundSales(array $data): JsonResponse
    {

        try {

            $sales = $this->repository->show(null, $data['sale'], null);

            //TODO - update no sales para refund_requested
            $update = $this->repository->update($sales->id, [
                'description_canceled' => $data['description'],
                'date_canceled' => now(),
                'status' => 'refund_requested'
            ]);

            if (!$update) {
                return $this->returnRequestWarning($data, 'Não foi possível atualizar o sales', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            //TODO - atualiza os ingressos todos os ingressos para cancedeld
            $tickets = DB::table('tickets')
                ->join('bags', 'bags.ticket_id', '=', 'tickets.id')
                ->join('sales', 'sales.bag', '=', 'bags.bag')
                ->where('sales.id', $sales->id)
                ->get();

            $errors = [];

            foreach ($tickets as $ticket) {

                $aux = DB::table('tickets')->where('id', $ticket->ticket_id)->update([
                    'status' => 'canceled'
                ]);

                if (!$aux) {
                    $errors[] = $ticket;
                }

                //TODO - atualizar as baths adicionando a quantidade de ingressos cancelados (amount_userd)
                DB::table('batchs')->where('id', $ticket->batch_id)->decrement('amount_used', 1);

            }
            if (count($errors) > 0) {
                $this->returnRequestWarning([], 'Ocorreu um erro ao cancelar os ingressos!', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            //TODO - solicatar o estorno no azzas
            $data['payment_id'] = $sales->payment_id;
            $data['value'] = $sales->value;
            $payment = new Payment();
            $response = $payment->setRefund($data);
            if (!$response) {
                $this->returnRequestWarning([], 'Não foi possível realizar o processamento de seu pagamento! Verifique os seus dados e tente novamente!', HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
            }

            $event = new Event();
            $aux = $event->select('producer_id')->find($sales['event_id']);


            DB::table('producers_history_money')->where('sales_id', '=', $sales->id)->where('type', '=', "C")->update([
                'libered' => 1,
                'libered_date' => now()
            ]);

            DB::table('producers_history_money')->insert([
                'producer_id' => $aux->producer_id,
                'type' => 'D',
                'value' => $sales->value,
                'block' => date('Y-m-d H:i:s', strtotime("-1 days", strtotime(now()))),
                'sales_id' => $sales->id
            ]);

            $event = DB::table('events')->select('name', 'date')->find($sales->event_id);
            $user = DB::table('users')->select('name', 'email')->find($sales->user_id);

            // send mail for validade acount
            $dateFormat = date('d-m-Y', strtotime($event->date));
            $dataEmail['userEmail'] = $user->email;
            $dataEmail['userName'] = $user->name;
            $dataEmail['title'] = 'Confirmação de Cancelamento - ' . $event->name;
            $dataEmail['content'] = "
<br>Estamos entrando em contato para informar que o seu pedido de ingresso para {$event->name} foi cancelado com sucesso. Entendemos que as circunstâncias podem mudar, e estamos aqui para ajudar no que for necessário.
<br><b>Detalhes do Ingresso:</b>
<br>Número do Pedido: {$sales->sale}
<br>Evento: {$event->name}
<br>Data do Evento: {$dateFormat}
<br>Quantidade de Ingressos: {$sales->amount}";
            //sendMail::dispatch($dataEmail)->delay(now());
            $mail = new \App\Helpers\SendMail($dataEmail);
            $mail->sendNotification();

            return $this->returnRequestSucess($data, "Solicitação de Cancelamento realizada com sucesso!");
            //$log = new Log();
            //  $log->createLog(self::LOG_INFO, 1, "Hebhook de cobrança recebido, iniciando processamento do pagamento {$data['payment']['id']}");


        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    public function authenticateRefund(array $data): JsonResponse
    {
        try {
            $aux['type'] = $data['type'];
            $aux['transfer'] = json_encode($data['transfer']);
            $aux['date'] = now();
            $aux['status'] = $data['transfer']['status'];
            $aux['refuseReason'] = null;
            DB::table('transfers')->insert($aux);
            return $this->returnRequestSucess([
                "status" => "APPROVED",
                'refuseReason' => 'Transferência não encontrada no nosso banco'
            ]);
        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }
    }

}
