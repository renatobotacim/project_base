<?php

namespace App\Repositories;

use App\Models\City;

interface CityRepositoryInterface
{

    public function __construct(City $data);

    public function index(string $search): object|null;

    public function show(int $id): object|null;

    public function states(string $state): object|null;

}
