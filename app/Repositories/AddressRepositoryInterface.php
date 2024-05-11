<?php

namespace App\Repositories;

use App\Models\Address;
use PhpParser\Node\Expr\Array_;

interface AddressRepositoryInterface
{

    public function __construct(Address $data);

    public function index(int $paginate, int $page, string $search, int $producerId = null): object|null;

    public function show(int $id): object|null;

    public function store(array $data): object|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}
