<?php

namespace App\Models;

use App\Enums\AiStatus;
use App\Enums\TicketSentiment;
use App\Enums\TicketStatus;
use App\Enums\TicketUrgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Ticket', description: 'Support ticket model')]
class Ticket extends Model
{
    #[OA\Property(property: 'id', description: 'Ticket ID', type: 'integer')]
    #[OA\Property(property: 'user_id', description: 'User ID', type: 'integer')]
    #[OA\Property(property: 'category_id', description: 'Category ID', type: 'integer', nullable: true)]
    #[OA\Property(property: 'title', description: 'Ticket title', type: 'string')]
    #[OA\Property(property: 'description', description: 'Ticket description', type: 'string')]
    #[OA\Property(property: 'status', description: 'Ticket status', type: 'string', enum: ['open', 'in_progress', 'resolved', 'closed'])]
    #[OA\Property(property: 'is_stale', description: 'Ticket is stale (not updated for 1 week)', type: 'boolean')]
    #[OA\Property(property: 'sentiment', description: 'AI detected sentiment', type: 'string', enum: ['Positive', 'Neutral', 'Negative'], nullable: true)]
    #[OA\Property(property: 'urgency', description: 'AI detected urgency', type: 'string', enum: ['low', 'medium', 'high'], nullable: true)]
    #[OA\Property(property: 'suggested_reply', description: 'AI suggested reply', type: 'string', nullable: true)]
    #[OA\Property(property: 'ai_status', description: 'AI processing status', type: 'string', enum: ['queued', 'processing', 'completed', 'failed'])]
    #[OA\Property(property: 'ai_error', description: 'AI processing error', type: 'string', nullable: true)]
    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time')]
    #[OA\Property(property: 'updated_at', type: 'string', format: 'date-time')]
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'status',
        'is_stale',
        'sentiment',
        'urgency',
        'suggested_reply',
        'ai_status',
        'ai_error',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'is_stale' => 'boolean',
        'sentiment' => TicketSentiment::class,
        'urgency' => TicketUrgency::class,
        'ai_status' => AiStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
