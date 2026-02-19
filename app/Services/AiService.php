<?php

namespace App\Services;

use App\Contracts\AiServiceInterface;
use App\Models\Category;
use App\Models\PromptSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService implements AiServiceInterface
{
    public function analyzeTicket(string $description): array
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return $this->mockAnalysis($description);
        }

        return $this->realAnalysis($description, $apiKey);
    }

    private function getSystemPrompt(): string
    {
        $categories = Category::where('is_active', true)->pluck('name')->toArray();
        $sentiments = ['Positive', 'Neutral', 'Negative'];
        $urgencies = ['low', 'medium', 'high'];

        $promptSetting = PromptSetting::where('key', 'system_prompt')
            ->where('is_active', true)
            ->first();

        if ($promptSetting) {
            $prompt = $promptSetting->value;
        } else {
            $prompt = <<<'PROMPT'
You are a helpful customer support agent. Analyze the support ticket and respond with ONLY valid JSON (no markdown formatting, no explanations).
PROMPT;
        }

        $prompt .= "\n\nYour response must be valid JSON with these exact keys:\n";
        $prompt .= '- category: One of "'.implode('", "', $categories)."\"\n";
        $prompt .= '- sentiment: One of "'.implode('", "', $sentiments)."\"\n";
        $prompt .= '- urgency: One of "'.implode('", "', $urgencies).'" (based on sentiment and content)'."\n";
        $prompt .= '- reply: A helpful, professional response to the customer'."\n\n";
        $prompt .= 'Example format:'."\n";
        $prompt .= '{"category": "'.$categories[0].'", "sentiment": "'.$sentiments[1].'", "urgency": "medium", "reply": "We understand..."}'."\n\n";
        $prompt .= 'Analyze this ticket:';

        return $prompt;
    }

    private function realAnalysis(string $description, string $apiKey): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-3.5-turbo'),
                'messages' => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt()],
                    ['role' => 'user', 'content' => $description],
                ],
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]);

            if (! $response->successful()) {
                Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);

                return $this->mockAnalysis($description);
            }

            $content = $response->json('choices.0.message.content');
            $content = trim($content);

            if (preg_match('/\{.*\}/s', $content, $matches)) {
                $result = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $category = $this->findCategoryByName($result['category'] ?? 'General');

                    return [
                        'category_id' => $category?->id,
                        'sentiment' => $result['sentiment'] ?? 'Neutral',
                        'urgency' => $result['urgency'] ?? 'medium',
                        'reply' => $result['reply'] ?? 'Thank you for contacting us.',
                    ];
                }
            }

            return $this->mockAnalysis($description);
        } catch (\Exception $e) {
            Log::error('AI service exception', ['message' => $e->getMessage()]);

            return $this->mockAnalysis($description);
        }
    }

    private function findCategoryByName(string $name): ?Category
    {
        return Category::where('name', $name)
            ->orWhere('slug', strtolower(str_replace(' ', '-', $name)))
            ->first();
    }

    private function mockAnalysis(string $description): array
    {
        $descriptionLower = strtolower($description);

        $sentiment = 'Neutral';
        if (preg_match('/(angry|frustrated|terrible|awful|worst|hate|broken|not working|fatal|crash)/i', $description)) {
            $sentiment = 'Negative';
        } elseif (preg_match('/(thank|great|love|excellent|awesome|perfect|happy)/i', $description)) {
            $sentiment = 'Positive';
        }

        $urgency = match ($sentiment) {
            'Negative' => 'high',
            'Positive' => 'low',
            default => 'medium',
        };

        if (preg_match('/(urgent|immediately|asap|critical|emergency|down)/i', $description)) {
            $urgency = 'high';
        }

        $categoryName = 'General';
        if (preg_match('/(error|bug|crash|not working|broken|issue|problem|how to|help)/i', $description)) {
            $categoryName = 'Technical';
        } elseif (preg_match('/(payment|invoice|bill|charge|refund|price|cost|subscription)/i', $description)) {
            $categoryName = 'Billing';
        } elseif (preg_match('/(account|login|password|email|profile|delete)/i', $description)) {
            $categoryName = 'Account';
        } elseif (preg_match('/(feature|request|suggest|would be nice|add|implement)/i', $description)) {
            $categoryName = 'Feature Request';
        }

        $category = $this->findCategoryByName($categoryName);

        $reply = match ($categoryName) {
            'Technical' => 'Thank you for reporting this technical issue. Our support team will investigate and get back to you with a solution.',
            'Billing' => 'Thank you for contacting us regarding your billing concern. Our billing team will review your request and respond shortly.',
            'Account' => "Thank you for reaching out about your account. We'll help you resolve this as quickly as possible.",
            'Feature Request' => "Thank you for your suggestion! We've noted your feature request and will consider it for future updates.",
            default => 'Thank you for contacting our support team. We appreciate your message and will respond as soon as possible.',
        };

        return [
            'category_id' => $category?->id,
            'sentiment' => $sentiment,
            'urgency' => $urgency,
            'reply' => $reply,
        ];
    }
}
