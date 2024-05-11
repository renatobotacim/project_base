<?php

namespace App\Repositories;

use App\Models\Hit;

interface HitRepositoryInterface
{

    public function __construct(Hit $data);

    public function index(string $search): object|null;

    public function show(int $id): object|null;

    public function store(array $data): object|null;

}
