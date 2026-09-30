# Équipes expertes, locations et contrat boissons

## Scénario courant

`costing-rental-experts-v1` est une nouvelle hypothèse de travail, 6 équipes + 6 indépendants et 2 jours. Les anciens scénarios sont archivés et restent consultables. Leurs contrats, paiements et frais personnels ne sont pas supprimés, ni transformés en encaissements du nouveau scénario. Avant toute nouvelle vente, rapprocher les engagements historiques.

Le mobilier est fourni et payé par chaque participant : location privilégiée, matériel propre ou prêt documenté possibles. Aucun ensemble table-bancs inclus ; fabrication Douglas retirée du scénario actif. Le devis participant sépare quantité, prix, livraison/reprise et caution. Aucun de ces flux directs au loueur ne devient du chiffre d’affaires QAPAS. La tente reste dans la provision existante : cette modification concerne le mobilier. Cible d’accueil conservée : 24 personnes abritées, 12 assises, circulation et espaces debout préservés.

À hypothèses inchangées hors mobilier, le coût chiffré passe de 12 157,08 € à 10 629,04 €. Le déficit prévisionnel passe de 6 285,24 € à 4 757,20 €, avant les inconnues dont la rémunération complète. Ce n’est pas un budget prêt à lancer. Les coûts exclus sont 1 288,04 € de fabrication et 240 € de service mobilier. Les quantités de candidatures payées ne sont jamais inventées.

## Rôles de l’équipe, choix citoyen

Cuisinier, greffeur, fruiticulteur, maraîcher, pelliste confirmé, excellent conducteur de tracteur, gardien des comptes, athlète endurant, personne de force, expert en adresse, commercial, conteur, leader et musicien capable de lire une partition simple. Les libellés et explications existent en FR/PT. Ces rôles n’impliquent pas automatiquement quatorze personnes distinctes ; un cumul doit être explicitement justifié (horaires, aptitude, responsabilités et protocole de vote).

Chaque équipe reçoit quatorze postes ; six sont indispensables selon les règles communautaires actuelles. Proposition → contrôle du consentement, du registre local et de la compétence → confirmation. Dans une édition avec campagne payante, il faut lier une candidature payée pour confirmer. Une modification de la personne ou de ses justificatifs révoque sa confirmation. Le partage interéquipes est limité au pelliste, avec les accords et horaires compatibles décrits dans COMMUNITY-2026.md. L’inscription ne remplace ni le bulletin à la junta, ni le procès-verbal local. Le site ne publie pas les noms, coordonnées ou pièces des candidats.

## Candidature payante

10 € TTC par personne et édition, y compris pour plusieurs rôles. Le formulaire d’intérêt reste gratuit et distinct. La clé d’unicité email normalisée empêche un deuxième dossier par adresse dans l’édition ; elle ne prétend pas vérifier l’identité civile. Vérifier identité et éligibilité au registre local avant confirmation des rôles. Aucun compte administratif n’est créé.

Après acceptation explicite des conditions FR/PT et de leur version, le dossier conserve un instantané du contrat et de la politique d’annulation. Le candidat reçoit une page privée signée et paie chez Stripe Checkout. Le retour navigateur ne valide rien : seul le serveur confirme le montant exact, la devise, le dossier et le statut via l’API Stripe, après réception d’un webhook signé. Les tentatives ont une clé d’idempotence ; un Checkout encore ouvert est réutilisé. Aucun paiement n’est effectué par le seed ou les tests.

La page privée est valable 30 jours, sans données nominatives. Le gestionnaire peut produire un nouveau lien depuis le contrat accepté après avoir vérifié la personne. Aucun courriel n’est envoyé automatiquement. Les inscriptions et pièces contractuelles sont exclues de la purge des simples pistes commerciales à 180 jours ; leur durée légale de conservation et la procédure d’exercice des droits doivent être renseignées avant ouverture.

## Vote, tickets, remboursements

Consigner la clôture des candidatures et des scrutins, puis les procès-verbaux dans la campagne. Consigner les équipes élues avant d’appliquer le résultat aux dossiers. Un candidat retenu doit correspondre à un rôle confirmé d’une équipe élue. Un candidat payé non retenu reçoit exactement un crédit de 10 € de tickets boissons. C’est la contrepartie nominale retenue pour cette version ; ses modalités d’usage doivent figurer au contrat.

Au bar, un administrateur actif recherche le code privé et décompte la valeur des boissons servies. Chaque opération a un UUID, un montant, un libellé, un agent et une date. Un rejeu identique est sans effet et le solde ne peut devenir négatif. Un remboursement ou litige gèle le crédit. L’action de remboursement effectue une demande réelle à Stripe et attend sa confirmation par webhook ; un crédit déjà consommé impose un traitement individuel. Une correction du résultat après publication est également un traitement individuel avec historique : pas de bascule silencieuse susceptible de créer plusieurs crédits.

## Trésorerie → contrat boissons

Filament : **3 · Mobiliser → Inscriptions et contrat boissons**. Trois notions distinctes :

- **Paiement confirmé** : Stripe a confirmé le paiement, pas nécessairement son arrivée en banque.
- **Affectable** : montant net de l’IVA qualifiée et des frais connus, plafonné aux fonds bancaires rapprochés, puis diminué de la réserve de remboursements. Frais inconnus ou réserve/preuve bancaire manquantes : montant concerné non affectable.
- **Contreparties** : crédits potentiels, émis et consommés. La valeur faciale de 10 € n’est ni le coût d’achat des boissons ni une marge disponible.

L’affectation peut financer l’acompte ou l’approvisionnement prévu au contrat boissons, y compris les boissons dues aux non-retenus. Elle requiert fournisseur, contrat, montant, preuve de rapprochement, réserve et décision documentée. Elle ne déclenche aucun virement et ne prouve pas l’accord du brasseur. Après un remboursement/litige, le tableau signale l’affectation excédant les ressources restantes.

Les tickets sont un moyen d’utiliser un paiement antérieur : aucune nouvelle recette n’est créée lors du service. Le registre reste séparé des lignes prévisionnelles de vente du bar. Pour consolider le business plan, distinguer boissons payées en espèces/carte et boissons déjà payées via ces tickets, documenter coûts et IVA, et rapprocher une seule fois les inscriptions. Ce mécanisme ne débloque pas automatiquement le break-even ni l’autorisation de lancement.

## Activation

La campagne livrée est fermée et les clés absentes. Aucun encaissement réel n’est activé par défaut.

1. Finaliser conditions FR/PT (éligibilité, vote, recours, tickets, annulation/report/remboursement), dates, site, capacité, assurances, fiscalité, facturation et conservation. Valider les exigences de l’édition et référencer les preuves. La présence de textes n’est pas un avis juridique.
2. Configurer `REGISTRATION_PAYMENTS_ENABLED=true`, `REGISTRATION_STRIPE_LIVE=false`, `REGISTRATION_STRIPE_SECRET=sk_test_…`, `REGISTRATION_STRIPE_WEBHOOK_SECRET=whsec_…` dans l’environnement privé. Ne jamais committer les secrets. Tester l’intégration et ses retours avec son compte Stripe dans une base de test séparée. Les dossiers portent leur mode test/réel ; un paiement test ne finance pas une exploitation en mode réel.
3. Endpoint POST `/payments/stripe/webhook`. Événements : `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `charge.refunded`, `charge.dispute.created`. API épinglée `2025-06-30.basil`. Même mode et version côté endpoint. Signature sur le corps brut, tolérance de 300 secondes, journal des événements idempotent.
4. TVA : aucune valeur par défaut. Si taux > 0, créer un taux Stripe actif **inclusif** identique et renseigner son `txr_…`. Le prix total reste 1 000 centimes. Le document produit par Stripe ne présume pas de conformité de la facturation portugaise ; renseigner le circuit fiscal applicable.
5. Passer en réel uniquement avec clé live, secret du webhook live, `REGISTRATION_STRIPE_LIVE=true`, URL HTTPS, procédures validées, puis ouvrir la campagne dans Filament. Un changement des conditions ferme la campagne et exige une nouvelle version si des contrats ont été acceptés.
6. Configurer les reprises de fûts, mobilier, acompte et échéances dans le contrat fournisseur réel. Rien ne présume l’acceptation de Super Bock, Sagres ou d’un autre fournisseur.

Les ventes de stands restent fermées : cette version n’active que le mécanisme spécifique de candidature, après ses contrôles.

Sources techniques/fiscales vérifiées : [Stripe Checkout](https://docs.stripe.com/api/checkout/sessions/create), [signatures](https://docs.stripe.com/webhooks/signature), [versions](https://docs.stripe.com/api/versioning), [CIVA art. 7](https://info.portaldasfinancas.gov.pt/pt/informacao_fiscal/codigos_tributarios/civa_rep/Pages/iva7.aspx). La qualification du paiement et des bons doit être examinée avec le professionnel chargé de la fiscalité ; les règles des bons à usage unique et multiple diffèrent.
