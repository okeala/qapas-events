<div class="space-y-4">
 <p>10 € TTC par candidature. Les montants ci-dessous proviennent des paiements confirmés. Ils ne sont pas ajoutés automatiquement aux ventes prévisionnelles du bar.</p>
 <dl class="grid grid-cols-2 gap-3">
 @foreach(['gross_cents'=>'Paiements valides TTC','fees_cents'=>'Frais de paiement connus','available_cents'=>'Affectable après IVA, frais, rapprochement bancaire et réserve','potential_drink_credit_cents'=>'Contrepartie potentielle (candidats en attente ou non retenus)','issued_cents'=>'Tickets émis, valeur faciale','redeemed_cents'=>'Tickets consommés','allocated_cents'=>'Affecté au contrat boissons','allocation_shortfall_cents'=>'Affectation à réexaminer après remboursement / litige'] as $key=>$label)
 <dt>{{ $label }}</dt><dd>{{ \App\Domain\Finance\Money::format($funding[$key]) }}</dd>
 @endforeach
 </dl><p>{{ $funding['fees_pending'] }} paiement(s) avec frais encore inconnus, exclus du montant affectable.</p>
 <p>Le crédit promis n’est pas sa marge ni son coût d’achat. Chiffrer les boissons réellement à fournir ; les consommations réglées en tickets ne doivent jamais être comptées comme de nouveaux encaissements du bar. Aucun seuil de lancement n’est débloqué par cette seule affectation.</p>
</div>
