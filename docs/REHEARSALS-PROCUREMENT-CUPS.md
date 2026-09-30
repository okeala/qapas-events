# Répétitions, devis et gobelets — 30 septembre 2026

L’édition Os Jogos do Agricultor exige désormais des essais documentés. Le menu **Préparer → Répétitions et essais des défis** et l’onglet d’une épreuve permettent de planifier et consigner les essais. Les dates du week-end se définissent dans l’édition ; aucun calendrier, volontaire, accord ou résultat positif n’est inventé.

- Épreuves QAPAS : répétition avec volontaires locaux, responsables, compte rendu, durée, ajustements. Les exigences techniques et de sécurité restent indépendantes.
- Défis des stands : essai chez le proposant, puis installation et contrôle à la Quinta durant les sept jours précédant l’ouverture. Les phases et leur ordre sont validés côté serveur.
- Un changement de scénario de jeu ou d’implantation invalide les essais concernés. Les observations restent consultables.
- Un défi externe non prêt ne bloque pas à lui seul les jeux officiels. L’action « Maintenir uniquement le stand » retire sa présentation publique et laisse le stand en place. À l’ouverture, un défi externe sans essais valides est masqué. Aucun coût déjà prévu ou engagé n’est automatiquement annulé.

## Courriers et offres

**Chiffrer → Courriers et devis fournisseurs** : brouillons FR/PT, personnalisables, téléchargement `.eml` pour ouvrir dans votre messagerie. Aucun email n’est envoyé par l’application. Le bouton « Préparer les courriers des coûts actuels » complète les demandes manquantes sans réécrire vos textes. Chaque poste budgétaire de coût et chaque besoin matériel dispose aussi d’une action directe. Les besoins du plan disposent d’une action par élément du site.

Les besoins mutualisés, notamment la captation commune, ont une seule consultation. Chaque consultation conserve plusieurs offres : fournisseur, prêteur local, sponsor ou bouche-à-oreille ; quantité, unité, TTC, IVA, transport, frais, caution, validité, conditions et référence de la pièce reçue. Une offre incomplète peut être enregistrée sans devenir un coût certain.

La reprise d’un devis au budget exige la même quantité et unité et un prix tout compris, documenté, daté et encore valide. Les frais annexes et cautions positives exigent une décomposition manuelle pour ne pas les oublier. La reprise laisse le prix « devis reçu, à valider » et ne crée ni commande, ni engagement, ni paiement. Une offre déjà reprise conserve son historique : saisir une nouvelle version pour la modifier.

Mini-rétro : la prévision de transport est ramenée à **150 € pour un forfait** uniquement si la ligne initiale est encore intacte. HT/TTC, aller-retour et répétition distincte restent à confirmer. MaquiDonas est une piste de prospection avec coordonnées publiques, pas un fournisseur engagé ; aucun devis ni remise ne lui sont attribués. Les solutions au village restent à comparer.

## Gobelets et soupe

**Préparer → Gobelets, stocks et soupe** distingue stock prévu, stock reçu et allocations physiques. Plan initial : 1 000 gobelets, 500 par catégorie, répartis entre les six stands de chaque catégorie (84, 84, 83, 83, 83, 83). Aucun gobelet n’est présumé reçu ou remis. La réception, les responsables et les remises doivent être documentés ; impossible de distribuer davantage que le stock disponible ou de dépasser le lot d’une catégorie. Les écritures sont sérialisées par verrou sur le plan de stock.

La copie de tarif fournie porte sur **250 pièces, 300 ml, 356,39 € HT / 424,10 € avec 19 % et livraison annoncée**. La page du produit n’a pas permis de vérifier les conditions détaillées. Ni ce taux, ni un délai affiché, ni une compatibilité avec le chaud ne sont confirmés pour la commande au Portugal.

Le budget contient une **provision explicite de 1 696,40 €** (quatre lots de la référence transmise), et non un devis ferme pour 1 000. Demander le véritable tarif livré pour 1 000, le BAT, les échéances et l’IVA. La ligne `cups-resale` laisse le prix de revente aux stands inconnu. Elle doit être chiffrée et contractualisée ; aucun bénéfice de revente n’est ajouté d’office.

La **consigne visiteur conseillée de 2,50 €**, gérée par les stands selon des règles à convenir, n’est pas une recette QAPAS. Ne pas confondre marge commerciale, caution remboursable et souvenir conservé. Définir les retours, responsabilités, invendus, casse et soldes. La gestion du gobelet n’autorise pas un bar payant chez les exposants.

Demander au fabricant de la référence exacte les usages alimentaires, température et durée autorisées, alcool, soupe et vin chaud, nombre de lavages et tenue de l’impression. En attendant, **ne pas utiliser ces gobelets à chaud**. Prévoir un devis distinct pour bols adaptés à la soupe au chou, cuillères, location/prêt et lavage. L’ancienne provision de bols jetables est remplacée par un poste à chiffrer ; les autres consommables/lavage du bar sont séparés de l’achat du stock.

Le sponsoring principal peut financer une production avec BAT, livrables et quantité documentés ; aucun financement ni taux de conservation du souvenir n’est présumé.

## Lisibilité et doublons

Le tableau budgétaire ouvre un seul scénario actif, permet de choisir explicitement les archives, groupe les postes par service, stand, nature ou fiabilité et filtre les inconnues. Les sous-totaux TTC connus séparent dépenses et recettes ; les inconnues restent visibles et bloquent toujours la confirmation du budget.

Deux libellés normalisés identiques dans la même unité, même nature et même scénario sont rejetés. Les mêmes prestations pour deux stands différents restent légitimes. Une clé de mutualisation représente une dépense commune unique. Les doublons historiques ne sont pas effacés : une action permet d’en écarter un sans montants engagés/payés ni justificatif, en conservant le lien vers le poste retenu. Des doublons actifs empêchent la validation financière.

La migration conserve les scénarios archivés et les lignes financières existantes. Le seeder est réexécutable, préserve les prix personnalisés et les textes des demandes. Les dépenses déjà engagées exigent un examen humain, jamais une suppression pour rendre le budget vert.

## Mise à jour locale

```bash
git pull --ff-only origin master
composer install
php artisan migrate --seed
php artisan optimize:clear
npm ci
npm run build
```

Sources : https://maquidonas.pt/sobre-nos/ ; https://www.onlineprinters.org/p/festival-cups (copie de tarif fournie, détails non vérifiables lors de la consultation) ; https://relevo.app/en/reusable-system-plastic/ (exemple de bols réutilisables pour soupe, pas un devis ou une disponibilité locale) ; https://filamentphp.com/docs/5.x/tables/grouping ; https://filamentphp.com/docs/5.x/tables/summaries .
