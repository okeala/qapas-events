# Architecture indépendante

> Évolution du 30 septembre 2026 : [équipes expertes, locations, candidatures payantes et contrat boissons](EXPERT-REGISTRATION.md). Cette évolution remplace la fabrication Douglas et introduit un paiement de candidature distinct des ventes de stands, toujours fermées.


Monolithe Laravel 13 : domaine et données propres, controllers publics fins, services financiers/de transition, administration Filament 5/Livewire 4. Le produit a sa propre base, clé, cookie, identité `events` et configuration. Aucun appel à Platform n’est nécessaire pour lire une édition ou utiliser l’atelier local.

`packages/qapas-shared` 0.1.8 est un instantané de présentation repris de `okeala/qapas-application`, source inspectée au 30 septembre 2026, arbre Git `661dfb0758f2350f776d772872d962b55454120d`. Le paquet est utilisé pour la navigation publique et les styles, pas comme application mère. Les composants supplémentaires restent disponibles sans exposer leurs routes. Les dépendances Composer/npm ont été reprises verrouillées à l’identique de cette source ; seul le nom du projet et l’empreinte de métadonnées racine ont changé.

La synchronisation complète `qapas-core` de qapas-application ne s’applique pas ici : elle écraserait une partie des conventions propres à ce nouveau projet. Aucune certification de compatibilité des autres installations n’est avancée.

## Sécurité de la fondation

Deux providers/guards : `web` (réservé aux futures identités) et `admin`. Aucun endpoint public de création d’administrateur. Argon2id ; création CLI avec mot de passe masqué ; Filament refuse un administrateur inactif. Les policies refusent la suppression depuis l’atelier. Les ressources n’exposent pas de champs arbitraires ou d’édition de rôle.

Les adresses de ressources utilisent des UUIDv4. Les IDs SQL ne sont que des clés internes et des valeurs de relation dans l’administration authentifiée. Les routes publiques utilisent le slug et vérifient la visibilité en lecture comme en écriture. Le formulaire public crée uniquement les champs explicitement validés ; CSRF, limites de fréquence, champ piège, opt-in marketing indépendant et échappement Blade.

Le registre des validations exige une référence de preuve, une personne identifiée, une date passée et une validité couvrant la date de fin prévue. Les dérogations « non applicable » ne concernent que nourriture et musique. Supprimer une ligne ne contourne pas le contrôle : le service attend toujours la liste canonique des conditions. Aucun chemin de vente activable par simple variable d’environnement.

La phase est modifiable par le service de transition, pas un champ du formulaire d’édition. Le passage « prêt » et « en cours » vérifie les mêmes préconditions. Les validations restent sous la responsabilité humaine de l’organisateur ; le logiciel ne délivre aucune autorisation administrative.

Journal d’activité local : type, UUID, action, compte administrateur et noms des champs modifiés. Il n’enregistre ni mot de passe ni copie des messages. Ce journal n’est pas une preuve infalsifiable : une chaîne de conservation documentaire sera nécessaire dans le lot contractuel.

## Modèle

Une édition possède scénarios, offres, demandes, idées, conditions, équipes, stands, activités, séquences et bilans. Une ligne budgétaire appartient à un seul scénario. Les scénarios ne se cumulent pas. Les deux types de stands sont distincts du type de structure ; un emplacement nu et un emplacement avec structure peuvent appartenir au même stock futur.

L’administration initiale est un atelier commun aux organisateurs de confiance, pas un SaaS multi-tenant. Les équipes/juntas/exposants n’y reçoivent pas de compte. Le modèle d’habilitation par édition sera livré avant leurs espaces privés.

## Exploitation future

Les dates sont stockées en UTC, saisies en Europe/Lisbon dans les séquences. SQLite facilite le démarrage ; avant la vente concurrente, choisir PostgreSQL ou MySQL et tester transactions, verrous, idempotence et sauvegarde/restauration. Prévoir stockage privé des preuves, files de traitement, supervision des callbacks, limitation des imports et rétention documentée. Ne pas déployer la collecte tant que sa notice, ses contacts et son hébergement ne sont pas validés.

## Épreuves et terrasses (v0.2)

`activities` porte la carte publique et le dossier interne ; `activity_materials` est sa nomenclature. Le pivot unique `activity_scenario` sélectionne les épreuves chiffrées sans copier leurs coûts. `ActivityCost` marque les besoins incomplets et `ScenarioCalculator` en déduit coûts économiques et réserves TTC. Leaflet 1.9.4 est la nouvelle dépendance npm, verrouillée ; aucun fournisseur de tuiles externe n’est appelé.

`terraces.boundary` contient un polygone simple relatif à l’image, de 3 à 80 sommets dans [0,100]. Géométrie, appartenance à l’édition et inclusion des points sont contrôlées sur le serveur. La réduction d’une terrasse ne peut abandonner une épreuve hors de sa zone. Toute modification opérationnelle invalide sa revue de risque. Les champs internes ne sont pas sérialisés dans la projection publique.

Le plan est réencodé PNG via GD et stocké sur le disque privé avec un UUID. Les routes image contrôlent les deux indicateurs publics, ou le guard admin actif. Le service de publication limite les polygones/points aux objets publics ; il ne masque pas des pixels dans l’image de fond. Le remplacement d’un fond déjà positionné n’est pas implémenté. Les garde-fous applicatifs ne remplacent pas la visite du site, l’étude de stabilité ou les autorisations.

## Géographie et préfinancement (v0.3)

`site_features` stocke uniquement une géométrie GeoJSON validée ; `site_needs` en décrit les coûts fixes ou journaliers. `activity_locations` donne les rôles de plusieurs zones sans dupliquer les coûts d’une épreuve. Projection publique explicite, masquage serveur des objets privés et des activités cachées. L’ancienne géométrie sur image est conservée.

Les budgets restent propres à chaque scénario. Les lignes peuvent pointer un stand et son partenaire, et distinguent frais communs, stand, bar, friterie ou partenariat général. Les coûts de site sont intégrés via un pivot unique. `LaunchReport` calcule contributions et préfinancement, `ScenarioCalculator` garde la prévision et les engagements. Les rapprochements sont des attestations manuelles auditées, invalidées par les changements financiers. L’exposition aux remboursements est une réserve documentée, sans moteur juridique automatique. Les seuils ne déclenchent aucun achat, paiement ou message.

`ideas.depends_on` décrit un graphe sans cycle dans une même édition. La preuve d’un jalon est vérifiée aussi en lecture. `Revelation` contrôle les conditions d’une annonce confirmée et affiche une réévaluation si elles cessent d’être remplies. Le frontend n’affiche pas les règles des aperçus. L’accès privé reste réservé aux administrateurs actifs de confiance.

## Chiffrage par unité (v0.5)

Les Relation Managers Filament partagent le formulaire financier et imposent propriétaire, nature et appartenance au scénario côté serveur. Les pages utilisent les UUID publics. `Pricing` suit origine et fiabilité ; `UnitCosting` regroupe les lignes sans cumuler les scénarios. Les références de coûts mutualisés relient matériel et ligne budgétaire sans duplication. `costing-two-days-v1` est un seed privé idempotent : prix publics identifiés, hypothèses distinctes, aucun engagement ni paiement inventé. Un prix connu avec IVA inconnue conserve le coût TTC dans le besoin de financement, en signalant l’incomplétude. Les investissements initiaux restent à financer en totalité. Voir `docs/UNIT-COSTING.md`.

## Préventes, agents et reçus

`PresalePlan` porte les conditions versionnées FR/PT, le type de contrepartie, le contingent et les quatre affectations. `Ticketing::issue` verrouille le plan, réserve une place (y compris reçus en attente), fige les conditions et la répartition et déduplique le formulaire par UUID. Le guard `relay` est distinct de `admin` et `web`. Un `Distributor` est lié à un `StandPartner` actif et à un mandat documenté. La suspension du mandat ferme ses opérations, pas le rapprochement QAPAS de sommes déjà collectées.

Espèces : le relais encaisse 10 €, conserve 1 € selon le contrat et doit remettre 9 €. Le billet reste `pending`. Seul un administrateur actif peut créer un `TicketSettlement` avec sélection des billets, somme exacte, date réelle, référence unique et preuve de réception. La transition en `paid` est atomique et met aussi à jour la candidature liée, sans deuxième paiement. Le registre distingue montant brut, commission déjà retenue et solde reçu. Le QR n'est qu'une consultation : aucun GET ne valide ni ne consomme le billet.

Carte : Stripe Checkout du compte QAPAS, métadonnées `ticket`/`attempt`, montant TTC, IVA inclusive vérifiée, clé d'idempotence et facture demandée. Le webhook existant vérifie sa signature puis route aussi le registre des billets ; la session est relue chez Stripe (identité, montant, devise et mode). Les événements sont idempotents et un remboursement/litige ne peut pas être annulé par une confirmation tardive. La conformité de facturation portugaise reste un préalable explicite, pas une propriété présumée de Stripe.

Les numéros sont chiffrés au repos avec APP_KEY. Le QR UUID ne révèle ni email ni téléphone ni nom complet. La gestion privée du reçu utilise des URL signées. La liste publique est volontaire et révocable. Seuls les paiements confirmés du mode courant y figurent. L'entrée payante, si activée dans le contrat, possède un contrôle unique atomique réservé à un administrateur actif. Les crédits boissons candidats restent dans leur registre existant, sans seconde recette.

`TicketMessage` est une boîte d'envoi transactionnelle créée à la confirmation. `events:ticket-sms` (planificateur chaque minute) reste désactivé par défaut et ne transmet que les vrais billets confirmés. Adaptateur Twilio : `accepted` n'est pas `delivered` ; la commande consulte l'état du prestataire. Un timeout ou une interruption produit `unknown`, sans réessai automatique pouvant dupliquer le message. Vérifier alors le compte Twilio. Aucun SMS ni email n'est envoyé par le seeder ou les tests.

Le rapport des préventes sépare sommes affectées, frais inconnus, contreparties et fonds bancaires rapprochés. Il ne crée aucune ligne de recette budgétaire automatique. Ne pas additionner ses fonds à ceux du rapport candidats : il s'agit des mêmes flux. Une candidature dispose d'un seul `EventTicket`; le paiement historique est interdit quand ce lien existe. Les ouvertures de prévente restent fermées tant que les contrats et la répartition ne sont pas validés.

Les vues `/workspace/preview/{slug}` exigent **environnement local ET administrateur actif**, en-têtes no-store/noindex. Aucun paramètre de requête ne transforme une page publique en page privée. Le catalogue, les règles secrètes et la carte privée peuvent y être examinés sans publication. Illustration générée : `public/images/festival-concept.webp`, dérivée de `generated_images/exec-634cdc9a-8807-4114-b2d3-9c5c39a9039d.png`, mode imagegen neuf. Prompt : illustration éditoriale hivernale d'une petite fête agricole à Aldeia do Souto, terrasses et maisons de granite, stands de produits, habitants, soupe au chou et tracteur immobile éloigné, palette vert forêt/crème/ocre, sans textes, logos ni portrait identifiable. Image d'ambiance, pas photo ou représentation certifiée du site.
