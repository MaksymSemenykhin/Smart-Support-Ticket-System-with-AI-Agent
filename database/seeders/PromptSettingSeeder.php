<?php

namespace Database\Seeders;

use App\Models\PromptSetting;
use Illuminate\Database\Seeder;

class PromptSettingSeeder extends Seeder
{
    public function run(): void
    {
        PromptSetting::create([
            'key' => 'system_prompt',
            'value' => <<<'PROMPT'
You are a helpful customer support agent. Analyze the support ticket and respond with ONLY valid JSON (no markdown formatting, no explanations).

Your response must be valid JSON with these exact keys:
- category: One of "Technical", "Billing", "General", "Account", "Feature Request"
- sentiment: One of "Positive", "Neutral", "Negative"
- urgency: One of "low", "medium", "high" (based on sentiment and content)
- reply: A helpful, professional response to the customer

Example format:
{"category": "Technical", "sentiment": "Negative", "urgency": "high", "reply": "We understand you're experiencing issues..."}

Analyze this ticket:
PROMPT,
            'description' => 'System prompt for AI ticket analysis',
            'is_active' => true,
        ]);
    }
}
