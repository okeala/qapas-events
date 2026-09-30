# Os Jogos do Agricultor — QAPAS Events

> Règle active : [cabanes en récupération, raccord d’échafaudage au centre du partenariat principal](docs/RECLAIMED-CABINS.md). Équipes constructrices, locations QAPAS, coûts à chiffrer et dévoilement contrôlé.

> Évolution active du 30 septembre 2026 : [communauté et continuité 2027](docs/COMMUNITY-2026.md) — 10 € de boissons pour tous les billets payés, six rôles indispensables, conventions, prix de 500 €, QR et retours. Ces règles prévalent sur les exemples historiques divergents.

> Version active : [tickets-avantages, prospection, sponsoring, plantations et communication par phase](docs/ADVANTAGES-MOBILIZATION.md). Les paragraphes historiques ci-dessous sur soutien facultatif / entrée gratuite et contrepartie candidat distincte restent applicables aux anciens contrats seulement.

**Version du 30 septembre 2026 :** mobilier à la charge des participants (location privilégiée), treize rôles experts par équipe, candidature de 10 € TTC et tickets boissons pour les non-retenus. Paiement Stripe livré **désactivé**, avec ouverture conditionnée et suivi du financement du contrat boissons. Voir [le fonctionnement et l’activation](docs/EXPERT-REGISTRATION.md). Le scénario actif est `costing-rental-experts-v1` ; les chiffrages Douglas ci-dessous relèvent de l’historique.


Application autonome de conception et de pilotage d’événements locaux. **v0.3 — lancement 6 + 6, contributions et plan géographique**. Elle ne copie pas Farmers Games et ne repose pas sur l’application Platform. Elle réutilise le paquet de présentation QAPAS.

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
- Parcours de lancement, étapes dépendantes et preuves, rattachés aux 4P.
- Scénarios de taille, durée du travail, résultat prévu, couverture par engagements, manque pour l’équilibre et pour l’objectif QAPAS.
- TVA explicite par ligne, coût complet de rémunération, prudence sur la trésorerie ; cautions, aides affectées et flux tiers exclus de la marge.
- Catalogue indicatif, parcours public et demandes par profil, origine et consentement marketing séparé.
- Registre juridique avec preuves, relecteur, date et expiration ; conditions internes avant les paliers « prêt » et « en cours ».
- Équipes, préparation des élections locales, stands, activités officielles/publiques, déroulé opérationnel, incidents et bilans.
- Administration Filament séparée, comptes créés en CLI, politiques serveur, journal des champs modifiés (sans recopier les données personnelles).
- Navigation partagée QAPAS et repli autonome lorsque Platform est indisponible.

## Ce qui reste à développer

Pas de checkout, commandes, factures, remboursement, répartition automatique d’aides, réservation de stock, vote électronique, dépouillement vérifié, arbitrage/scoring en direct ni mode hors connexion. Les quantités engagées et réglées du simulateur sont des **saisies manuelles**, pas un registre bancaire. Le bilan est un dossier de clôture, pas une certification comptable.

Les références de preuves sont pour l’instant textuelles : pas de téléchargement de justificatifs ni de coffre documentaire. Les identités Platform et les espaces restreints par équipe font partie des lots suivants. La captation vidéo et le grand écran sont des besoins à préparer, pas un système de diffusion déjà intégré. Le rôle administrateur de v0.1 donne accès à l’ensemble de cet atelier ; ne pas l’attribuer aux équipes locales.

Le catalogue affiche des **hypothèses**, pas des prestations actuellement achetables. Les deux formules de stand indépendant sont des alternatives sur un même futur contingent ; leurs capacités ne doivent pas être additionnées. La gestion transactionnelle de ce contingent appartient au lot commercial.

## Données de départ

Os Jogos do Agricultor est un projet en préparation, sans date, lieu, autorisation, vente ou paiement confirmé. Le scénario courant est **6 freguesias + 6 indépendants**, sans prix ni encaissement inventé. Trois scénarios historiques restent conservés. Douze unités stand non attribuées sont préparées ; elles ne représentent aucune inscription. Les anciennes offres de départ sont conservées mais retirées du catalogue public. Les nouvelles offres fondateurs, parrains et relais restent à chiffrer.

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

## Lancement 6 + 6 et paliers — v0.3

- Chaque stand de freguesia peut avoir un parrain principal et jusqu’à trois relais sponsors. Une entreprise peut cumuler les deux rôles dans une même fiche. Aucun partenaire réel n’est inventé. Une spécialité et une équipe de vente sont à documenter ; les stands n’exploitent pas de bar payant. QAPAS prévoit son bar et sa friterie.
- La contribution d’un indépendant est le prix de l’offre QAPAS hors IVA due, diminué de ses coûts directs économiques. Marchandises, salariés et chiffre d’affaires de l’exposant ne sont pas importés dans ce calcul ; aucune commission sur ses ventes. Les dépenses communes restent au budget général. Le tableau de pilotage détaille les contributions prévues, engagées et rapprochées par unité stand.
- Un coût payé personnellement par l’organisateur conserve sa nature de coût. Le remboursement est un mouvement de trésorerie, pas une seconde charge. Le solde des avances apparaît séparément ; le coût complet de rémunération reste inclus même si payé à la fin. Le terrain mis à disposition est documenté sans inventer un loyer.
- Un montant vide est inconnu. Les lignes à quantité zéro sont inactives. Les recettes estimées du bar et des frites améliorent la prévision, mais ne financent pas le lancement. Un encaissement ne compte au préfinancement qu’après rapprochement manuel : référence, date passée et administrateur identifié. Enregistrer d’abord la ligne, puis son rapprochement ; modifier montant, quantité ou affectation annule celui-ci. Une même référence ne peut être rapprochée deux fois dans le même scénario. Ceci n’est ni une connexion bancaire ni un processeur de paiement.
- Le lancement demande un budget complet, les stands correspondant au format, les parrains actifs, au moins trois épreuves officielles distinctes par jour incluses au budget, une durée, l’hypothèse de public, la capacité du chapiteau et les réserves documentées. Le solde après coûts TTC réserve prudemment l’IVA collectée sans anticiper les crédits de TVA. L’objectif QAPAS s’ajoute au seuil d’équilibre pour débloquer l’étude du palier suivant.
- Un palier est un **scénario complet**, lié à son parent. Ne pas additionner les scénarios ni réutiliser une recette comme nouvel encaissement dans une synthèse globale. Le parent doit préserver l’objectif QAPAS ; le palier suivant ne peut réduire cet objectif. L’outil autorise l’étude, pas la réservation ou la vente automatique. Les tarifs fondateurs restent à décider après les devis ; contingent et date limite se renseignent dans les offres.

## Plan géographique et révélations

**Concevoir / Plan du site** : fond OSM, fond aérien DGT 2025, tracés Point/LineString/Polygon et import GeoJSON/KML en WGS84. GeoJSON MultiPolygon et anneaux intérieurs sont acceptés ; KML convertit les coordonnées 3D en plan 2D. Limites : 2 Mo, 100 objets par import, 500 sommets par anneau. Les DTD, entités, NetworkLink, modèles 3D et images KML sont refusés. Les attributs métier étrangers ne sont pas importés : seuls nom et géométrie sont conservés. Choisir le calque lors de l’import, puis affiner chaque objet. Export GeoJSON disponible aux administrateurs.

Le fond aérien DGT est un service externe dont la disponibilité dépend du fournisseur ; les tests applicatifs ne prouvent pas sa disponibilité sur site. Le service a répondu 502 depuis l’environnement de développement. OSM reste sélectionnable et le client signale les erreurs de tuiles. L’URL, le nom de couche (espaces compris) et l’attribution sont configurables via `EVENTS_IMAGERY_URL`, `EVENTS_IMAGERY_LAYER`, `EVENTS_IMAGERY_ATTRIBUTION`. Aucune prélecture massive ou cache hors ligne des tuiles OSM. Sources : [DGT](https://www.dgterritorio.gov.pt/atividades/cartografia/cartografia-topografica/ortofotos/ortofotos-digitais), [service municipal déclarant la couche DGT](https://geoloule.cm-loule.pt/MuniSIG/REST/sites/MO_PMOT_Elab/map/mapservices/267), [politique des tuiles OSM](https://operations.osmfoundation.org/policies/tiles/).

Les anciens plans sur image et leurs positions sont conservés séparément : aucune conversion fictive en coordonnées géographiques. Dans **Éléments du site et besoins**, chiffrer les installations, personnes, eau et énergie. Sélectionner ces objets dans le scénario pour reprendre leurs coûts une seule fois ; retirer toute provision budgétaire couvrant déjà le même besoin. Les besoins par jour suivent la durée du scénario. Le ratio de personnes par stand est une hypothèse commerciale, pas une capacité réglementaire.

**Implantations des épreuves** relie une épreuve à plusieurs quartéis/zones avec rôles performance, spectateurs, attente ou technique. Cette relation ne multiplie pas son matériel : ajuster les quantités si plusieurs dispositifs sont réellement nécessaires. Les modifications d’implantation invalident la revue de risque. Pour les engins, zones de performance et spectateurs distinctes et accès adaptés restent obligatoires dans le contrôle interne.

**Parcours de lancement** : étapes avec responsable, échéance, hypothèse, expérience, preuves, dépendances et intitulé public. Les cycles sont refusés. Un jalon n’est publiquement accompli que si ses preuves et ses préalables restent valides. Aucun courrier ni message n’est envoyé automatiquement.

Dans **Épreuves**, choisir non dévoilée, aperçu, règles dévoilées ou confirmée. Les règles et points ne sont pas rendus dans le HTML d’un aperçu. Un titre caché n’apparaît pas dans les implantations publiques. La confirmation exige financement rapproché, préparation et revue technique ; si ces conditions cessent d’être réunies, l’affichage indique une réévaluation. Conditions essentielles de participation accessibles avant engagement. Un identifiant YouTube peut lier un extrait ou direct externe ; pas de régie vidéo intégrée.

Le seeder ajoute le nouveau socle une seule fois, conserve les travaux antérieurs et initialise un seul aperçu officiel. Un second lancement du seeder conserve modifications, rapprochements et choix éditoriaux. Les anciens prix restent des références historiques et ne sont pas présentés comme une remise nouvellement validée.

### Accueil, mobilisation et exploitation

Le scénario actuel, le chiffrage du mobilier Douglas, les relais et leur seuil de révélation, la presse et les emprises d’épreuves sont décrits dans [docs/HOSPITALITY-OPERATIONS.md](docs/HOSPITALITY-OPERATIONS.md). Après mise à jour : `composer install`, `php artisan migrate`, `php artisan db:seed`, `npm ci`, `npm run build`. Le catalogue reste une collecte de besoins sans paiement ni réservation.

## Chiffrage par unité

Le menu **Chiffrer → Chiffrage par unité** compare les stands, services et épreuves d’un scénario. Les fiches **Chiffrer** ouvrent les tableaux liés Coûts/Recettes ou Matériel. La simulation privée de deux jours contient les premières provisions, sans engagement ni paiement. Voir [hypothèses, calculs et sources](docs/UNIT-COSTING.md). Après mise à jour : `php artisan migrate --seed`, puis rebuild des assets.

Les répétitions, demandes de devis, allocations de gobelets et regroupements budgétaires sont décrits dans [le guide opérationnel](docs/REHEARSALS-PROCUREMENT-CUPS.md).

### Mise à jour tournées / préventes / emplacements

```bash
cd ~/PhpstormProjects/qapas-events
git pull --ff-only origin master
composer install
php artisan migrate
php artisan db:seed
php artisan optimize:clear
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=8890
```

Ne pas employer `migrate:fresh` sur vos données. Les migrations ajoutent les tables ; le seeder ajoute les nouveaux dossiers sans effacer les offres, preuves ni paiements. La correction Presse concerne l'injection typée du Builder de Filament dans le champ Scénario, pas un remplacement de la base.

- **Concevoir / éditions** : bouton « Prévisualiser tout en local » (compte admin actif, `APP_ENV=local`). Adresse directe : `/workspace/preview/os-jogos-do-agricultor`.
- **Mobiliser** : tournées juntas/cafés, courriers imprimables, offres, préventes/répartition, agents, billets, reversements et suivi SMS. Mandats/distributeurs à créer après accord, sans compte de démonstration. Le relais se connecte à `/relais/connexion` ; aucune administration partagée.
- **Préparer** : infrastructures et fiches stands (« Fiche et besoins »), quartel, numéro et demandes justifiées. Les puissances déclarées ne deviennent pas disponibles automatiquement.
- Prévente : `/events/os-jogos-do-agricultor/prevente`. En espèces, le relais crée le reçu **avant que le client parte** et le lui imprime/remet. Internet nécessaire ; aucun mode hors ligne non contrôlé. Le client vérifie le QR ; seul le rapprochement du solde reçu par QAPAS valide.
- `PRESALES_ENABLED=false` et `PRESALES_SMS_ENABLED=false` par défaut. Finaliser droits exacts, split, dates confirmées, IVA, contrats, capacité, légalité et procédure de remboursement, puis enregistrer et ouvrir séparément le plan dans Filament. Les 12–13 décembre sont seulement proposés ; le domaine `.tld` doit être remplacé.
- Paiement carte : paramètres Stripe existants dans `.env`, webhook `/payments/stripe/webhook`, clé et mode cohérents. Tester avec Stripe test ; ne jamais convertir les reçus test en recettes réelles.
- SMS : `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM` ; activer uniquement après validation du numéro expéditeur et du tarif. Exécuter `php artisan schedule:work` en développement ou installer le planificateur Laravel en exploitation. `php artisan events:ticket-sms` traite la file et les états prestataire. Un état `unknown` exige une vérification chez Twilio avant toute intervention ; pas de réémission aveugle.
- Les six euros QAPAS sont avant IVA/frais/prestations, pas une marge de 60 %. Le rapport préventes ne double pas automatiquement les lignes budgétaires ; les crédits boissons des candidats non retenus restent dus. Aucun contrat boissons ne doit se fonder sur le brut collecté chez un relais.

L’email est facultatif dans la prévente, y compris au relais pour les candidats : nom et numéro SMS suffisent. Sans email, la déduplication utilise une empreinte protégée du nom et du numéro ; aucun faux email n’est fabriqué. Les vérifications locales d’identité et du scrutin restent nécessaires. Un paiement candidat reçu après sa clôture est conservé en revue, sans validation ni SMS de confirmation, et peut être remboursé avec preuve.

Voir [prix, trophée et welcome pack](docs/RECOGNITION-SPONSORSHIP.md) : préfinancement, tailles, réserve et sponsoring.

Actualisation : [merchandising, badges QR et prix de couverture 6 + 6](docs/MERCHANDISING-BREAKEVEN.md).

Actualisation : [parcours public, communes, équipes locales et croissance](docs/PUBLIC-MOBILIZATION.md).

Actualisation : [déroulé opérationnel, coordination terrain et gardien des comptes](docs/FIELD-COORDINATION.md).

Proposition privée à examiner : [financement accessible du format 6 + 6](docs/ACCESSIBLE-LAUNCH-PROPOSAL.md), sans modification des tarifs actifs.
