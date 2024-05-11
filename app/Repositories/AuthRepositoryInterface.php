<?php

namespace App\Repositories;

use App\Models\User;
use PhpParser\Node\Expr\Array_;

interface AuthRepositoryInterface
{

    public function __construct(User $data);

    public function index(int $paginate, int $page, string $search): object|null;

    public function permissions(int $paginate, int $page, int $producerId, string $search): object|null;

    public function permissionsAdmMaster(int $paginate, int $page, string $search = null, string $status): object|null;

    public function show(int $id): object|null;

    public function showEmail(string $email): object|null;

    public function showCpf(string $cpf): object|null;

    public function store(array $data): object|null;

    public function storePermissions(User $user, array $data): array|null;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function checkPermission(int $userId): array|null;

}
