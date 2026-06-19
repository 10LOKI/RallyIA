# LogiMind Maroc — Plan MVP (démo demain)

Objectif : démo web qui marche, UI front-end **prioritaire**. Données mock + 1 vraie API (OSRM) + IA Claude (fallback mock).

## ⏸️ REPRISE DEMAIN (état au shutdown)
Tout le code est sur disque, rien perdu. DB MySQL `rallyia` persistée. Pour reprendre :
1. Démarrer **XAMPP** → MySQL (Apache pas requis, on utilise `artisan serve`).
2. **Ollama** : finir le modèle IA — le pull `qwen2.5:3b` était bloqué/incomplet.
   ```
   ollama pull qwen2.5:3b
   ollama list            # doit montrer qwen2.5:3b
   ```
   (déjà présent : `llama3.2:3b` mais trop faible — hallucine. Garder qwen.)
3. Lancer l'app :
   ```
   php artisan serve      # http://127.0.0.1:8000
   ```
4. Tester reco IA réelle sur `/portuaire` (sinon fallback mock propre).

État IA : `.env` `LLM_PROVIDER=ollama`, `OLLAMA_MODEL=qwen2.5:3b`. Ollama installé (v0.30.6), sert sur :11434.
Sans qwen → reco/sentiment = mock (démo marche quand même, avec vrais chiffres ROI).

Clés : NewsAPI ✅ live · OSRM ✅ live · aisstream ⚠️ clé rejetée (navires en mock).

### Reste / idées jury
- [x] ROI chiffré (MAD + heures + litres) — fait, sur /portuaire et /fluidite.
- [x] Page « Parcours conteneur » (`/parcours`) — timeline mer→port→ville + bandeau décision + total MAD.
- [x] Carte décision one-liner — intégrée au bandeau Parcours.
- [x] **Planification & ETA prédictive** (`/planification`) — choix créneau départ port étranger → date arrivée prédite + fenêtre/confiance.
- [x] IA réelle qwen2.5:3b OK (testé, reco cohérente FR). Active sur toutes pages.
- [x] Polish responsive (header mobile scrollable, tables overflow, padding).
- [ ] Scénario démo scripté + relire tout en vrai.

## IA — note perf
qwen2.5:3b sur CPU : ~5-15s/réponse. Pages /portuaire fait 2 appels (reco+sentiment). Pour démo fluide : charger chaque page 1× avant de présenter (warm-up). Sentiment déjà caché 30min.

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

## Intégrations data (màj)
- [x] **OSRM** (routing terre) — réel, fix SSL `curl.cainfo`.
- [x] **NewsAPI.org** — réel (clé OK). Requête EN « Morocco » + filtre géo/anti-bruit → 3 titres pertinents. Sentiment via LLM (mock tant qu'Ollama absent).
- [x] **IA driver** `LLM_PROVIDER=ollama|anthropic|mock` (`ClaudeService`). Fallback mock partout.
- [x] **Navires** : `VesselService` + commande `vessels:poll` (aisstream WS) + fallback mock. Carte + arrivées attendues sur page port.
- [ ] ⚠️ **aisstream clé REJETÉE** : serveur ferme la connexion après envoi clé (testé textalk PHP + .NET = même résultat). Clé invalide/inactive. → vérifier/régénérer sur aisstream.io. En attendant : navires mock.
- [ ] **Ollama pas installé** : `winget install Ollama.Ollama` puis `ollama pull qwen2.5:7b` (ou `llama3.2:3b` plus rapide, CPU Iris Xe). Sinon IA reste mock.

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
