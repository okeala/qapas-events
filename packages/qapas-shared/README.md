# okeala/qapas-shared 0.1.3

Source canonique : `okeala/qapas-application/packages/qapas-shared`. Le paquet fournit la navigation, les parcours, le hero, les cartes de contenu, les blocs CTA et les tuiles éditoriales communs. Il requiert Flux UI gratuit 2.20 pour les composants interactifs et les cartes ; Tailwind 4 compose les mises en page et les styles métier restent dans chaque application.

Dans chaque application, importer `vendor/livewire/flux/dist/flux.css` après Tailwind, puis les styles QAPAS ; insérer `@livewireScripts` et `@fluxScripts` dans les layouts publics qui utilisent Flux. Garder les balises HTML sémantiques pour la navigation et les contenus simples. La convention complète est dans `docs/CONVENTION_FLUX_UI.md` du dépôt source.

Le paquet est installé par Composer depuis une copie versionnée de ce dossier via un dépôt `path`. Chaque application importe le paquet depuis une révision vérifiée de la source, met à jour son lockfile et valide migrations, build et tests sur son port propre.
