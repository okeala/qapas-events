# QAPAS Events — instructions de travail

Lire `README.md`, `docs/PRODUCT.md`, `docs/LEGAL.md` et `docs/ARCHITECTURE.md` avant une évolution métier.

- Application indépendante : ne pas importer le métier Farmers Games, les abonnements Platform ou le mécanisme complet de synchronisation qapas-application.
- Stack : Laravel 13, PHP 8.3 minimum applicatif, PHP 8.4+ pour PHPUnit verrouillé, Filament 5, Livewire 4, Node 24. Installation de développement sur `127.0.0.1:8890` (port proposé, non enregistré dans Platform).
- Livrer sur `master` après vérification ; ne pas laisser la version utilisable uniquement dans une PR.
- Réutilisation QAPAS limitée au paquet `packages/qapas-shared`. Documenter sa provenance et tester toute mise à jour. Ne pas déclarer compatible le socle complet de qapas-application.
- Identité Platform, session frontend et administration sont distinctes. Aucun compte public n’obtient de rôle administratif. Pas de compte ou mot de passe de démonstration.
- Toutes les écritures et lectures privées exigent une autorisation serveur. Utiliser les UUIDv4 publics pour les routes de ressources. Échapper les contenus publics ; conserver CSRF et les limites de requêtes.
- Montants en centimes entiers, taux d’IVA en points de base ; hypothèses, engagements et paiements sont distincts. Dépôts, argent affecté et flux tiers ne sont jamais du chiffre d’affaires libre.
- L’objectif QAPAS est mensuel ; le coût complet de rémunération doit être renseigné. Aucune transformation fictive du net en coût employeur, aucune estimation inventée pour obtenir un résultat vert.
- Paiement réel uniquement après offres/contrats versionnés, stocks transactionnels, fiscalité, prestataire et webhooks idempotents. En v0.1 il n’y a pas de paiement ni réservation.
- Maintenir FR/PT et expliciter les replis linguistiques. Pas d’envoi marketing ou de partage de contacts sans consentement adapté.
- Tests exigés pour l’autorisation, les transitions, le financier et les formulaires ; build Vite et thème Filament avant livraison. Ne pas présenter une vérification statique comme une exécution PHP.

## V2 intégrée

Lire `docs/IMPLEMENTATION_V02.md` et `docs/CDC_QAPAS_EVENEMENTS.md`. Le planificateur V2 appartient à ce dépôt ; Farmers Games n’est qu’un jeu local historique isolé. Conserver les parcours existants, la séparation des tables et fonds, l’administration propre et le port 8890. Ne pas importer le socle complet ni les comptes démo de Farmers Games. Exécuter la suite existante et les tests V2, le build et Playwright avant intégration sur master.
