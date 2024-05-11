<?php

namespace App\Services;

use App\Repositories\BagRepositoryInterface;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class BagService extends Service
{

    private BagRepositoryInterface $repository;
    private TicketService $ticketService;
    private BatchService $batchService;

    public function __construct(
        BagRepositoryInterface $repository,
        TicketService          $ticketService,
        BatchService           $batchService
    )
    {
        $this->repository = $repository;
        $this->ticketService = $ticketService;
        $this->batchService = $batchService;
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
    public function show(int $id): object
    {
        try {
            return $this->repository->show($id, null);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Generate code the bag and
     * @param int $id
     * @return JsonResponse
     */
    public function start($data): JsonResponse
    {


        $newItens = $data['itens'];

        unset($data['itens']);

        if (!isset($data['bag'])) {
            $bag = [
                "bag" => $this->generateHash('C', 14),
                'expires_at' => Carbon::now()->addRealMinutes(15)->format('Y-m-d H:i:s')
            ];
        } else {

            /**
             *  VERIFICAR SE EXPIROU O TEMPO, E AI EXCLUIR TODOS OS INGRESSOS DA BAG INFORMADO.
             */

            $bag = $data;
        }

        unset($data);

        $contItensRequest = 0;
        $valueTotal = 0;

        foreach ($newItens as $item) {

            for ($i = 0; $i < $item['qtd']; $i++) {

                $contItensRequest++;

                /*
                 * 0 pegar o id do lote (batch)-ok
                 * 1 gerar o ingresso - OK
                 * 2 abatar do quantitativo do evento -
                 */

                // $batch = $this->batchService->show($item['id']);
                //   $valueTotal = $valueTotal + $batch->value + $batch->rate;

                $ticket = $this->ticketService->store([
                    "ticket" => $this->generateHash("T", 14),
                    "batch_id" => $item['batch_id'],
                ]);

                if ($ticket->status() == 200) {

                    $batch = DB::table('batchs')->where('id', $item['batch_id'])->increment('amount_reserved', 1);

                    $dataBag['bag'] = $bag['bag'];
                    $dataBag['expires_at'] = $bag['expires_at'];
                    $dataBag['ticket_id'] = json_decode($ticket->content())->data->id;

                    $batStore = $this->store($dataBag);

                    if ($batStore->status() == 200) {
                        $aux = json_decode($batStore->content())->data;
                        $aux->ticket = json_decode($ticket->content())->data;
                        $aux->ticket->batch = json_decode($batStore->content())->data;;
                        $bag['itens'][] = $aux;
                        unset($aux, $dataBag);
                    } else {
                        return response()->json(
                            [
                                'message' => 'OPSSS! Ocorreu um erro ao registrar os dados do ingresso em sua sacola. Tente Novamente',
                            ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                        );
                    }

                } else {
                    return response()->json(
                        [
                            'message' => 'OPSSS! Ocorreu um erro ao registar o ingresso no nosso sistema. Recarregue a página e tente novamente.',
                        ], HTTP_RESPONSE::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
            }
        }

        // $bag['value'] = $valueTotal;
        //$bag['acountItens'] = count($bag['itens']);

        if ($contItensRequest != count($bag['itens'])) {
            return response()->json(
                [
                    'message' => 'OPSSS! Houve uma divergência entre o número de ingressos solicitados e criados. Verifique os dados e tente novamente.',
                    'data' => $bag
                ], HTTP_RESPONSE::HTTP_BAD_REQUEST
            );
        }

        return response()->json(
            [
                'message' => 'Os ingressos foram gerando com sucesso e ficarão disponíveis nesta sacola por 15 minutos. Se após o término desses 15 minutos não for computado o pagamento, os ingressos serão cancelados.',
                'data' => $bag['bag']
            ], HTTP_RESPONSE::HTTP_OK
        );

        try {
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Generate code the bag and
     * @param int $id
     * @return JsonResponse
     */
    public function checkout(string $bag): JsonResponse
    {

        try {

            $bags = $this->repository->show(null, $bag);

            if (count($bags) == 0) {
                return response()->json(
                    [
                        'message' => 'OPSS! Não foi localizado nenhum registro',
                        'data' => [],
                    ], HTTP_RESPONSE::HTTP_OK
                );
            }

            $event = [];
            $aux = 0;
            foreach ($bags as &$bag) {
                if (empty($event) || (end($event)->id != $bag->ticket->batch->ticketEvent->event->id)) {
                    $event[] = $bag->ticket->batch->ticketEvent->event;
                    unset($bags[$aux]->ticket->batch->ticketEvent->event);
                    $aux++;
                }
            }

            if (count($event) > 10000000) {
                return response()->json(
                    [
                        'message' => 'OPSS! Existe uma incosistência nos ingressos de sua sacola. Verifique os dados e tente novamente.',
                    ], HTTP_RESPONSE::HTTP_OK
                );
            }

            $result['event']['name'] = $event[0]['name'];
            $result['event']['date'] = $event[0]['date'];
            $result['event']['id'] = $event[0]['id'];
            $result['event']['slug'] = $event[0]['slug'];
            $result['event']['producer_id'] = $event[0]['producer_id'];
            $result['event']['contact_name'] = $event[0]['contact_name'];
            $result['event']['contact_email'] = $event[0]['contact_email'];
            $result['itens'] = $bags;

            unset($event, $bags);

            return response()->json(
                [
                    'message' => 'Os ingressos foram gerando com sucesso e ficarão disponíveis nesta sacola por 15 minutos. Se após o término desses 15 minutos não for computado o pagamento, os ingressos serão cancelados.',
                    'data' => $result
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
     * Method created to register new users
     * @param array $data
     * @return JsonResponse
     */
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
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
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

}
