<?php

namespace App\Repositories;

use App\Models\Hit;
use Illuminate\Database\Eloquent\Collection;

class HitRepositoryEloquent implements HitRepositoryInterface
{

    private $model;

    public function __construct(Hit $data)
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
     * @param array $data
     * @return object|null
     */
    public function store(array $data): object|null
    {
        return $this->model->create($data);
    }

}
