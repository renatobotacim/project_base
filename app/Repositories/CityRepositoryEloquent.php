<?php

namespace App\Repositories;

use App\Models\City;
use Illuminate\Database\Eloquent\Collection;

class CityRepositoryEloquent implements CityRepositoryInterface
{

    private $model;

    public function __construct(City $data)
    {
        $this->model = $data;
    }

    /**
     * @return object|null
     */
    public function index(string $search): object|null
    {
        return $this->model->where('name', 'like',$search . '%')
            ->limit(5)
            ->get();
    }

    /**
     * @param int $id
     * @return object|null
     */
    public function show(int $id): object|null
    {
        return $this->model->find($id);
    }

    /**
     * @return object|null
     */
    public function states(string $state): object|null
    {
        return $this->model->where('uf','like', '%' . $state . '%')->get();
    }

}
