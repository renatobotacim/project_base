<?php

namespace App\Repositories;

use App\Models\Maps;
use PhpParser\Node\Expr\Array_;

interface MapsRepositoryInterface
{

    public function __construct(Maps $data);

    public function index(int $paginate, int $page, string $search, int $producerId = null): object|null;

    public function show(int $id): object|null;

    public function store(array $data): object|null;

    public function storeSectors(Maps $map, array $data): object|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

}
