# LogiMind Maroc — Plan MVP (démo demain)

Objectif : démo web qui marche, UI front-end **prioritaire**. Données mock + 1 vraie API (OSRM) + IA Claude (fallback mock).

## Décisions scope
- Bloomberg / temps-réel marché → **mock** (trop cher/lent en 1 jour).
- Météo marine / news → **mock** seeders.
- Itinéraire camion → **OSRM réel** (gratuit, sans clé).
- Raisonnement / recommandations → **Claude** (`claude-haiku-4-5`), avec **fallback mock** si pas de clé.
- Pas d'auth.

## Vrai vs Mock (démo)
| Élément | Statut |
|---------|--------|
| Itinéraire camion (carte) | ✅ Vrai (OSRM) |
| Reco IA texte | ✅ Vrai si clé / sinon mock crédible |
| Score saturation port | 🟡 Simulé (heuristique sur seed) |
| Météo / news | 🟡 Mock JSON réaliste |

---

## Statut (✅ fait)
- [x] Laravel 12 installé, APP_KEY, locale FR
- [x] Fix PHP 8.5 deprecation `config/database.php`
- [x] Fix SSL cURL → `curl.cainfo` dans `php.ini`
- [x] `.env` : ANTHROPIC_API_KEY, MODEL, OSRM_URL ; `config/services.php`
- [x] Migration `ports` / `port_conditions` / `shipments`
- [x] Models + relations
- [x] Seeders : 3 ports, 7j conditions, 2 conteneurs démo
- [x] `ClaudeService` (fallback mock)
- [x] `PortSaturationService` (score risque + forecast)
- [x] `RoutingService` (OSRM + fallback ligne droite)
- [x] Controllers Dashboard / Port / Routing + routes
- [x] UI : layout dark premium + 3 vues (Tailwind + Leaflet)
- [x] Test : 3 routes HTTP 200, OSRM OK après fix SSL

## Reste à faire (ordre)
1. [ ] **Vérifier OSRM après fix SSL** : recharger `/fluidite`, confirmer tracé routier réel (plus le bandeau « approximatif »).
2. [ ] **Clé Claude** : coller `ANTHROPIC_API_KEY` dans `.env` → reco/brief deviennent vraie IA. (Sinon démo reste sur mock.)
3. [ ] **Polish UI** : responsive mobile, états vides, micro-animations, favicon/logo.
4. [ ] **Scénario démo scripté** : conteneur Shanghai → Tanger Med → Casablanca, parcours fluide pour la présentation.
5. [ ] (Option) Page conteneur détaillée reliant Étape 1 + Étape 2 (timeline mer→port→ville).
6. [ ] (Option) Breeze auth si le jury veut un login.

## Risques / notes
- OSRM public = ~limites de débit ; fallback ligne droite garde la carte vivante.
- Sans clé Claude, IA = mock (acceptable pour démo, le dire au jury si demandé).
- Démo locale uniquement (XAMPP), pas de déploiement prévu pour le MVP.

## Pitch (1 phrase)
> LogiMind connecte la mer et la ville : il dit à l'importateur **quand** faire arriver son conteneur,
> et au chauffeur **par où** sortir du port — moins de retards, moins de frais, moins de carburant.
