<?php

namespace App\Http\Controllers\Api;

use App\Contracts\TicketRepositoryInterface;
use App\Enums\AiStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Jobs\ProcessTicketJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class TicketController extends Controller
{
    public function __construct(
        private TicketRepositoryInterface $ticketRepository
    ) {}

    #[OA\Get(
        path: '/api/tickets',
        summary: 'List all tickets for current user',
        security: [['bearerAuth' => []]],
        tags: ['Tickets'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'title', type: 'string'),
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(property: 'ai_status', type: 'string'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        )),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $tickets = $this->ticketRepository->getAllByUser(Auth::id());

        return response()->json($tickets);
    }

    #[OA\Post(
        path: '/api/tickets',
        summary: 'Create a new ticket',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'My computer is broken'),
                    new OA\Property(property: 'description', type: 'string', example: 'My computer is not working properly and I need help immediately.'),
                ]
            )
        ),
        tags: ['Tickets'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'ticket', properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'title', type: 'string'),
                            new OA\Property(property: 'status', type: 'string'),
                            new OA\Property(property: 'ai_status', type: 'string'),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $ticket = $this->ticketRepository->create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => TicketStatus::OPEN,
            'ai_status' => AiStatus::QUEUED,
        ]);

        ProcessTicketJob::dispatch($ticket);

        return response()->json([
            'message' => __('api.tickets.created'),
            'ticket' => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'status' => $ticket->status,
                'ai_status' => $ticket->ai_status,
                'created_at' => $ticket->created_at,
            ],
        ], 201);
    }

    #[OA\Get(
        path: '/api/tickets/{id}',
        summary: 'Get ticket by ID',
        security: [['bearerAuth' => []]],
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'category', type: 'string', nullable: true),
                        new OA\Property(property: 'sentiment', type: 'string', nullable: true),
                        new OA\Property(property: 'urgency', type: 'string', nullable: true),
                        new OA\Property(property: 'suggested_reply', type: 'string', nullable: true),
                        new OA\Property(property: 'ai_status', type: 'string'),
                        new OA\Property(property: 'ai_error', type: 'string', nullable: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Ticket not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $ticket = $this->ticketRepository->getById($id, Auth::id());

        if (! $ticket) {
            return response()->json(['message' => __('api.tickets.not_found')], 404);
        }

        return response()->json([
            'id' => $ticket->id,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'status' => $ticket->status,
            'category' => $ticket->category?->name,
            'sentiment' => $ticket->sentiment,
            'urgency' => $ticket->urgency,
            'suggested_reply' => $ticket->suggested_reply,
            'ai_status' => $ticket->ai_status,
            'ai_error' => $ticket->ai_error,
            'created_at' => $ticket->created_at,
            'updated_at' => $ticket->updated_at,
        ]);
    }

    #[OA\Put(
        path: '/api/tickets/{id}',
        summary: 'Update ticket status',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['open', 'in_progress', 'resolved', 'closed'], example: 'resolved'),
                ]
            )
        ),
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'ticket', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Ticket not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateTicketRequest $request, int $id): JsonResponse
    {
        $ticket = $this->ticketRepository->getById($id, Auth::id());

        if (! $ticket) {
            return response()->json(['message' => __('api.tickets.not_found')], 404);
        }

        $validated = $request->validated();

        if (! empty($validated['status'])) {
            $ticket = $this->ticketRepository->update($id, ['status' => $validated['status']]);
        }

        return response()->json([
            'message' => __('api.tickets.updated'),
            'ticket' => $ticket,
        ]);
    }
}
