# Version 0.2.0 — périmètre vérifiable

La version 0.2.0 est une nouvelle base de développement utilisable. Le CDC v0.3 reste la cible détaillée ; son contenu ne signifie pas que toutes les intégrations externes sont déjà réalisées.

## Moteur et référence

Les tables `planner_events` et `event_*` appartiennent au moteur. Chaque événement contient des scénarios, avec leur plan, leurs coûts, offres et produits. Une variante ne copie pas clients, encaissements, réservations, personnes affectées ou stocks. Les pièces commerciales et publications gardent leur historique.

Farmers Games est un fixture activé explicitement. Ses sources et hypothèses sont référencées dans `fixture.php`. Ses prix, 12 + 12 stands, six villages et trois sponsors ne sont pas des constantes du moteur. Le jeu propose des besoins sans les présenter comme acquis.

## Disponible

Les six pages Filament et les services métier sont opérationnels. Le plan utilise Leaflet et Geoman, des gabarits serveur et des zones libres contrôlées. La révision protège les modifications concurrentes ; finance et stock utilisent transactions et verrous. Les montants sont des centimes entiers ; les inconnus sont des valeurs nulles.

Les validations 4P ont une portée, un public, indicateur, cible, résultat, source, échéance, responsable et décision. Les informations de réussite ne deviennent pas automatiquement des revenus. Les prix et conditions des nouvelles offres peuvent évoluer sans réécrire les engagements antérieurs.

La publication produit une projection publique immuable. Le site marketing présente visite, offres, FAQ et affiche avec QR. Les offres expirées, désactivées, épuisées ou modifiées sont fermées. La confirmation produit une publication officielle ; une simple date ou un nombre de contacts ne confirme pas l’événement.

Le bilan distingue prévisions, acquis, réserves et scénarios. Les ventes de l’organisateur produisent leur recette et consommation de stock ; investissements et approvisionnement restent distincts. Les mouvements sont rapprochés par référence et emplacement.

## Points restant à connecter ou étendre

| Sujet | État et suite |
|---|---|
| Paiement et restitution | Rapprochement manuel disponible ; connecter le prestataire, la protection réelle des fonds et les webhooks signés. Le logiciel ne séquestre pas de fonds bancaires. |
| Contrats et fiscalité | Prix TTC et nets distincts. Charger les textes adoptés et valider la portée des contrats avant les ventes réelles. |
| Signature électronique | Référence d’accord externe conservée. Intégrer un prestataire si nécessaire ; l’interface ne fabrique pas une signature client. |
| Annulation après confirmation | Non-confirmation avant échéance gérée. Une annulation d’un événement confirmé exige son parcours contractuel ; la commande de non-confirmation la refuse. |
| Communication externe | Préparation, validation et attestation disponibles. Choisir les transports et consentements avant les envois. Aucun message n’a été envoyé aux contacts par cette livraison. |
| Comptabilité | Bilan de gestion et références disponibles ; connecter facturation, avoirs et comptabilité réglementaire. Un retour physique de produit n’est pas un remboursement client. |
| Terrain | Implantation demo illustrative ; importer le relevé exact et valider accès, évacuation, alimentation électrique, capacités et ressources. |
| Planification avancée | Besoins, créneaux et conflits par scénario disponibles ; étendre aux disponibilités entre événements, fournisseurs, accessibilité et contrôles techniques. |
| Mesure commerciale automatique | Hypothèses et constats documentés disponibles ; connecter attribution, résultats de campagnes et retours clients. |
| Exports professionnels | GeoJSON, SVG, PNG et PDF A3 publics disponibles. Compléter grands formats, plusieurs pages et profils internes selon le besoin. |

Les preuves restent des références vérifiées par l’organisateur : cette version ne consulte pas une banque ou un fournisseur pour les certifier. Le dossier documente une décision et ne supprime pas les risques d’exploitation.

## Intégration dans QAPAS Events

La V2 est intégrée dans `okeala/qapas-events`, sur `master`, et fonctionne sur le port 8890. Elle ajoute les six modules du groupe **V2 · Pilotage événementiel** à l’administration existante. Les comptes créés avec `events:admin` conservent leur accès ; aucun compte de démonstration n’est créé.

Les tables `planner_events`, `event_scenarios`, `plan_elements` et les registres `event_*` sont distincts du modèle `event_projects`, de ses scénarios et de ses préventes. Les migrations sont additives. Les scénarios, engagements et fonds des deux ateliers ne sont pas additionnés. Aucune conversion automatique d’une édition historique vers une édition V2 n’est effectuée.

La visite V2 est publiée sous `/visites`. Les pages historiques `/events` et leurs parcours restent disponibles. Le paquet de présentation existant est conservé ; cette livraison n’importe ni la synchronisation complète qapas-core ni les comptes démo du socle Farmers Games. Le parcours public de candidature V2 reste fermé jusqu’à livraison d’une identité vérifiée adaptée à QAPAS Events. Aucun utilisateur public ne peut devenir administrateur.

Le jeu Farmers Games V2 est un exemple historique isolé et illustratif (12 + 12). Il ne remplace ni le scénario 6 + 6 + 4 de QAPAS Events ni ses tarifs actifs. `bash scripts/setup-local.sh --demo` active cet exemple uniquement en local ; la démonstration est absente des hôtes publics.

La version antérieure de QAPAS Events est conservée au commit `2e3f419f8f395f4f72daa7e4129fb2de51e3501c`. Aucune base existante n’est remise à zéro. Le port 8888 et la référence Farmers Games mentionnés dans les sources de reprise sont historiques et ne définissent pas cette installation.
