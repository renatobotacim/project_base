<?php

namespace App\Repositories;

use App\Models\Owner;
use PhpParser\Node\Expr\Array_;

interface OwnerRepositoryInterface
{

    public function __construct(Owner $data);

    public function index(int $paginate, int $page, string $search): object|null;

    public function show(int $id): object|null;

    public function store(array $data): object|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}
