<?php

use App\Enums\AiStatus;
use App\Enums\TicketSentiment;
use App\Enums\TicketStatus;
use App\Enums\TicketUrgency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('status', array_column(TicketStatus::cases(), 'value'))->default(TicketStatus::OPEN->value);
            $table->enum('sentiment', array_column(TicketSentiment::cases(), 'value'))->nullable();
            $table->enum('urgency', array_column(TicketUrgency::cases(), 'value'))->nullable();
            $table->text('suggested_reply')->nullable();
            $table->enum('ai_status', array_column(AiStatus::cases(), 'value'))->default(AiStatus::QUEUED->value);
            $table->text('ai_error')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
