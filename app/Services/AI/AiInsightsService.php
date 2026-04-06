<?php

namespace App\Services\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class AiInsightsService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Du bist der integrierte Finanzassistent von FinanzPilot, einer persönlichen Finanz-App für Haushaltsführung. Der Nutzer verwaltet hier bereits Konten, Buchungen, Kategorien und Kredite. Empfehle keine externen Tools oder Apps — der Nutzer hat alles in FinanzPilot.

Analysiere die anonymisierten Finanzdaten und gib konkrete, umsetzbare Empfehlungen auf Deutsch.

Die Daten sind als Prozentwerte normalisiert. Verwende in deiner Antwort ausschließlich Prozentangaben — nenne niemals absolute Beträge, Einheiten oder Euro-Werte.

Regeln:
- Nenne keine absoluten Beträge, keine "Einheiten", keine Euro-Werte — nur Prozente
- Maximal 3-4 konkrete Empfehlungen
- Jede Empfehlung sollte eine spezifische Handlung vorschlagen
- Beziehe dich auf Funktionen in FinanzPilot (z.B. Kategorien anpassen, Kredite prüfen, Konten vergleichen)
- Fokussiere auf Trends und Veränderungen, nicht auf statische Zahlen
- Sei direkt und konkret, keine allgemeinen Spartipps
- Antworte in 150-200 Wörtern
- Verwende Aufzählungszeichen für Übersichtlichkeit
PROMPT;

    public function getInsights(): ?array
    {
        $provider = $this->resolveProvider();
        if (! $provider) {
            return null;
        }

        $snapshot = FinancialSnapshot::capture();

        if (empty($snapshot->monthlyRatios)) {
            return null;
        }

        // Check cache
        $cacheKey = 'ai_insights_'.$snapshot->hash;
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $response = $provider->chat(
                self::SYSTEM_PROMPT,
                $snapshot->toPromptContext()
            );

            $result = [
                'insights' => $response,
                'provider' => $provider->getName(),
                'generatedAt' => now()->toIso8601String(),
                'snapshotHash' => $snapshot->hash,
            ];

            // Cache for 24 hours per snapshot hash
            Cache::put($cacheKey, $result, now()->addHours(24));

            return $result;
        } catch (\Throwable $e) {
            return [
                'insights' => null,
                'error' => 'KI-Analyse fehlgeschlagen: '.$e->getMessage(),
                'provider' => $provider->getName(),
            ];
        }
    }

    /**
     * Force refresh insights (bypass cache).
     */
    public function refreshInsights(): ?array
    {
        $snapshot = FinancialSnapshot::capture();
        Cache::forget('ai_insights_'.$snapshot->hash);

        return $this->getInsights();
    }

    private function resolveProvider(): ?AiProviderInterface
    {
        $providerType = Setting::get('ai_provider', 'none');

        $apiKey = null;
        $rawKey = Setting::get('ai_api_key');
        if ($rawKey) {
            try {
                $apiKey = decrypt($rawKey);
            } catch (\Throwable) {
                $apiKey = $rawKey; // Fallback for unencrypted legacy keys
            }
        }

        $model = Setting::get('ai_model', '');
        $baseUrl = Setting::get('ai_base_url', '');

        return match ($providerType) {
            'claude' => new ClaudeProvider(
                apiKey: $apiKey ?? '',
                model: $model ?: 'claude-sonnet-4-5-20250514',
            ),
            'openai' => new OpenAiCompatibleProvider(
                baseUrl: $baseUrl ?: 'https://api.openai.com',
                model: $model ?: 'gpt-4o',
                apiKey: $apiKey,
            ),
            'ollama' => new OpenAiCompatibleProvider(
                baseUrl: $baseUrl ?: 'http://localhost:11434',
                model: $model ?: 'llama3',
                apiKey: null,
            ),
            default => null,
        };
    }
}
