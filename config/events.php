<?php
return [
 'organizer_name'=>env('EVENTS_ORGANIZER_NAME'),
 'contact_email'=>env('EVENTS_CONTACT_EMAIL'),
 'privacy_ready'=>(bool) env('EVENTS_PRIVACY_READY',false),
 'interest_retention_days'=>180,
 // Stand sales remain closed. Candidate fees have a separate gated registration configuration.
 'checkout_enabled'=>false,
 'gates'=>[
   'registration'=>['organizer','site','terms','tax','insurance','privacy'],
   'sales'=>['organizer','site','terms','tax','insurance','privacy'],
   'live'=>['organizer','site','terms','tax','insurance','privacy','safety','food','music'],
 ],
 'requirements'=>[
  'organizer'=>'Identité juridique et pouvoir de signature',
  'site'=>'Accord du site, capacité et autorisations applicables',
  'terms'=>'Conditions contractuelles, annulation, report et remboursements',
  'tax'=>'IVA par prestation, facturation et rapprochement comptable',
  'insurance'=>'Assurances et périmètres de couverture',
  'privacy'=>'Responsable de traitement, information, conservation et droits',
  'safety'=>'Accessibilité, secours, risques et validation des activités',
  'food'=>'Exploitants alimentaires, hygiène et boissons — ou non-applicabilité motivée',
  'music'=>'Musique, bruit et droits — ou non-applicabilité motivée',
 ],
];
