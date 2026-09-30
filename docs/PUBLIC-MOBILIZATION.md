# Parcours public, communes et croissance — 30 septembre 2026

La page de l’édition relie ses cartes aux communes, aux équipes, aux relais, aux partenaires et à une explication de la croissance. Les intitulés canoniques sont traduits FR/PT. Le format initial est un objectif de douze stands : six communes et six indépendants, sans présenter ces places comme vendues.

## Répertoire alimenté par la base

`FreguesiaInvitation` distingue invitation prévue, remise/envoyée, participation acceptée et déclinée. La remise et l’acceptation exigent dates effectives et références privées ; une réponse positive ne remplace pas la convention juridique. Dans Filament : **Mobiliser → Communes · invitations et présentation**. Le répertoire public expose uniquement les fiches publiées et les textes destinés au public, jamais le CRM, les signataires ou les preuves.

La première liste reprend six entités déjà prospectées dans la base, sans présumer de contact effectif : Vale Formoso e Aldeia do Souto (Covilhã), Belmonte, Caria, Colmeal da Torre, Maçainhas et Inguias (Belmonte). Les deux accueils de Vale Formoso / Aldeia do Souto sont une seule entité. Sources municipales consultées le 30/09/2026 : https://www.cm-covilha.pt/?cix=1059&lang=1&tab=795 et https://www.cm-belmonte.pt/contactos-uteis/. Cette liste initiale n’est pas un inventaire exhaustif des freguesias de la région. Le seed conserve toutes les modifications et n’envoie aucun courrier.

Les équipes publiques sont des lignes `Team` publiées dans cette édition. Les noms des candidats, coordonnées, protocoles, procès-verbaux et justificatifs ne figurent pas sur ces pages. Les profils à réunir sont présentés sans inventer de joueurs élus. Le lien facultatif vers la commune est contrôlé côté serveur.

**Local uniquement** : `php artisan db:seed` ajoute une équipe fictive par commune initiale, avec quatorze rôles vacants et un bandeau explicite. Pas de faux paiement, scrutin, personnalité, parrain ou coordonnées. Ces équipes ne peuvent être élues ni recevoir de personnes réelles. Le filtre de production les exclut même si la base locale est copiée et leur publication cochée. Elles ne changent ni les douze stands du scénario ni leurs recettes. Les fiches locales se consultent depuis la page normale, sans ouvrir les autres informations privées de l’atelier.

Les relais sont lus depuis `StandPartner` : place de relais, activation documentée, publication du partenaire et de son stand. Adresse et horaires publics sont distincts des preuves internes. La carte Leaflet/OSM ne positionne que les coordonnées complètes avec source de vérification ; les autres adresses restent listées. Aucun géocodage inventé, pas de prospect affiché comme relais confirmé. Publication et coordonnées se règlent dans **Mobiliser → Parrains et relais locaux**.

Les remerciements utilisent uniquement les sponsors visibles selon leurs accords et droits existants, et les parrains de stands publiés avec accord documenté. En leur absence, le texte invite à devenir partenaire. Les sponsors d’épreuves encore secrètes suivent les règles existantes de révélation.

## Pourquoi les prix avaient augmenté

Le dernier prix calculé visait le coût connu, les charges, les réserves ET l’objectif QAPAS, pas seulement le résultat nul. Dans les seules données initiales : 650 € TTC par indépendant ; parrain de commune à 950 € TTC pour l’équilibre simulé ou 1 750 € TTC avec l’objectif QAPAS. Ces deux cas supposent les autres partenariats prévus vendus, conservent la provision supplémentaire de 2 000 €, et restent incomplets tant que les devis manquent. Ne pas remplacer les prix négociés ni réduire silencieusement l’objectif.

Le rapport privé **Prix de couverture 6 + 6** distingue coûts rattachés aux stands et autres coûts du format. Ces derniers ne sont pas tous des frais fixes : des stocks, consommables, passages et besoins de capacité sont variables. Le repère initial d’un indépendant à 650 € TTC est : 528,46 € hors IVA simulée à 23 %, moins 140 € TTC de coûts directs connus = 388,46 € disponibles avant nouveaux besoins. Quatre indépendants supplémentaires apporteraient donc 1 553,84 € avant coûts non recensés et nouveaux paliers, pas 2 600 € de bénéfice.

Le comparateur reprend le coût direct connu le plus élevé des indépendants existants, signale l’incomplétude et montre +2/+4/+6 unités. Il ne déclare aucun minimum rentable, ne crée pas d’offre, n’affecte pas de capacité et ne déclenche aucune dépense. Un scénario complet d’extension doit chiffrer ses propres besoins et conserver les garanties du socle. Sur le frontend, seule cette logique est expliquée ; les budgets et objectifs internes restent privés.

Mise à jour : migrations, seed idempotent et build Vite (nouvelle entrée `relay-map.js`). Les tests couvrent les liens, les états d’invitation, FR/PT, l’étanchéité des éditions, la confidentialité, le retrait de publication, les démonstrations locales, la projection cartographique et la contribution marginale.
