<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'PromptSetting', description: 'AI prompt settings model')]
class PromptSetting extends Model
{
    #[OA\Property(property: 'id', description: 'Setting ID', type: 'integer')]
    #[OA\Property(property: 'key', description: 'Setting key', type: 'string')]
    #[OA\Property(property: 'value', description: 'Setting value', type: 'string')]
    #[OA\Property(property: 'description', description: 'Setting description', type: 'string', nullable: true)]
    #[OA\Property(property: 'is_active', description: 'Is setting active', type: 'boolean')]
    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time')]
    #[OA\Property(property: 'updated_at', type: 'string', format: 'date-time')]
    protected $fillable = [
        'key',
        'value',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function getSystemPrompt(): string
    {
        $setting = self::where('key', 'system_prompt')->where('is_active', true)->first();

        return $setting?->value ?? self::defaultPrompt();
    }

    public static function getCategories(): array
    {
        return Category::where('is_active', true)
            ->pluck('name')
            ->toArray();
    }

    public static function getSentiments(): array
    {
        return ['Positive', 'Neutral', 'Negative'];
    }

    public static function getUrgencies(): array
    {
        return ['low', 'medium', 'high'];
    }

    private static function defaultPrompt(): string
    {
        return <<<'PROMPT'
You are a helpful customer support agent. Analyze the support ticket and respond with ONLY valid JSON (no markdown formatting, no explanations).

Your response must be valid JSON with these exact keys:
- category: One of "Technical", "Billing", "General", "Account", "Feature Request"
- sentiment: One of "Positive", "Neutral", "Negative"
- urgency: One of "low", "medium", "high" (based on sentiment and content)
- reply: A helpful, professional response to the customer

Example format:
{"category": "Technical", "sentiment": "Negative", "urgency": "high", "reply": "We understand you're experiencing issues..."}

Analyze this ticket:
PROMPT;
    }
}
