# Cahier des charges QAPAS événements centré sur le plan

**Version 0.3 du cahier des charges — 1er octobre 2026 — conception du moteur événementiel générique, pilotage des 4P, nouvelle base Laravel et démonstration Farmers Games.**

Cette application organise un événement en partant de son implantation réelle. Le plan réunit espaces, équipements, accès et postes de travail ; chaque élément possède une fiche économique, opérationnelle et éditoriale couvrant Produit, Prix, Distribution et Promotion. La visite guidée en ligne réutilise ces éléments, leurs explications et les offres disponibles. La progression teaser, essai sur le marché puis confirmation pilote les affiches et communications de l’édition.

Le présent CDC décrit la nouvelle version à construire. Il ne constate aucune modification du dépôt ni aucun archivage Git déjà effectué. L’application actuelle sera conservée comme **version de référence non aboutie** : elle documente les parcours et règles déjà étudiés, sans constituer une version déclarée achevée.

L’inspiration OnePlan porte sur la conception du site, les mesures, le déploiement du personnel, la collaboration et le partage des plans. Ces fonctions sont présentées sur sa [page Festivals](https://www.oneplan.io/fr/festivals/). Les liens automatiques avec le business plan et les règles QAPAS ci-dessous constituent notre propre proposition.

L’interface d’administration utilise Laravel 13, Livewire, Filament 5 et Leaflet. L’identité commune et les contrats de navigation proviennent de qapas-application et qapas-platform, avec Passport. Le frontend public reprend la présentation marketing QAPAS.

## 1 Le plan et la carte

### 1.1 Le parcours de conception

Après création d’un événement et de son édition, l’organisateur choisit le site, le calendrier et un scénario. L’écran principal présente une carte largement ouverte, une bibliothèque d’objets à gauche et la fiche de l’élément sélectionné à droite. Sur un écran étroit, ces panneaux deviennent des tiroirs.

Le parcours est : **choisir une catégorie → placer ou dessiner → dimensionner → renseigner les besoins → chiffrer → affecter un responsable → contrôler → publier**. On peut commencer le dessin avant d’avoir obtenu les devis : les montants manquants restent explicitement « à chiffrer ».

La fiche comporte les onglets Général, 4P et réussite, Ressources, Coûts, Recettes, Personnel, Communication et Documents. Elle montre les quantités, le coût prévisionnel, la recette attendue, le rôle dans l’expérience et les informations manquantes. Cliquer une ligne de budget retrouve l’objet sur la carte ; cliquer l’objet retrouve ses lignes de budget. Les données éditoriales validées alimentent sa fiche publique et la visite guidée.

Les six entrées Filament sont Plan, Coûts, Recettes, Bilan, Communication et Produits. Elles partagent un sélecteur d’événement, d’édition, de scénario et de période. Les fonctions Farmers Games restent disponibles comme activités spécialisées : équipes, stands villageois, défis, rotations, résultats et récompenses.

### 1.2 Les règles de dessin

Deux modes sont clairement séparés.

**Implanter un équipement.** L’utilisateur choisit un gabarit dimensionné : carré, rectangle, cercle, triangle équilatéral, polygone régulier à nombre de côtés choisi, ou empreinte prédéfinie d’un objet. Il saisit les dimensions en mètres et l’orientation. Le déplacement, la rotation et le redimensionnement conservent les contraintes du gabarit. Un stand rectangulaire reste rectangulaire ; un hexagone régulier conserve ses côtés égaux. Le rectangle est un gabarit contraint, même s’il n’est pas un polygone régulier au sens mathématique.

**Délimiter un espace.** Le dessin libre autorise un polygone irrégulier suivant le terrain, avec modification des sommets. Il sert aux zones publiques ou privées, parkings, périmètres et espaces réservés. Les trous ou exclusions permettent d’écarter un bâtiment, un arbre ou une partie indisponible du terrain.

Les routes et accès possèdent une emprise polygonale, régulière ou libre selon le terrain. Un axe associé permet de mesurer leur longueur et de préciser leur largeur. Les câbles, canalisations et lignes de barrières utilisent aussi un tracé linéaire : les convertir artificiellement en surfaces fausserait les quantités.

Chaque élément accepte une couleur saisie en HEX, un sélecteur visuel, une couleur de contour, une opacité et une étiquette. La couleur de catégorie reste une valeur par défaut modifiable. Icônes, noms et légende complètent la couleur pour garder un plan lisible.

Les outils comprennent déplacement, rotation, duplication, alignement, accrochage, annulation/rétablissement, sélection multiple, masquage et verrouillage des calques. Une copie conserve le gabarit et ses besoins, mais crée une nouvelle implantation ; elle ne copie ni paiement ni facture ni affectation nominative.

### 1.3 La bibliothèque des équipements

Le catalogue initial ci-dessous est proposé pour couvrir les besoins exprimés. Chaque sous-catégorie possède un gabarit, une unité de quantité, des champs techniques et des ressources habituelles. QAPAS peut ajouter des modèles sans modifier le code.

| Catégorie principale | Sous catégories proposées | Données et unités principales |
|---|---|---|
| Toilettes et sanitaires | WC portable, WC accessible, bloc sanitaires, urinoir, lave mains, douche, vidange, réserve sanitaire | Unité, capacité renseignée, location par jour, nettoyage, eau et vidange |
| Audiovisuel | Scène et estrade, sonorisation, micro, régie, écran, projection, éclairage de spectacle, éclairage de service, liaison de données | Unité ou ensemble, puissance, jours, montage et opérateurs |
| Électricité | Arrivée principale, compteur, tableau, coffret de distribution, départ protégé, prise de stand, rallonge, passage de câble, groupe électrogène, batterie, éclairage | Unité, mètre, jour, puissance demandée et attribuée, ressource commune |
| Structures | Stand villageois, stand indépendant, abri recyclé, chalet, tente, chapiteau, auvent, podium, plancher, ancrage et lestage | Unité ou m², empreinte, montage, transport, réemploi |
| Mobilier et fournitures | Table, chaise, banc, comptoir, étagère, rangement, tapis, protection de sol, kit de nettoyage, consommables d’accueil | Unité, lot, m², quantité disponible et réservée |
| Véhicules et engins | Dyna et camion, utilitaire, remorque, tracteur, manutention, broyeur, véhicule prestataire, navette, véhicule de secours | Véhicule, mission, trajet, km, heures, carburant et immobilisation |
| Restauration et boissons | Cuisine collective, baraque à frites QAPAS, bar, point d’eau, chambre froide, frigo, stockage alimentaire, dégustation, retrait de repas | Poste, équipement, jour, capacité, produits et vendeur |
| Déchets | Poubelle, tri, verre, biodéchets, huiles, point de collecte, conteneur, enlèvement, nettoyage final | Unité, volume, rotation, prestation, responsable |
| Marque et signalétique | Accueil, flèche, plan affiché, affiche, banderole, logo sponsor, panneau de stand, QR, accessibilité, consigne et sécurité | Support, dimensions, tirage, impression, pose et bénéficiaire |
| Barrières et clôtures | Barrière de foule, clôture temporaire, balisage, rubalise, portillon, protection d’équipement, séparation de file | Mètre, module, unité, jours, ouverture et circulation |
| Espaces routes et accès | Voir le catalogue des espaces ci-dessous | Surface, longueur, largeur, capacité renseignée, droits d’accès |
| Personnel et postes | Voir le catalogue de workforce ci-dessous | Poste, personne, créneau, heures, mission et organisation |

Une fonction secondaire peut relier un élément à plusieurs besoins. Une tente médicale appartient physiquement aux Structures, dessert l’espace médical et accueille des postes de secours. Elle garde une seule identité physique et un seul coût de location.

### 1.4 Les espaces routes et accès

Tous les espaces ci-dessous sont représentés par des polygones. L’utilisateur distingue la délimitation, les équipements contenus et les frais propres à l’espace.

| Famille | Sous catégories |
|---|---|
| Accueil du public | Zone publique, accueil, information, repos, assises, rassemblement, espace familles, espace enfants |
| Activités | Zone de jeux, défi Farmers Games, démonstration, exposition, atelier, scène et public |
| Commerce | Village des stands, emplacement indépendant, cuisine collective, bar, dégustation, merchandising |
| Accès réservés | Zone privée QAPAS, coulisses, réserve, local technique, stockage, personnel, prestataires |
| Stationnement | Parking public, participants, exposants, personnel, accessible, vélos, navettes, attente de véhicules |
| Circulation | Allée piétonne, parcours accessible, accès véhicules, livraison, voie de service, entrée, sortie, contrôle, file d’attente |
| Secours et contraintes | Accès secours, poste médical, évacuation, point de rassemblement, périmètre protégé, zone interdite, obstacle, zone indisponible |
| Logistique temporaire | Montage, démontage, dépôt, chargement, déchargement, déchets, attente des prestataires |

Les espaces peuvent avoir des horaires et états différents : ouvert au public pendant l’événement, accessible aux prestataires pendant le montage, fermé pendant une intervention. La capacité d’accueil est une donnée justifiée et validée par le responsable habilité ; la surface seule ne vaut pas jauge approuvée.

Les relations conteneur/contenu permettent de déplacer ou sélectionner un ensemble sans ajouter automatiquement le coût des enfants au coût propre du parent. Deux polygones superposés peuvent exprimer deux usages ; ils ne constituent pas deux achats d’un même aménagement.

### 1.5 Le personnel et les prestataires

La workforce est organisée par **organisation, métier, poste, personne et créneau**. Organisation : QAPAS, sous-traitant, association partenaire, équipe villageoise ou service intervenant. Statut : salarié, prestataire, bénévole ou personnel d’un organisme. Métier et statut restent distincts.

| Famille | Sous catégories de métiers |
|---|---|
| Direction | Directeur d’événement, manager de site, responsable opérations, coordinateur, steward supervisor, chef de zone |
| Sécurité et accueil | Sécurité privée, steward, accueil, information, contrôle d’accès, gestion de file, parking |
| Secours | Médical, secouriste, liaison police, police si intervention convenue, liaison incendie, pompiers si présence convenue |
| Technique | Électricien, son, lumière, audiovisuel, informatique, montage, structures, manutention |
| Logistique | Chauffeur, livraison, stock, propreté, déchets, réapprovisionnement |
| Activités et vente | Arbitre, animateur, responsable défi, responsable stand, cuisine, bar, vendeur merchandising |
| Administration et communication | Comptable professionnel, responsable de caisse mandaté, relation exposants, relation sponsors, presse, photographe |
| Renforts | Bénévole polyvalent, remplaçant, réserve opérationnelle, assistant de coordination |

Un poste possède une position ou une zone de travail sur le plan. Une personne peut couvrir plusieurs postes à des horaires compatibles. Une représentation à deux endroits ne crée pas deux rémunérations. Le système détecte les chevauchements de créneaux, les postes non couverts et les quantités demandées supérieures aux effectifs disponibles.

Les bénévoles peuvent avoir un coût direct de rémunération nul, tout en portant des coûts de repas, déplacement, équipement ou formation. Un poste police ou incendie représente un besoin à confirmer : le dessiner n’engage aucun service extérieur.

### 1.6 La fiche commune des éléments

Chaque élément possède un identifiant stable, un nom, une catégorie et sous-catégorie, sa géométrie, ses dimensions, sa couleur, son scénario, son calendrier d’utilisation, son responsable, son fournisseur éventuel, ses 4P, sa contribution à la réussite, ses contenus publics et sa visibilité.

La fiche distingue **besoin prévu**, **ressource réservée** et **installation réalisée**. Elle conserve les dépendances : stand alimenté par tel coffret, zone desservie par tel sanitaire, poste rattaché à tel responsable. La disponibilité d’un équipement est contrôlée sur l’ensemble de ses utilisations.

Les alertes portent sur le hors périmètre, les intersections avec zones indisponibles, les accès obstrués, les ressources insuffisantes, les besoins non chiffrés et les postes non couverts. Ce sont des contrôles de préparation ; une carte ne délivre pas une validation technique automatique.

### 1.7 Les variantes et les périodes

Un événement peut avoir plusieurs sites, éditions et scénarios : configuration centrale, format réduit, météo défavorable ou report. Le plan possède aussi des phases de montage, ouverture, fermeture et démontage. Le sélecteur temporel montre seulement les éléments présents sur le créneau choisi.

Dupliquer un scénario copie ses hypothèses et rattache les ressources existantes sans créer un nouvel actif. Les variantes sont comparées ; leurs budgets ne sont jamais additionnés comme si tous les scénarios allaient être exécutés.

### 1.8 La carte publique et les exports

L’organisateur prépare une version publique depuis Filament, la prévisualise dans le frontend QAPAS puis la publie. Le public dispose de filtres, d’une recherche, d’une légende, d’une visite guidée, de fiches de stands et services, d’horaires et de liens utiles. Les offres disponibles et FAQ pertinentes complètent les étapes. Une liste accessible permet de consulter et suivre la visite sans carte.

Chaque objet possède une fiche publique distincte de sa fiche d’exploitation. Les budgets, coordonnées privées, noms de personnel non destinés au public, stocks et dispositifs sensibles sont exclus du contenu transmis. Une zone privée peut apparaître uniquement comme « accès réservé » ; certains dispositifs peuvent être entièrement absents du plan public.

La publication produit un instantané versionné des seules données autorisées. Les changements du brouillon ne se diffusent pas immédiatement. Prévisualisation, publication, retrait et retour à une publication antérieure sont tracés. Le lien public et le QR de l’événement peuvent rester stables tout en indiquant la date de mise à jour.

Exports : GeoJSON pour les données géographiques ; CSV pour l’inventaire et les budgets ; PDF et PNG pour un plan imprimable, avec légende, échelle, orientation, date et version. Les profils Public, Exploitation et Finance définissent les champs et droits de chaque export. Un PDF ou PNG est statique ; l’interactivité est conservée sur le frontend et dans les données GeoJSON.

Le choix du fournisseur de fond cartographique doit couvrir les usages publics, les quotas et l’impression. Un téléchargement autonome intégrant tous les fonds de carte ne sera promis que si le fournisseur le permet.

### 1.9 Les quatre P de chaque élément

Chaque élément du plan couvre les quatre dimensions du plan d’affaires : **Produit, Prix, Distribution et Promotion**. Un stand est simultanément une offre, un lieu où cette offre est accessible, un support de communication et une unité économique. Les équipements de service couvrent également ces dimensions, avec une réponse explicite lorsque l’usage est gratuit ou que la promotion ne concerne pas le public.

| Dimension | Champs de la fiche | Usage dans le frontend |
|---|---|---|
| Produit | Promesse, public visé, besoin satisfait, activité ou prestation, contenu inclus, qualité attendue, responsable et statut de réalisation | Ce que l’on trouvera ou pourra faire à cet endroit |
| Prix | Prix visiteur ou acheteur, gratuité éventuelle, prix exposant distinct, coûts, marge directe, frais partagés et conditions | Gratuit ou payant, montant et contenu de l’offre pertinente |
| Distribution | Emplacement physique, accès, horaires, capacité, canal en ligne ou sur place, réservation, retrait et disponibilité | Où, quand et comment participer ou obtenir le produit |
| Promotion | Présentation, images, message par phase, publics, supports, sponsors, QR, appel à l’action et indicateurs | Pourquoi venir, découvrir, candidater ou acheter |

Les offres au public et les prestations vendues à l’exposant sont distinguées. Un stand peut être gratuit à visiter et payant à louer. Plusieurs produits peuvent être proposés au même stand : ils ont leurs propres prix et stocks.

Chaque P possède un état « renseigné », « à compléter » ou « sans objet justifié ». Le produit comprend la fonction d’un service interne ; son prix peut être un coût d’exploitation sans tarif public ; sa distribution décrit sa disponibilité ; sa promotion peut être une consigne réservée aux équipes. On ne crée pas une vente pour rendre la fiche complète.

La fiche sépare ensuite **contribution au résultat** et **contribution à la réussite**. Le premier indicateur montre coûts et recettes selon le périmètre retenu. Le second décrit l’utilité réelle : animation, diversité, apprentissage, accueil, accessibilité, confort, coopération ou sécurité. Il indique objectif, caractère indispensable ou optionnel, mesure prévue et constat après événement.

Un stand peut être rentable et peu pertinent pour l’identité de l’événement ; une animation gratuite peut avoir un coût assumé et une grande valeur pour les visiteurs. La décision de conserver, déplacer, financer ou retirer un élément tient compte des deux dimensions. Une perte directe n’entraîne pas son retrait automatique.

Les effets indirects — attirer du public, créer du passage ou favoriser d’autres ventes — sont des hypothèses ou observations documentées. Ils ne deviennent pas des euros de recettes fictives. Aucun score unique ne masque les compromis entre marge, utilité et qualité de l’expérience.

### 1.10 La visite guidée en ligne

La carte publiée sert de base à une visite guidée comparable à la présentation d’un parc d’attractions : le visiteur comprend l’ensemble, découvre les étapes qui l’intéressent puis prépare sa venue. L’organisateur compose des parcours ordonnés à partir des éléments publics existants, sans tenir une seconde liste de lieux.

Un parcours peut présenter les villages, les défis, les saveurs, les savoir-faire, les espaces familiaux ou les services pratiques. Chaque étape associe l’élément du plan, son histoire, ses images, son activité, ses horaires, son accessibilité et son action utile. Les étapes apparaissent dans un panneau synchronisé avec la carte ; précédent, suivant et choix direct permettent une découverte libre.

Le parcours éditorial peut faire zoomer la carte ou sélectionner un lieu. Il n’est pas présenté comme un itinéraire piéton calculé si les cheminements n’ont pas été décrits et validés. Une liste permet de suivre la visite sans utiliser la carte.

Les fiches assemblent les 4P publics, les explications et FAQ pertinentes, ainsi que les offres réellement disponibles : exposition, participation, soutien, repas, produits ou merchandising. Les textes conservent leur richesse ; ils ne sont pas réduits à un nom, une icône et un prix.

La visite change avec la phase de communication. En teaser, les implantations sont des concepts ou intentions clairement signalés. Pendant l’essai sur le marché, les besoins et candidatures ouvertes apparaissent. Après confirmation, les éléments confirmés, le programme et les possibilités restantes sont mis en avant. Une implantation pressentie n’est jamais présentée comme une attraction garantie.

### 1.11 Le pilotage des 4P à tous les niveaux

La performance des quatre P repose sur des hypothèses vérifiables, des preuves et des décisions. Le système distingue une fiche complète, une hypothèse de marché et un résultat constaté. Une intention, une consultation de carte ou un clic ne devient ni une vente ni une présence physique.

| Niveau | Décision suivie |
|---|---|
| Organisation et portefeuille | Rémunération du travail, bénéfice cible, trésorerie, réemploi et capacité de l'équipe |
| Événement et édition | Promesse, publics prioritaires, format de base, confirmation et réussite |
| Scénario zone et parcours | Complémentarité, ambiance, flux, accessibilité et qualité de la visite |
| Élément du plan | Fonction, ressources, responsable, contribution financière et utilité |
| Offre et produit | Acheteur, contenu, valeur, prix, disponibilité et livraison |
| Canal et partenaire | Public atteint, coût, engagements obtenus et valeur délivrée |

Les publics bénéficiaires, acheteurs et décideurs sont distingués. Une famille, un village, un exposant, un relais et un sponsor ont leurs propres critères. La validation porte sur les prestations qui financent l'événement et sur l'intérêt des visiteurs qui lui donnent vie.

Chaque couple offre et public possède une promesse, les quatre P, une hypothèse, une cible, un indicateur, un responsable, une échéance, une preuve datée, une prochaine action et une décision. Les décisions possibles sont conserver, améliorer, déplacer, développer ou retirer. Une preuve conserve sa source, sa portée et son caractère réel ou simulé ; une donnée manquante reste inconnue.

Un stand peut porter une prestation pour l'exposant, plusieurs produits pour les visiteurs et une visibilité pour un sponsor. Les fiches d'offre partagent l'implantation ; elles ne créent pas plusieurs équipements physiques. Les valeurs communes peuvent être héritées de l'événement ou du scénario, avec leur provenance et une surcharge explicite. Un service interne décrit sa fonction et sa disponibilité sans inventer un tarif ou une campagne publique.

La validation du produit documente besoin, alternatives, différenciation, contenu inclus, exclusions et qualité. Le prix confronte coût complet, valeur perçue et acceptation du marché. La distribution suit découverte, candidature, commande, paiement, arrivée, circulation, consommation et retrait. La promotion associe message, preuve, action, canal, budget et mesure pour chaque public et chaque phase.

La visite guidée teste la compréhension et la préparation de la venue. Les flux physiques sont constatés par comptage ou observation documentée ; les vues du frontend restent des indicateurs numériques.

## 2 Le planificateur des coûts

### 2.1 Un objet et son dossier de coûts

Chaque élément dessiné possède une unité analytique de coûts. Elle peut contenir plusieurs lignes : achat ou location, transport, montage, exploitation, personnel, consommables, nettoyage, démontage et remise en état. Un objet peut afficher un coût direct nul justifié, mais une absence de devis reste « inconnu ».

Les unités disponibles sont : pièce, lot, mètre, m², heure, heure-personne, jour, trajet, kilomètre, portion et forfait. Chaque ligne indique quantité, prix unitaire, devise, base HT/TTC, traitement des taxes paramétré, montant, source du prix, date, fournisseur, payeur, échéance et justificatif.

Quatre vues restent séparées : **prévu**, **engagé**, **facturé** et **payé**. Un devis accepté ne devient pas un paiement. Les montants correspondent à des états ou pièces reliés ; ils ne sont pas additionnés les uns aux autres.

### 2.2 Le dessin produit des quantités contrôlables

La superficie d’un plancher peut alimenter ses m² ; la longueur d’un câble ses mètres ; le nombre de barrières dépend de la longueur et du module choisi ; une affectation de poste fournit des heures-personnes. La règle de quantité et ses éventuels arrondis sont visibles.

Changer une dimension recalcule la prévision liée. Si un devis est figé ou une commande déjà engagée, le système montre l’écart et propose une révision : il ne réécrit pas le devis, la facture ou le paiement. Les majorations pour réserve, pertes ou longueur de raccordement doivent être explicitement saisies.

Un cercle est défini par centre et rayon ; une ligne par son tracé et ses caractéristiques. Les calculs utilisent des dimensions métriques et une méthode géographique adaptée, pas les pixels de l’écran.

### 2.3 Les ressources communes et les investissements

Un coffret alimentant six stands est une ressource unique. Son achat figure une fois, même si six éléments l’utilisent. Une clé explicite répartit son coût d’usage entre bénéficiaires : quantité, durée, puissance prévue ou règle décidée.

Les équipements durables QAPAS ont un propriétaire, un coût d’achat, une durée ou règle d’utilisation, un stock, un emplacement de stockage et un historique. Le budget distingue l’investissement, sa sortie de trésorerie et la charge d’usage affectée à l’événement. Une même vue de résultat ne cumule pas arbitrairement achat complet et quote-part d’usage.

Les frais sans emplacement naturel — assurance, comptabilité générale, rémunération de préparation — sont des postes communs de l’événement. Ils peuvent être rattachés au site ou ventilés vers les objets, sans imposer un faux marqueur physique. Le bilan conserve toute charge non encore répartie.

### 2.4 Les éléments QAPAS à reprendre

Les éléments suivants viennent des demandes et décisions précédentes ; leurs prix restent des hypothèses de saisie jusqu’à justificatif.

| Élément | Règle à représenter |
|---|---|
| Abri recyclé | Matériaux, tubes, découpe, transport, montage, démontage et stockage détaillés |
| Mimosa pour bardage | Longueur maximale demandée 2,50 m ; disponibilité et transport non garantis |
| Sisal | Trois bobines par abri ; prix et contenu de bobine à renseigner |
| Broyeur | Location REMO annoncée à 100 € pour un jour ; carburant SP98 jusqu’à 20 litres ; disponibilité et conditions à confirmer |
| Dyna | Trois allers-retours Aldeia do Souto–Fundão demandés ; kilomètres, temps, carburant et chargements à documenter |
| Raccordement de stand | Besoin de 6 A et longueur maximale prévue de 50 m par stand ; matériel, protection et capacité générale à chiffrer |
| Distribution électrique | Coffret événementiel à six prises depuis la ligne principale ; équipement durable QAPAS et coût d’usage inclus selon l’offre |
| Main d’œuvre | Temps de préparation, coupe, chargement, pose, démontage et administration visible même en cas de matériau gratuit |

Ces paramètres sont des besoins d’organisation, pas un dimensionnement électrique ou structurel validé. La fiche conserve la vérification technique et la référence du matériel choisi. Les longueurs facturées suivent la règle commerciale explicite, qui peut différer de la longueur réellement posée.

### 2.5 Les validations et la traçabilité

Le responsable d’un objet prépare son budget. L’organisateur accepte l’engagement ; le comptable professionnel vérifie les pièces et affectations selon son mandat. Les droits sont attribués à des personnes identifiées.

Une suppression de dessin est réversible tant qu’il n’y a pas d’engagement. Après engagement, l’élément devient retiré du scénario et ses coûts demeurent dans le dossier, avec éventuelle annulation, restitution ou remboursement. Chaque révision conserve auteur, date, valeur antérieure et motif.

## 3 Le planificateur des recettes

### 3.1 Des recettes liées aux éléments commercialisables

Un emplacement peut produire une recette de location ; un support une prestation de visibilité ; un bar ou point de vente des ventes ; une activité une participation si l’offre le prévoit. Une zone publique ou un poste de secours peut ne produire aucune recette. Le logiciel n’invente pas une monétisation à chaque objet.

Chaque recette indique élément, offre et version du prix, quantité, vendeur, bénéficiaire, client éventuel, taxes, échéance, règles d’exécution et justificatifs. Les ventes à réaliser, offres acceptées, montants facturés, encaissements vérifiés et remboursements sont consultables séparément.

Les contributions d’équipes, sponsoring, prestations QAPAS et opérations des tiers utilisent des types distincts. Les paiements versés directement à un prestataire ne sont pas présentés comme des recettes QAPAS.

### 3.2 Les tarifs issus du projet de référence

La consolidation du 29 septembre est la source des paramètres ci-dessous. Ils seront importés comme version d’offre propre à l’édition de référence, et non comme prix universels des événements futurs.

| Offre | Paramètre conservé |
|---|---|
| Point relais | 49,99 € TTC pour la première édition |
| Emplacement indépendant | 2,40 × 2,40 m, soit 5,76 m² ; 250 € TTC |
| Stand monté | 500 € TTC au total, emplacement compris |
| Sponsors structurels | Trois places à 2 500 € TTC ; surfaces de visibilité 2/4, 1/4, 1/4 |
| Vente des indépendants | Aucune commission QAPAS |
| Accès du public | Entrée libre |
| Comptable QAPAS | Plafond souhaité de 100 € TTC, prestation encore à confirmer dans la consolidation |

Les autres montants restent à définir. La règle d’attribution de la place sponsor double reste ouverte. Un changement de prix crée une nouvelle version d’offre et conserve celui des commandes déjà acceptées.

La cible de référence est 12 stands villageois et 12 stands indépendants. La cible de 24 membres et le plafond envisagé de 48 concernent l’effectif des équipes ; ils ne doivent pas devenir un plafond d’emplacements par confusion.

### 3.3 La disponibilité et la confirmation

Le placement d’un stand sur un plan ne réserve pas automatiquement un emplacement au client. Les états sont : prévu sur le plan, offre disponible, demande reçue, admissibilité vérifiée, précommande conditionnelle éventuelle, paiement reçu et réservé, puis commande d’exécution après confirmation de l’édition et encaissement vérifié. Une candidature seule ne confirme ni emplacement commercial ni tenue de l’événement.

Le contrôle des dernières places est atomique ; un verrou technique bref pendant un paiement ne devient pas une option commerciale gratuite. Les capacités sont vérifiées par édition, scénario retenu, espace et période.

### 3.4 Les sponsors et les soutiens affectés

La prestation de communication vendue au sponsor et l’avantage affecté au stand sont décrits séparément. L’encaissement QAPAS, la part affectée, les achats associés, la réduction du reste dû et les justificatifs forment une chaîne traçable.

Le sponsor paie QAPAS selon l’offre. L’équipe ne reçoit pas automatiquement cette somme. Un soutien ne diminue qu’une fois son reste à financer ; sa ventilation ne génère pas un second encaissement. Une facture déjà émise passe par le circuit de rectification, plutôt que par une modification silencieuse.

Un sponsor soutenant plusieurs objets dispose d’une enveloppe unique ventilée. Le montant n’est pas répété intégralement sur chaque panneau ou stand. Les montants promis, contractés et reçus restent distingués. Toute réaffectation d’une aide suit les règles acceptées et garde sa justification.

### 3.5 Les recettes de ventes et leurs hypothèses

Les recettes prévisionnelles d’un point de vente proviennent de produits, volumes et prix. Les hypothèses de fréquentation, conversion et panier sont visibles, datées et propres au scénario. Le chiffre d’affaires des ventes réelles vient ensuite des transactions validées.

Les repas, boissons et produits QAPAS possèdent des vendeurs identifiés. Le montage de cuisine collective et les éventuelles redistributions restent paramétrables jusqu’au choix déjà annoncé comme ouvert dans le dossier de référence. Aucun partage de 90/10 n’est activé comme règle acquise.

### 3.6 Les offres encore disponibles dans la visite

Chaque élément public peut présenter les offres qui lui sont associées, avec bénéficiaire, vendeur, contenu, prix, période, capacité restante et action appropriée. La carte montre par exemple un emplacement encore commercialisable ou les produits disponibles au point de vente.

La disponibilité vient de la source métier : capacité admissible, commandes confirmées, stocks, réservations actives, période de vente et état de l’événement. « Dessiné » ne signifie ni « ouvert », ni « disponible à acheter ». Un produit épuisé est identifié ou retiré des actions de vente ; son activité peut rester visible.

Les libellés distinguent « découvrir », « proposer une candidature », « demander une offre », « acheter » et « retirer sur place ». Ils correspondent au parcours réel. Un prix non arrêté reste sur devis ; aucun bouton ne promet une réservation si le circuit de confirmation n’existe pas.

Le catalogue, la visite et les documents partagent la même version d’offre. Un prix figé dans une affiche reste rattaché à sa campagne ; le QR rejoint une page précisant les offres actuellement valables. Les éventuelles préventes pendant la phase d’essai montrent les conditions de maintien, l’échéance de décision et la suite prévue en cas de non-confirmation.

### 3.7 Les précommandes et la décision sous trois semaines

**Règle fixée pour la nouvelle conception :** la première précommande commerciale signée et acceptée de l’édition déclenche un délai maximal de **21 jours calendaires** pour décider si l’événement est confirmé. L’échéance peut être plus courte, notamment si la proximité de l’événement ou les délais de livraison le demandent.

L’échéance est commune à l’édition, affichée avec date, heure et fuseau dans chaque précommande. Les commandes suivantes ne la repoussent pas et ne disposent pas d’un nouveau délai de trois semaines. Le retrait de la première commande ne remet pas le compteur à zéro. Une première signature le 1er octobre à 11 h donne une échéance au plus tard le 22 octobre à 11 h, dans le fuseau indiqué.

La décision doit intervenir avant cette échéance, avec le dossier du chapitre 4.6. Si les conditions sont acquises, les précommandes concernées deviennent des commandes d’exécution de l’édition confirmée. Si elles ne le sont pas, l’édition est non confirmée, les prestations conditionnelles ne sont pas exécutées et les restitutions prévues sont dues. L’absence de décision à l’échéance produit la même issue ; elle n’entraîne pas une prolongation silencieuse.

### 3.8 Le montage proposé et les choix de paiement

La recommandation est une **précommande contractuelle soumise à la confirmation de l’édition avant une date précise**, avec remboursement intégral du paiement lié à cette édition si elle n’est pas confirmée. La qualification juridique et la clause finale devront être formalisées pour les offres et clients concernés. Le [Diário da República](https://diariodarepublica.pt/dr/lexionario/termo/condicao-negocio-juridico) décrit les conditions prévues par l’article 270 du Code civil ; cette référence générale ne valide pas à elle seule nos conditions commerciales.

| Solution | Engagement et trésorerie | Sort si non confirmé |
|---|---|---|
| Précommande signée sans encaissement | Engagement soumis à confirmation ; paiement ensuite selon l’offre, susceptible d’échouer | Clôture sans débit ; ce montant n’est pas de la trésorerie acquise |
| Précommande payée sous condition proposée | Offre, prix et échéance acceptés ; paiement réellement reçu mais réservé pendant la décision | Remboursement intégral du montant de la prestation conditionnelle |
| Commande ferme sans condition de lancement | Obligation d’exécuter déjà prise ; l’organisateur porte davantage de risque | Un échec de lancement n’efface pas automatiquement ses obligations |
| Transfert vers une édition suivante | Nouvelle proposition avec date, lieu, prestation et durée de conservation des fonds | Seulement sur acceptation explicite du client ; remboursement par défaut |

Pour tester l’engagement commercial avec des paiements réels, la deuxième solution est proposée. La première reste possible pour une offre sans prépaiement, mais le bilan ne doit pas présenter une signature comme un encaissement. Une simple manifestation d’intérêt est encore un état différent.

Le client voit avant signature : identité du vendeur, prestation, prix total, échéance commune, conditions de confirmation, montant demandé maintenant, remboursement et absence de report automatique. Il reçoit un exemplaire durable de la précommande et de la version des conditions.

### 3.9 La garantie de remboursement et la réserve

Pendant la période de décision, le paiement lié à la prestation conditionnelle est **réservé à son exécution future ou à son remboursement**. Il ne finance pas les dépenses exploratoires QAPAS. Le plafond de dépense du teaser et de l’essai est financé par l’organisateur ou par un financement expressément prévu pour ce risque.

La réserve couvre le montant intégral à restituer, et pas uniquement la somme nette après frais de paiement. QAPAS prend en charge les frais non récupérés, les dépenses de préparation et les éventuels décalages fiscaux nécessaires pour maintenir cette capacité de restitution. Une retenue fiscale ou de plateforme n’est pas une raison pour diminuer le remboursement promis.

Le tableau de trésorerie distingue fonds de précommandes réservés, fonds disponibles pour engager des dépenses et fonds affectés à d’autres bénéficiaires. La vérification de couverture de l’événement peut retenir les ventes conditionnelles payées comme base économique du scénario exécuté, mais leur réserve n’est pas ajoutée une deuxième fois aux liquidités libres pendant l’attente.

Après confirmation, la réserve de précommande est affectée à l’exécution du budget confirmé. Les réserves pour risques résiduels, obligations de remboursement et fonds affectés restent suivies séparément ; toute la banque ne devient pas automatiquement trésorerie libre.

La non-confirmation déclenche les notifications et ordres de remboursement sans demande individuelle du client. **Délai commercial proposé : ordre de remboursement intégral transmis au plus tard sous 14 jours calendaires après la non-confirmation ou l’échéance expirée.** L’objectif opérationnel est de l’initier dès le constat. Ce délai est une proposition d’engagement QAPAS, pas un délai légal universel affirmé ici ; les délais de crédit du moyen de paiement sont affichés.

Le dossier conserve montant dû, paiement d’origine, ordre transmis, état chez le prestataire, justificatif et éventuel échec. « Ordonné » et « reçu » sont distingués ; un remboursement en attente ou échoué n’est pas annoncé comme terminé. Les reprises empêchent les restitutions en double.

Un compte bancaire séparé facilite le contrôle de la réserve ; il ne constitue pas à lui seul un séquestre ni une garantie contre l’insolvabilité. Si une protection par tiers est annoncée au client, elle exige un dispositif réellement contractualisé avec le prestataire ou une garantie adaptée. L’application ne promet pas un séquestre qu’elle n’a pas.

### 3.10 Le report vers une autre année

La reconduction automatique à l’année suivante n’est pas le montage proposé. Le client peut préférer une autre date, refuser un autre lieu ou avoir besoin de récupérer sa trésorerie.

Une proposition de report indique l’édition, date ou borne précise, lieu, prestation conservée, prix, nouvelle échéance et modalités de sortie. Le client l’accepte activement sur un avenant ou une nouvelle commande. Son silence déclenche le remboursement prévu de l’édition non confirmée.

QAPAS ne retarde pas le remboursement pour obtenir cet accord. Le refus ou l’absence de réponse ne transforme pas le paiement en avoir. Les fonds transférés restent identifiés et soumis à la nouvelle affectation acceptée ; ils ne financent pas indistinctement plusieurs éditions.

### 3.11 Les contraintes de paiement et de facturation

Une autorisation bancaire n’est pas un encaissement. Les [autorisations cartes standard décrites par Stripe](https://docs.stripe.com/payments/place-a-hold-on-a-payment-method) expirent généralement avant 21 jours ; les durées prolongées dépendent de l’éligibilité. Une promesse de blocage bancaire de trois semaines n’est pas proposée sans vérification du moyen de paiement.

Les [remboursements Stripe](https://docs.stripe.com/refunds) utilisent les fonds disponibles ; des frais initiaux peuvent rester à la charge de l’organisateur et un remboursement peut attendre si le solde est insuffisant. Le CDC prévoit donc la réserve intégrale et les moyens de la rendre mobilisable, indépendamment de l’affichage « payé ».

Selon l’[article 36 du CIVA](https://info.portaldasfinancas.gov.pt/pt/informacao_fiscal/codigos_tributarios/civa_rep/Pages/iva36.aspx), les paiements anticipés relèvent d’une facturation à la réception. « Réservé » ne signifie pas « hors facturation ou hors fiscalité ». Une annulation suit les pièces rectificatives et les règles de régularisation applicables, notamment l’[article 78](https://info.portaldasfinancas.gov.pt/pt/informacao_fiscal/codigos_tributarios/civa_rep/Pages/iva78.aspx), dans le circuit du comptable.

### 3.12 Les délais de livraison et les prestations déjà exécutées

Le délai historique du kit relais de 14 jours après paiement ne peut pas être repris tel quel pour une précommande dont l’événement peut rester non confirmé pendant 21 jours. Pour une nouvelle offre conditionnelle, la production commence après confirmation et paiement reçu ; le délai de livraison court à compter de la plus tardive de ces deux dates et doit rester compatible avec l’ouverture.

Cette nouvelle règle ne modifie pas rétroactivement une commande existante. Si une prestation indépendante de la tenue de l’événement est vendue et livrée avant confirmation — par exemple une campagne publicitaire distincte — son objet, son prix et ses conditions sont acceptés séparément. Elle n’est pas prélevée après coup sur un paiement présenté comme intégralement remboursable.

### 3.13 Le texte client proposé

Le texte suivant est un brouillon de clause commerciale à formaliser, accompagné des conditions propres à l’offre. Les droits impératifs applicables au client restent préservés.

> Cette précommande concerne l’édition et la prestation indiquées ci-dessus. QAPAS doit annoncer la confirmation de l’événement au plus tard le [date, heure et fuseau], échéance commune fixée à 21 jours calendaires maximum après la première précommande signée de cette édition. Jusqu’à cette décision, votre paiement reste réservé à cette prestation ou à son remboursement. Si l’événement n’est pas confirmé avant l’échéance, la prestation conditionnelle n’est pas exécutée et le montant versé à ce titre est intégralement remboursé, sans frais déduits. QAPAS transmet l’ordre de remboursement au plus tard sous 14 jours calendaires après la non-confirmation ; le délai de crédit dépend du moyen de paiement et vous est communiqué. Aucun transfert vers une autre édition, gel supplémentaire ou avoir n’est imposé : il nécessite votre accord explicite.

Le compte client affiche la date limite, le statut de l’édition, celui de la précommande, le montant réservé et la suite prévue. La confirmation d’une commande après lancement et les règles de report d’un événement déjà confirmé constituent des situations distinctes.

### 3.14 Le catalogue commercial et les preuves de marché

Chaque offre décrit acheteur et décideur, vendeur, bénéficiaire, promesse, inclusions et exclusions, unités, capacité, prix versionné, fiscalité à vérifier, conditions, coûts complets, marge recherchée et délai de livraison. Le temps commercial, la préparation, le transport, le montage, l'impression, le stock, le service après vente et les remboursements prévisibles sont chiffrés dans le périmètre approprié.

Les entretiens, propositions, accords et refus sont reliés à la version de l'offre testée. Un refus conserve son motif sans entraîner automatiquement une baisse de prix. Les concessions commerciales ont une justification et une autorité ; aucune modification de tarif ne réécrit une commande antérieure.

Les prix de Farmers Games à 49,99 €, 250 €, 500 € et 2 500 € TTC appartiennent au jeu de démonstration. Leur présence ne prouve ni leur rentabilité ni leur acceptation. Les coûts à deviser restent inconnus. La différence de surface entre sponsors au même prix reste une question à valider, avec des prestations explicites si elle est maintenue.

Les ventes des exposants indépendants appartiennent à leur propre économie. Une observation volontaire peut aider à apprécier l'opportunité commerciale ; elle ne devient pas une recette QAPAS et n'active aucune commission.

Avant la première précommande signée, le format de base doit être suffisamment chiffré et ses ressources assez vérifiées pour permettre une décision dans le délai commun. Le teaser constitue la demande préalable ; les précommandes démarrent une échéance réelle, sans remise à zéro.

## 4 Le consolidateur du bilan

### 4.1 Quatre résultats à lire séparément

Le tableau de bord réunit le résultat économique de l’événement, la trésorerie par date, les investissements et les fonds affectés. Il présente le scénario retenu et permet la comparaison avec les variantes.

| Vue | Contenu |
|---|---|
| Résultat de l’événement | Recettes retenues diminuées des charges de l’événement, selon règles de taxes et d’imputation explicites |
| Trésorerie | Encaissements et décaissements datés, acomptes, soldes, investissement, taxes, remboursements et besoins de financement |
| Investissements et stocks | Achats durables, stock réutilisable, coût d’usage affecté, stockage, entretien et cessions éventuelles |
| Fonds affectés | Soutiens par équipe ou objet, consommations justifiées, obligations restantes et solde disponible |

Le calcul économique exclut la TVA récupérable des charges lorsque le régime renseigné le permet, et inclut la taxe non récupérable. Les règles fiscales sont paramétrées et validées dans le circuit comptable ; le CDC ne fixe pas un taux général à toutes les opérations.

### 4.2 Le résultat par objet et par périmètre

La consolidation peut être consultée par catégorie, espace, stand, équipe, vendeur, responsable ou période. Une ligne a un porteur unique ; ses ventilations doivent totaliser 100 % lorsqu’elles sont complètes. Les regroupements sont des vues du même montant.

Le logiciel distingue marge directe et contribution après frais communs. Un coût non ventilé reste visible au total de l’événement. Les transferts internes entre poches analytiques sont neutralisés à l’échelle QAPAS.

Les remboursements, frais de paiement, invendus, pertes et coûts retirés du plan restent inclus. Le solde en banque ne devient pas automatiquement une somme libre de toute affectation ou obligation.

### 4.3 Les scénarios et les décisions

Au minimum, le dossier compare un format réduit, le scénario central, une fréquentation basse et un report ou une annulation. Les coûts irréversibles et récupérables sont identifiés. Le seuil de rentabilité utilise des produits ou offres avec leur marge connue ; un total de recettes TTC ne remplace pas ce calcul.

Le besoin de financement correspond au déficit maximal de trésorerie dans le temps. Les sponsors espérés et les ventes futures ne sont pas utilisés comme encaissements déjà disponibles.

Le dossier de référence vise au moins 4 000 € restant à QAPAS par mois d’effort après versement d’au moins 1 000 € nets à Stéphane et paiement des charges applicables. La durée de préparation et le coût complet de rémunération restent des hypothèses à renseigner ; la nouvelle version doit permettre de tester cet objectif.

Une décision de lancement rassemble site, autorisations documentées, financement, ressources et postes couverts. Le chapitre 4.6 précise le dossier requis pour passer à la communication officielle, dans le délai maximal de 21 jours du chapitre 3.7. Pour Farmers Games, le minimum envisagé de six villages dont Aldeia do Souto fait partie du profil d’édition, sans devenir une règle de tous les événements.

### 4.4 La clôture

La clôture compare budget, engagements, pièces et paiements ; explique les écarts ; contrôle les aides affectées ; constate les stocks restants et prépare les remboursements ou règlements. Elle produit un rapport daté relié à la version du plan effectivement exécuté.

Le consolidateur fournit un bilan de gestion. Il échange avec le système de facturation et les pièces comptables ; il ne prétend pas remplacer à lui seul la comptabilité officielle.

### 4.5 La réussite et le bénéfice sont évalués séparément

Le bilan possède un volet financier et un volet de réussite de l’événement. Le second rassemble les objectifs adoptés : participation des villages, diversité des activités, satisfaction, apprentissages, qualité de l’accueil, accès aux services, coopération et exécution des engagements.

Chaque objectif comporte une mesure, une cible, un responsable et un résultat constaté. Une appréciation qualitative conserve ses éléments de preuve. Les données manquantes restent inconnues ; le logiciel n’invente pas une satisfaction à partir des ventes.

Le bilan par élément associe contribution financière et contribution à ces objectifs. Il peut identifier un équipement déficitaire mais essentiel, ou une activité rentable à revoir. Les dépenses assumées pour l’expérience sont visibles et financées dans le format de base si elles sont indispensables.

### 4.6 La confirmation du format de base

L’événement est d’abord un produit présenté en teaser, puis un produit soumis à l’essai du marché, et enfin un produit confirmé. Le passage à la communication officielle dépend d’un dossier de confirmation conservé avec l’édition.

Le **format de base** est une version datée du scénario. Il comprend ce qui est nécessaire pour tenir la promesse : implantation, activités minimales, équipes, personnel, équipements, services, montage, démontage, communication, frais de préparation et rémunérations. Les recettes complémentaires du jour J financent les améliorations et le surplus attendu ; elles ne doivent pas être indispensables pour financer ce format.

Le seuil de rentabilité de base est réputé atteint dans le périmètre retenu lorsque les recettes acquises et affectables couvrent les charges complètes du format de base et la réserve d’aléas approuvée. Les revenus sont retenus nets des taxes reversées, frais déduits des encaissements et affectations qui ne financent pas ce périmètre. Toutes les prestations à livrer figurent dans les charges. La méthode évite de retrancher deux fois les mêmes frais ou charges. Aucun poste indispensable ne peut rester « à chiffrer » dans le budget soumis à confirmation.

Les sponsors espérés, intentions et volumes de vente non acquis sont exclus de ce test. Les prépaiements ne sont mobilisés qu’avec leurs engagements de livraison et obligations de remboursement pris en compte. Une souscription en attente de versement n’est pas assimilée à des fonds disponibles.

Le financement apporté par QAPAS est affiché séparément : il peut couvrir un besoin de trésorerie, mais ne devient pas une recette commerciale démontrant la rentabilité. Les aides affectées à un stand ne couvrent que les charges autorisées de ce stand.

| Condition de confirmation | Preuve à conserver |
|---|---|
| Base économique couverte | Budget complet, recettes vérifiées et réserve d’aléas, sans dépendance aux ventes futures du jour J |
| Trésorerie suffisante | Calendrier des paiements, fonds effectivement mobilisables, retenues et obligations couvertes |
| Objectifs minimaux atteints | Critères propres à l’édition et engagements confirmés |
| Capacité de réalisation | Site, moyens, prestataires et postes essentiels disponibles |
| Conditions de lancement satisfaites | Pièces, autorisations et décisions requises pour ce format |
| Décision QAPAS | Validation datée par l’organisateur et contrôle financier selon les mandats |

Le bénéfice cible et le seuil de rentabilité sont deux critères distincts. L’objectif financier QAPAS déjà défini reste visible ; s’il est fixé comme condition de lancement de l’édition, il doit aussi être satisfait. Il ne disparaît pas parce que le budget atteint simplement zéro.

La couverture de base permet une confirmation documentée ; elle n’est pas une garantie d’absence de toute perte future. Les risques résiduels — annulation, météo, défaillance, remboursement et dérive des coûts — restent suivis. Toute modification importante du scénario exige une nouvelle vérification.

Après confirmation, une dégradation ouvre une alerte et une décision explicite de maintien, réduction, report ou annulation. Le système ne rétrograde pas silencieusement les informations publiques. Le budget, le plan et les communications concernés conservent leur version.

### 4.7 Les mécanismes pour limiter les pertes

Les sources de praticiens consultées donnent plusieurs leviers concrets. Le guide de budget Eventbrite recommande de comparer prévision et dépenses, de distinguer coûts fixes et variables, de consulter plusieurs devis et de prévoir des imprévus. Son guide de financement insiste sur le décalage entre acomptes fournisseurs et réception des soutiens. Sources : [budget](https://www.eventbrite.com/resources/budgets/) et [financement](https://www.eventbrite.com/resources/budgets/event-fundraising/).

Eventbrite présente aussi la précommande de repas, boissons ou merchandising comme moyen d’ajuster l’approvisionnement à la demande. Sa documentation de versements montre que des sommes encaissées par la plateforme peuvent rester retenues ou n’être versées qu’après l’événement : ces ventes ne constituent donc pas nécessairement la trésorerie disponible pour le montage. Sources : [précommandes](https://www.eventbrite.com/blog/event-wont-sell-ds00/) et [versements](https://www.eventbrite.com/help/en-ie/articles/115913/set-up-a-payout-schedule/).

Howden Portugal présente des couvertures de coûts ou pertes de revenus lors d’annulation, report ou interruption, selon contrat, pour certaines circonstances hors du contrôle de l’organisateur. Cette présentation ne prouve pas que notre événement ou une insuffisance de ventes seraient couverts. Source : [assurance événementielle](https://www.howdengroup.com/pt-pt/cover/event-cancellation-non-appearance).

À partir de ces leviers, les fonctions suivantes sont proposées pour QAPAS. Elles ne constituent pas une prétendue méthode universelle de tous les organisateurs.

| Fonction | Utilité |
|---|---|
| Plafond avant confirmation | Limiter la dépense de teaser et d’essai du marché acceptée par QAPAS |
| Dates de décision | Revoir la viabilité avant chaque acompte ou dépense devenant irréversible |
| Format minimal et extensions | Financer la promesse de base avant d’ajouter les améliorations |
| Coûts de sortie par contrat | Connaître acompte perdu, frais d’annulation, report et restitution |
| Réserve d’aléas | Couvrir les risques identifiés ; montant justifié par l’édition, sans taux universel automatique |
| Précommandes et production progressive | Acheter ou produire selon la demande acquise et les délais réels |
| Calendrier de trésorerie | Distinguer vente, encaissement intermédiaire, versement disponible et règlement fournisseur |
| Limite de perte acceptée | Rendre visible l’exposition dans les scénarios testés et arrêter l’escalade de dépenses |
| Registre des risques | Documenter coût, réponse, responsable et éventuelle assurance confirmée |
| Comparaison maintien et arrêt | Comparer le coût supplémentaire de continuer avec celui de réduire, reporter ou annuler |

Le montant déjà dépensé ne justifie pas à lui seul une nouvelle dépense. À chaque date de décision, le dossier présente les engagements restants et la perte envisagée dans les scénarios explicitement testés. Un risque non quantifié reste signalé plutôt que transformé en faux plafond garanti.

### 4.8 Les indicateurs et les arbitrages

Les indicateurs sont définis par leur périmètre, unité, méthode, période, cible, responsable et source. Le suivi distingue prévision, engagement et constat. Les métriques commerciales portent sur contacts qualifiés, propositions, engagements conditionnels, confirmations et livraison ; les métriques visiteurs sur intérêt, venue, participation et satisfaction.

Le coût d'acquisition inclut les dépenses de campagne et le temps commercial retenu. Les contacts et commandes ne sont pas comptés plusieurs fois. Une origine déclarée ou un QR fournit une attribution documentée ; elle ne prouve pas à elle seule la causalité ni toutes les ventes indirectes.

Les deux lectures financière et événementielle restent séparées. Les services indispensables et les dépenses d'expérience sont financés explicitement. Chaque arbitrage indique décision, auteur, justification, budget et effet attendu. Les données de clôture nourrissent les futures éditions sans créer une reconduction automatique des contrats.

## 5 Le gestionnaire de communication

### 5.1 Les publics et les dossiers

Les segments comprennent visiteurs, participants, équipes, relais, exposants, sponsors, prestataires, bénévoles, services intervenants et presse. Chaque dossier garde l’origine de la demande, le besoin exprimé, le responsable QAPAS, le statut, la prochaine action et son échéance.

Une personne venue proposer un service garde ce parcours. Elle n’est pas redirigée automatiquement vers une équipe de village. L’identité commune n’accorde ni abonnement, ni rôle métier, ni autorisation de prospection par elle-même.

### 5.2 Les communications liées au plan

Une campagne peut concerner tout l’événement, une zone, un stand, un créneau ou un produit. Exemples : consigne de montage aux occupants d’une zone ; changement d’accès aux livreurs ; informations d’accueil aux visiteurs ; preuve de visibilité à un sponsor.

Les documents réutilisent les données validées du plan et des offres : fiche exposant, plan d’accès, mission de bénévole, inventaire technique, devis, kit sponsor, affiche et QR. Les tirages et versions restent identifiés pour savoir quels sponsors figurent sur chaque support.

Les changements du plan génèrent des propositions de communication ciblées, avec liste de destinataires et motif. L’application ne confond pas enregistrer une modification avec l’annonce effective du changement.

### 5.3 La production et les échéances

Pour le relais de référence, le kit comprend 250 flyers, un A3, trois A4 et trois A5 en couleur, avec délai demandé historiquement de 14 jours maximum à compter du paiement. La nouvelle offre conditionnelle articule ce délai avec la décision sous 21 jours selon le chapitre 3.12. Les commandes existantes gardent leurs engagements. Production, validation, impression, livraison et preuve sont suivies par version d’offre.

Les contacts, modèles, brouillons, validations, envois, échecs et reprises sont tracés. Les canaux initiaux sont courriel, documents imprimables et consignes publiées sur le frontend. SMS, messageries et réseaux sociaux dépendent de connecteurs explicitement configurés.

Les accusés de réception ne clôturent pas les dossiers. Les permissions de contact, désinscriptions et préférences sont suivies par finalité ; les informations nécessaires à l’exécution d’une commande ne sont pas mélangées aux campagnes commerciales.

### 5.4 Le suivi des prestations et des obligations

Le gestionnaire suit dates limites de logos, acceptation des visuels, publication, fabrication, pose et photos de réalisation. Une promesse « visible sur tous les stands » peut être contrôlée avec la liste des stands concernés.

Les équipes et partenaires voient les informations utiles à leur mission. Les comptes de gestion détaillés et coordonnées privées suivent des droits propres. Les actions préparées par une assistance IA restent des propositions identifiées, avec validation et attribution selon leur effet.

### 5.5 Les trois phases et leurs affiches

L’événement possède **trois phases normales de mise sur le marché**. Elles sont indépendantes des phases de terrain — montage, ouverture et démontage — et des statuts individuels de candidature ou commande. Une commande confirmée ne confirme pas à elle seule l’événement.

| Phase | Produit présenté | Affiche et message central | Action proposée |
|---|---|---|---|
| 1 Teaser | Concept, identité, promesse et possibilités pressenties | Teaser : découvrir ce qui se prépare | Découvrir la visite, exprimer un intérêt, suivre les nouvelles |
| 2 Candidatures ouvertes | Produit à l’essai sur le marché, soumis aux conditions de lancement | Candidatures ouvertes — en route pour l’événement | Candidater, constituer une équipe, proposer un stand ou soutenir une offre définie |
| 3 Communication officielle | Format de base confirmé après couverture économique et critères de lancement | Événement confirmé : dates, lieu et contenu officiellement annoncés | Préparer sa visite, consulter le programme et accéder aux offres restantes |

Les premières précommandes de phase 2 déclenchent l’échéance commune de 21 jours du chapitre 3.7. La phase 2 n’est pas un simple compte à rebours promotionnel. Elle sert à observer l’intérêt, recevoir les candidatures, vérifier la capacité de production et acquérir les engagements nécessaires. Le tableau de bord sépare visites, intérêts, candidatures admissibles, engagements et encaissements.

La phase 3 n’est ni déclenchée par une date seule, ni par le nombre de clics, ni par un objectif de recettes encore espéré. Elle exige le dossier de confirmation du chapitre 4.6. Les contenus annonçant l’événement comme confirmé ne peuvent être publiés tant que ces conditions ne sont pas satisfaites.

Chaque campagne conserve phase, affiche source, langue, visuel, texte, version du plan public, appels à l’action, date, tirage et supports diffusés. Les trois affiches font partie de la même identité graphique ; elles indiquent exactement le statut de la promesse à leur date de production.

L’affiche, le hero, la visite, les fiches, la FAQ, les courriels et les contenus sociaux utilisent le même état de communication et les mêmes informations de référence. Les documents imprimés restent datés ; leurs QR renvoient vers une page donnant l’état actuel. Un report ou une annulation est une communication exceptionnelle explicite, pas une quatrième phase commerciale implicite.

### 5.6 Les textes des trois phases

Les formulations ci-dessous sont de nouveaux brouillons proposés pour le CDC. Elles ne sont pas présentées comme des affiches déjà approuvées. Les textes riches existants sont repris selon le chapitre 5.7 ; les lieux, dates et conditions sont injectés uniquement lorsqu’ils sont renseignés et publiables.

| Phase | Texte français proposé | Texte portugais proposé |
|---|---|---|
| Teaser | **A Forqua de Ouro se prépare.** Villages, saveurs, défis et rencontres : chacun y trouvera sa place. Découvrez ce que nous imaginons et dites-nous comment vous aimeriez y participer. Dates et programme seront annoncés au fil de la préparation. | **A Forqua de Ouro está a preparar-se.** Aldeias, sabores, desafios e encontros: há lugar para toda a gente. Descubra o que estamos a imaginar e diga-nos como gostaria de participar. As datas e o programa serão anunciados à medida que a preparação avançar. |
| Candidatures | **Les candidatures sont ouvertes. En route pour A Forqua de Ouro.** Rejoignez une équipe, proposez un stand ou contribuez à l’organisation. Découvrez les possibilités et leurs conditions. L’événement sera confirmé lorsque les objectifs de lancement seront atteints ; une candidature seule ne vaut pas confirmation. | **Candidaturas abertas. A caminho de A Forqua de Ouro.** Junte-se a uma equipa, proponha uma banca ou contribua para a organização. Conheça as possibilidades e as condições. O evento será confirmado quando os objetivos de lançamento forem atingidos; a candidatura, por si só, não é uma confirmação. |
| Officielle | **A Forqua de Ouro est confirmée.** Retrouvez les dates, le lieu, les villages, les activités et les informations pratiques dans la visite guidée. Découvrez ce qui reste disponible et préparez votre venue. L’entrée est libre pour cette édition. | **A Forqua de Ouro está confirmada.** Consulte as datas, o local, as aldeias, as atividades e as informações práticas na visita guiada. Descubra o que ainda está disponível e prepare a sua visita. A entrada é livre nesta edição. |

Les boutons proposés suivent ces phases : « Découvrir ce qui se prépare », « Voir les candidatures ouvertes », puis « Préparer ma visite ». Ils complètent les actions propres à chaque élément. L’entrée libre est reprise du profil Farmers Games ; elle n’est pas automatiquement appliquée à tout futur événement.

### 5.7 La reprise des bons textes et de la FAQ

L’affiche, le guide et la FAQ de l’application précédente constituent une base éditoriale réelle. Les fichiers suivants ont été consultés le 1er octobre 2026 : [games_poster.php](https://github.com/okeala/qapas-farmers-games/blob/master/config/games_poster.php), [games_guide_flow.php](https://github.com/okeala/qapas-farmers-games/blob/master/config/games_guide_flow.php), [games_faq.php](https://github.com/okeala/qapas-farmers-games/blob/master/config/games_faq.php) et le [cartaz imprimable](https://github.com/okeala/qapas-farmers-games/blob/master/resources/views/games/documents/poster.blade.php).

L’affiche conserve notamment la formule française « Villages, saveurs, défis et rencontres : chacun y trouvera sa place. » et sa version portugaise. Le guide fournit déjà les parcours jouer, tenir un stand, cuisiner, exposer, visiter ou aider. La FAQ couvre dates et lieu, équipes, défis, relais, financement, sponsors, équilibre du stand, cuisine, devenir des abris, documents, dépenses et nettoyage.

Ces textes sont inventoriés avec source, langue, audience, éléments du plan concernés, phase de validité et état de relecture. Les corrections et traductions déjà effectuées sont reprises. Les formulations contradictoires entre un support figé et une page courante sont réconciliées ; aucun texte ancien n’est déclaré encore valable sans contrôle de ses prix, dates et conditions.

Le contenu est géré en blocs réutilisables : promesse de l’événement, présentation d’une activité, offre, explication, réponse FAQ et appel à l’action. Les pages, visites et affiches réemploient les blocs approuvés. La rédaction administrative, les états techniques et les détails du calcul ne remplacent pas un texte accueillant et compréhensible.

Les questions suivantes complètent le corpus existant ; leurs réponses proposées expriment les règles de la nouvelle version.

| Question | Réponse proposée |
|---|---|
| L’événement est-il déjà confirmé ? | Le statut affiché en haut de la page l’indique. Pendant le teaser et l’ouverture des candidatures, l’événement se prépare. Sa confirmation est annoncée après atteinte des conditions de lancement. |
| Ma candidature me donne-t-elle déjà une place ? | Elle permet à QAPAS d’examiner votre proposition. La suite précise les conditions et vous indique quand votre participation est confirmée. |
| Pourquoi certaines activités sont-elles encore indiquées comme pressenties ? | La visite présente aussi ce qui est en préparation. Une activité n’est annoncée comme confirmée que lorsque les moyens et engagements nécessaires sont réunis. |
| Que puis-je encore acheter ou proposer ? | Chaque fiche indique les offres encore disponibles et la bonne démarche : candidature, demande d’offre ou achat. |
| Un paiement garantit-il à lui seul la tenue de l’événement ? | Il confirme la commande selon ses conditions. La tenue de l’événement dépend aussi de la confirmation générale annoncée par QAPAS ; les conditions de report ou de non-confirmation sont indiquées avant paiement. |
| Une activité gratuite rapporte-t-elle quelque chose à l’événement ? | Elle peut être essentielle à la découverte, au plaisir ou à l’accueil. Son coût est prévu dans l’organisation même lorsqu’elle ne génère pas de vente directe. |

### 5.8 Le suivi commercial et la livraison

Le gestionnaire de communication suit le parcours contact, qualification, proposition, engagement conditionnel, confirmation, prestation livrée et retour client. Chaque dossier conserve public, source, offre, phase de communication, responsable, prochaine action et échéance. Une candidature demeure une candidature jusqu'à une décision explicite ; son dépôt ne démarre pas le délai de précommande.

Le parcours visiteur suit découverte, intérêt, préparation, présence, participation et satisfaction avec des mesures appropriées. Il respecte l'entrée libre du profil Farmers Games et ne crée aucune inscription obligatoire pour visiter.

Les campagnes ont un budget, un objectif, des supports, des preuves et des résultats. La communication sponsor documente la visibilité promise et livrée, les observations disponibles et le retour du partenaire. Une publication externe espérée n'est jamais présentée comme obtenue.

Les trois phases gardent une source de vérité commune. La fiche d'offre, les affiches, la visite, les conditions et les traductions publiées doivent donner le même état, le même prix applicable et la même échéance de décision.

## 6 Les produits et le merchandising

### 6.1 Le catalogue et les points de vente

Le catalogue couvre vêtements, accessoires, souvenirs, produits agricoles vendus par QAPAS, repas, boissons et autres produits autorisés par l’édition. Il gère variantes, référence, images, prix, taxes paramétrées, coût d’achat ou de fabrication, conditionnement et fournisseur.

Les points de vente sont des éléments du plan. Un produit peut être disponible sur plusieurs points ; le stock est réparti entre dépôt et points de vente. Les ventes réalisées alimentent directement les recettes de ces éléments.

Une offre de prestation — location d’un stand ou sponsoring — reste distincte d’un article de stock. Un kit peut regrouper des articles, sans créer artificiellement un stock supplémentaire des composants.

### 6.2 Les mouvements et les coûts

Les mouvements comprennent achat, réception, transfert, réservation, vente, retour, casse, perte, consommation interne et inventaire. Une réservation n’est ni une vente ni une sortie financière. Les seuils et quantités disponibles tiennent compte des réservations actives.

Le coût d’achat de stock et le coût consommé par les produits vendus suivent deux vues distinctes : trésorerie et résultat. Les marchandises invendues restent identifiées ; leur achat n’est pas compté une seconde fois à travers un coût des ventes mal rapproché.

Les matières et consommables d’un produit fabriqué peuvent être décrits dans une composition. Des consommations internes — repas de personnel, cadeau à un sponsor, récompense — sont imputées à leur destination, sans recette fictive.

### 6.3 La vente et le rapprochement

Le minimum prévoit un suivi des quantités, ventes importées ou saisies, moyens de paiement et clôture de caisse. La boutique en ligne et une caisse connectée constituent un lot distinct si elles sont retenues ; un écran de stock ne sera pas présenté comme un terminal de paiement opérationnel.

Chaque vente possède un vendeur, un point de vente, une quantité, un prix, une date et un identifiant externe éventuel. Annulations et remboursements corrigent les ventes et le stock selon ce qui s’est réellement passé. Un retour financier ne remet pas automatiquement un produit consommé en stock.

Les systèmes de paiement et de facturation fournissent leurs confirmations ; les importations et reprises utilisent des identifiants uniques pour éviter les doubles recettes.

### 6.4 Les tiers et la propriété

Les produits des exposants indépendants restent leurs ventes et leur stock ; aucune commission QAPAS n’est ajoutée. Une prestation tierce apparaît avec son propre vendeur.

La fabrication ou cession d’un abri réutilisable est une opération différente de sa location. Une vente de cet équipement doit modifier sa disponibilité future et conserver la propriété et les pièces correspondantes.

## 7 La conservation de la référence et la transition

Le point de référence historique identifié le 1er octobre 2026 est le commit `09870bd772711420a1af35e96eab800847ece841` de `okeala/qapas-farmers-games`. La branche `reference/non-aboutie-2026-10-01` en conserve l'état, avec dépendances, documentation et migrations. Ce commit immuable identifie la référence non aboutie ; une branche reste techniquement déplaçable. Les données réelles et leurs sauvegardes demeurent séparées.

Le nom de tag peut suivre la convention du dépôt, par exemple `reference/non-aboutie-2026-10-01`. Le numéro de version final ne sera choisi qu’après lecture des tags existants. Le commit mentionné dans l’audit du 29 septembre n’est pas supposé être le dernier état du dépôt.

La nouvelle version repart d'une application Laravel fraîche dans une branche dédiée du dépôt. Le socle commun QAPAS est adopté intégralement. Les règles, textes et offres utiles de Farmers Games alimentent le moteur générique ou le seeder de démonstration selon leur responsabilité ; le code applicatif historique ne sert plus de base à prolonger.

La reprise établit un tableau **conservé / transformé / abandonné / non réalisé**, fondé sur le code courant. Une demande historique ne sera pas déclarée implémentée simplement parce qu’elle figure dans un document.

La migration est d’abord répétée sur une copie, avec rapport de correspondance et rapprochement des totaux. Les commandes et pièces financières déjà enregistrées gardent leurs identifiants et montants. Le basculement dépend de la recette de la nouvelle version ; il n’est pas autorisé par la rédaction de ce CDC.

## 8 Le socle technique et les données

### 8.1 L’intégration QAPAS

Laravel 13 porte les règles métier, validations, permissions, calculs et publications. Filament 5 accueille une page de dessin dédiée et les modules de gestion ; Livewire orchestre les formulaires et panneaux. Leaflet assure le rendu du plan dans les deux interfaces.

Le composant de carte est isolé des rerendus qui le recréeraient à chaque changement de formulaire. Les modifications passent par des échanges structurés et des sauvegardes limitées aux éléments concernés. Le calcul financier et la validation des droits restent côté serveur.

qapas-application fournit le chrome partagé et les mises à jour de socle. qapas-platform fournit l’identité interapplications via le contrat Passport. L’authentification ne donne pas automatiquement les droits d’organisateur ou de comptable. Une configuration locale d’identité incomplète doit être signalée sans se présenter comme une connexion interapplications testée.

### 8.2 Les géométries et l’éditeur

Les géométries sont conservées en GeoJSON avec identifiant stable. Les paramètres du gabarit — dimensions, rayon, nombre de côtés, angle — sont enregistrés également pour préserver sa régularité après édition. Les cercles sont exportés avec centre/rayon et une représentation polygonale documentée lorsqu’un consommateur GeoJSON le demande.

Le dessin peut s’appuyer sur Leaflet-Geoman, dont la [documentation officielle](https://geoman.io/docs/leaflet) décrit les outils de dessin et d’édition. Le choix final exige une vérification des fonctions et licences : les fonctions payantes ne seront pas supposées présentes dans la version libre. Les contraintes de gabarit et le lien métier restent développés pour QAPAS.

La [documentation Leaflet](https://leafletjs.com/reference) décrit le rendu des polygones, lignes et GeoJSON. Les rectangles orientés nécessitent une empreinte polygonale pilotée par des dimensions métriques ; un simple rectangle géographique ne répond pas à lui seul au besoin.

### 8.3 Les responsabilités des données

| Ensemble | Responsabilité |
|---|---|
| Événement et édition | Calendrier, organisateur, profils métier, paramètres et référence Platform |
| Site et scénario | Périmètre, fond de carte, variantes, phases et hypothèses |
| Élément du plan | Géométrie, catégorie, gabarit, calendrier, responsable, 4P, utilité et fiche publique |
| Ressource | Équipement physique, propriétaire, stock, réservations et utilisations |
| Besoin et affectation | Demande technique, fournisseur, personnel, poste et créneau |
| Coût et pièce | Prévision, engagement, facture, règlement et ventilation |
| Offre et recette | Capacité vendable, prix versionné, commande, encaissement et remboursement |
| Fonds affecté | Origine, destinataire prévu, consommation et justification |
| Produit et stock | Variante, mouvements, point de vente et transactions |
| Communication | Phase teaser, candidatures ou officielle, public, finalité, affiche, campagne, validation et suivi |
| Révision et publication | Instantané du plan, références financières, auteur, date et profil de diffusion |
| Visite et contenu éditorial | Parcours, étapes liées au plan, explications, FAQ, langues et validité par phase |
| Précommande conditionnelle | Signature, échéance commune, montant réservé, décision, restitution et accord de report |

Le plan et la fiche économique partagent les mêmes identifiants. La géométrie ne constitue pas un second inventaire séparé des équipements.

### 8.4 Les droits et les publications

Les rôles initiaux sont administrateur QAPAS, organisateur, dessinateur, responsable de zone, financier/comptable, responsable de communication, prestataire limité et lecteur public. Les équipes ne voient que leurs dossiers et informations partagées.

Le public reçoit une projection générée par une liste explicite de champs autorisés. Les données privées sont absentes de la réponse serveur, y compris pour les objets masqués. Les exports reprennent les mêmes règles.

Une sauvegarde concurrente détecte les révisions incompatibles plutôt que d’écraser le travail d’un autre utilisateur. Les paiements, importations et affectations financières disposent de contrôles empêchant les doublons. Les objets facturés ou payés ne sont pas supprimés physiquement.

### 8.5 Les exigences d’usage

L’édition doit rester fluide avec un jeu de référence de 1 000 éléments et des calques sélectifs. La recette mesure le chargement, la sélection et la sauvegarde sur un environnement défini ; aucun délai universel n’est annoncé sans cette mesure.

L’utilisateur voit les états de sauvegarde et peut retrouver les dernières révisions. La liste publique, les fiches et commandes usuelles restent utilisables au clavier. Les libellés publics existent en portugais, français et anglais selon le socle QAPAS. La légende, les unités, l’orientation et les dates sont explicites.

### 8.6 La nouvelle application et son projet de démonstration

La réalisation repart d'une application Laravel 13 fraîche adoptant le socle complet QAPAS, Livewire, Filament 5, Passport et Leaflet. Le code métier antérieur constitue une référence historique. La nouvelle architecture conserve les contrats communs et reprend les règles utiles sous forme générique, avec tests ; elle ne dépend pas des anciennes migrations métier.

| Couche | Contenu |
|---|---|
| Socle QAPAS | Identité, navigation, administration, styles, contrats, mises à niveau et droits |
| Moteur événementiel | Plans, ressources, 4P, offres, coûts, recettes, bilan, communications, produits et décisions |
| Catalogue commun | Catégories, sous-catégories, gabarits et types de postes personnalisables |
| Projet Farmers Games | Villages, activités, offres, tarifs, textes, hypothèses et besoins logistiques |
| Fixtures de test | Transactions et cas limites entièrement simulés, sans encaissement réel |

Les données Farmers Games sont déclarées dans un jeu versionné et installées par un seeder explicite, idempotent, réservé au développement et aux tests. Le seeder conserve provenance, date et statut connu, hypothèse, à chiffrer ou à confirmer. Il ne crée ni paiements réels, ni confirmations, ni consentements, ni autorisations.

Une installation sans démonstration ne contient aucun événement imposé. Un second événement neutre doit pouvoir être créé sans modifier le code. Les objectifs de villages, le nombre de stands, l'entrée libre, les tarifs et les règles commerciales particulières sont des données du profil, avec les unités et conditions appropriées.

Les textes existants, explications et FAQ sont repris comme données éditoriales sourcées, avec contrôle de leur phase et de leurs conditions. La démonstration sert de référence fonctionnelle reproductible pour développer et vérifier les six modules. Elle ne constitue pas une édition confirmée.

La nouvelle application utilise une base vierge. Aucun effacement, conversion ou remplacement silencieux de données réelles n'est effectué. Une reprise éventuelle d'historique financier constitue une opération distincte, documentée et répétée sur copie avant basculement.

## 9 La recette et les lots de réalisation

### 9.1 Les cas de réception

| Cas | Résultat attendu |
|---|---|
| Dessiner un stand carré de 2,40 m | Empreinte contrainte de 5,76 m², rotation conservant la forme et fiche de coûts liée |
| Délimiter une zone de terrain | Polygone irrégulier modifiable, couleur saisissable, surface calculée et exclusion possible |
| Créer un accès | Emprise polygonale avec axe, largeur, longueur et période d’usage |
| Dupliquer dix stands | Dix nouvelles implantations et prévisions ; aucune copie de paiement, facture ou personne |
| Agrandir un élément déjà commandé | Prévision et écart actualisés ; commande et facture antérieures conservées |
| Alimenter six stands par un coffret | Une ressource physique et un achat, avec usages et ventilation explicites |
| Retirer un stand déjà payé | Retrait du plan retenu, historique et circuit financier conservés |
| Affecter deux postes simultanés à la même personne | Conflit signalé ; aucun double coût automatique |
| Répartir un sponsoring sur trois objets | Un encaissement, ventilation totale contrôlée et aide appliquée une seule fois |
| Vendre un produit à deux points de vente | Stocks par lieu et recettes par élément rapprochés ; coût d’achat non doublé |
| Comparer deux scénarios | Deux résultats séparés, sans cumul des alternatives |
| Publier une carte | Frontend QAPAS interactif ; données financières et privées absentes du flux public |
| Modifier un brouillon publié | Publication antérieure inchangée jusqu’à nouvelle publication explicite |
| Exporter le plan public | Même périmètre de diffusion que le frontend, version et légende visibles |
| Migrer les données de référence | Rapport de correspondance, totaux rapprochés et pièces historiques préservées |
| Compléter les 4P d’un stand | Produit, prix, distribution et promotion renseignés ; gratuité visiteur distincte du tarif exposant |
| Examiner une activité gratuite | Coût et contribution à la réussite visibles séparément ; aucun retrait automatique pour marge négative |
| Composer une visite guidée | Étapes synchronisées avec les objets publics, textes riches, FAQ et offres disponibles |
| Épuiser un produit ou un emplacement | Disponibilité actualisée et action d’achat retirée sans supprimer l’histoire du lieu |
| Publier en phase de candidatures | Affiche, hero, visite et messages portent le même statut, sans annonce de tenue confirmée |
| Demander la communication officielle | Blocage si budget de base incomplet, trésorerie insuffisante ou objectifs minimaux non satisfaits |
| Tester une baisse des ventes du jour J | Couverture du format de base conservée ; surplus prévisionnel corrigé |
| Consulter une affiche antérieure | Campagne datée conservée ; QR donnant accès à l’état courant |
| Reprendre les textes existants | Sources, langues et prix vérifiés ; modifications adaptées à chaque phase |
| Dépasser le plafond de dépenses avant confirmation | Nouvelle dépense signalée et soumise à la décision habilitée |
| Signer la première précommande | Échéance commune fixée à 21 jours calendaires maximum, visible dans les conditions |
| Signer une commande plus tard | Même échéance, sans redémarrage du délai |
| Laisser expirer le délai sans confirmation | Non-confirmation et remboursements déclenchés ; aucune reconduction silencieuse |
| Refuser un report à l’année suivante | Remboursement prévu maintenu, sans avoir imposé |
| Rembourser une précommande payée | Montant intégral restituable, frais financés par QAPAS et statut réel suivi |
| Confirmer et rembourser au même moment | Décision cohérente et absence de double traitement contrôlées |
| Installer sans démo | Aucun événement Farmers Games ni tarif spécifique imposé |
| Seeder deux fois la démo | Jeu reproductible sans doublons ni écrasement d'une commande réelle |
| Créer un autre événement | Même moteur et mêmes six modules sans branche conditionnelle Farmers Games |
| Tester une offre auprès de deux publics | Promesses, prix, preuves et décisions distincts sur une implantation unique |
| Consulter un indicateur sans observation | État inconnu ou hypothèse ; aucune valeur constatée fabriquée |
| Suivre une candidature | Dossier commercial et prochaine action ; aucune précommande automatiquement signée |
| Changer le prix d'une offre | Nouvelles propositions actualisées, commandes existantes inchangées |
| Clôturer une campagne | Coûts, engagements et preuves de livraison rapprochés |
| Modifier une géométrie contrainte | Gabarit carré ou régulier conservé et contrôle serveur |
| Consulter un plan de démo hors développement | Accès refusé selon politique de démonstration |

### 9.2 Les lots

| Lot | Livraison | Dépendance |
|---|---|---|
| 0 | Gel de la référence, inventaire du code, règles métier et données de reprise | Accès au dépôt courant |
| 1 | Événement, scénario, catalogue, éditeur Leaflet, gabarits et zones | Lot 0 |
| 2 | Ressources, coûts, quantités, workforce et clés de partage | Lot 1 |
| 3 | Offres, recettes, sponsoring et consolidateur | Lot 2 |
| 4 | Publication frontend QAPAS, projection publique et exports | Lot 1 ; données validées des lots 2 et 3 |
| 5 | Communication, documents, suivis et prestations | Lots 1 et 3 |
| 6 | Produits, stocks, ventes et rapprochement | Lots 2 et 3 |
| 7 | Migration répétée, recette complète et basculement | Lots précédents |

La première tranche utilisable doit permettre de dessiner un petit événement, renseigner ses 4P, chiffrer ses besoins, comparer ses recettes, lire son bilan financier et ses objectifs de réussite, puis publier une visite guidée adaptée à sa phase. Les précommandes, l’échéance commune de 21 jours et les remboursements font partie du parcours commercial minimal. Les communications et produits sont conçus dès le départ, puis livrés sans interrompre cette chaîne.

### 9.3 Les paramètres encore ouverts

Le CDC peut servir de base de développement sans fixer arbitrairement les devis, le vendeur de la cuisine collective, le partage de certaines recettes, les honoraires comptables ou la puissance électrique disponible. Ces sujets deviennent des paramètres ou dossiers à compléter, avec état visible.

Le fond cartographique, les fonctions libres ou payantes du dessinateur et les exigences d’impression seront arrêtés après essai technique. La caisse connectée et la boutique en ligne restent un lot conditionnel ; le suivi des produits et des ventes fait partie du périmètre demandé.

La localisation finale, les autorisations, les horaires et le stationnement du profil Farmers Games conservent leur statut de préparation. Les dates des 12 et 13 décembre 2026 restent envisagées dans la consolidation, sans être présentées ici comme confirmées.

## 10 Les sources et le statut des choix

La consolidation de référence a été relue dans sa version du 29 septembre 2026 : [Consolidation Farmers Games](library-file:file_000000007d348210818bcd91e35e07c9). Elle fournit les décisions de prix, de gouvernance et de financement citées ici. Certaines propositions y restent ouvertes et sont signalées comme telles dans ce CDC.

Les demandes logistiques concernant le broyeur, le Dyna, le sisal, les mimosas et les raccordements viennent des échanges du projet du 29 septembre. Elles sont reprises comme besoins à documenter, sans devis nouveau ni preuve d’implémentation.

Références de conception consultées le 1er octobre 2026 : [OnePlan Festivals](https://www.oneplan.io/fr/festivals/), [Leaflet](https://leafletjs.com/reference) et [Leaflet Geoman](https://geoman.io/docs/leaflet).

Sources ajoutées pour la version 0.2, consultées le 1er octobre 2026 : [budget Eventbrite](https://www.eventbrite.com/resources/budgets/), [financement et calendrier](https://www.eventbrite.com/resources/budgets/event-fundraising/), [précommandes](https://www.eventbrite.com/blog/event-wont-sell-ds00/), [versements aux organisateurs](https://www.eventbrite.com/help/en-ie/articles/115913/set-up-a-payout-schedule/), [Howden Portugal](https://www.howdengroup.com/pt-pt/cover/event-cancellation-non-appearance), [conditions contractuelles dans le Diário da República](https://diariodarepublica.pt/dr/lexionario/termo/condicao-negocio-juridico), [AT article 36](https://info.portaldasfinancas.gov.pt/pt/informacao_fiscal/codigos_tributarios/civa_rep/Pages/iva36.aspx), [AT article 78](https://info.portaldasfinancas.gov.pt/pt/informacao_fiscal/codigos_tributarios/civa_rep/Pages/iva78.aspx), [autorisations Stripe](https://docs.stripe.com/payments/place-a-hold-on-a-payment-method) et [remboursements Stripe](https://docs.stripe.com/refunds). Les règles QAPAS proposées sont distinguées des règles ou pratiques décrites par ces sources.

Les catalogues de sous-catégories, les écrans, les entités, les règles de version du plan et les lots de réalisation constituent la proposition de nouvelle conception. Ce CDC n’ajoute aucune promesse commerciale aux commandes existantes et ne modifie pas l’application actuelle.
