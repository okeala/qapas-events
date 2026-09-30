# Da mimosa à cabana · règle active du 30 septembre 2026

## Concept et pièce maîtresse

Le **raccord d’échafaudage est la pièce maîtresse**, autour de laquelle s’assemblent des tubes récupérés auprès de démolisseurs. Son fournisseur est une piste pour le **sponsor principal existant**, avec la formule « Celui qui nous relie et fait tenir les Jeux ». Les fournisseurs de tubes constituent une contribution distincte. Ni accord, ni don, ni prix, ni capacité portante ne sont présumés.

- Une **largeur et une hauteur imposées de 2,40 m**, et une **longueur libre à partir de 2,40 m**, sans obligation de multiples de 2,40 m. Le dossier conserve une cabane par stand, quelle que soit sa longueur. Les équipes d’une freguesia construisent et partagent leur stand ; QAPAS fabrique les cabanes qu’elle loue aux indépendants, au fur et à mesure que les emplacements se concrétisent. Chaque ajout suppose son coût de fabrication et son financement ; le budget de six unités est un scénario, pas un ordre de fabriquer six cabanes immédiatement.
- Ossature en tubes métalliques récupérés, raccords d’échafaudage compatibles. Le prototype détermine références, géométrie, quantités, ancrages et contrôles ; ce document n’est pas un plan de structure.
- Support de toiture et bardages en plessis de mimosa et/ou cannes identifiées, **diamètre maximal 6 cm pour les deux matériaux**, ligatures en **sisal ou fibre naturelle adaptée**. Cannes perforées et compositions artistiques possibles. Décoration en récupération ; pas d’amiante, plastique ou bois neuf. Raccords, visserie et ligatures peuvent être achetés.
- Surcouverture optionnelle en **paille propre récupérée**, distincte du support en plessis : piste à deux pentes, couches chevauchantes et faîtage soigné, largeur et hauteur totale toujours de 2,40 m. Les équipes personnalisent leur silhouette et leurs décors. Valider pluie, stabilité et comportement au feu sur le prototype ; aucune recette de toiture certifiée par cette idée.
- La surface du format de base de 5,76 m² **ne remplace pas** l’espace convivial prévu pour 24 personnes abritées et 12 assises. Le plessis n’est pas présumé étanche. Résoudre l’usage sous pluie et la stabilité au prototype ; ne pas publier une promesse d’abri non démontrée.
- Aucun objectif d’économie de mimosa ; utiliser la quantité nécessaire à la construction et au décor. Aucun quota de mimosas/cannes par cabane, dans les zones de prélèvement désignées. L’absence de quota ne remplace pas l’identification des espèces, le suivi des quantités et l’organisation du chantier.
- Prix gratuit du public prévu pour la cabane d’équipe la plus spectaculaire, distinct de la Forquilha de Ouro. Les cabanes de location QAPAS ne concurrencent pas celles des équipes. Bulletin, période, contrôle, égalités et dépouillement à préparer avant ouverture. **Pas de vote électronique livré.** Ce défi collectif n’ajoute pas une septième épreuve officielle chronométrée au programme.

## Atelier et publication

**Concevoir → Cabanes · équipes et locations** : dossier unique par stand, inventaire par partie (dont raccords et ligatures), provenance, quantités/unités et diamètre maximal déclaré du lot végétal (1–60 mm), suivi végétation, coûts et location, réception sur site, résumés FR/PT. Le lien Emplacement et budget ouvre la fiche du stand, son quartel, son numéro et ses besoins.

La réception exige un administrateur actif, un inventaire des parties requises, un stand implanté, responsable, origine, méthode de contrôle, suivi et preuve de réception. Elle enregistre auteur, date et empreinte d’implantation. Modifier les matériaux ou le dossier technique annule la réception ; déplacer le stand ou modifier ses zones rend cette réception invalide. Les stands inclus dans le scénario de lancement doivent avoir leur cabane réceptionnée avant passage en exploitation. Ce contrôle documentaire n’est pas une certification technique automatisée.

**Éditions → Défi cabane · révélation publique** : non dévoilé par défaut, idée seulement, puis règles et cabanes autorisées. La page `/events/{slug}/cabanes` ne publie ni inventaire privé, ni coût, ni responsable, ni preuve ou géométrie. Une cabane n’apparaît que si son propre accord de publication et celui du stand sont actifs. Les résumés sont échappés et le repli éditorial utilise FR puis PT si la langue demandée manque.

Prévisualisation complète `/workspace/preview/{slug}/cabanes` réservée aux administrateurs actifs en `APP_ENV=local`, sans cache. Aucun dévoilement automatique par seed. Les étapes du chantier se trouvent aussi dans le parcours de lancement réordonnable.

La [note de calcul interne de l’ossature](CABIN-STRUCTURE-PRELIMINARY.md) contient un modèle explicite de toit à deux pans avec tirant, les comparaisons de poids/flexion/flèche/espacement, les efforts dans le tirant, le flambement idéal et les actions de pression. Elle est aussi accessible par le bouton **Évaluation interne** de la liste des cabanes. Le script Python et son fichier d’hypothèses permettent de reproduire les résultats ; aucune charge admissible ou validation de la configuration réelle n’est produite.

## Allongement libre et portiques

Largeur et hauteur restent à 2 400 mm. Seule la longueur (champ technique `depth_mm`) augmente librement : par exemple 3 500 ou 5 000 mm. Aucun multiple de 2 400 mm ni entraxe fixe des travées n’est imposé.

L’ossature est envisagée en tubes récupérés de **30 mm de diamètre extérieur**, à confirmer par mesure. Ce diamètre ne permet pas à lui seul de déduire une résistance ou une portée : relever épaisseur, matériau et état, choisir des raccords adaptés au diamètre réel, puis justifier poussées, flexion/flambement, toiture sèche/mouillée, vent, ancrages et appuis. Ne pas présumer qu’un raccord d’échafaudage quelconque convient à 30 mm.

Le dossier conserve entraxe maximal calculé, auteur compétent, date et référence de note de calcul. Ces quatre éléments sont exigés pour réceptionner **toute cabane, y compris le modèle de base**. Il s’agit d’une **évaluation technique interne documentée**, sans contrat ni attestation d’ingénieur imposés. Identifier données, hypothèses, calculs, essais, configuration étudiée et limites d’utilisation. Le logiciel conserve cette référence ; il ne certifie pas lui-même la solidité. Les forces s’expriment en N, les charges réparties en N/m ou N/m², les moments en N·m : aucune valeur limite ou résistance de raccord n’est inventée. Toute modification des dimensions, de l’inventaire ou de ces preuves remet la réception à refaire. Enregistrer d’abord géométrie, inventaire, diamètre et entraxe : leur modification efface la date de validation structurelle. Réexaminer ensuite la note et enregistrer sa validation, avant la réception. Les essais, photos et constats de tenue du prototype alimentent le dossier ; ils ne remplacent pas la justification des charges, du vent, de la toiture sèche/mouillée et des ancrages.

Une extension exige un nouveau relevé des besoins et un devis couvrant ses dimensions complètes, ainsi qu’une vérification de l’emprise et des circulations. Elle ne crée pas un nouveau stand administratif, un nouveau sponsor ou un revenu automatique. Le logiciel enregistre ces décisions et preuves, sans fournir un calcul de structure ni adapter automatiquement les quantités du devis.

## Vidéo de démonstration QAPAS

Un brouillon FR/PT et son séquencier sont créés dans **Mobiliser → Blog et making-of**, « Construire notre cabane : du raccord au plessis ». Le parcours comprend son tournage : raccord en gros plan, tubes récupérés, contrôle du diamètre de 6 cm, sisal, tressage, variante de toit mimosa/paille, essais, puis démontage et devenir des végétaux. Utiliser le prototype effectivement vérifié ; droits des personnes/sponsors, montage et sous-titres à prévoir, temps et moyens imputés une seule fois à l’organisation/captation.

La vidéo **n’est pas encore produite**. Après réalisation, renseigner son ID YouTube dans l’article et valider sa publication. La page du défi affiche alors le lien ; tant que la vidéo manque, elle annonce la démonstration à venir. Le lien ouvre l’article puis YouTube au clic, sans lecteur ni traceur embarqués. L’article de démonstration reste caché au public tant que la révélation du défi n’est pas au niveau « règles », même si son statut éditorial a été publié ; l’aperçu local administrateur montre le brouillon.

## Coût et sponsoring

Le temps de préparation de l’évaluation interne reste du travail d’organisation à chiffrer, sans ajouter d’honoraires externes supposés.

Un poste investissement commun prévoit **l’achat du broyeur QAPAS, financé par l’initiative**, avec prix/IVA inconnus et décaissement intégral. La capacité réelle à traiter mimosa et cannes de 60 mm, le débit, les protections, la livraison et la mise en service sont à vérifier au devis. Le prix d’achat n’est pas répété par cabane ; usage futur et valeur résiduelle ne diminuent pas le cash à financer.

Six postes de fabrication QAPAS sont créés dans le scénario 6 + 6, avec **prix et IVA inconnus** et quantité un. Un poste commun couvre seulement transports mutualisés, tri, opérateur/carburant/entretien et pièces d’usure du broyeur, traitement des lots à risque, manutention, stockage temporaire, paillage et suivi supplémentaires. Le détail du prototype doit inclure raccords, préparation, ligatures, travail, montage/démontage et contrôle, sans répéter les transports communs. Les demandes de devis FR/PT sont disponibles pour chacun de ces postes ; aucune n’est envoyée automatiquement.

La fabrication est enregistrée comme investissement avec décaissement complet dans cette édition. Le fait de réutiliser les cabanes plus tard ne diminue pas les liquidités nécessaires maintenant. La construction financée par une équipe est renseignée séparément ; toute dépense réellement supportée par QAPAS doit rejoindre son budget une seule fois.

Les six lignes de supplément de location sont inactives (quantité zéro). Une location comprise dans la formule du stand ne crée pas une deuxième recette. Le serveur interdit l’activation d’une ligne liée à une location non tarifée ou incluse. Un supplément exige une prestation explicitement distincte et ses conditions. Caution remboursable séparée du chiffre d’affaires ; le champ du dossier ne crée pas de mouvement de trésorerie, qu’il faut préparer dans les lignes dédiées.

L’offre du sponsor principal place le **raccord** au centre du récit : lot fourni/prêté, compatibilité, transport et reprise, droits web, making-of et signalétique à convenir. Une valorisation publicitaire d’apport n’est pas du cash. Réduire uniquement les coûts réellement évités sur preuve, et ne pas comptabiliser le même don comme recette financière. Les obligations existantes ou textes personnalisés ne sont pas réécrits. Aucun tarif de participation n’est relevé automatiquement.

La proposition de **12 partenaires de proximité à 250 € est retirée**. Son ancien tableau est conservé comme historique ; il ne constitue pas un objectif de prospection actif. Le nouveau montage doit être recalculé après prototype et devis, sans annoncer prématurément le break-even. Les **1 000 € de nettoyage/nivellement** et les provisions d’accueil restent conservés tant qu’aucune économie réelle n’est établie.

## Mimosa et cannes : valoriser sans propager

Couper du mimosa ne prouve pas son éradication : *Acacia dealbata* peut rejeter vigoureusement après coupe et possède une banque de graines durable. Identifier les espèces et origines ; ne pas transporter graines, gousses, racines, rhizomes, terre contaminée ou parties capables de propagation. Préparation des matériaux, déplacements, devenir après démontage et méthode de contrôle sont à documenter avec une compétence adaptée. Le projet n’autorise ni transport de plants vivants ni diffusion d’espèces invasives.

« Canas » n’identifie pas une espèce à lui seul. Si ce sont des *Arundo donax*, les rhizomes et fragments capables de reprise demandent une vigilance spécifique. Le suivi des repousses, avec responsable et prochaine inspection, reste prévu après le chantier. Aucun protocole de produit phytopharmaceutique ni chantier de coupe chronométré pour le public n’est prescrit ici.

Sources consultées le 30 septembre 2026 : [ICNF — Acacia dealbata](https://rubus-qua.icnf.pt/RUBUSEE/SpeciesSheet_Entry.aspx?SpecieId=565), [ICNF — Arundo donax](https://rubus-qua.icnf.pt/RUBUSEE/SpeciesSheet_Entry.aspx?SpecieId=181), [Invasoras / UC & ESAC — Arundo donax](https://www.invasoras.pt/sites/default/files/Arundo-donax_torrinha.pdf), [Decreto-Lei 92/2019](https://diariodarepublica.pt/dr/detalhe/decreto-lei/92-2019-123025739). Faire vérifier les modalités réelles du chantier ; aucune assimilation générale du bois mort à une plante vivante.

## Après les Jeux : du stand au sol, puis aux plantations

Le démontage sépare les parties végétales des tubes, raccords, visserie et ligatures. Les éléments métalliques restent réutilisables ; les panneaux végétaux sont destinés au broyage et au paillage sur place après tri et validation de leur innocuité vis-à-vis de la propagation. Le broyeur ne garantit pas à lui seul la destruction des graines ni de tous les fragments viables de cannes : les lots douteux ne sont pas épandus et suivent une filière adaptée documentée.

Parler de **broyat végétal ou plaquettes de paillage**, et de BRF seulement pour la fraction qui en a les caractéristiques. Le BRF est issu de rameaux jeunes non desséchés ; un mélange de gros bois, panneaux séchés et cannes ne répond pas automatiquement à cette définition. La décomposition est progressive, pas une assimilation immédiate par le sol. L’[INRAE décrit le BRF et sa dégradation](https://ephytia.inrae.fr/fr/C/26619/Tropifruit-Exemples) ; ses exemples tropicaux ne donnent pas une durée ou une dose applicable telle quelle à la Quinta.

La coupe des zones envahies permet de **préparer** des plantations après l’événement, dès que parcelles et matériel végétal sont prêts. Elle ne suffit pas à assurer leur réussite : suivi des rejets de mimosa/cannes, protection et suivi des plantations restent nécessaires. Le projet conserve la destination de verger déjà envisagée pour les terrasses ; aucune promesse générique de reboisement réussi immédiatement après coupe rase. Les plants, protections, arrosage et entretien restent dans les postes de plantation, sans deuxième comptage dans les cabanes.

## Mise à jour

`php artisan migrate` puis `php artisan db:seed`, sans remise à zéro. Le seeder ajoute les dossiers manquants et conserve les noms, responsables, inventaires, prix, preuves et publications déjà modifiés. Une seconde exécution ne crée pas de deuxième cabane, de second sponsor principal ou de doublon budgétaire.
