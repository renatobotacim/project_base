<?php

namespace App\Repositories;

use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

class WithdrawalRepositoryEloquent implements WithdrawalRepositoryInterface
{

    private $model;

    public function __construct(Withdrawal $data)
    {
        $this->model = $data;
    }

    /**
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return object|null
     */
    public function index(int $paginate, int $page, string $search = null): object|null
    {
        return $this->model
            ->with('user')
            ->when($search, function ($query) use ($search) {
                return $query->whereHas('user', function ($query) use ($search) {
                    return $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('cpf', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('request', 'DESC')
            ->paginate($paginate);
    }

    /**
     * @param int $paginate
     * @param int $page
     * @param string $search
     * @return object|null
     */
    public function history(int $paginate, int $page, string $search, int $producerId): object|null
    {
        return $this->model
            ->where('producer_id', $producerId)
            ->paginate($paginate);
    }


    /**
     * @param int $producerId
     * @return object|null
     */
    public function showBalance(int $producerId): object|null
    {
        return DB::table('producers_balance')
            ->select('producers_balance.*', 'producers.*', 'owners.email', 'owners.name as ownerName')
            ->leftJoin('producers', 'producers.id', '=', 'producers_balance.producer_id')
            ->leftJoin('owners', 'owners.id', '=', 'producers.owner_id')
            ->where('producers_balance.producer_id', $producerId)->first();
    }

    /**
     * @param int $id
     * @return object|null
     */
    public function show(int $id): object|null
    {
        return $this->model
            ->find($id);
    }

    /**
     * @param array $data
     * @return bool
     */
    public function generateCode(array $data): bool
    {
        return DB::table('withdrawals_code')->insert($data);
    }


    /**
     * @param array $data
     * @return bool
     */
    public function validateCode(string $code): bool
    {
        return DB::table('withdrawals_code')->where('code', $code)->whereNull('checked')->update(['checked' => now()]);
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
