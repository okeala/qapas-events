# Forquilha de Ouro et welcome pack — 30 septembre 2026

## Prix et trophée

500 € sont intégralement destinés au bénéficiaire de la freguesia gagnante pour son événement communautaire de Noël. Conception, fourniture, peinture, transport et plaque du trophée se chiffrent séparément. Une ligne existante `community-christmas-prize` est réutilisée ; aucun second coût de 500 € ni double réserve dans le calcul du break-even.

Quatre dossiers de parrainage indépendants : le prix monétaire, la conception, la fourniture/fabrication, la peinture/finition. Une entreprise peut en cumuler plusieurs, mais un seul accord actif par rôle. Aucun quatrième rang secondaire n’est ajouté. Pistes : Crédito Agrícola ou entreprise agricole pour le prix, artiste local pour la maquette, artisan/quincaillerie pour la fourche et son socle, peintre/carrossier pour le doré. Ce sont des pistes, pas des partenaires acquis.

Dans **Mobiliser → Forquilha de Ouro**, relier le parrain et le coût existant, rapprocher la recette puis utiliser **Réserver les 500 €** avec preuve d’affectation. Le calcul réserve l’IVA : si un contrat est réellement soumis à 23 %, 500 € TTC ne procurent que 406,50 € hors IVA ; 615 € TTC procurent 500 €. Le taux réel doit être qualifié, jamais présumé par le seeder. La réservation est une affectation documentée contrôlée par l’organisateur, pas une transaction bancaire automatique ni un compte séquestre.

Une simple promesse ne débloque pas les participations payantes. Toute modification du sponsor, du prix, du paiement ou du rapprochement invalide la preuve précédente. Le jalon de financement et son communiqué sont revérifiés. La réserve ne constitue pas un bénéfice libre : les 500 € restent intégralement dans les coûts du scénario.

Pour chaque contributeur au trophée : identité, accord, livrable, délai, ligne de coût et réception conforme documentée. Coût nul uniquement avec apport attesté et chiffrage validé. La production doit être réceptionnée avant exploitation. La remise conjointe exige trophée réceptionné, versement réel des 500 € rapproché le même jour et procès-verbal ; règlement des résultats, bénéficiaire et garde du trophée restent ceux de la convention.

## Welcome pack

Dans **Chiffrer → Welcome pack · effectifs et tailles**, hypothèse initiale :

| Bénéficiaires distincts | Personnes |
|---|---:|
| Joueurs : 6 équipes × 14 personnes | 84 |
| Organisation QAPAS | 6 |
| Personnes de service | 18 |
| Total | 108 |

Ce n’est pas une obligation de 14 personnes par équipe. Six rôles sont indispensables, certains cumuls ou prêts sont possibles ; le relevé individuel remplace l’hypothèse après élection. Un t-shirt par personne pour les deux jours. `108 + ceil(108 × 20 %) = 130`. Pour deux t-shirts par personne, `216 + ceil(216 × 20 %) = 260`.

| Taille indicative | Besoin | Réserve | Lot |
|---|---:|---:|---:|
| XS | 4 | 1 | 5 |
| S | 14 | 3 | 17 |
| M | 32 | 6 | 38 |
| L | 34 | 7 | 41 |
| XL | 18 | 4 | 22 |
| XXL | 6 | 1 | 7 |
| Total | 108 | 22 | 130 |

Réserve globale arrondie une fois, puis distribuée par la méthode des plus forts restes. Pas d’arrondi par taille ajoutant des pièces en excès. XS à 4XL disponibles dans le relevé ; modèle, coupe, tailles inhabituelles et disponibilité fournisseur à confirmer. Les tailles ne sont pas déduites de l’âge ou du sexe.

Importer les joueurs élus et confirmés, puis compléter QAPAS et le service. Une référence stable par personne. Les joueurs partageant une identité de candidature sont regroupés. Réutiliser la fiche importée lorsqu’une personne assure aussi le service. Les références différentes correspondant à une même personne doivent être rapprochées lors du contrôle du registre. Aucun relevé nominatif n’est public ni fourni au sponsor. Une modification du relevé ou du modèle annule sa validation. L’import ajoute les nouveaux élus sans supprimer silencieusement une personne ayant déjà reçu un rôle de service ; contrôler les anciens joueurs en cas de changement d’élection.

La quantité du poste budgétaire est mise à jour par une action explicite ; aucun changement automatique d’une commande engagée. Toute modification de quantité exige une nouvelle validation du devis. Les hypothèses de tailles ne permettent pas de valider une commande nominative. Les vues et actions sont réservées aux administrateurs actifs.

Budget d’attente clairement marqué **hypothèse non devisée** : `130 × 10 € TTC + 50 € préparation/BAT + 25 € livraison = 1 375 €`. Ce n’est ni un devis fournisseur ni le prix de vente du sponsoring. L’IVA et les tarifs restent non validés. Le financement nécessaire est le coût livré, avec IVA de sortie réservée si le sponsor paie QAPAS. Les prestations publicitaires supplémentaires sont chiffrées dans le contrat. Un fournisseur sponsor peut fournir directement le lot ; seuls les coûts effectivement évités sont réduits avec justificatif, sans recette fictive. Les 20 % concernent la quantité, pas une seconde majoration financière.

Référence consultée le 30/09/2026 : [Webnial](https://webnial.pt/grafica/t-shirts/), 3,90 € HT par pièce à 100 unités pour une petite impression devant, avec livraison économique Portugal continental. Ce tarif n’est pas celui de notre lot 130 pièces devant/dos et tailles étendues : demander un devis, sans extrapolation. Source de comparaison conservée dans le poste et consultation FR/PT prête ; rien n’a été envoyé.

Modèle proposé : même couleur et même visuel pour tout le lot, logo des Jeux et du sponsor après BAT. Identifier les fonctions par badge distinct afin que la réserve reste interchangeable. T-shirt souvenir, badge et fiche pratique constituent le pack simple envisagé ; badges et fiches relèvent des impressions générales existantes à chiffrer, pas d’un cadeau supplémentaire garanti au billet. En décembre, le t-shirt ne remplace pas les vêtements chauds ou les équipements de travail.

## Déploiement

`php artisan migrate --force` puis `php artisan db:seed --force`. Le seeder versionné conserve les contrats et chiffrages existants. Les nouvelles offres restent en brouillon avec prévisualisation locale administrateur ; aucun paiement, publication ni email automatique n’est activé. Les pages FR/PT annoncent séparément la recherche de partenaires et les 500 € effectivement sécurisés.
