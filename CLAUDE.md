# LogiMind Maroc — Contexte projet

Copilote IA pour import-export et transporteurs routiers au Maroc.
Transforme données complexes (météo marine, contexte éco, trafic) en décisions simples :
**« Partez à ce moment, prenez cette route, économisez temps et argent. »**

Deux étapes :
1. **Optimisation portuaire (mer)** — prédit la saturation des ports (Tanger Med, Casablanca…) et recommande le meilleur créneau d'arrivée/départ des conteneurs.
2. **Fluidité urbaine (terre)** — génère un itinéraire optimisé port → ville pour les chauffeurs, sans embouteillages.

## Stack
- **Laravel 12.62** / PHP 8.5 (XAMPP, Windows)
- **DB** : SQLite (`database/database.sqlite`), défaut Laravel 12
- **Front** : Blade + Tailwind (Play CDN) + Leaflet.js (cartes) — pas de build Vite
- **IA** : Claude API (Anthropic) via `ClaudeService`, modèle `claude-haiku-4-5`
- **Routing** : OSRM public (`router.project-osrm.org`), pas de clé
- Données port/météo/news : **mock** (seeders) — pas de Bloomberg (hors scope MVP)

## Lancer
```bash
php artisan serve          # http://127.0.0.1:8000
php artisan migrate:fresh --seed --force   # reset + données démo
```

## Routes
| URL | Controller | Vue | Rôle |
|-----|-----------|-----|------|
| `/` | `DashboardController` | `dashboard` | Accueil, KPIs ports, conteneurs |
| `/portuaire` | `PortController` | `port` | Prévision saturation 7j + reco IA |
| `/fluidite` | `RoutingController` | `routing` | Carte itinéraire OSRM + brief chauffeur IA |

## Architecture
```
app/
  Models/         Port, PortCondition, Shipment
  Services/
    ClaudeService          ask(system, prompt, mock) — fallback mock si pas de clé
    PortSaturationService  risk()=score 0-100 (sat 50% + météo 30% + sentiment 20%); forecast(); level()
    RoutingService         route() via OSRM, fallback ligne droite (haversine) si échec
  Http/Controllers/   Dashboard, Port, Routing
database/
  migrations/2026_06_18_000001_create_logimind_tables.php
  seeders/DatabaseSeeder.php   3 ports + 7j conditions + 2 shipments démo
resources/views/
  layouts/app.blade.php   header, theme dark (ink/brand vert-cyan), glass cards
  dashboard / port / routing
```

## Modèle données
- `ports` : nom, ville, lat, lng, capacite_max
- `port_conditions` : port_id, date, meteo_score (0-100), saturation_pct (0-100), news_sentiment (-100..100)
- `shipments` : reference, marchandise, origine, destination_ville, dest_lat/lng, statut

## Calcul risque (PortSaturationService)
`risk = saturation*0.5 + (100-meteo)*0.3 + (100-(sentiment+100)/2)*0.2`
Niveaux : <35 Faible (vert) · <60 Modéré (ambre) · ≥60 Élevé (rose).

## Config (.env)
```
ANTHROPIC_API_KEY=        # vide => réponses IA mock (UI marche quand même)
ANTHROPIC_MODEL=claude-haiku-4-5
OSRM_URL=https://router.project-osrm.org
DB_CONNECTION=sqlite
```

## Conventions
- IA : tout passe par `ClaudeService::ask()` avec un `$mock` fallback → démo robuste sans clé/sans réseau.
- Pas d'auth dans le MVP.
- UI = **priorité** : thème sombre premium, cartes glass, accents vert/cyan (`brand`/`brand-deep`), Plus Jakarta Sans.

## Gotchas / env
- **SSL cURL** : PHP/XAMPP Windows manquait le CA bundle → `curl.cainfo` pointé sur
  `C:\xampp\apache\bin\curl-ca-bundle.crt` dans `php.ini`. Sans ça, appels OSRM/Claude = `cURL error 60`.
  (Pense aussi à `openssl.cafile` même valeur si erreurs SSL persistent.)
- PHP 8.5 : `config/database.php` patché `PDO::MYSQL_ATTR_SSL_CA` → `\Pdo\Mysql::ATTR_SSL_CA` (deprecation).
- Locale dates FR via Carbon `->locale('fr')`.
