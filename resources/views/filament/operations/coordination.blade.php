<div class="space-y-5">
    <section class="space-y-2">
        <h3 class="font-bold">Aujourd’hui : un planning partagé par les administrateurs</h3>
        <p>Chaque séquence comporte une heure, un responsable, une zone, des consignes et un état. Les responsables de coordination mettent ce planning à jour. Le tableau est rafraîchi toutes les 15 secondes. Les signalements QR sont traités séparément dans <a href="{{ \App\Filament\Resources\IncidentResource::getUrl() }}" class="underline">Incidents</a>.</p>
        <p>Il n’existe pas encore de messagerie terrain, de notification aux intervenants, d’accusé de réception ni de suivi de présence. Modifier une séquence ne transmet pas de consigne à son responsable.</p>
    </section>
    <section class="space-y-2">
        <h3 class="font-bold">Organisation proposée · à mettre en place</h3>
        <p>Coordination QAPAS → responsables de zone → intervenants affectés. QAPAS arbitre le programme et les moyens ; les responsables répartissent et vérifient les missions ; chacun connaît sa mission actuelle, son rendez-vous et son remplaçant.</p>
        <p>Zones à dimensionner : accueil/parking, épreuves, stands, bar/soupe, technique et médias. La personne chargée de la sécurité peut demander l’arrêt d’une activité ; la reprise exige la décision du responsable habilité.</p>
        <p>Une consigne doit préciser : destinataire, mission, lieu, horaire limite, moyens disponibles, résultat attendu et condition de départ. Exemple : « Responsable épreuves, rendez-vous au quartel A à 10 h 20 ; confirmer barrières et public en place avant le lancement de 10 h 30. » Ce lieu est un exemple, pas une implantation validée.</p>
    </section>
    <section class="space-y-2">
        <h3 class="font-bold">Boucle de retour à développer</h3>
        <p>Envoyée → reçue et acceptée → en place → en cours → terminée → vérifiée. « Bloqué » reste possible à chaque étape avec motif et aide demandée. Un message lu ne vaut ni acceptation ni tâche terminée.</p>
        <p>Absence de réponse, retard ou blocage remontent au responsable de zone puis à QAPAS. Une consigne modifiée demande un nouvel accusé de réception. Les urgences sont traitées sur place ou par radio, sans attendre une notification web.</p>
    </section>
    <section class="space-y-2">
        <h3 class="font-bold">Écran terrain et répétition</h3>
        <p>Prévoir un accès personnel limité à l’édition et aux missions attribuées : « Ma mission maintenant », « Où aller », « Prochaine mission », « Signaler un blocage ». Le QR public d’un badge ne donne aucun accès privé. Aucun compte administrateur collectif à partager.</p>
        <p>Tester au week-end de répétition : changement de responsable, absence de réseau, consigne contradictoire et arrêt/reprise. Radios, liste de contacts et fiches papier constituent le repli. Le coût du matériel, des communications et du personnel doit être ajouté au budget avant engagement.</p>
    </section>
</div>
