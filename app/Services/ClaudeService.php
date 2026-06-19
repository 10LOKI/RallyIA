<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Couche IA SmartPort. Route vers le moteur configure (LLM_PROVIDER):
 *   ollama    -> modele local (gratuit)
 *   anthropic -> Claude API
 *   mock      -> texte fige fourni par l'appelant
 * Tout echec retombe sur le mock => la demo ne casse jamais.
 */
class ClaudeService
{
    public function ask(string $system, string $prompt, ?string $mock = null): string
    {
        $provider = config('services.llm.provider', 'mock');
        $model = config("services.{$provider}.model", '');

        // Cache des reponses LLM => pages instantanees apres le 1er chargement (demo fluide).
        $cacheKey = 'llm:' . md5($provider . '|' . $model . '|' . $system . '|' . $prompt);
        if (($hit = Cache::get($cacheKey)) !== null) {
            return $hit;
        }

        $result = match ($provider) {
            'ollama'    => $this->ollama($system, $prompt, $mock),
            'anthropic' => $this->anthropic($system, $prompt, $mock),
            default     => $mock ?? '[Mode démo]',
        };

        // Ne cache pas les placeholders d'erreur (commencent par '[').
        if ($result !== '' && !str_starts_with($result, '[')) {
            Cache::put($cacheKey, $result, now()->addMinutes(30));
        }

        return $result;
    }

    private function ollama(string $system, string $prompt, ?string $mock): string
    {
        try {
            $res = Http::timeout(90)->post(config('services.ollama.url') . '/api/chat', [
                'model' => config('services.ollama.model'),
                'stream' => false,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'options' => ['temperature' => 0.4],
            ]);

            if ($res->failed()) {
                Log::warning('Ollama echec', ['status' => $res->status(), 'body' => $res->body()]);
                return $mock ?? '[IA locale indisponible]';
            }

            $text = trim($res->json('message.content') ?? '');
            return $text !== '' ? $text : ($mock ?? '');
        } catch (\Throwable $e) {
            Log::error('Ollama exception', ['msg' => $e->getMessage()]);
            return $mock ?? '[IA locale indisponible — Ollama lancé ?]';
        }
    }

    private function anthropic(string $system, string $prompt, ?string $mock): string
    {
        $key = config('services.anthropic.key');
        if (empty($key)) {
            return $mock ?? '[Clé Claude non configurée]';
        }

        try {
            $res = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model'),
                'max_tokens' => 700,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($res->failed()) {
                Log::warning('Claude API echec', ['status' => $res->status(), 'body' => $res->body()]);
                return $mock ?? '[IA indisponible]';
            }

            return trim($res->json('content.0.text') ?? ($mock ?? ''));
        } catch (\Throwable $e) {
            Log::error('Claude API exception', ['msg' => $e->getMessage()]);
            return $mock ?? '[IA indisponible]';
        }
    }
}
