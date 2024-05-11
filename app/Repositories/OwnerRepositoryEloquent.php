<?php

namespace App\Repositories;

use App\Models\Owner;

class OwnerRepositoryEloquent implements OwnerRepositoryInterface
{

    private $model;

    public function __construct(Owner $data)
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
            ->with("address")
            ->where('name', 'like', '%' . $search . '%')
            ->where('cpf', 'like', '%' . $search . '%')
            ->simplePaginate($paginate);
    }

    /**
     * @param int $id
     * @return object|null
     */
    public function show(int $id): object|null
    {
        return $this->model->with("address")->find($id);
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
