<?php

namespace Tests\Feature\Api;

use App\Enums\AiStatus;
use App\Enums\TicketSentiment;
use App\Enums\TicketStatus;
use App\Enums\TicketUrgency;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_ticket_requires_authentication(): void
    {
        $this->postJson('/api/tickets', [
            'title' => 'Test Ticket',
            'description' => 'This is a test ticket description.',
        ])->assertUnauthorized();
    }

    public function test_create_ticket_with_valid_data(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tickets', [
                'title' => 'My computer is not working',
                'description' => 'My computer is not working properly and I need help.',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'ticket' => ['id', 'title', 'status', 'ai_status', 'created_at'],
            ])
            ->assertJsonPath('ticket.status', TicketStatus::OPEN->value)
            ->assertJsonPath('ticket.ai_status', AiStatus::QUEUED->value);

        $this->assertDatabaseHas('tickets', [
            'user_id' => $user->id,
            'title' => 'My computer is not working',
            'status' => TicketStatus::OPEN->value,
        ]);
    }

    public function test_create_ticket_validation_fails_without_required_fields(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tickets', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);
    }

    public function test_create_ticket_validates_description_min_length(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tickets', [
                'title' => 'Test',
                'description' => 'Short',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    public function test_get_ticket_by_id(): void
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
            'title' => 'Test Ticket',
            'description' => 'This is a test ticket description for testing.',
            'status' => TicketStatus::OPEN,
            'sentiment' => TicketSentiment::NEGATIVE,
            'urgency' => TicketUrgency::HIGH,
            'suggested_reply' => 'We will help you.',
            'ai_status' => AiStatus::COMPLETED,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tickets/'.$ticket->id);

        $response->assertOk()
            ->assertJsonPath('id', $ticket->id)
            ->assertJsonPath('title', 'Test Ticket')
            ->assertJsonPath('category', 'Technical')
            ->assertJsonPath('sentiment', TicketSentiment::NEGATIVE->value)
            ->assertJsonPath('urgency', TicketUrgency::HIGH->value)
            ->assertJsonPath('suggested_reply', 'We will help you.');
    }

    public function test_user_cannot_access_other_users_tickets(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $token = $user2->createToken('api-token')->plainTextToken;

        $ticket = Ticket::create([
            'user_id' => $user1->id,
            'title' => 'User 1 Ticket',
            'description' => 'This belongs to user 1.',
            'status' => TicketStatus::OPEN,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tickets/'.$ticket->id)
            ->assertStatus(404);
    }

    public function test_list_tickets(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        Ticket::create([
            'user_id' => $user->id,
            'title' => 'Ticket 1',
            'description' => 'Description 1 for testing purposes.',
            'status' => TicketStatus::OPEN,
        ]);

        Ticket::create([
            'user_id' => $user->id,
            'title' => 'Ticket 2',
            'description' => 'Description 2 for testing purposes.',
            'status' => TicketStatus::RESOLVED,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tickets');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'status', 'created_at'],
                ],
                'current_page',
                'total',
            ])
            ->assertJsonPath('total', 2);
    }

    public function test_update_ticket_status(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Test Ticket',
            'description' => 'This is a test ticket description for testing.',
            'status' => TicketStatus::OPEN,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/tickets/'.$ticket->id, [
                'status' => TicketStatus::RESOLVED->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('ticket.status', TicketStatus::RESOLVED->value);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => TicketStatus::RESOLVED->value,
        ]);
    }
}
