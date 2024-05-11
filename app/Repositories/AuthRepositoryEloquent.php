<?php

namespace App\Repositories;

use App\Models\User;

class AuthRepositoryEloquent implements AuthRepositoryInterface
{

    private $model;

    public function __construct(User $data)
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
            ->with('tickets')
            ->where('users.name', 'like', '%' . $search . '%')
            ->orWhere('users.name', 'cpf', '%' . $search . '%')
            ->orWhere('users.email', 'email', '%' . $search . '%')
            ->Paginate($paginate);
    }


    /**
     * @param int $paginate
     * @param int $page
     * @param int $producerId
     * @param string $search
     * @return object|null
     */
    public function permissions(int $paginate, int $page, int $producerId, string $search): object|null
    {
        return $this->model
            ->with('permissions')
            ->where('producer_id', $producerId)
            ->where('level', 2)
            ->where('name', 'like', '%' . $search . '%')
            ->orderBy('users.id', 'DESC')
            ->Paginate($paginate);
    }

    /**
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return object|null
     */
    public function permissionsAdmMaster(int $paginate, int $page, string $search = null, string $status): object|null
    {
        return $this->model
            ->with('permissions')
            ->where('users.level', 3)
            ->where('users.status', $status)
            ->when($search, function ($query, string $search) {
                $query->where('users.name', 'like', '%' . $search . '%')
                    ->orWhere('users.cpf', 'like', '%' . $search . '%');
            })
            ->orderBy('users.id', 'DESC')
            ->Paginate($paginate);
    }

    /**
     * @param int $id
     * @return object
     */
    public function show(int $id): object|null
    {
        return $this->model
            ->with('permissions', 'address.city')
            //->with('permissions', 'address.city', 'tickets')
            ->find($id);
    }

    /**
     * @param string $email
     * @return object|null
     */
    public function showEmail(string $email): object|null
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * @param string $cpf
     * @return object|null
     */
    public function showCpf(string $cpf): object|null
    {
        return $this->model->where('cpf', $cpf)->first();
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
     * @param User $user
     * @param array $data
     * @return array|null
     */
    public function storePermissions(User $user, array $data): array|null
    {
        return $user->permissions()->sync($data);
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
     * @param int $id
     * @return array|null
     */
    public function checkPermission(int $id): array|null
    {
        return ($this->model->with('permissions')->find($id))->permissions->pluck('id')->toArray();
    }

}
