# Calculs internes · stand en tubes de 30 mm et toit à deux pans

**État : non dimensionné ; comparaison mécanique chiffrée.** QAPAS, 30 septembre 2026. Cette note fournit des calculs reproductibles, sans contrat ni attestation d’ingénieur imposés. Le modèle décrit une géométrie hypothétique ; il ne qualifie pas encore les matériaux, raccords, ancrages et charges du site réel.

## Ce que nous étudions

Le gabarit extérieur reste de 2,40 m en largeur et hauteur, avec allongement libre. Pour le premier calcul, on prend un toit symétrique à deux pans, des tubes acier circulaires idéaux de diamètre extérieur 30 mm, et plusieurs épaisseurs/espacements. L’épaisseur de référence de 2 mm, les égouts à 2 m et les masses de toiture sont **des hypothèses**, pas des mesures du matériel disponible.

Chaque portique hypothétique comporte deux poteaux, deux rampants et **un tirant horizontal à hauteur des égouts**, reliant les deux pieds de toiture. Le modèle suppose des articulations aux égouts et au faîtage, un tirant continu sans glissement, et des égouts maintenus latéralement par un contreventement extérieur au modèle. Le faîtage n’est pas supposé porté par un poteau central. La charge de toiture est uniforme, symétrique et transmise directement aux rampants.

Ce modèle ne convient pas tel quel à un toit dépourvu de tirant, un tirant remonté, des raccords excentrés, des débords, des appuis mobiles, des charges dissymétriques ou une toiture reportant sa charge sur des pannes intermédiaires. Les lisses longitudinales sont comptées dans l’inventaire partiel, **pas vérifiées en flexion**. Les panneaux, cannes, mimosa, sisal, paille et leurs fixations restent aussi à vérifier ; aucune rigidité stabilisatrice du plessis n’est présumée.

## Formules et unités

Calculs de section avec D, d, t en mm :

- Diamètre intérieur : `d = D − 2t`.
- Aire métallique : `A = π(D² − d²)/4`, en mm².
- Moment quadratique : `I = π(D⁴ − d⁴)/64`, en mm⁴.
- Module élastique : `W = 2I/D`, en mm³.
- Masse linéaire : `μ = A × 10⁻⁶ × ρ`, en kg/m.

Pour le toit, b est la largeur, h la montée entre égout et faîtage, s la largeur de toiture reprise par le portique, m la masse par m² **de surface inclinée** :

- Longueur d’un rampant : `ℓ = √((b/2)² + h²)` ; `θ = atan(2h/b)`.
- Charge verticale par mètre de rampant : `qv = (m × s + μ) × g`, en N/m. Le poids propre du tube s’ajoute à la couverture.
- Composante normale : `qn = qv × cos(θ)`.
- Réaction verticale à chaque égout : `P = qv × ℓ` ; la réaction verticale au faîtage est nulle par symétrie.
- Traction du tirant : `T = P × b/(4h)`. Équilibre d’un demi-toit autour du faîtage : `P × b/2 − P × b/4 − T × h = 0`.
- Compression maximale du rampant, au pied : `Nmax = T × cos(θ) + P × sin(θ)`.
- Moment maximal de flexion : `Mmax = qn × ℓ²/8`, en N·m.
- Contrainte de flexion : `σb = Mmax × 1000/W`, en MPa. L’enveloppe `Nmax/A + σb` borne les contraintes au premier ordre en superposant deux maxima, sans être une vérification de stabilité.
- Flèche locale de flexion : `δ = 5qnℓ⁴/(384EI)`. Utiliser ici E en N/m² et I en m⁴ ; convertir le résultat de m en mm.

La flèche utilise la théorie élastique des petites déformations, par rapport à la droite reliant les extrémités du rampant. La compression, le déplacement des nœuds et la souplesse des raccords nécessitent une analyse complémentaire ; la flèche locale ne décrit pas à elle seule la déformation du toit.

Pour un poteau idéal : `Ncr = π²EI/(K × L)²`. Pour les actions illustratives de pression nette p : force sur le mur `F = p × longueur × hauteur d’égout`, moment au sol `F × hauteur d’égout/2`, et soulèvement vertical symétrique du toit `U = p × largeur × longueur`. Ce sont des actions brutes, pas des résistances.

<!-- BEGIN GENERATED CALCULATIONS -->
## Résultats recalculables · hypothèses non mesurées

Ces tableaux décrivent les cas théoriques ci-dessous, sans charge admissible, entraxe recommandé ou validation du stand. Les charges sont non majorées.

Tube acier idéal : **30,00 × 2,00 mm** ; E = 210000 MPa ; masse volumique = 7850 kg/m³ ; g = 9,81 m/s².

Largeur 2,40 m ; faîtage 2,40 m ; égouts 2,00 m ; longueur 2,40 m. Cela donne une montée de 0,40 m, une pente de **18,43°**, un rampant de **1,265 m** et 6,07 m² de toiture réelle. Ces cotes d’axe sont un modèle : l’épaisseur de couverture est encore à déduire du gabarit extérieur de 2,40 m.

### Section et poids du tube de 30 mm

| Épaisseur (mm) | Masse (kg/m) | A (mm²) | I (mm⁴) | W (mm³) |
|---:|---:|---:|---:|---:|
| 1,00 | 0,715 | 91,11 | 9588,93 | 639,26 |
| 1,50 | 1,054 | 134,30 | 13673,73 | 911,58 |
| 2,00 | 1,381 | 175,93 | 17329,03 | 1155,27 |
| 3,00 | 1,998 | 254,47 | 23474,77 | 1564,98 |

La seule barre de 2,40 m en 30,00 × 2,00 mm pèse **3,31 kg**.

### Espacement des portiques et flexion des rampants

Charge de toiture hypothétique : **20,00 kg par m² de pente**, plus poids du tube rampant. L’espacement est ici la largeur de toiture reprise par un portique intérieur. Les portiques d’extrémité reprennent en général une demi-travée, hors débord. Aucun débord dans ce modèle.

| Largeur reprise (m) | Moment (N·m) | Contrainte de flexion (MPa) | Flèche locale (mm) | Traction du tirant (N) | Réaction verticale à chaque égout (N) |
|---:|---:|---:|---:|---:|---:|
| 0,60 | 24,91 | 21,56 | 1,14 | 249,06 | 166,04 |
| 0,90 | 36,07 | 31,23 | 1,65 | 360,74 | 240,50 |
| 1,20 | 47,24 | 40,89 | 2,16 | 472,42 | 314,95 |
| 1,80 | 69,58 | 60,23 | 3,19 | 695,78 | 463,85 |
| 2,40 | 91,91 | 79,56 | 4,21 | 919,14 | 612,76 |

La flèche est la flexion locale du rampant par rapport à sa corde. Elle n’inclut ni déplacement des appuis, ni allongement du tirant, ni glissement des raccords, ni amplification par compression. Ce n’est pas le déplacement global du toit.

Dans le cas de référence à 1,20 m : charge verticale 248,99 N/m ; compression maximale du rampant **547,77 N** ; enveloppe élastique Nmax/A + Mmax/W = **44,01 MPa**. Cette enveloppe au premier ordre n’est pas une vérification de résistance ou de flambement ; aucune nuance d’acier réelle n’est supposée certifiée.

### Sensibilité au poids sec ou mouillé

Même tube, même géométrie, même largeur reprise de 1,20 m. Les trois masses sont des hypothèses de sensibilité ; aucune ne constitue une mesure sèche ou mouillée du plessis.

| Toiture (kg/m² de pente) | Moment (N·m) | Flèche locale (mm) | Traction du tirant (N) |
|---:|---:|---:|---:|
| 10,00 | 24,91 | 1,14 | 249,06 |
| 20,00 | 47,24 | 2,16 | 472,42 |
| 30,00 | 69,58 | 3,19 | 695,78 |

### Masse partielle du stand

Pour la longueur de 2,40 m avec 3 portiques répartis à 1,20 m : deux poteaux, deux rampants et un tirant par portique ; trois lisses longitudinales. Soit **33,99 m de tube = 46,94 kg**, et **121,43 kg** pour la toiture hypothétique.

Inventaire partiel : ne comprend pas les raccords, diagonales, ancrages, bardages, fixations, lisses supplémentaires ni décors. Il ne faut pas le traiter comme le poids total ni comme un lest mobilisable. Les charges de ces éléments restent à ajouter à leur véritable chemin de reprise.

### Flambement idéal d’un poteau

| Facteur de longueur efficace K | Charge critique d’Euler (N) |
|---:|---:|
| 1,00 | 8979,11 |
| 2,00 | 2244,78 |

Longueur physique 2,00 m. K = 1 illustre des extrémités articulées effectivement maintenues latéralement ; K = 2 illustre un encastrement parfait en pied avec sommet libre. Ces conditions ne sont pas acquises pour le stand. Euler décrit un poteau idéal : **ces nombres ne sont pas des charges autorisées**, et un portique articulé sans contreventement peut être un mécanisme. Corrosion, faux aplomb, excentricités et interaction flexion/compression ne sont pas couverts.

### Actions de pression : ce que doivent reprendre les liaisons

Pressions nettes uniformes choisies pour comparer les efforts, pas un vent local calculé. Mur rectangulaire latéral seul, sans triangle de pignon ; soulèvement vertical symétrique des deux pans. Les lignes sont des cas séparés, pas des combinaisons de dimensionnement.

| Pression nette (kN/m²) | Force sur le mur (N) | Moment au sol (N·m) | Soulèvement brut du toit (N) |
|---:|---:|---:|---:|
| 0,30 | 1440,00 | 1440,00 | 1728,00 |
| 0,60 | 2880,00 | 2880,00 | 3456,00 |
| 1,00 | 4800,00 | 4800,00 | 5760,00 |

Aucune déduction de poids ni répartition automatique entre ancrages. Les ouvertures, pressions internes, succions de rive, rugosité, relief et rafales restent à définir pour le site. Aucun seuil de vent d’exploitation n’est déduit de ce tableau.

<!-- END GENERATED CALCULATIONS -->

## Raccords et stabilité : les calculs encore ouverts

Le raccord reste la pièce maîtresse du projet. Le calcul gravitaire fournit déjà la traction à transmettre entre tirant et pieds de toiture, la compression des rampants et les réactions verticales. Pour comparer ces demandes aux capacités réelles, relever référence, diamètre compatible, état, résistance au glissement, géométrie et serrage documenté des raccords. Ne pas assimiler un collier quelconque à une articulation parfaite ni à un encastrement rigide. Aucune valeur de serrage ou capacité n’est inventée.

Il reste à construire le modèle complet avec les diagonales dans les deux directions, les pieds et ancrages, les lisses et les charges réelles. Vérifier résistance des tubes, flambement et flexion/compression, glissement des raccords, efforts excentrés, soulèvement, renversement, glissement au sol et portance. Un simple toit triangulaire ne contrevente pas les quatre murs du stand.

Les charges du site devront inclure les situations pertinentes de vent, toiture mouillée, neige éventuelle, montage et charges localisées, avec leurs combinaisons et coefficients. Les tableaux n’affectent pas artificiellement zéro à ces cas manquants. La pente calculée de 18,43° est une hypothèse géométrique : ce n’est pas une pente d’étanchéité recommandée pour la paille.

## Relevés nécessaires pour remplacer les hypothèses

| Relevé | Utilisation dans le calcul |
|---|---|
| Diamètre extérieur et épaisseur minimale des tubes, matériau, corrosion et déformations | Section utile, poids propre, rigidité et résistance |
| Croquis coté : longueur, hauteur d’égout, pente, tirants, diagonales, nombre et position des portiques, débords | Portées réelles, chemin des efforts et stabilité |
| Référence exacte et données des raccords | Compatibilité 30 mm, glissement, excentricité et assemblages |
| Masse et surface d’un panneau de plessis représentatif, couverture sèche puis mouillée, accessoires | Charges surfaciques réelles ; l’eau retenue s’ajoute au poids sec |
| Sol, appuis, ancrages, site et exposition | Arrachement, glissement, portance et actions climatiques |

Les végétaux restent limités à 60 mm de diamètre et sans quota de quantité. Le mimosa n’est pas à économiser ; toute quantité supplémentaire augmente toutefois le poids ou la surface exposée à prendre en compte.

## Reproduire et conserver l’évaluation

Le fichier `docs/cabin-structure-assumptions.json` contient les valeurs de comparaison. Le script utilise uniquement Python 3 standard, sans dépendance ajoutée à Laravel :

```bash
python3 scripts/cabin_structure.py
python3 scripts/cabin_structure.py --format json
python3 scripts/cabin_structure.py --input /chemin/hypotheses.json
PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s tests/structure -v
```

Les tableaux de cette note correspondent au fichier d’hypothèses livré. Une variante exécutée en ligne de commande ne réécrit ni cette note ni un dossier de construction du stand. Conserver ensemble la configuration d’entrée et son résultat ; la commande ne délivre jamais un statut « approuvé ».

Dans **Concevoir → Stands → Évaluation interne**, cette note est consultable par les administrateurs. Chaque stand conserve séparément l’auteur, la date et la référence de son évaluation réelle. Cette note illustrative ne suffit pas comme preuve de réception : enregistrer d’abord les dimensions, inventaire, diamètre et entraxe réellement étudiés, compléter l’évaluation, puis référencer sa version. Les changements techniques invalident la revue antérieure et la réception.

## Références de méthode

Consultées le 30 septembre 2026 ; les calculs et cas numériques ci-dessus sont produits pour QAPAS, pas extraits d’une validation extérieure.

- [MIT OpenCourseWare — Structural Mechanics, lecture 5, poutres et déformations](https://ocw.mit.edu/courses/2-080j-structural-mechanics-fall-2013/3533c046dafcc488f1432e92d05ec210_MIT2_080JF13_Lecture5.pdf).
- [MIT OpenCourseWare — lecture 9, stabilité élastique](https://ocw.mit.edu/courses/2-080j-structural-mechanics-fall-2013/5a7038cb6ca0db1dda868e9d669b6836_MIT2_080JF13_Lecture9.pdf).
- [JRC — Eurocode 1, actions sur les structures](https://eurocodes.jrc.ec.europa.eu/EN-Eurocodes/eurocode-1-actions-structures), pour les familles d’actions à compléter.
- [Steel Tube Institute — dimensions et propriétés des sections](https://steeltubeinstitute.org/wp-content/uploads/2020/05/STI-Brochure-V3-ASTM-Dimensions-Section-Properties.pdf), pour les grandeurs de section ; les références américaines ne qualifient pas nos tubes récupérés.
