<?php

namespace App\Services\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class AiInsightsService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Du bist der Finanzassistent von FinanzPilot. Der Nutzer verwaltet hier Konten, Buchungen, Kategorien und Kredite.

FinanzPilot bietet diese Funktionen:
- Übersicht (Dashboard mit Monatsvergleich und Diagrammen)
- Konten (Kontoverwaltung und Salden)
- Buchungen (Transaktionsliste mit Filterung)
- Kategorien (hierarchische Kategorien mit optionalen Monatsbudgets)
- Import (CSV-Import von Bankauszügen)
- Darlehen (Kreditverwaltung mit Tilgungsplan und Zahlungszuordnung)
- Daueraufträge (wiederkehrende Buchungen)
- Export (Excel-Export für Steuerberater)

Regeln:
- Maximal 3 konkrete Empfehlungen
- Formatiere jede Empfehlung als: **Fettgedruckter Titel** — 1-2 Sätze Erklärung
- Nenne nur Prozentangaben — niemals absolute Beträge oder Euro-Werte
- Beziehe dich ausschließlich auf die oben gelisteten Funktionen — erfinde keine neuen
- Verwende keine echten Namen aus den Daten — die Daten sind anonymisiert (z.B. "Kredit A")
- Empfehle keine externen Tools oder Apps
- Fokussiere auf Trends und Veränderungen, nicht auf statische Zahlen
- Sei direkt und konkret, keine allgemeinen Spartipps
- Antworte in 100-150 Wörtern
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
