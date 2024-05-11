<?php

namespace App\Repositories;

use App\Models\Withdrawal;

interface WithdrawalRepositoryInterface
{

    public function __construct(Withdrawal $data);

    public function index(int $paginate, int $page, string $search=null): object|null;

    public function history(int $paginate, int $page, string $search, int $producerId): object|null;

    public function showBalance(int $producerId): object|null;

    public function show(int $id): object|null;

    public function generateCode(array $data): bool;

    public function validateCode(string $code): bool;

    public function store(array $data): object|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

}
