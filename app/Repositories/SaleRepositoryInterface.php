<?php

namespace App\Repositories;

use App\Models\Sale;
use PhpParser\Node\Expr\Array_;

interface SaleRepositoryInterface
{

    public function __construct(Sale $data);

    public function index(int $paginate, int $page, string $search): object|null;

    public function show(int $id = null, string $sale = null, string $paymentId = null): object|null;

    public function listTickts(string $sale, bool $payment = null): object|null;

    public function moviment(int $paginate, int $page, array $params, int $producerId): object|null;

    public function store(array $data): object|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

}
