<?php

namespace App\Helpers;

use App\Models\Event;
use App\Repositories\EventRepositoryInterface;
use Illuminate\Support\Facades\DB;

class MetricsEvents
{

    private string $event;

    public object|null $show;
    public bool|null $update;


    public function count(int $producerId = null): int
    {

        return 12;
    }

    public function generateCortesies()
    {
        $this->updateCortesies();
        return $this->update;
    }


}
