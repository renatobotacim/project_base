<?php

namespace App\Helpers;

use App\Models\Event;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class Events
{

    private string $event;
    private int $userId;

    public object|null $show;
    public bool|null $update;

    public function showEvent($event)
    {
        $this->event = $event;
        $this->event();
        return $this->show;
    }

    public function generateCortesies()
    {
        $this->updateCortesies();
        return $this->update;
    }

    public function showSales(int $eventId, int $userId)
    {
        $this->event = $eventId;
        $this->userId = $userId;
        $this->sales();
        return $this->show;
    }

    private function event()
    {
        $model = new Event();
        $this->show = $model
            ->with("address.city", "category", "producer", 'ticketEvents.sector', 'ticketEvents.batchs', 'coupons', 'map.sectors')
            ->where('event', $this->event)
            ->first();
    }

    private function updateCortesies()
    {
        $this->update = DB::table('events')->where('event', $this->event)->increment('courtesies_used', 1);
    }

    private function sales()
    {
        $this->show = DB::table('sales')
            ->select('sales.status', 'sales.date', 'users.name', 'users.email', 'sales.sale', 'sales.value_final', 'sales.payment_type', 'sales.event_id')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->where('event_id', $this->event)->where('user_id', $this->userId)->get();

    }
}
