<?php

namespace App\Repositories;

use App\Models\Event;

interface EventRepositoryInterface
{

    public function __construct(Event $data);

    public function index(int $paginate, int $page, array $params): object|null;

    public function search(int $paginate, int $page, array $params): object|null;

    public function panel(int $params);

    public function show(int $id, string $event, string $slug): object|null;

    public function list(int $paginate, int $page, string $search, int $producer_id): object|null;

    public function listActive(int $producer_id): object|null;

    public function count(int $producerId = null): int;

    public function mapTickets(?int $mapId = null, int $producerId): object|null;

    public function store(array $data): object|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;


}
