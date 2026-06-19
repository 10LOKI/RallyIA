<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsService
{
    public function __construct(private ClaudeService $claude) {}

    /**
     * Titres eco/logistique Maroc via NewsAPI.org. Cache 30 min.
     * Fallback mock si pas de cle / erreur.
     */
    public function headlines(int $limit = 6): array
    {
        return Cache::remember('news.headlines', now()->addMinutes(30), function () use ($limit) {
            $key = config('services.newsapi.key');
            if (empty($key)) {
                return $this->mock();
            }

            try {
                // FR sur tier gratuit ~vide; EN "Morocco" donne de vrais titres eco/commerce.
                $res = Http::timeout(20)->get(config('services.newsapi.url') . '/everything', [
                    'q' => 'Morocco AND (logistics OR port OR "Tanger Med" OR freight OR exports OR trade OR shipping OR economy OR customs OR supply)',
                    'language' => 'en',
                    'sortBy' => 'relevancy',
                    'pageSize' => 30, // pool large, on filtre la pertinence ensuite
                    'apiKey' => $key,
                ]);

                if ($res->failed() || $res->json('status') !== 'ok') {
                    Log::warning('NewsAPI echec', ['body' => $res->body()]);
                    return $this->mock();
                }

                // Doit etre ancre Maroc (geo) ET ne pas etre du bruit sport/people.
                $geo = '/\b(morocc|tanger|casablanca|rabat|agadir|maghreb)\w*/i';
                $noise = '/\b(world cup|football|soccer|match|visa|fan|player|goal|tournament|promo code|bonus|basic economy|airfare|flight deal|round.?trip|hotel)\b/i';

                $perSource = [];
                $items = collect($res->json('articles', []))
                    ->filter(function ($a) use ($geo, $noise, &$perSource) {
                        $src = $a['source']['name'] ?? '';
                        if (stripos($src, 'flightdeal') !== false) {
                            return false;
                        }
                        $text = ($a['title'] ?? '') . ' ' . ($a['description'] ?? '');
                        if (!preg_match($geo, $text) || preg_match($noise, $text)) {
                            return false;
                        }
                        // max 2 articles par source
                        $perSource[$src] = ($perSource[$src] ?? 0) + 1;
                        return $perSource[$src] <= 2;
                    })
                    ->take($limit)
                    ->map(fn ($a) => [
                        'title' => $a['title'] ?? '',
                        'source' => $a['source']['name'] ?? 'Source',
                        'url' => $a['url'] ?? '#',
                        'publishedAt' => $a['publishedAt'] ?? null,
                    ])->values()->all();

                return empty($items) ? $this->mock() : $items;
            } catch (\Throwable $e) {
                Log::error('NewsAPI exception', ['msg' => $e->getMessage()]);
                return $this->mock();
            }
        });
    }

    /**
     * Sentiment eco agrege a partir des titres, via Claude.
     * Retourne ['score' => -100..100, 'resume' => string]. Cache 30 min.
     */
    public function sentiment(array $headlines): array
    {
        if (empty($headlines)) {
            return ['score' => 0, 'resume' => 'Pas de données économiques.'];
        }

        $cacheKey = 'news.sentiment.' . md5(collect($headlines)->pluck('title')->implode('|'));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($headlines) {
            $titres = collect($headlines)->pluck('title')->map(fn ($t) => "- $t")->implode("\n");

            $system = "Tu es un analyste économique. À partir de titres d'actualité, évalue le climat "
                . "économique et logistique pour le commerce import-export au Maroc. "
                . "Réponds STRICTEMENT en JSON: {\"score\": <entier -100 à 100>, \"resume\": \"<une phrase>\"}. "
                . "score négatif = contexte défavorable (risque), positif = favorable.";

            $prompt = "Titres récents:\n{$titres}\n\nDonne le JSON.";

            $mock = '{"score": 12, "resume": "Climat économique globalement stable, légèrement favorable au commerce."}';

            $raw = $this->claude->ask($system, $prompt, $mock);

            // extrait le 1er bloc JSON
            if (preg_match('/\{.*\}/s', $raw, $m)) {
                $data = json_decode($m[0], true);
                if (is_array($data) && isset($data['score'])) {
                    return [
                        'score' => (int) max(-100, min(100, $data['score'])),
                        'resume' => $data['resume'] ?? '',
                    ];
                }
            }

            return ['score' => 0, 'resume' => 'Analyse indisponible.'];
        });
    }

    private function mock(): array
    {
        return [
            ['title' => 'Tanger Med franchit un record de trafic de conteneurs', 'source' => 'Le Matin', 'url' => '#', 'publishedAt' => now()->subHours(3)->toIso8601String()],
            ['title' => 'Le Maroc renforce ses corridors logistiques vers l\'Afrique', 'source' => 'Médias24', 'url' => '#', 'publishedAt' => now()->subHours(6)->toIso8601String()],
            ['title' => 'Hausse des coûts du fret maritime au premier trimestre', 'source' => 'L\'Économiste', 'url' => '#', 'publishedAt' => now()->subHours(9)->toIso8601String()],
            ['title' => 'Casablanca : investissements dans la modernisation portuaire', 'source' => 'Aujourd\'hui le Maroc', 'url' => '#', 'publishedAt' => now()->subHours(12)->toIso8601String()],
            ['title' => 'Import-export : les échanges Maroc-Europe en progression', 'source' => 'Challenge', 'url' => '#', 'publishedAt' => now()->subHours(15)->toIso8601String()],
            ['title' => 'Météo marine : vigilance sur le détroit de Gibraltar', 'source' => 'MAP', 'url' => '#', 'publishedAt' => now()->subHours(18)->toIso8601String()],
        ];
    }
}
