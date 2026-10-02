# Piloter une entreprise événementielle avec peu de trésorerie

Proposition de conception du 2 octobre 2026 pour QAPAS Events. La demande est celle d’un commercial et dirigeant qui assure seul le développement, délègue souvent la réalisation et finance d’abord par les engagements et préventes. Aucun crédit bancaire ni investissement initial n’est supposé disponible. Les clients sont des partenaires dont les fonds et les droits doivent rester traçables.

**Statut : conception des prochains lots, pas fonctionnalités déjà livrées.** L’application possède des budgets, rapprochements manuels et précommandes conditionnelles. Elle ne possède pas encore le moteur chronologique de disponibilité, les conventions fournisseurs conditionnelles et les garanties par jalon décrits ici. Le seeding explicite des utilisateurs et de l’accès Filament appartient à la livraison actuelle. Aucune commande, aucun prix et aucune clause acquis ne sont modifiés par ce document.

## 1. La règle qui change la décision

Une recette rentable sur papier peut être indisponible au moment de payer. Un acompte en banque peut rester intégralement dû au client si l’événement s’annule. Une promesse de sponsor peut être signée mais ne pas arriver à temps. Il faut donc répondre séparément à trois questions : le projet rémunère-t-il tout le travail ; le compte peut-il payer à chaque échéance ; chaque sortie prévue reste-t-elle couverte si le projet s’arrête ?

Exemple pédagogique sans hypothèse fiscale : 1 000 € reçus et 1 000 € encore remboursables ne laissent aucun euro disponible pour une dépense irrécupérable. Un fournisseur réclamant 200 € définitivement perdus crée un besoin de couverture supplémentaire de 200 €. Si le dirigeant n’a pas cet argent, le projet doit changer : option fournisseur sans frais, contrat moins risqué, prestation réellement livrée et acceptée ouvrant une libération licite de fonds, ou réduction du format. L’application ne peut pas transformer une obligation de remboursement en argent libre.

La certitude opérationnelle est une preuve valable pour un périmètre et jusqu’à une date. Le logiciel doit dire « non prouvé », « expiré » ou « couvert jusqu’à cette échéance », au lieu de fabriquer une assurance absolue. Un bon score commercial ne remplace aucune condition critique.

## 2. Vendre une séquence qui protège chacun

| Étape | Ce que le partenaire accepte | Ce que QAPAS peut engager |
|---|---|---|
| Qualification | Besoin, interlocuteur habilité, capacité de décision et budget à vérifier | Temps de prospection plafonné, aucun achat anticipé |
| Proposition conditionnelle | Périmètre, prix, format minimum, seuil et échéance communs, sortie prévue | Devis et options fournisseurs documentés ; aucune dépense irréversible sans couverture |
| Décision de lancement | Conditions critiques satisfaites, contrat figé, fonds réellement disponibles | Engagements financés avec dates de paiement compatibles |
| Jalons de réalisation | Livrable identifiable, prix propre, critères d’acceptation et sort des fonds explicites | Dépenses autorisées dans la limite des fonds libérables et des réserves restantes |
| Extension du format | Besoin supplémentaire, financement additionnel et nouvelle preuve de faisabilité | Coûts supplémentaires financés sans fragiliser le format déjà confirmé |
| Clôture | Livraison, réserves, avoirs, remboursements et résultat expliqués | Libération finale après rapprochement des obligations |

La fenêtre maximale de 21 jours de la V2 reste une règle de décision commerciale commune, pas une garantie technique de conservation d’une empreinte carte pendant 21 jours. Sa durée n’est pas prolongée implicitement lorsqu’un nouveau client arrive.

Un jalon ne doit pas être un intitulé ajouté pour rendre un acompte non remboursable. Une étude vendue séparément, une conception acceptée ou une production livrée doivent avoir une valeur réelle, un périmètre et un accord adaptés au cas. Les règles applicables au remboursement, aux consommateurs et à la fiscalité restent à qualifier avant activation. Le travail livré peut conserver une valeur pour le partenaire même si l’événement n’a pas lieu ; ce bénéfice doit être prévu dès l’offre.

## 3. Négocier les deux côtés du risque

À chaque obligation envers le client doit correspondre un engagement fournisseur compatible. Un devis ne réserve pas une équipe ; une équipe annoncée ne constitue pas une disponibilité confirmée.

Le dossier fournisseur doit porter le prix TTC, les frais de transport et de mise en place, le coût d’annulation à chaque date, la part récupérable, les délais réels de remboursement, la capacité réservée, les conditions de changement, la date d’expiration de l’option et la preuve signée. Pour un poste critique : personne joignable, solution de remplacement chiffrée et délai de mobilisation. Un accord oral reste une hypothèse visible.

Le système doit confronter les dates : une option gratuite qui expire avant la décision commerciale ne sécurise pas le lancement. Un remboursement fournisseur attendu dans trois semaines ne permet pas un remboursement client promis demain. Une location ne supprime pas les dépôts, transports ou pénalités. Un apport gratuit reste conditionnel tant que disponibilité, qualité et responsabilité ne sont pas documentées.

Proposer d’abord location, mutualisation et sous-traitance sur commandes confirmées. Aucun stock spéculatif n’est nécessaire pour faire un pitch. Les prototypes indispensables ont leur propre budget et une limite de perte explicitement approuvée. Une rémunération différée du dirigeant et son matériel personnel ne sont pas des ressources gratuites.

## 4. Le registre de cash à construire en premier

Chaque flux doit conserver son édition, sa contrepartie, sa pièce d’origine, sa nature et son montant en centimes : fonds propres disponibles, paiement client, dépôt remboursable, montant affecté à un tiers, charge, taxe, remboursement ou transfert. Une promesse, une autorisation carte et un encaissement bancaire rapproché sont des états différents.

Pour chaque entrée et sortie : échéance contractuelle, date estimée, date constatée, preuve de rapprochement, frais connus ou inconnus, restriction et règle de libération. Un versement attendu du prestataire n’est pas encore le solde bancaire. Les flux d’une ancienne édition ou d’un autre registre ne financent pas une nouvelle promesse sans affectation explicite et admissible.

Le cockpit doit montrer :

- le solde constaté et sa dernière date de rapprochement ;
- les fonds protégés pour clients et tiers, ainsi que les réserves fiscales ;
- les paiements obligatoires par date et les coûts restant nécessaires à la livraison ;
- la trésorerie minimale dans le calendrier, avec la première date de rupture ;
- la dépense supplémentaire autorisable aujourd’hui, avec sa justification ;
- l’exposition en cas d’arrêt et les montants restant couverts pour chaque partenaire.

Ne pas présenter une formule statique comme une garantie. Une dépense proposée est acceptée seulement si, après insertion de ses effets dans l’échéancier, le projet reste finançable et les sorties testées restent couvertes. Chaque réserve n’est affectée qu’une fois : un paiement à un fournisseur n’est pas aussi une dépense future ; un remboursement client n’est pas ajouté à une seconde réserve couvrant déjà le même droit. Le registre conserve les liens qui permettent de démontrer cette absence de double compte.

Dans le scénario prudent, une entrée future non garantie ne finance aucune échéance ferme. Une somme récupérable chez un fournisseur n’entre dans la couverture disponible à une date que si son droit, son montant et son délai sont documentés. Frais de paiement, commissions non récupérées, taxes, pénalités et avances personnelles restent visibles après annulation.

## 5. Tester les sorties avant d’autoriser une dépense

| Situation testée | Question bloquante |
|---|---|
| Seuil commercial non atteint | Peut-on restituer les sommes protégées à la date promise sans nouvel encaissement ? |
| Fournisseur critique défaillant | Le remplacement est-il disponible, assez rapide et financé, ou l’arrêt reste-t-il couvert ? |
| Annulation liée au site ou à la météo | Quelles sommes restent dues, quels frais sont perdus et quelle couverture vérifiée reste mobilisable ? |
| Versement client ou prestataire retardé | Peut-on respecter les échéances sans compter deux fois la même entrée ? |
| Remboursements ou litiges simultanés | Le cash couvre-t-il les droits, frais conservés et commissions non récupérées ? |
| Dirigeant indisponible | Qui possède le dossier, les accès délégués et la capacité d’arrêter ou de livrer ? |

Les situations corrélées doivent aussi être testées : météo défavorable, baisse des ventes et remboursements peuvent arriver ensemble. Une assurance n’est retenue comme couverture qu’avec périmètre, exclusions, franchise et délai documentés ; une indemnité incertaine n’est pas du cash immédiat.

Le plafond de risque du dirigeant est un montant explicite, associé à des fonds disponibles. Avec un plafond de zéro et aucune réserve propre, toute perte possible non couverte bloque la dépense. L’application propose alors la prochaine action permettant de la débloquer : renégocier une option, obtenir une preuve, réduire le format ou renoncer avant d’aggraver la perte. Elle n’efface pas les inconnues pour atteindre l’objectif de marge.

## 6. Rendre le tandem visible au partenaire

Le partenaire doit retrouver dans son dossier la version acceptée de l’offre, ce qui lui sera livré, les conditions de lancement, la prochaine décision, les sommes encore protégées, celles libérées sur une base documentée et la procédure de sortie. Les obligations du partenaire ont aussi un responsable et une date : validation d’un visuel, apport matériel, autorisation du site, paiement, présence d’équipe.

La garantie est précise : objet, montant, conditions, responsable, preuve, délai et limites. « Votre acompte est remboursable si le seuil n’est pas atteint à telle date » est vérifiable ; « vous ne perdez jamais rien » ne décrit pas les pertes de temps, frais ou situations exclues. Une proposition de report appelle un accord nouveau ; l’absence de réponse ne transfère pas les fonds vers l’année suivante.

Les engagements sont versionnés et les décisions conservent auteur, date et pièces. Une modification de prix, date, périmètre ou garantie rend la proposition à accepter à nouveau et ne réécrit pas les anciennes commandes. Les pièces privées sont accessibles uniquement aux personnes habilitées ; partager un avancement ne partage pas les contacts des autres partenaires.

## 7. Un cockpit utile au dirigeant seul

La première page doit répondre à « quelle action réduit le plus l’incertitude aujourd’hui ? ». Une file limitée d’actions montre le blocage, la date limite, le responsable et la preuve attendue. Exemple : obtenir l’option du loueur avant demain, plutôt que relancer dix prospects alors que la capacité expirera avant leur décision.

Le pipeline commercial distingue contact qualifié, proposition remise, accord conditionnel signé, financement rapproché et prestation livrée. Un montant probable reste une prévision ; il ne change pas la capacité de dépenser. La valeur commerciale s’apprécie aussi par les heures de vente, le coût de préparation, le délai d’encaissement et le risque supporté. Le temps du dirigeant inclut les pitches perdus et entre dans le coût complet, sans double compte avec sa rémunération.

Des offres modulaires réutilisables rendent les pitches plus rapides : format minimum, options financées séparément, prestations sponsors livrables et conditions de lancement compréhensibles. Une remise fondateur possède un coût maximal, un contingent et une échéance ; elle ne promet pas un retour commercial ou une audience non prouvés. Aucun email, contrat signé ou paiement ne part automatiquement à partir d’un score.

## 8. Ordre de développement et recette

| Priorité | Lot | Test décisif |
|---|---|---|
| 1 | Échéancier de cash, restrictions et scénarios de sortie | Un événement rentable dont le cash devient négatif avant livraison est bloqué ; 1 000 € entièrement remboursables n’autorisent pas 200 € irrécupérables sans autre couverture |
| 2 | Options et engagements fournisseurs reliés aux offres clients | Une option expirant avant la décision, un délai de restitution incompatible ou un poste critique sans remplacement bloque l’autorisation concernée |
| 3 | Jalons, acceptations et garanties versionnées | Aucune libération sur simple intitulé ; preuve d’acceptation et droits applicables requis ; une modification ne change pas les commandes acquises |
| 4 | Actions du dirigeant et dossier partenaire | Chaque blocage désigne une prochaine action ; chacun ne voit que ses pièces ; aucun prospect n’obtient de permission admin |
| 5 | Connexions bancaires et prestataires V2 | Webhooks dédupliqués, remboursements suivis jusqu’à confirmation, retards et frais pris en compte ; aucune recette fabriquée par un callback tardif |

Les premiers lots peuvent fonctionner avec rapprochement humain documenté. L’automatisation du paiement vient après les règles de décision. L’application ne prétend pas créer un séquestre : isoler un montant dans une base de données ne protège pas juridiquement ni techniquement un compte bancaire. Le choix éventuel d’un prestataire et d’un dispositif de garde des fonds exige son propre dossier.

Le contrôle de lancement doit cumuler les preuves essentielles : contrats et signatures admissibles, site et dates, capacité, sécurité, prestataires critiques, coûts complets, fiscalité qualifiée, cash aux échéances, réserves et plafonds de risque. Les critères sont recalculés lorsque l’une de ces preuves change ou expire. Les ressources mutualisées et les flux des registres historiques sont reliés et rapprochés, jamais dupliqués.

## 9. Sources techniques vérifiées

Consultation le 2 octobre 2026. Les règles de gestion ci-dessus constituent une proposition QAPAS ; les documents suivants précisent uniquement des contraintes de prestataire.

- [Stripe : autoriser puis capturer](https://docs.stripe.com/payments/place-a-hold-on-a-payment-method) : l’autorisation expire ; la date `capture_before` doit être vérifiée. Ce mécanisme ne justifie pas une promesse générale de blocage de 21 jours.
- [Stripe : autorisations étendues](https://docs.stripe.com/payments/extended-authorization) : possibilités spécifiques à vérifier pour l’intégration réelle, sans les présumer actives.
- [Stripe : remboursements](https://docs.stripe.com/refunds) : la disponibilité du solde conditionne leur traitement ; remboursement demandé et remboursement arrivé ne sont pas le même état.

Les références juridiques et fiscales du dossier [LEGAL](LEGAL.md) restent à appliquer aux clauses réelles B2B/B2C. Aucun taux, droit de conserver un acompte ou certificat de conformité n’est déduit automatiquement de cette conception.
