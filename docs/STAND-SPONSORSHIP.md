# Financement des stands et catalogue de parrainage

Règle active au 1er octobre 2026. Elle remplace le prix automatique présenté par équipe et le parrain local nécessairement exclusif. Les accords, prix validés, encaissements et offres déjà publiées sont conservés.

## Ce que l’on finance

La **cabane** est une construction. Le **stand** est une unité d’accueil et de participation : emplacement, cabane, exposition, animation, services propres et contribution à l’organisation. La récupération réduit certains achats ; elle ne supprime ni les frais de montage, ni les branchements, ni les coûts mutualisés.

| Source | Destination et règle de comptage |
|---|---|
| Parrainage local | Le sponsor règle QAPAS pour les prestations convenues. Une enveloppe par stand de freguesia, même si plusieurs équipes s’y rattachent. Les cinq parts se répartissent cette enveloppe, elles ne la multiplient pas. |
| Junta | Aide financière, achat direct, prêt ou logistique documentés. Aucun soutien présumé. Une somme affectée ne devient pas de la marge libre. |
| Points-relais | Leur contribution convenue est distincte de leur commission de distribution. S’ils achètent une visibilité de parrain, leurs parts occupent le même contingent de cinq. |
| Tickets-avantages | Seules les affectations stand/défi prévues par l’offre y contribuent. La part de la junta est distincte. Le paiement du billet ne devient jamais intégralement de la recette disponible : commissions, parking et contreparties boissons subsistent. |
| Habitants et partenaires en nature | Matériaux, travail, transport, prêt : quantités, fournisseur et périmètre documentés. Ils réduisent une dépense réelle, sans créer un encaissement fictif. |
| Sponsors de l’événement et QAPAS | Financement de l’organisation et des moyens mutualisés selon le budget. Ne pas réaffecter au stand une somme déjà réservée au prix, à une épreuve ou à une prestation. |
| Ventes de spécialités | Recettes du vendeur. Aucun transfert automatique vers QAPAS, ni préfinancement garanti. |

L’indépendant règle sa formule et ses options. Sa contribution aux frais communs correspond à sa recette nette pour QAPAS, diminuée des coûts directement occasionnés par sa prestation. La part de sponsoring d’une freguesia ne comprend pas un stand d’exposition personnel pour son parrain.

## Offre en cinq parts

Le prix commercial total `P` est choisi, justifié et enregistré en centimes, divisible par cinq. L’hypothèse de départ est **500 € TTC**, donc **100 € TTC par part de 20 %**. Elle doit être testée et chiffrée ; elle ne démontre pas la rentabilité.

| Formule | Prix | Surface dans la zone des parrains locaux |
|---|---|---|
| Partagée, 1 à 4 parts | `P × parts / 5` | 20 %, 40 %, 60 % ou 80 % |
| Exclusive, 5 parts | `P` | 100 % |

La proportion porte sur la **surface réservée** à chaque sponsor, pas sur la hauteur du logo. Dimensions, supports, durée et BAT restent à convenir. L’exclusivité locale ne supprime pas les identifications de QAPAS, de la freguesia, des relais, ni les sponsors de l’événement ou des épreuves ; elle ne donne aucun droit sur les votes.

Les accords `agreed` et `active` consomment les cinq parts au maximum, sous verrou transactionnel de l’édition. Une prospection ou une demande web ne réserve rien. Un ancien `main_slot` vaut cinq parts. Le prix de référence est figé dans chaque nouvel accord ; une modification du tarif courant ne réécrit pas l’accord. Les engagements financiers et paiements restent à rapprocher des lignes budgétaires existantes, sans duplication automatique de recettes.

## Les sponsors structurels ont leur propre stand

Le principal et les trois secondaires, dont le partenaire gobelets, disposent chacun d’un stand distinct **inclus dans leur partenariat**. Le scénario de référence comprend donc **6 stands de freguesia + 6 indépendants + 4 sponsors = 16 emplacements physiques**. Les quatre espaces sponsors ne réduisent pas le contingent indépendant.

Chaque dossier de partenariat est lié à son stand ; un stand ne peut appartenir à deux dossiers actifs, ni à une autre édition. Avec cette règle, l’accord structurel exige le lien vers son stand. Sa fiche fournit quartel, numéro, implantation et besoins lorsqu’ils sont renseignés ; aucun emplacement précis n’est inventé.

La fabrication de la cabane constitue un poste propre. Un second poste couvre uniquement les services du sponsor à deviser (accueil, mobilier convenu, branchements, consommations), sans recopier fabrication et logistique commune. La recette de sponsoring existante finance la formule : **aucune seconde recette de location** du même stand. Les coûts inconnus restent inconnus et bloquent la déclaration de préparation financière.

Les sponsors d’épreuve, de t-shirts, du trophée ou des plaques ne reçoivent pas automatiquement un espace personnel. Une prestation supplémentaire doit être convenue et budgétée.

## Prix commercial et besoin de couverture

Le calcul antérieur reportait le solde budgétaire sur six parrains puis utilisait ce résultat comme tarif. Désormais le plan sépare :

- le prix commercial choisi pour le stand de freguesia ;
- le besoin théorique par freguesia pour couvrir le scénario, avec ou sans l’objectif QAPAS ;
- les recettes prévues au prix choisi et le financement restant.

Le tarif de 500 € ne force aucun résultat au vert. Les quatre nouveaux stands ajoutent des coûts restant à chiffrer. Charges du porteur, dépenses personnelles à rembourser, réserves et objectif QAPAS restent dans le calcul. Les apports en nature, promesses et recettes spéculatives ne sont pas transformés en argent encaissé.

## Navigation et administration

`/events/os-jogos-do-agricultor/parrainer` propose deux entrées : freguesia/équipes ou événement/équipements. Le formulaire enregistre une demande et son prix indicatif recalculé côté serveur ; aucun paiement ou réservation. Validation de l’information de confidentialité requise pour la collecte. Les détails privés des prospects et accords restent privés.

Prévisualisation locale : `/workspace/preview/os-jogos-do-agricultor/parrainer`, avec administrateur actif. Publication par le réglage **Catalogue des parrainages**, puis visibilité individuelle des stands et dossiers. Les épreuves encore secrètes restent absentes du catalogue public. Aucun sponsor ni épreuve n’est rendu public par le seeder.

Dans Filament :

- **Stands** : type sponsor, prix total du parrainage local, provenance, fiche et besoins ;
- **Parrains et relais locaux** : parts, exclusivité, état et preuve ;
- **Sponsoring** : stand structurel lié, prix indicatif et description publique FR/PT ;
- **Demandes** : choix du prospect et photographie de la proposition ;
- **Plan commercial / scénario** : prix choisi, besoin calculé, postes de coûts et quatre stands supplémentaires.

Mise à jour : migrations puis seeder normal, sans `migrate:fresh`. Le seeder est idempotent et conserve les données modifiées. Il ajuste uniquement l’ancienne proposition calculée non engagée, ajoute les quatre emplacements et leurs coûts non chiffrés. Les scénarios historiques restent inchangés.
