# Chiffrage par unité — simulation du 30 septembre 2026

## Où travailler

**Chiffrer → Chiffrage par unité** : sélectionner un scénario, comparer stands et services, ouvrir une unité. **Scénarios → Chiffrer** et **Stands → Chiffrer** : onglets **Coûts / Recettes**, ajout et édition en tableau lié (Relation Managers Filament 5). **Épreuves → Chiffrer** : nomenclature du matériel et prestations, quantités fixes ou par passage.

Dans la fiche stand, choisir un seul scénario dans le filtre. Effacer le filtre masque les lignes ; cela ne cumule jamais plusieurs hypothèses. Le formulaire de création vérifie côté serveur que le scénario inclut bien le stand. Prix en centimes dans les formulaires, affichage en euros dans les tableaux. Une quantité prévue à zéro conserve une option sans la considérer vendue. Prévision, engagement et paiement restent trois quantités distinctes.

Les tableaux indiquent quantité, unité facturée, prix TTC, total, coût retenu/recette hors IVA, fiabilité et source. Le scénario porte séparément les mois de travail, le coût complet de rémunération, les charges et l’objectif QAPAS. Une gratification nette souhaitée ne donne pas automatiquement le coût employeur.

## Scénario renseigné

`costing-two-days-v1` est une **simulation distincte**, sans modifier les prix du catalogue public ni les engagements du scénario courant `launch-hospitality-v1`. Douze stands : six freguesias et six indépendants ; deux jours en hypothèse, trois épreuves officielles distinctes par jour, six passages d’équipes par épreuve. Les six nomenclatures sont remplies seulement si encore vierges. Elles sont communes aux scénarios qui sélectionnent ces mêmes épreuves : le coût n’est pas recopié. Modifier la durée exige de revoir les jours de location, le nombre de passages, les stocks et le personnel ; aucune mise à l’échelle automatique d’un inventaire fixe.

Le seed est versionné et ne remplace pas les modifications ultérieures. `php artisan migrate --seed` suffit pour créer le scénario et les nouvelles données. Il ne crée aucun paiement, contrat, engagement, contact fournisseur ou autorisation.

### Lecture du premier calcul, sur une base vierge

| Poste | Montant |
|---|---:|
| 6 indépendants × 500 € TTC, emplacement inclus | 3 000,00 € |
| 6 parrains de stand × 500 € TTC, hypothèse à tester | 3 000,00 € |
| 6 premiers relais × 49,99 € TTC, hypothèse à tester | 299,94 € |
| Boissons 600 × 2 €, vin chaud 100 × 2 € | 1 400,00 € |
| Soupe 300 × 2,50 € | 750,00 € |
| Recettes TTC envisagées | **8 449,94 €** |
| Recettes après IVA simulée, arrondi par unité | **6 871,84 €** |
| Coûts déjà provisionnés TTC, sans récupération d’IVA présumée | **12 157,08 €** |
| Réserve imprévus de travail | 1 000,00 € |
| Solde après coûts déjà chiffrés et imprévus | **−6 285,24 €** |

Ces totaux ne sont **ni un devis global, ni un seuil de rentabilité définitif**. Il manque encore le coût complet de rémunération/charges, le chef, certaines validations techniques et autorisations, la gestion des paiements, les animations inventées localement et la réserve d’annulation. Les 4 000 € d’objectif QAPAS s’ajoutent au besoin pour atteindre l’objectif, pas pour calculer la simple égalité recettes/coûts. Aucune recette sur place ne préfinance le lancement. Aucun sponsor structurant n’est considéré acquis.

Le modèle charge l’intégralité des investissements initiaux pour mesurer la capacité à financer cette édition ; ce solde prudent n’est pas un résultat comptable avec amortissements. Les scénarios ne se cumulent pas. Les valeurs changent lorsque l’utilisateur a déjà renseigné des inventaires ou modifie la simulation.

### Une unité stand

Provision directe : abri fourni par QAPAS 120 €/événement, installation/consommables 20 €, livraison/reprise/nettoyage d’un ensemble six places 20 €, soit **160 €**. Il s’agit d’une enveloppe interne sans devis local. Retirer le coût d’abri seulement lorsque son apport par l’exposant ou son prêt est documenté. Structure particulière, branchement spécial et dimensions supérieures restent à ajouter.

- Indépendant : 500 € TTC → 406,50 € hors IVA simulée → **246,50 €** disponibles après les 160 € directs.
- Village : parrain 500 € TTC + premier relais 49,99 € TTC → 447,14 € hors IVA simulée → **287,14 €** après coûts directs.
- Total contribution des douze stands : **3 201,84 €**. Ce total doit encore absorber frais communs, épreuves, investissement et rémunération.

L’ensemble six places inclus n’est pas refacturé en supplément caché. Sa fabrication apparaît une seule fois au budget commun. Les six assises complémentaires nécessaires à l’objectif 24 abritées/12 assises peuvent être propres, prêtées ou louées après chiffrage. Option mobilier supplémentaire à 50 € TTC inactive ; tente plus grande sur devis inactive. Avant activation ajouter les coûts de fourniture/service, vérifier les stocks, le plan et les capacités. Aucun « second banc » ne vaut implicitement six places supplémentaires s’il fait partie du même ensemble.

### Mobilier Douglas

Simulation séparée `douglas-costing-v1`, débit illustratif existant, 12 ensembles table + deux bancs de six places :

- Volume acheté indicatif par ensemble avec 15 % pertes : **0,143135 m³**.
- Douglas **450 €/m³ TTC supposés**, pas un prix obtenu en scierie : bois **64,42 €/ensemble**.
- Quincaillerie 20 €/ensemble ; protection 40 €/5 L fournie par le porteur comme hypothèse de prix, rendement supposé 10 m²/L/couche, deux couches ; cinq bidons pour le lot, rouleau 15 €, autres consommables 60 €.
- Matières et consommables : **1 288,04 € à décaisser**, environ **107,34 €/ensemble**.
- Temps valorisé à 150 minutes × 15 €/h : coût complet indicatif **144,84 €/ensemble**. Ce temps du porteur est payé via son coût complet de rémunération dans le scénario, pas une seconde fois comme facture de fabrication.
- À 50 € TTC et 20 € de service par rotation : contribution simulée **20,65 €**, donc environ **huit rotations** pour couvrir le coût complet. À 30 € : **4,39 €**, environ **33 rotations**. Cela dépend notamment de la fiscalité et du véritable coût de manutention.

Prix scierie, sections, résistance, assemblages, protection adaptée à l’usage mobilier et séchage restent à valider. Le calcul n’est pas un plan de construction homologué. Le budget contient l’instantané initial des matières : après modification du plan de débit ou des prix, reporter le nouveau coût dans la ligne `furniture-manufacture` en citant la révision. Ne pas compter cet investissement une deuxième fois dans chaque stand.

### Épreuves

| Épreuve | Inventaire TTC provisionné |
|---|---:|
| Omelete Retro | 422,32 € |
| A Torre do Reboque | 340,00 € |
| O Grande Restaurante | 154,00 € |
| O Tabuleiro das Sementes | 61,92 € |
| Mestre das Mimosas | 207,00 € |
| O Pichecultor | 388,00 € |
| Total des nomenclatures | **1 573,24 €** |

Ces montants excluent les transports, essais et prestations techniques ventilés au budget commun. Les renvois `shared_cost_key=common-broadcast` ne sont pas des gratuités : la captation de 1 000 € figure une seule fois dans la ligne commune de même clé. Si une épreuve utilise cette mutualisation dans un autre scénario sans cette ligne active, le contrôle bloque la décision. Six passages couvrent les équipes, pas les essais ni l’accueil supplémentaire du grand public ; une enveloppe essais existe, à détailler après répétitions.

Les inventaires conservent les adaptations de sécurité existantes : accessoires factices/souples, dispositifs séparés, validation professionnelle requise. Les provisions de barrières, engins ou eau ne prouvent ni leur aptitude technique ni la capacité du terrain.

## Sources et statut des montants

Consultation le 30/09/2026. Seuls les faits explicitement relevés ci-dessous proviennent d’un tarif public ; les autres montants sont des hypothèses internes de scénario.

- [Filament 5, managing relationships](https://filamentphp.com/docs/5.x/resources/managing-relationships) : tables liées aux pages d’édition pour les relations HasMany ; adapté à des lignes nombreuses et détaillées.
- [Rentapa, mini-escavadoras](https://rentapa.pt/maquinas/mini-escavadoras) : 1,5 t à 77 € HT/jour, soit 94,71 € TTC, livraison à partir de 130 € HT par trajet. Deux jours et deux trajets sont notre hypothèse. Fournisseur de la région de Lisbonne : **pas un devis pour Belmonte**, ni preuve d’inclusion de l’assurance, du conducteur ou des accessoires. Demander un prestataire local.
- [Tendapro, article 4762](https://www.tendapro.pt/tenda-festa/4762.html) : **achat** d’un modèle PVC 3 × 6 m, 18 m², affiché 515 € TTC avec IVA 23 %. Ce n’est **pas un prix de location** et il n’alimente pas la provision location 120 €. Les indications commerciales de places utilisent un autre mobilier ; aucune capacité avec nos tables n’en découle. Charges vent/neige non testées dans cette référence : repère de catalogue seulement, modèle/implantation/ancrage adaptés à faire étudier.
- [TOI TOI Portugal](https://www.toitoi.pt/) : catalogue et prestations de sanitaires, dont modèles accessibles. Aucun tarif public confirmé ; les provisions 150/250 € et transport n’en sont pas des citations.

États enregistrés : **hypothèse**, **référence publique**, **devis reçu**, **prix/périmètre validés**. Une référence publique n’est pas automatiquement un devis livré à la quinta. Valider exige montant, IVA, source et date passée. Modifier le prix, la quantité ou la source d’une ligne validée impose de la revalider. L’état « saisie manuelle existante » assure seulement la compatibilité avec les anciennes lignes et ne peut être réappliqué à une estimation pour contourner la validation.

Les prix validés ne remplacent pas les accords, factures ou encaissements : il faut encore renseigner engagements, paiements puis rapprochement manuel. Les anciens contrôles juridiques, d’inventaire et de lancement restent en vigueur. Un coût TTC connu avec IVA inconnue demeure intégralement dans la prévision et la trésorerie, mais laisse le dossier incomplet. Une recette avec IVA inconnue n’est pas assimilée à un chiffre d’affaires net.

## Ce qu’il faut résoudre pour consolider

1. Devis abris, scierie et transports ; identifier les apports réels des exposants, juntas et partenaires. L’abri fourni gratuitement et un investissement mobilier payé personnellement n’ont pas la même incidence de trésorerie.
2. Devis assurances, secours, engins, captation, sanitaires, électricité et validations du site. Remplacer les enveloppes, pas les déclarer obtenues.
3. Accord brasseur écrit : prix par fût, livraison, reprise des fûts non ouverts, dépôt/caution, tireuse/froid/mobilier et contreparties. Le stock acheté reste financé tant qu’une reprise n’est pas acquise.
4. Avec le chef : recette unique au chou, rendements, production, coûts et intervention. Avec les personnes au service : heures, statut et coût complet. Aucune famille présumée gratuite.
5. Comptable : IVA par flux, récupération éventuelle, coût complet de rémunération et charges ; exposition réelle aux remboursements. Fixer ensuite tarifs, apports nécessaires et format soutenable. Les seuls 500 € par stand ne prouvent pas le break-even de cette simulation.
