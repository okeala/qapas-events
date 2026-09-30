# Os Jogos do Agricultor — QAPAS Events

Application autonome de conception et de pilotage d’événements locaux. **v0.2 — épreuves, nomenclatures et terrasses**. Elle ne copie pas Farmers Games et ne repose pas sur l’application Platform. Elle réutilise le paquet de présentation QAPAS.

## Installation locale

Prérequis : **PHP 8.4+**, Composer 2, extensions `pdo_sqlite`, `mbstring`, `intl`, `dom`, `curl`, `zip`, `fileinfo`, `gd` (import des plans), Node 24 et npm. Laravel accepte PHP 8.3 ; la suite PHPUnit verrouillée exige PHP 8.4.

Dans un répertoire local vide :

```bash
mkdir -p ~/PhpstormProjects/qapas-events
cd ~/PhpstormProjects/qapas-events
git init -b master
git remote add origin git@github.com:okeala/qapas-events.git
git fetch origin master
git switch -C master --track origin/master
bash scripts/setup-local.sh
php artisan events:admin votre@email.pt
php artisan serve --host=127.0.0.1 --port=8890
```

Le dépôt distant est déjà initialisé : **ne pas créer de commit local concurrent avant `git fetch`**. Ces commandes supposent un dossier vide et un accès SSH GitHub déjà configuré ; sinon utiliser `https://github.com/okeala/qapas-events.git` comme remote. Ouvrir le dossier dans PhpStorm et sélectionner son interpréteur PHP 8.4+.

- Public : http://127.0.0.1:8890
- Atelier : http://127.0.0.1:8890/admin
- Aucun mot de passe fourni ou enregistré dans Git ; la commande demande le mot de passe sans l’afficher.
- Le script conserve `.env`, la clé existante et la base SQLite. Il refuse un environnement non local, migre sans remise à zéro, seede sans écraser l’édition existante, compile et lance les tests.
- Pour les mises à jour : `git pull --ff-only`, puis `bash scripts/setup-local.sh`.

## Ce qui fonctionne dans cette version

- Éditions autonomes avec description des **4P** et visibilité publique contrôlée.
- Hypothèses, expériences et observations de brainstorming.
- Scénarios de taille, durée du travail, résultat prévu, couverture par engagements, manque pour l’équilibre et pour l’objectif QAPAS.
- TVA explicite par ligne, coût complet de rémunération, prudence sur la trésorerie ; cautions, aides affectées et flux tiers exclus de la marge.
- Catalogue indicatif, parcours public et demandes par profil, origine et consentement marketing séparé.
- Registre juridique avec preuves, relecteur, date et expiration ; conditions internes avant les paliers « prêt » et « en cours ».
- Équipes, préparation des élections locales, stands, activités officielles/publiques, conducteur, incidents et bilans.
- Administration Filament séparée, comptes créés en CLI, politiques serveur, journal des champs modifiés (sans recopier les données personnelles).
- Navigation partagée QAPAS et repli autonome lorsque Platform est indisponible.

## Ce qui reste à développer

Pas de checkout, commandes, factures, remboursement, répartition automatique d’aides, réservation de stock, vote électronique, dépouillement vérifié, arbitrage/scoring en direct ni mode hors connexion. Les quantités engagées et réglées du simulateur sont des **saisies manuelles**, pas un registre bancaire. Le bilan est un dossier de clôture, pas une certification comptable.

Les références de preuves sont pour l’instant textuelles : pas de téléchargement de justificatifs ni de coffre documentaire. Les identités Platform et les espaces restreints par équipe font partie des lots suivants. La captation vidéo et le grand écran sont des besoins à préparer, pas un système de diffusion déjà intégré. Le rôle administrateur de v0.1 donne accès à l’ensemble de cet atelier ; ne pas l’attribuer aux équipes locales.

Le catalogue affiche des **hypothèses**, pas des prestations actuellement achetables. Les deux formules de stand indépendant sont des alternatives sur un même futur contingent ; leurs capacités ne doivent pas être additionnées. La gestion transactionnelle de ce contingent appartient au lot commercial.

## Données de départ

Os Jogos do Agricultor est un projet en préparation, sans date, lieu, autorisation, vente ou paiement confirmé. Trois scénarios sont créés **sans coûts inventés**. Le format 12 équipes / 24 stands est un horizon, pas un minimum de lancement. Les offres reprennent les hypothèses discutées : emplacement nu 250 € TTC, structure + emplacement 500 € TTC total, relais 49,99 € TTC, trois partenaires structurants à 2 500 € TTC.

Les scénarios commencent donc incomplets. Renseigner le nombre de mois, le coût complet de rémunération et les coûts avant de leur demander une décision financière. Les scénarios sont indépendants : ne jamais additionner leurs encaissements.

## Ouvrir la collecte d’intérêt

Renseigner dans `.env` l’identité réelle du responsable (`EVENTS_ORGANIZER_NAME`) et son email (`EVENTS_CONTACT_EMAIL`). Relire et adapter l’information sur les données personnelles avec les modalités réelles d’hébergement et de traitement ; puis définir `EVENTS_PRIVACY_READY=true` et exécuter `php artisan config:clear`. La présentation peut fonctionner avant cette étape ; le serveur refuse alors la collecte.

Conservation des demandes initiales : 180 jours. En déploiement, exécuter le scheduler Laravel chaque minute pour appliquer la suppression quotidienne ; traiter les demandes d’exercice de droits via le contact indiqué. Il n’y a aucun outil publicitaire ni envoi automatique de newsletter. Ne pas utiliser ce formulaire comme base contractuelle ou dossier de facturation.

## Identité visuelle et langues

Navigation et CSS proviennent de `okeala/qapas-shared` 0.1.8, inclus localement et verrouillés. `QAPAS_PLATFORM_URL` est vide par défaut ; renseigner l’origine autorisée pour activer le manifeste public. Celui-ci ne crée ni session ni permission. Le port 8890 et l’identifiant `events` sont propres à cette application ; aucune modification du registre Platform n’est incluse.

Interface publique FR/PT ; navigation commune en six langues avec repli français documenté. Le contenu éditorial des offres, les scénarios des épreuves et le back-office initial sont français. Leur traduction éditoriale complète reste un lot distinct. Les chaînes publiques sont localisables ; aucune prétention de livraison multilingue complète.

## Vérification

```bash
composer validate --strict
php artisan test
npm ci
npm run build
php artisan view:cache
```

La CI exécute l’installation verrouillée, les migrations, le seed, le build, les tests, puis la compilation des vues et de la configuration sur PHP 8.4 / Node 24. En production : HTTPS, secret unique, `APP_DEBUG=false`, cookies sécurisés, sauvegardes, scheduler et configuration d’hébergement adaptée ; **ne pas utiliser `setup-local.sh` pour déployer**.

Documentation : [produit et lots](docs/PRODUCT.md), [contraintes juridiques](docs/LEGAL.md), [architecture](docs/ARCHITECTURE.md).

## Épreuves, chiffrage et plan — v0.2

Les six fiches de départ : **Omelete Retro**, **A Torre do Reboque**, **O Grande Restaurante**, **O Tabuleiro das Sementes**, **Mestre das Mimosas**, **O Pichecultor**. Toutes sont des concepts à éprouver, sans approbation technique ni devis fictif. Le seeder renomme l’édition initiale, conserve ses identifiants, budgets et travaux ; il ajoute les fiches manquantes et n’écrase pas leurs modifications. L’ancienne URL redirige vers `/events/os-jogos-do-agricultor`.

Ordre de l’atelier : **Concevoir → Chiffrer → Mobiliser → Préparer → Exploiter → Clôturer**.

1. Dans **Concevoir / Épreuves**, rédiger le spectacle, règles, points, accès et carte publique. Les stands de freguesia et indépendants utilisent les mêmes fiches. L’idée originale et les adaptations techniques restent internes.
2. Renseigner les matériaux : quantité entière, unité, besoin fixe ou par passage, achat/location/prêt/apport, prix TTC en centimes, IVA et déductibilité confirmée. Le nombre de passages multiplie uniquement les consommables « par passage ». Une quantité ou un prix vide n’est pas zéro ; la gratuité exige une référence. Confirmer l’inventaire complet après revue.
3. Dans **Chiffrer / Scénarios**, sélectionner les épreuves incluses. Leurs coûts alimentent directement la marge et les réserves : ne pas recopier les mêmes coûts dans les lignes budgétaires. Un équipement partagé (régie, écran…) est compté une seule fois au budget commun, ou réparti avec des parts documentées. Les paiements fournisseurs du matériel ne sont pas rapprochés dans cette version ; la réserve reste prudente.
4. Dans **Préparer / Plan des terrasses**, importer le véritable plan de la Quinta en PNG/JPEG/WebP : 8 Mo, 4 000 pixels par côté, 12 mégapixels au maximum. GD réencode l’image, retire les métadonnées et la conserve en stockage privé. Tracer les sommets des terrasses, puis choisir une épreuve et cliquer dans sa terrasse. Aucun fond cadastral ni emplacement n’est inventé.
5. Dans **Terrasses**, choisir l’accès (public, qualifiés ou réservé) et la visibilité. Dans **Épreuves**, affecter aussi la terrasse des spectateurs. Dans **Éditions**, activer explicitement la publication du plan. Le fond entier devient alors visible : utiliser une image préparée pour le public, sans informations sensibles imprimées dessus. Les polygones et points privés sont exclus de la réponse publique.
6. Une modification des règles, zones, accès ou positions remet la sécurité à vérifier et une épreuve approuvée en essais. Un point hors terrasse ou une relation avec une autre édition est refusé côté serveur. Les engins nécessitent des zones distinctes, des conditions de qualification et une revue technique avant le passage de phase.

Le plan Leaflet utilise des coordonnées relatives à l’image, **sans géoréférencement ni valeur de bornage**. Une fois des terrasses dessinées, le remplacement du fond est bloqué pour éviter de décaler les positions : préparer le bon fond dès le départ ; un autre site relève d’une autre édition. Aucun plan réel n’a été fourni avec cette version.

Les adaptations proposées pour les mimosas, les outils et l’eau sont expliquées dans [les contraintes des épreuves](docs/ACTIVITIES.md). Elles préservent le ressort comique sans présenter un dispositif non évalué comme prêt à fonctionner.
