<?php

namespace App\Repositories;

use App\Models\Sale;

class SaleRepositoryEloquent implements SaleRepositoryInterface
{

    private $model;

    public function __construct(Sale $data)
    {
        $this->model = $data;
    }

    /**
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return object|null
     */
    public function index(int $paginate, int $page, string $search): object|null
    {
        return $this->model
            ->where('name', 'like', '%' . $search . '%')
            ->simplePaginate($paginate);
    }

    /**
     * @param int|null $id
     * @param string|null $sale
     * @return object|null
     */
    public function show(int $id = null, string $sale = null, string $paymentId = null): object|null
    {

        if (isset($id)) {
            return $this->model->find($id);
        }
        if (isset($sale)) {
            return $this->model->where('sale', $sale)->first();
        }
        if (isset($paymentId)) {
            return $this->model->where('payment_id', $paymentId)->first();
        }
        return null;
    }

    /**
     * @param string $sale
     * @param bool $payment
     * @return object|null
     */
    public function listTickts(string $sale, bool $payment = null): object|null
    {
        return $this->model
            ->select('bags.ticket_id', 'tickets.batch_id','tickets.ticket', 'users.name','users.email')
            ->join('bags', 'bags.bag', '=', 'sales.bag')
            ->join('tickets', 'tickets.id', '=', 'bags.ticket_id')
            ->join('users', 'users.id', '=', 'tickets.user_id')
            ->where('sale', $sale)
            ->where('tickets.status', 'reserved')
            ->when($payment == true, function ($query) {
                $query->where('sales.status', 'CONFIRMED');
            })
            ->when($payment == false, function ($query) {
                $query->where('sales.status', '<>', 'CONFIRMED');
            })
            ->get();
    }

    /**
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @param int $producerId
     * @return object|null
     */
    public function moviment(int $paginate, int $page, array $params, int $producerId): object|null
    {
        return $this->model
            ->select('events.id as event_id', 'events.event', 'sales.id', 'sales.payment_type', 'sales.status', 'sales.value','sales.rates','sales.value_real', 'sales.date', 'users.cpf',)
            ->join('events', 'events.id', '=', 'sales.event_id')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->where('events.producer_id', $producerId)
            ->when($params['search'], function ($query) use ($params) {
                $query->where('events.event', 'like', '%' . $params['search'] . '%')
                    ->orWhere('users.cpf', 'like', '%' . $params['search'] . '%');
            })
            ->when($params['event'], function ($query) use ($params) {
                $query->where('events.id', $params['event']);
            })
            ->when($params['payment'], function ($query) use ($params) {
                $query->where('sales.payment_type', $params['payment']);
            })
            ->when($params['status'], function ($query) use ($params) {
                $query->where('sales.status', $params['status']);
            })
            ->when($params['dateStart'], function ($query) use ($params) {
                $query->whereDate('sales.date', '>=', $params['dateStart']);
            })
            ->when($params['dateEnd'], function ($query) use ($params) {
                $query->whereDate('sales.date', '<=', $params['dateEnd']);
            })
            ->orderBy('sales.date', 'DESC')
            ->paginate($paginate);
    }

    /**
     * @param array $data
     * @return object|null
     */
    public function store(array $data): object|null
    {
        return $this->model->create($data);
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->find($id)->update($data);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $data = $this->model->find($id);
        return empty($data) ? false : $this->model->find($id)->delete();
    }

}
