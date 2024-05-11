<?php

namespace App\Repositories;

use App\Models\Sector;
use PhpParser\Node\Expr\Array_;

interface SectorRepositoryInterface
{

    public function __construct(Sector $data);

    public function index(int $paginate, int $page, string $search): object|null;

    public function show(int $id): object|null;

    public function store(array $data): object|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

}
