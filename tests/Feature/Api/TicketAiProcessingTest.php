<?php

namespace Tests\Feature\Api;

use App\Enums\AiStatus;
use App\Enums\TicketSentiment;
use App\Enums\TicketStatus;
use App\Enums\TicketUrgency;
use App\Jobs\ProcessTicketJob;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketAiProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_is_created_and_ai_job_is_dispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tickets', [
                'title' => 'My computer is broken',
                'description' => 'My computer is not working and I need help immediately.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('ticket.ai_status', AiStatus::QUEUED->value);

        Queue::assertPushed(ProcessTicketJob::class);
    }

    public function test_ai_job_processes_ticket_and_updates_fields(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'Technical',
            'slug' => 'technical',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Test ticket',
            'description' => 'My computer is broken and not working!',
            'status' => TicketStatus::OPEN,
            'ai_status' => AiStatus::QUEUED,
        ]);

        $job = new ProcessTicketJob($ticket);
        $job->handle(app(AiService::class));

        $ticket->refresh();

        $this->assertEquals(AiStatus::COMPLETED->value, $ticket->ai_status->value);
        $this->assertNotNull($ticket->category_id);
        $this->assertNotNull($ticket->sentiment);
        $this->assertNotNull($ticket->urgency);
        $this->assertNotNull($ticket->suggested_reply);
    }

    public function test_ai_job_detects_negative_sentiment_for_frustrated_tone(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Terrible service!',
            'description' => 'I am extremely frustrated with this broken product. This is the worst experience ever!',
            'status' => TicketStatus::OPEN,
            'ai_status' => AiStatus::QUEUED,
        ]);

        $job = new ProcessTicketJob($ticket);
        $job->handle(app(AiService::class));

        $ticket->refresh();

        $this->assertEquals(TicketSentiment::NEGATIVE->value, $ticket->sentiment->value);
        $this->assertEquals(TicketUrgency::HIGH->value, $ticket->urgency->value);
    }

    public function test_ai_job_detects_positive_sentiment_for_thankful_tone(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Great job!',
            'description' => 'Thank you so much for your excellent support! I love this product and it works perfectly!',
            'status' => TicketStatus::OPEN,
            'ai_status' => AiStatus::QUEUED,
        ]);

        $job = new ProcessTicketJob($ticket);
        $job->handle(app(AiService::class));

        $ticket->refresh();

        $this->assertEquals(TicketSentiment::POSITIVE->value, $ticket->sentiment->value);
        $this->assertEquals(TicketUrgency::LOW->value, $ticket->urgency->value);
    }

    public function test_ai_job_categorizes_technical_issues(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'Technical',
            'slug' => 'technical',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Bug in the system',
            'description' => 'There is a critical error and the application keeps crashing. I need help ASAP!',
            'status' => TicketStatus::OPEN,
            'ai_status' => AiStatus::QUEUED,
        ]);

        $job = new ProcessTicketJob($ticket);
        $job->handle(app(AiService::class));

        $ticket->refresh();

        $this->assertEquals($category->id, $ticket->category_id);
    }

    public function test_ai_job_categorizes_billing_issues(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'Billing',
            'slug' => 'billing',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Payment issue',
            'description' => 'I was charged twice for my subscription. Please process a refund.',
            'status' => TicketStatus::OPEN,
            'ai_status' => AiStatus::QUEUED,
        ]);

        $job = new ProcessTicketJob($ticket);
        $job->handle(app(AiService::class));

        $ticket->refresh();

        $this->assertEquals($category->id, $ticket->category_id);
    }

    public function test_ai_job_updates_status_to_processing_before_starting(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Test ticket',
            'description' => 'Test description.',
            'status' => TicketStatus::OPEN,
            'ai_status' => AiStatus::QUEUED,
        ]);

        $job = new ProcessTicketJob($ticket);

        $this->assertEquals(AiStatus::QUEUED->value, $ticket->ai_status->value);

        $job->handle(app(AiService::class));

        $ticket->refresh();

        $this->assertEquals(AiStatus::COMPLETED->value, $ticket->ai_status->value);
    }

    public function test_ticket_returns_ai_analysis_in_response(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $category = Category::create([
            'name' => 'Technical',
            'slug' => 'technical',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test ticket',
            'description' => 'Test description.',
            'status' => TicketStatus::OPEN,
            'sentiment' => TicketSentiment::NEGATIVE,
            'urgency' => TicketUrgency::HIGH,
            'suggested_reply' => 'We will help you.',
            'ai_status' => AiStatus::COMPLETED,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tickets/'.$ticket->id);

        $response->assertOk()
            ->assertJsonPath('category', 'Technical')
            ->assertJsonPath('sentiment', TicketSentiment::NEGATIVE->value)
            ->assertJsonPath('urgency', TicketUrgency::HIGH->value)
            ->assertJsonPath('suggested_reply', 'We will help you.')
            ->assertJsonPath('ai_status', AiStatus::COMPLETED->value);
    }

    public function test_ai_job_generates_suggested_reply(): void
    {
        $user = User::factory()->create();

        Category::create([
            'name' => 'Technical',
            'slug' => 'technical',
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'System not working',
            'description' => 'The system is down and I cannot access my account.',
            'status' => TicketStatus::OPEN,
            'ai_status' => AiStatus::QUEUED,
        ]);

        $job = new ProcessTicketJob($ticket);
        $job->handle(app(AiService::class));

        $ticket->refresh();

        $this->assertNotNull($ticket->suggested_reply);
        $this->assertIsString($ticket->suggested_reply);
        $this->assertStringContainsString('Thank you', $ticket->suggested_reply);
    }
}
