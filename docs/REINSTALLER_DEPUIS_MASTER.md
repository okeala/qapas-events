# Réinstaller QAPAS Events depuis master

Dépôt : `okeala/qapas-events`. Branche compilée : `master`. PHP 8.4+, Composer 2, Node 24, extensions listées dans le README.

Depuis la racine du dossier vide de l’IDE :

```bash
bash <<'BASH'
set -Eeuo pipefail
if [[ ! -e .git ]]; then git init -b master; fi
if git remote get-url origin >/dev/null 2>&1; then
    case "$(git remote get-url origin)" in
        *:okeala/qapas-events.git|*/okeala/qapas-events.git|*/okeala/qapas-events) ;;
        *) printf 'Le remote origin doit pointer vers okeala/qapas-events.\n' >&2; exit 1 ;;
    esac
else
    git remote add origin https://github.com/okeala/qapas-events.git
fi
if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    printf 'Conserver les modifications locales avant la mise à jour.\n' >&2
    exit 1
fi
git fetch origin master
if git show-ref --verify --quiet refs/heads/master; then
    git switch master
    git merge --ff-only origin/master
else
    git switch --create master --track origin/master
fi
git branch --set-upstream-to=origin/master master
bash scripts/setup-local.sh --demo
read -r -p 'Email administrateur : ' qapas_admin_email </dev/tty
php artisan events:admin "$qapas_admin_email" </dev/tty
php artisan serve --host=127.0.0.1 --port=8890
BASH
```

Le mot de passe est demandé sans affichage et doit comporter au moins 16 caractères. Aucun mot de passe n’est écrit dans Git ou `.env`. Si le compte existe déjà, ne pas relancer sa création : lancer simplement le serveur après l’installation.

Administration : `/admin/event-plan`, avec les six modules V2 regroupés dans la navigation. Visites : `/visites`. L’installation conserve `.env`, APP_KEY, SQLite et les dossiers historiques. La suppression préalable de ces fichiers dans l’IDE nécessite leur sauvegarde pour restaurer leurs anciennes valeurs ; Git ne stocke pas les données privées.

Mises à jour : `git pull --ff-only origin master`, puis `bash scripts/setup-local.sh` et `php artisan serve --host=127.0.0.1 --port=8890`. Ne jamais employer `migrate:fresh` sur une base travaillée.
