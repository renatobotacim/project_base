<?php

namespace App\Services;

use App\Repositories\BagRepositoryInterface;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;

class AdmMasterService extends Service
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
    public function dashboard(): object
    {

        try {

            $data = [];

            $data['events'] = DB::table('events')->count();
            $data['producers'] = DB::table('producers')->count();
            $data['customers'] = DB::table('users')->whereNull('producer_id')->count();

            $data['valueTotal'] = DB::table('sales')->where('status', '=', 'CONFIRMED')->sum('value');
            $data['profit'] = DB::table('sales')->where('status', '=', 'CONFIRMED')->sum('rates');

            $data['performance'] = DB::table('tickets')
                ->select('events.name', 'events.event', 'events.id', DB::raw('count(*) as total'))
                ->join('batchs', 'batchs.id', '=', 'tickets.batch_id')
                ->join('tickets_events', 'tickets_events.id', '=', 'batchs.ticket_event_id')
                ->join('events', 'events.id', '=', 'tickets_events.event_id')
                ->groupBy('events.id')
                ->orderBy('total', 'desc')
                ->limit(3)
                ->get();

            $profitChart = DB::table('sales')
                ->where('status', '=', 'CONFIRMED')
                ->get();

            $data['profit_chart'] = [];
            foreach ($profitChart as $profit) {
                //dd($profit);
                if (array_key_exists(date("m-Y", strtotime($profit->date)), $data['profit_chart'])) {
                    $data['profit_chart'][date("m-Y", strtotime($profit->date))]['qtd']++;
                } else {
                    $data['profit_chart'][date("m-Y", strtotime($profit->date))]['qtd'] = 1;
                    $data['profit_chart'][date("m-Y", strtotime($profit->date))]['date'] = date("m-Y", strtotime($profit->date));
                }
            }

            $acess = DB::table('hits')->get();

            $chartAccess = [];

            foreach ($acess as $a) {
                if (array_key_exists(date("Y-m", strtotime($a->moment)), $chartAccess)) {
                    $chartAccess[date("Y-m", strtotime($a->moment))]['hits']++;
                } else {
                    $chartAccess[date("Y-m", strtotime($a->moment))]['date'] = date("Y-m", strtotime($a->moment));
                    $chartAccess[date("Y-m", strtotime($a->moment))]['hits'] = 1;
                }
            }

            $aux = $chartAccess;
            unset($chartAccess);
            $chartAccess = [];
            foreach ($aux as $x) {
                $data['access_chart'][] = $x;
            }

            return $this->returnRequestSucess($data);


        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => __('OPSS! An internal error has occurred. Try again later.'),
                ], HTTP_RESPONSE::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


}
