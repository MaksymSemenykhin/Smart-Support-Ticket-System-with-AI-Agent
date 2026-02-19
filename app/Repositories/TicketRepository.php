<?php

namespace App\Repositories;

use App\Contracts\TicketRepositoryInterface;
use App\Models\Ticket;
use Illuminate\Pagination\LengthAwarePaginator;

class TicketRepository implements TicketRepositoryInterface
{
    public function getAllByUser(int $userId): LengthAwarePaginator
    {
        return Ticket::where('user_id', $userId)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function getById(int $id, int $userId): ?Ticket
    {
        return Ticket::where('user_id', $userId)
            ->with('category')
            ->find($id);
    }

    public function create(array $data): Ticket
    {
        return Ticket::create($data);
    }

    public function update(int $id, array $data): Ticket
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->update($data);

        return $ticket->fresh();
    }

    public function delete(int $id, int $userId): bool
    {
        $ticket = Ticket::where('user_id', $userId)->find($id);
        if (! $ticket) {
            return false;
        }

        return $ticket->delete();
    }
}
