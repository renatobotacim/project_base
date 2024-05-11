<?php

namespace App\Repositories;

use App\Models\Ticket;

class TicketRepositoryEloquent implements TicketRepositoryInterface
{

    private $model;

    public function __construct(Ticket $data)
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
    /**
     * @param int $id
     * @return object|null
     */
    public function show(int $id = null, string $ticket = null): object|null
    {
        return $this->model->with('batch')
            ->when($id, function ($query, string $id) {
                $query->where('id', $id);
            })
            ->when($ticket, function ($query, string $ticket) {
                $query->where('tickets.ticket', $ticket);
            })
            ->first();
    }


    /**
     * @param string $now
     * @return object|null
     */
    public function listTicketsClear(string $now): object|null
    {
        return $this->model
            ->where('status', '=', 'reserved')
            ->whereRaw(" '{$now}' > DATE_ADD(created_at, INTERVAL 17 MINUTE)")
            ->get();
    }

    /**
     * @param int $userId
     * @return object|null
     */
    public function showByUsers(int $userId): object|null
    {
        return $this->model
            ->with('batch.ticketEvent.event.address.city', 'bags.sales')
            ->where('tickets.user_id', $userId)
            ->orWhere('tickets.payment_id', $userId)
            ->get();
    }

    /**
     * @param int $eventId
     * @param int $userId
     * @return object|null
     */
    public function showByEvent(int $eventId, int $userId): object|null
    {
        return $this->model
            ->select('tickets.*')
            ->with('user', 'userPayment')
            ->join('batchs', 'batchs.id', '=', 'tickets.batch_id')
            ->join('tickets_events', 'tickets_events.id', '=', 'batchs.ticket_event_id')
            ->join('bags', 'tickets.id', '=', 'bags.ticket_id')
            ->join('sales', 'sales.bag', '=', 'bags.bag')
            ->where('tickets_events.event_id', $eventId)
            ->where('tickets.user_id', $userId)
            ->orWhere('tickets.payment_id', $userId)
            ->groupBy('tickets.ticket')
            ->get();
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


    /**
     * @param string $now
     * @return bool
     */
    public function clearTickets(string $now): bool
    {
        return $this->model
            ->where('status', '=', 'reserved')
            ->whereRaw(" '{$now}' > DATE_ADD(created_at, INTERVAL 17 MINUTE)")
            ->delete();
    }

}
