<?php

namespace App\Services;

use App\DTOs\ClassificationDTO;
use App\Models\Ticket;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;

class TicketClassifier
{
    public function classify(Ticket $ticket): ClassificationDTO
    {
        $enabled = filter_var(env('OPENAI_CLASSIFY_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
        $model   = config('services.classify.model', 'gpt-4o-mini');

        if (!$enabled || empty(config('openai.api_key'))) {
            return $this->fallback($ticket);
        }

        $sys = <<<SYS
            You are a precise ticket classifier for a helpdesk.
            Allowed categories: ["billing","technical","account","other"].
            Return ONLY compact JSON with keys: category, explanation, confidence (0..1).
            - category must be exactly one of the allowed values (or null if unknown).
            - explanation max 200 characters.
            - confidence as a number between 0 and 1.
            SYS;

        $usr = <<<USR
            Subject: {$ticket->subject}

            Body:
            {$ticket->body}
            USR;

        $resp = OpenAI::chat()->create([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $sys],
                ['role' => 'user',   'content' => $usr],
            ],
            'temperature' => 0.0,
        ]);

        $content = $resp->choices[0]->message->content ?? '{}';
        // strip code fences if any
        $content = preg_replace('/^```json|```$/m', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return $this->fallback($ticket);
        }

        $category    = $this->sanitizeCategory($data['category'] ?? null);
        $explanation = $this->clip((string)($data['explanation'] ?? ''), 200);
        $confidence  = $this->clamp((float)($data['confidence'] ?? 0.0), 0.0, 1.0);

        return new ClassificationDTO($category, $explanation ?: null, $confidence);
    }

    private function sanitizeCategory(?string $cat): ?string
    {
        if (!$cat) return null;
        $cat = strtolower($cat);
        return in_array($cat, ['billing','technical','account','other'], true) ? $cat : null;
    }

    private function clamp(float $v, float $min, float $max): float
    {
        return max($min, min($max, $v));
    }

    private function clip(string $s, int $limit): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $s)), $limit, '');
    }

    private function fallback(Ticket $t): ClassificationDTO
    {
        $text = strtolower($t->subject.' '.$t->body);

        $map = [
            'technical' => ['error','bug','crash','issue','cannot','failed','server','api','database'],
            'billing'   => ['invoice','refund','payment','charge','bill','pricing'],
            'account'   => ['login','password','account','profile','signup','2fa'],
        ];

        $chosen = null;
        foreach ($map as $cat => $keys) {
            foreach ($keys as $k) {
                if (str_contains($text, $k)) { $chosen = $cat; break 2; }
            }
        }

        $chosen ??= 'other';
        $exp = "Heuristic match based on keywords.";
        $conf = $chosen === 'other' ? 0.55 : 0.75;

        return new ClassificationDTO($chosen, $exp, $conf);
    }

}
