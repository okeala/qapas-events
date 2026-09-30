# Architecture indépendante

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
