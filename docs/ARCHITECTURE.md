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
