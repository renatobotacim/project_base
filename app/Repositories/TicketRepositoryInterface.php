<?php

namespace App\Repositories;

use App\Models\Ticket;

interface TicketRepositoryInterface
{

    public function __construct(Ticket $data);

    public function index(int $paginate, int $page, string $search): object|null;

    public function show(int $id = null, string $ticket = null): object|null;

    public function listTicketsClear(string $now): object|null;

    public function showByUsers(int $userId): object|null;

    public function showByEvent(int $eventId, int $userId): object|null;

    public function store(array $data): object|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function clearTickets(string $now): bool;

}
