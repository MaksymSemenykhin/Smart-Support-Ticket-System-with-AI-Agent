# Smart Support Ticket System with AI Agent

A Laravel-based backend API where users submit support tickets, and an AI Agent automatically categorizes the ticket, analyzes sentiment, and drafts a response.

## Features

- RESTful API for ticket management
- AI-powered ticket analysis (category, sentiment, urgency)
- Auto-generated suggested replies
- Background job processing for AI analysis
- JWT authentication via Laravel Sanctum
- Swagger API documentation

## Requirements

- PHP 8.2+
- Composer
- MySQL/PostgreSQL or SQLite

## Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Database

Edit `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Run Migrations & Seeds

```bash
php artisan migrate
php artisan db:seed
```

### 5. (Optional) Configure OpenAI

To use real AI instead of mock:

```env
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_MODEL=gpt-3.5-turbo
```

If no API key is set, the system uses a mock AI service that simulates analysis.

### 6. Start Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

### 7. Queue Worker (for AI processing)

```bash
php artisan queue:listen
```

Or use sync mode for development:

```env
QUEUE_CONNECTION=sync
```

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/register | Register new user |
| POST | /api/auth/login | Login user |
| GET | /api/auth/me | Get current user |
| POST | /api/auth/logout | Logout user |

### Tickets (require authentication)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/tickets | List user's tickets |
| POST | /api/tickets | Create new ticket |
| GET | /api/tickets/{id} | Get ticket details |
| PUT | /api/tickets/{id} | Update ticket status |

### API Documentation

Swagger documentation is available at: `/api/documentation`

## Example Usage

### Register

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"secret123"}'
```

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"secret123"}'
```

### Create Ticket

```bash
curl -X POST http://localhost:8000/api/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"My computer is broken","description":"My computer is not working and I need help immediately."}'
```

### Get Ticket Details

```bash
curl -X GET http://localhost:8000/api/tickets/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Prompt Strategy

The AI service uses a carefully crafted system prompt that:

1. **Specifies JSON-only output**: The prompt explicitly instructs the AI to return only valid JSON with no markdown formatting
2. **Defines exact categories**: Provides a fixed list of valid values for category, sentiment, and urgency from the database
3. **Sets temperature to 0.3**: Low temperature ensures consistent, focused responses
4. **Includes fallback handling**: If the AI returns malformed JSON, the service falls back to a rule-based mock analysis
5. **Dynamic categories**: Categories are loaded from the database, allowing easy extension

The prompt also instructs the AI to act as a "Helpful Customer Support Agent" to ensure the suggested replies are professional and empathetic.

## Architecture

```
app/
├── Contracts/          # Interfaces
│   └── TicketRepositoryInterface.php
├── Enums/              # Enumerations
│   ├── AiStatus.php
│   ├── TicketSentiment.php
│   ├── TicketStatus.php
│   └── TicketUrgency.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   └── TicketController.php
│   └── Requests/
│       ├── Auth/
│       │   ├── LoginRequest.php
│       │   └── RegisterRequest.php
│       └── Ticket/
│           ├── StoreTicketRequest.php
│           └── UpdateTicketRequest.php
├── Jobs/
│   └── ProcessTicketJob.php
├── Models/
│   ├── Category.php
│   ├── PromptSetting.php
│   ├── Ticket.php
│   └── User.php
├── Repositories/
│   └── TicketRepository.php
└── Services/
    ├── AiService.php
    └── UserService.php
```

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=TicketApiTest
php artisan test --filter=TicketAiProcessingTest
php artisan test --filter=AuthApiTest
```

## Database Schema

### tickets
- id (bigint)
- user_id (foreign key)
- category_id (foreign key, nullable)
- title (string)
- description (text)
- status (enum: open, in_progress, resolved, closed)
- sentiment (enum: Positive, Neutral, Negative, nullable)
- urgency (enum: low, medium, high, nullable)
- suggested_reply (text, nullable)
- ai_status (enum: queued, processing, completed, failed)
- ai_error (text, nullable)
- timestamps
- soft_deletes

### categories
- id (bigint)
- name (string)
- slug (string)
- description (text, nullable)
- is_active (boolean)
- timestamps

### prompt_settings
- id (bigint)
- key (string, unique)
- value (text)
- description (text, nullable)
- is_active (boolean)
- timestamps

## Technology Stack

- **Framework**: Laravel 12
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **API Documentation**: Swagger (L5 Swagger)
- **Queue**: Database queue (configurable)
