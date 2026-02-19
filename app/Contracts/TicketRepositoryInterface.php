<?php

namespace App\Contracts;

use App\Models\Ticket;
use Illuminate\Pagination\LengthAwarePaginator;

interface TicketRepositoryInterface
{
    public function getAllByUser(int $userId): LengthAwarePaginator;

    public function getById(int $id, int $userId): ?Ticket;

    public function create(array $data): Ticket;

    public function update(int $id, array $data): Ticket;

    public function delete(int $id, int $userId): bool;
}
