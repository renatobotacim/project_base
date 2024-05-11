<?php

namespace App\Repositories;

use App\Models\Batch;
use PhpParser\Node\Expr\Array_;

interface BatchRepositoryInterface
{

    public function __construct(Batch $data);

    public function index(int $paginate, int $page, string $search): object|null;

    public function show(int $id): object|null;

    public function store(array $data): object|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

}
