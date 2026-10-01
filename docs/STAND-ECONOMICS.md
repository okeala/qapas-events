# Le stand : unité de coûts et de recettes

Règle du 1er octobre 2026. Un stand regroupe son installation, son exploitation, ses coûts et ses recettes. L’intervenant qui supporte le coût ou conserve la recette reste identifié. Le stand et son dossier de construction sont une seule unité.

## Dans Filament

**Préparer → Stands et emplacements → Fiche et besoins** : synthèse du scénario de lancement, onglets **Coûts QAPAS**, **Recettes QAPAS** et **Budget des participants · hors QAPAS**. Chaque onglet filtre un seul scénario ; la synthèse annonce explicitement son scénario. Les inconnus restent à compléter. Les sous-totaux connus sont distincts d’un solde complet et ne constituent pas une preuve de rentabilité globale.

La nouvelle table conserve des prévisions TTC par titulaire (équipe, exposant, junta, sponsor ou autre). Par exemple, l’achat de fromage et sa vente appartiennent à l’exposant. Ils ne rejoignent jamais le calcul du break-even, le plan financier ou la trésorerie QAPAS. Les budgets ne sont pas additionnés entre intervenants.

Si le participant paie QAPAS pour une prestation, sa dépense peut être reliée à la recette QAPAS existante du même stand et scénario. La liaison ne crée aucune autre ligne ni aucun paiement. Les apports en nature restent documentés dans les dossiers existants ; leur valorisation n’est pas une recette monétaire. Les cautions, affectations et fonds de tiers du budget QAPAS restent séparés des recettes disponibles.

## Nomenclature de travail du stand monopente

Dans **Concevoir → Construction des stands**, le détail modifiable reprend la proposition du porteur. Il sert à comparer une référence neuve à un approvisionnement réel (casse, don, prêt, neuf). Il ne modifie pas la configuration réellement réceptionnée ni son inventaire. Le changement physique de toiture exige de reprendre ce dossier technique. Le précédent calcul de toit à deux pans ne valide pas cette variante monopente.

| Tubes proposés | Quantité | Longueur unitaire | Longueur utile |
|---|---:|---:|---:|
| Cadre au sol | 4 | 2,40 m | 9,60 m |
| Montants arrière | 2 | 2,40 m | 4,80 m |
| Montants avant | 2 | 2,10 m | 4,20 m |
| Traverses hautes | 2 | 2,40 m | 4,80 m |
| Pentes latérales | 2 | 2,42 m | 4,84 m |
| Pannes | 3 | 2,40 m | 7,20 m |
| Renforts latéraux | 2 | 2,40 m | 4,80 m |
| **Total** | **17** | | **40,24 m** |

| Groupe | Référence fournie par le porteur, base IVA inconnue |
|---|---:|
| Tubes : 40,24 m × 5 € | 201,20 € |
| 4 socles + 4 angles + 4 tés : 12 × 8 € | 96,00 € |
| 4 tés ouvrants + 4 articulés + 6 fixations : 14 × 8,50 € | 119,00 € |
| 12 colliers Oméga × 1,80 € | 21,60 € |
| Lot de 12 cavaliers/rondelles et 24 boulons/vis avec écrous | 15,00 € |
| **Sous-total de référence** | **452,80 €** |

Ce sous-total corrige les 35 m et 426,60 € initiaux. Il n’est ni un devis, ni un montant HT/TTC confirmé, ni le coût complet du stand. Les prix à la casse restent inconnus. Les longueurs sont utiles, sans chutes, traits de coupe ni correction pour la géométrie des raccords.

Six postes supplémentaires restent ouverts : bac acier/finitions, ancrages/lestage/contreventement, bardage et ligatures, chutes/découpe/préparation, transport propre au stand, montage/démontage/contrôle. Ne pas répéter les transports et prestations déjà chiffrés en commun ; quantité zéro explicite si le poste est couvert ailleurs, avec référence dans les notes.

La différence de hauteur 0,30 m sur 2,40 m décrit une monopente théorique de 12,5 % ; la longueur géométrique est d’environ 2,419 m, avant raccords et débords. Une tôle de 2,40 m ne couvre donc pas cette pente telle que décrite. Profil de tôle, sens de pose, recouvrement, débords, pente minimale et fixations restent à préciser avec le fournisseur.

**Compatibilité** : le [catalogue fabricant Kee Safety](https://www.keesafety.com/media/yzrnpaas/safety_components_catalogue_v10_0623_s2.pdf), p. 4–5 imprimées, consulté le 1er octobre 2026, indique notamment 26,9 et 33,7 mm de diamètre extérieur ; il ne permet pas de présumer un raccord adapté à 30 mm. Relever diamètre extérieur et épaisseur réels ; demander les références compatibles. Vérifier également la fermeture du cadre de base et sa liaison aux quatre montants : quatre angles ne démontrent pas à eux seuls tous les assemblages. Les socles ne constituent pas l’ancrage complet, et les traverses horizontales ne prouvent pas le contreventement. Aucune résistance, entraxe admissible ni tenue au vent n’est déduite du seul diamètre.

## Reprise du chiffrage

L’action **Reporter le chiffrage au budget** exige toutes les quantités et tous les prix prévisionnels, une base TTC et l’IVA renseignée. En présence de traitements IVA différents, ventiler les postes directement plutôt que transférer un lot unique. Les références de prix neuf ne sont jamais prises comme coûts réels ou financements acquis.

Pour un stand construit/fourni par QAPAS, l’action remplace la prévision sur son poste de construction déjà lié, sans seconde ligne. Prix confirmé, engagement ou règlement empêchent cette reprise. Pour un stand construit par l’équipe, elle alimente un seul poste du budget de l’équipe dans le scénario choisi. Aucune recette QAPAS n’est générée. Le détail est une décomposition de ce poste agrégé, pas un deuxième jeu de dépenses à additionner.

Installation : `php artisan migrate` puis `php artisan db:seed`. Initialisation idempotente des seuls détails encore absents ; aucune remise à zéro, aucun prix récupéré inventé, aucun envoi fournisseur ni achat automatique. Les formulaires et actions sont réservés à l’administration active.
