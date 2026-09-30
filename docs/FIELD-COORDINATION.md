# Déroulé opérationnel et coordination terrain

État au 30 septembre 2026. Le déroulé opérationnel est le planning de réalisation de l’événement : séquences, heures, responsables, lieux, consignes et état constaté. Il couvre montage, ouverture, épreuves, annonces, accueil, pauses, clôture et démontage. Ce n’est pas le programme public ni le parcours commercial de lancement.

## Livré

`Exploiter → Déroulé opérationnel` affiche les séquences chronologiquement, les heures du Portugal, le responsable et la zone. Recherche par séquence/responsable/zone, filtres édition/état, rafraîchissement toutes les 15 secondes. Dates, responsable et édition sont vérifiés côté serveur. Les états restent prévue, en cours, terminée, annulée ; ils sont renseignés par la coordination.

Le bouton **Organiser la coordination terrain** explique le fonctionnement existant et la proposition ci-dessous. `Incidents` garde les signalements QR, leur prise en charge et leur résolution. Les équipes fictives du mode local ne bloquent plus la préparation du véritable événement.

Il n’y a pas encore de messagerie terrain, de notifications de mission, d’accusé de réception, de géolocalisation des personnes ou d’espace intervenant. Les quatre états du planning ne constituent pas un suivi individuel de présence.

## Chaîne proposée

| Niveau | Responsabilité | Vue utile |
|---|---|---|
| Coordination QAPAS | Arbitrer le programme, les moyens et les conflits ; préserver la sécurité et le budget | Retards, blocages, incidents et décisions attendues |
| Responsable de zone | Distribuer les missions, confirmer les effectifs et contrôler le résultat | Missions de sa zone et dépendances avec les autres zones |
| Intervenant | Accuser réception, rejoindre son poste, exécuter et signaler | Ma mission maintenant, rendez-vous, contact du responsable, prochaine mission |

Zones possibles : accueil/parking, épreuves, stands, bar/soupe, technique/logistique, médias. Une zone ne signifie pas automatiquement un salarié supplémentaire ; les effectifs et cumuls doivent être dimensionnés par créneau. QAPAS dirige, les personnes affectées au bar et à la soupe assurent ce service. Une personne chargée de la sécurité est explicitement habilitée à demander l’arrêt ; ses conditions de reprise sont connues avant l’ouverture.

Chaque mission a un responsable unique, un remplaçant, une zone/un point du plan, une séquence, un horaire, un résultat attendu, des moyens et une condition de départ. Les tâches simultanées incompatibles sont signalées. Chaque participant consulte sa mission sans devoir parcourir tout un groupe de discussion.

## Échanges et retours

Cycle proposé : **envoyée → reçue/acceptée → en place → en cours → terminée → vérifiée par le responsable**. « Bloqué » reste accessible à toute étape avec motif, conséquence et aide demandée. « Lu » ne prouve ni acceptation ni exécution.

Exemple fictif : à 10 h 20, le responsable épreuves doit confirmer la fermeture du paddock et le positionnement du public avant le passage de 10 h 30. Il répond « en place, contrôle achevé » ou « bloqué : deux barrières manquent ». QAPAS peut décaler le passage et prévenir l’animation/écran. Le lancement demeure une décision humaine après les contrôles requis.

- Les consignes vont au bon destinataire ou à une équipe explicitement constituée. Diffusion générale réservée aux changements qui concernent tout le monde.
- Pas de réponse dans le délai, retard ou blocage : alerte au responsable de zone ; escalade à QAPAS si non résolu. Les délais sont fixés selon la mission, pas identiques pour une livraison et une urgence.
- Toute modification importante crée une nouvelle version ; l’accusé de l’ancienne ne valide pas la nouvelle. Les messages contradictoires sont arbitrés par le responsable, pas empilés.
- Enregistrer émetteur, destinataires, version, heure et retours. Une mission terminée est contrôlée ; conserver le journal pour le débriefing.
- Un incident urgent impose une intervention sur place/radio. Une notification web n’est jamais la condition préalable d’un arrêt de sécurité. Pas de redémarrage automatique après clôture informatique.

## Lots de développement proposés, non livrés

1. **Identités terrain et affectations** : accès personnel révocable, limité à l’édition et aux zones/missions ; comptes distincts des administrateurs, du public et des distributeurs. Le QR public d’authenticité du badge n’est jamais un identifiant de connexion.
2. **Missions et journal de retours** : rattachement au planning et au plan du site, suppléances, accusés versionnés, blocages, contrôle de clôture ; autorisation serveur de chaque lecture et transition, prévention des doubles actions.
3. **Tableau de coordination et alertes** : retard/non-réponse, filtre par zone, incidents liés, échéances et accusés. Première version mobile Livewire par rafraîchissement régulier ; notification SMS seulement via transport configuré, avec preuve d’envoi et statut distinct de la lecture.
4. **Répétition et repli** : tester sur le terrain couverture réseau, permissions, absence d’un chef de zone, changement de consigne, perte de connexion, arrêt et reprise. Conserver radios, contacts et fiches papier ; budgéter matériel, communications et permanences. Le développement n’est pas une économie de personnel présumée.

## Gardien des comptes

Libellé FR **Gardien des comptes**, PT **Guardião das contas**. Suit recettes, dépenses et pièces de son équipe ; explique la situation à l’équipe et à QAPAS. Ce rôle n’est ni un mandat d’élu de la junta ni une exigence de profession comptable. Les obligations des entités et de leurs professionnels restent distinctes.

Le code technique historique `accountant` est conservé pour maintenir candidatures et affectations. Le seeder de vocabulaire corrige uniquement les formulations générées connues dans les scénarios actifs et la consigne initiale encore au stade idée. Les documents signés, preuves, archives et textes personnalisés ne sont pas réécrits en bloc. Les libellés publics sont bilingues ; l’atelier d’administration conserve sa langue française actuelle.
