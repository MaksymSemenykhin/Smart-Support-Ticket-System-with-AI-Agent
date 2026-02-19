<?php

namespace App\Jobs;

use App\Enums\AiStatus;
use App\Enums\TicketSentiment;
use App\Enums\TicketUrgency;
use App\Models\Ticket;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Ticket $ticket
    ) {}

    public function handle(AiService $aiService): void
    {
        $this->ticket->update(['ai_status' => AiStatus::PROCESSING]);

        try {
            $result = $aiService->analyzeTicket($this->ticket->description);

            $this->ticket->update([
                'category_id' => $result['category_id'],
                'sentiment' => TicketSentiment::from($result['sentiment']),
                'urgency' => TicketUrgency::from($result['urgency']),
                'suggested_reply' => $result['reply'],
                'ai_status' => AiStatus::COMPLETED,
                'ai_error' => null,
            ]);

            Log::info('Ticket processed successfully', ['ticket_id' => $this->ticket->id]);
        } catch (\Exception $e) {
            Log::error('Ticket processing failed', [
                'ticket_id' => $this->ticket->id,
                'error' => $e->getMessage(),
            ]);

            $this->ticket->update([
                'ai_status' => AiStatus::FAILED,
                'ai_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->ticket->update([
            'ai_status' => AiStatus::FAILED,
            'ai_error' => $exception->getMessage(),
        ]);

        Log::error('Ticket job failed permanently', [
            'ticket_id' => $this->ticket->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
