<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

class EventRepositoryEloquent implements EventRepositoryInterface
{

    private Event $model;

    public function __construct(Event $model)
    {
        $this->model = $model;
    }

    /**
     * [ADM MASTER]
     * @param int $paginate
     * @param int $page
     * @param array $search
     * @return object|null
     */
    public function index(int $paginate, int $page, array $params): object|null
    {

        return $this->model
            ->select("events.id", "events.name", "events.event", "events.banner", "events.scheduling", "events.date", "events.canceled", "producers.name as producer_name")
            ->leftJoin('categories', 'events.category_id', '=', 'categories.id')
            ->leftJoin('producers', 'events.producer_id', '=', 'producers.id')
            ->leftJoin('address', 'events.address_id', '=', 'address.id')
            ->leftJoin('cities', 'address.city_id', '=', 'cities.id')
            ->when($params['search'], function ($query) use ($params) {
                $query->orWhere('events.name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('events.local', 'like', '%' . $params['search'] . '%')
                    ->orWhere('producers.name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('categories.name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('cities.name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('cities.state', 'like', '%' . $params['search'] . '%');
            })
            ->when(isset($params['status']) && $params['status'] == 'canceled', function ($query) {
                $query->where('events.canceled', 1);
            })
            ->when(isset($params['status']) && $params['status'] == 'scheduling', function ($query) {
                $query->whereDate('events.scheduling', '>', now())->where('events.canceled', 0);
            })
            ->when(isset($params['status']) && $params['status'] == 'active', function ($query) {
                $query->whereDate('events.scheduling', '<', now())->where('events.canceled', 0);
            })
            ->when(isset($params['status']) && $params['status'] == 'finalized', function ($query) {
                $query->whereDate('events.date', '<', now())->where('events.canceled', 0);
            })
            ->when($params['producer'], function ($query) use ($params) {
                $query->where('events.producer_id', $params['producer']);
            })
            ->orderBy('events.id', 'DESC')
            ->paginate($paginate);
    }

    /**
     * [CLIENT]
     * @param int $paginate
     * @param int $page
     * @param array $params
     * @return object|null
     */
    public function search(int $paginate, int $page, array $params): object|null
    {
        $date = false;
        $dateS = false;
        $dateE = false;

        if ($params['period'] == "today") {
            $date = date('Y-m-d');
        }

        if ($params['period'] == "tomorrow") {
            $date = date('Y-m-d');
            $date = date('Y-m-d', strtotime($date . ' + 1 days'));
        }

        if ($params['period'] == "this_week") {
            $dateS = date('Y-m-d', strtotime("sunday -1 week"));
            $dateE = date('Y-m-d', strtotime($dateS . ' +  6 days'));
        }

        if ($params['period'] == "next_week") {
            $dateS = date('Y-m-d', strtotime("sunday 0 week"));
            $dateE = date('Y-m-d', strtotime($dateS . ' +  6 days'));
        }

        if ($params['period'] == "this_month") {
            $dateS = date('Y-m-d', strtotime("first day of this month"));
            $dateE = date('Y-m-d', strtotime("last day of this month"));
        }

        return $this->model
            ->select("events.*")
            ->with("address.city", "category", "producer")
            ->leftJoin('categories', 'events.category_id', '=', 'categories.id')
            ->leftJoin('producers', 'events.producer_id', '=', 'producers.id')
            ->leftJoin('address', 'events.address_id', '=', 'address.id')
            ->leftJoin('cities', 'address.city_id', '=', 'cities.id')
            ->when($params['city'], function ($query) use ($params) {
                $query->where('cities.id', $params['city']);
            })
            ->when($params['category'], function ($query) use ($params) {
                $query->where('categories.name', $params['category']);
            })
            ->when($params['producer'], function ($query) use ($params) {
                $query->where('producers.id', $params['producer']);
            })
            ->when($params['search'], function ($query) use ($params) {
                $query->where('events.name', 'like', '%' . $params['search'] . '%');
            })
            ->when($date, function ($query, string $date) {
                $query->where('events.date', "=", $date);
            })
            ->when($dateS && $dateE, function ($query) use ($dateS, $dateE) {
                $query->WhereBetween(DB::raw('DATE(events.date)'), [$dateS, $dateE]);
            })
            ->where('events.date', '>', now())
            ->where('events.scheduling', '<', now())
            ->orderBy('events.date', 'DESC')
            ->paginate($paginate);
    }

    /**
     * @param int $params
     * @return object|null
     */
    public function panel(int $params): object|null
    {
        $sections = ['banner', 'emphasis', 'suggestion'];
        $data = collect([]);
        $empty = false;
        $usedEventIds = [];

        foreach ($sections as $section) {
            $queryResult = $this->model
                ->select("events.id", "events.event", "events.name", "events.slug", "events.date", "events.banner", "events.classification", "events.local", "events.address_id")
                ->with("address.city")
                ->leftJoin('address', 'events.address_id', '=', 'address.id')
                ->leftJoin('cities', 'address.city_id', '=', 'cities.id')
                ->where('events.canceled', 0)
                ->where('events.date', '>', now())
                ->where('events.scheduling', '<', now())
                ->where('events.emphasis_date_init', "<", now())
                ->where('events.emphasis_date_finish', ">", now())
                ->when($params, function ($query, int $params) {
                    $query->where('cities.id', $params);
                })
                ->whereJsonContains('emphasis_type', $section)
                ->orderBy('events.date', 'ASC')
                ->limit(6)
                ->get();

            $data[$section] = $queryResult;
        }

        foreach ($data as $section => $result) {
            if ($result->isEmpty()) {
                $randomEvents = $this->model
                    ->select("events.id", "events.event", "events.name", "events.slug", "events.date", "events.banner", "events.classification", "events.local", "events.address_id")
                    ->with("address.city")
                    ->leftJoin('address', 'events.address_id', '=', 'address.id')
                    ->leftJoin('cities', 'address.city_id', '=', 'cities.id')
                    ->where('events.canceled', 0)
                    ->where('events.date', '>', now())
                    ->where('events.scheduling', '<', now())
                    ->where('events.emphasis_date_init', "<", now())
                    ->where('events.emphasis_date_finish', ">", now())
                    ->whereJsonContains('emphasis_type', $section)
                    ->whereNotIn('events.id', $usedEventIds)
                    ->orderBy('events.date', 'ASC')
                    ->limit(6)
                    ->get();
    
                $data[$section] = $randomEvents;
                $usedEventIds = array_merge($usedEventIds, $randomEvents->pluck('id')->toArray());
            }
        }
      
        if($data['banner']->isEmpty() || $data['suggestion']->isEmpty() || $data['emphasis']->isEmpty()){
            
            if($params){
                foreach ($data as $section => $result) {
                    if ($result->isEmpty()) {
                        $randomEvents = $this->model
                            ->select("events.id", "events.event", "events.name", "events.slug", "events.date", "events.banner", "events.classification", "events.local", "events.address_id")
                            ->with("address.city")
                            ->leftJoin('address', 'events.address_id', '=', 'address.id')
                            ->leftJoin('cities', 'address.city_id', '=', 'cities.id')
                            ->where('events.canceled', 0)
                            ->where('events.date', '>', now())
                            ->where('events.scheduling', '<', now())
                            ->where('cities.id', $params)
                            ->whereNotIn('events.id', $usedEventIds)
                            ->orderBy('events.date', 'ASC')
                            ->limit(6)
                            ->get();
            
                        $data[$section] = $randomEvents;
                        $usedEventIds = array_merge($usedEventIds, $randomEvents->pluck('id')->toArray());
                    }
                }
            }

            if($data['banner']->isEmpty() && $data['suggestion']->isEmpty() && $data['emphasis']->isEmpty()){
                $empty = true;
            }

            foreach ($data as $section => $result) {
                if ($result->isEmpty()) {
                    $randomEvents = $this->model
                        ->select("events.id", "events.event", "events.name", "events.slug", "events.date", "events.banner", "events.classification", "events.local", "events.address_id")
                        ->with("address.city")
                        ->leftJoin('address', 'events.address_id', '=', 'address.id')
                        ->leftJoin('cities', 'address.city_id', '=', 'cities.id')
                        ->where('events.canceled', 0)
                        ->where('events.date', '>', now())
                        ->where('events.scheduling', '<', now())
                        ->whereNotIn('events.id', $usedEventIds)
                        ->orderBy('events.date', 'ASC')
                        ->limit(6)
                        ->get();
        
                    $data[$section] = $randomEvents;
                    $usedEventIds = array_merge($usedEventIds, $randomEvents->pluck('id')->toArray());
                }
            }

            foreach ($data as $section => $result) {
                if ($result->isEmpty()) {
                    $randomEvents = $this->model
                        ->select("events.id", "events.event", "events.name", "events.slug", "events.date", "events.banner", "events.classification", "events.local", "events.address_id")
                        ->with("address.city")
                        ->leftJoin('address', 'events.address_id', '=', 'address.id')
                        ->leftJoin('cities', 'address.city_id', '=', 'cities.id')
                        ->where('events.canceled', 0)
                        ->where('events.date', '>', now())
                        ->where('events.scheduling', '<', now())
                        ->orderBy('events.date', 'ASC')
                        ->limit(6)
                        ->get();
        
                    $data[$section] = $randomEvents;
                }
            }
        }

        $data['empty'] = $empty;
        return $data;
    }

    /**
     * @return object|null
     */
    public function emphasis(int $paginate, int $page, array $params): object|null
    {
        return $this->model
            ->select("events.id", "events.event", "events.name", "events.date", "events.banner", "events.emphasis_type", "events.emphasis_date_init", 'events.emphasis_date_finish')
            ->addSelect(DB::raw('(SELECT count(id) FROM hits WHERE hits.event_id = events.id) as access'))
            ->whereNotNull('events.emphasis_type')
            ->when($params['search'], function ($query) use ($params) {
                $query->where('events.name', 'like', '%' . $params['search'] . '%')
                    ->where('events.event', 'like', '%' . $params['search'] . '%')
                    ->where('events.emphasis_type', 'like', '%' . $params['search'] . '%');
            })
            ->where('events.canceled', 0)
            ->orderBy('events.emphasis_rate', 'DESC')
            ->paginate($paginate);
    }


    /**
     * @param int $id
     * @return object|null
     */
    public function show(int $id = null, string $event = null, string $slug = null): object|null
    {
        return $this->model
            ->with("address.city", "category", "producer", 'ticketEvents.sector', 'ticketEvents.batchs', 'coupons', 'map.sectors')
            ->when($id, function ($query, string $id) {
                $query->where('events.id', $id);
            })
            ->when($event, function ($query, string $event) {
                $query->where('events.event', $event);
            })
            ->when($slug, function ($query, string $slug) {
                $query->where('events.slug', $slug);
            })
            ->first();
    }

    /**
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @param int $producer_id
     * @return object|null
     */
    public function list(int $paginate, int $page, string $search, int $producer_id): object|null
    {
        return $this->model
            ->select("events.*")
            ->with("address.city", "category", "producer")
            ->leftJoin('categories', 'events.category_id', '=', 'categories.id')
            ->leftJoin('producers', 'events.producer_id', '=', 'producers.id')
            ->leftJoin('address', 'events.address_id', '=', 'address.id')
            ->leftJoin('cities', 'address.city_id', '=', 'cities.id')
            ->where('events.producer_id', $producer_id)
            ->when($search, function ($query) use ($search) {
                $query->Where('events.name', 'like', '%' . $search . '%')
                    ->orWhere('events.event', 'like', '%' . $search . '%')
                    ->orWhere('events.local', 'like', '%' . $search . '%')
                    ->orWhere('producers.name', 'like', '%' . $search . '%')
                    ->orWhere('categories.name', 'like', '%' . $search . '%')
                    ->orWhere('cities.name', 'like', '%' . $search . '%')
                    ->orWhere('cities.state', 'like', '%' . $search . '%');
            })
            ->orderBy('events.created_at', 'DESC')
            ->paginate($paginate);
    }

    /**
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @param int $producer_id
     * @return object|null
     */
    public function listActive(int $producer_id): object|null
    {
        return $this->model
            ->select("events.id", "events.name", "events.date", "events.event")
            ->where('events.producer_id', $producer_id)
            ->where('events.date', '>=', now())
            ->orderBy('events.created_at')
            ->get();
    }

    public function count(int $producerId = null): int
    {
        return 1;
    }

    /**
     * @param int $mapId
     * @param int $producerId
     * @return object|null
     */
    public function mapTickets(?int $mapId = null, int $producerId): object|null
    {
        return $this->model
            ->select('events.id', 'events.name')
            ->with('ticketEvents.sector', 'ticketEvents.batchs')
            ->where('events.maps_id', $mapId)
            ->where('events.producer_id', $producerId)
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

}
