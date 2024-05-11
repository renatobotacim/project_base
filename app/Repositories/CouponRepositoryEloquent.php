<?php

namespace App\Repositories;

use App\Models\Coupon;

class CouponRepositoryEloquent implements CouponRepositoryInterface
{

    private $model;

    public function __construct(Coupon $data)
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
     * @param int $id
     * @return object|null
     */
    public function show(int $id = null, string $coupon = null, int $eventId = null): object|null
    {
        if (isset($id)) {
            return $this->model->find($id);
        }
        if (isset($coupon)) {
            return $this->model->where('name', $coupon)->where('event_id', $eventId)->first();
        }
        return null;
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
