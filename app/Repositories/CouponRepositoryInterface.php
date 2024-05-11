<?php

namespace App\Repositories;

use App\Models\Coupon;
use PhpParser\Node\Expr\Array_;

interface CouponRepositoryInterface
{

    public function __construct(Coupon $data);

    public function index(int $paginate, int $page, string $search): object|null;

    public function show(int $id = null, string $coupon = null,int $eventId = null): object|null;

    public function store(array $data): object|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

}
