<?php
namespace Database\Seeders;
use App\Models\EventProject;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
 public function run(): void {
  // Never overwrite an edition that has already been worked on.
  if(EventProject::whereIn('slug',['forqua-de-ouro','os-jogos-do-agricultor'])->exists()) {$this->call(OfficialActivitiesSeeder::class);$this->call(LaunchModelSeeder::class);$this->call(HospitalityOperationsSeeder::class);$this->call(CostingSeeder::class);$this->call(ParticipantFurnitureAndRolesSeeder::class);$this->call(RegistrationCampaignSeeder::class);$this->call(RehearsalProcurementSeeder::class);$this->call(OutreachPresalesSeeder::class);return;}
  $p=EventProject::create([
   'name'=>'Os Jogos do Agricultor','slug'=>'os-jogos-do-agricultor','phase'=>'concept','is_public'=>app()->environment('local','testing'),
   'product'=>'Une fête autour des freguesias, de défis accessibles et des savoir-faire agricoles. Les équipes de volontaires représentent leur village ; les habitants viennent les soutenir. Des temps distincts sont réservés aux essais du grand public.',
   'price'=>'Entrée grand public prévue gratuite. Les emplacements, services et prestations publicitaires contribuent au financement de l’organisation. Les prix affichés incluent l’IVA ; les ventes restent fermées pendant la préparation.',
   'place'=>'Un site accessible reste à confirmer. Les équipes se constituent localement. Les citoyens élisent des volontaires à la junta ou dans un autre lieu accepté. Les indépendants peuvent également proposer une animation.',
   'promotion'=>'Des équipes fières de leur freguesia, des relais locaux et des démonstrations utiles : chacun donne une raison concrète de venir. Pas de promesse de fréquentation inventée.',
  ]);
  foreach ([['Pilote à dimensionner',0,0],['Palier intermédiaire à chiffrer',0,0],['Horizon : 12 équipes et 24 stands',12,24]] as [$name,$teams,$stands]) {
   $p->scenarios()->create(['name'=>$name,'months'=>1,'team_target'=>$teams,'stand_target'=>$stands,'assumptions'=>'Hypothèse de format uniquement. Aucun coût, contrat, paiement, permis ou fréquentation confirmé. Le nombre de mois et le coût complet de rémunération doivent être renseignés.']);
  }
  $offers=[
   ['Stand indépendant · emplacement nu','stand',25000,12,'Emplacement au sol 2,4 × 2,4 m (5,76 m²).','Structure, mobilier, branchements et services non expressément décrits.'],
   ['Stand indépendant · structure comprise','stand',50000,12,'Prix total comprenant la structure et son emplacement.','Modalités de mise à disposition, montage, démontage et équipements à contractualiser.'],
   ['Point-relais · première édition','relay',4999,0,'Profil, présence sur la carte/liste, QR personnalisé, 250 flyers, 1 A3, 3 A4 et 3 A5.','Capacité et production à confirmer. Délai prévu : 14 jours maximum après paiement lorsque la vente sera ouverte.'],
   ['Partenaire structurant','sponsor',250000,3,'Trois places au même tarif. Visibilité envisagée : 2/4, 1/4, 1/4.','Attribution du premier rang et livrables à définir avant vente. Aucune audience garantie.'],
   ['Soutenir un stand de freguesia','village',null,0,'Prestation publicitaire et aide matérielle précisément décrites sur devis.','Le sponsor paie QAPAS ; QAPAS documente les achats. Pas de versement informel à une équipe.'],
  ];
  foreach ($offers as [$name,$kind,$price,$capacity,$includes,$excludes]) $p->offers()->create(['name'=>$name,'kind'=>$kind,'price_gross_cents'=>$price,'capacity'=>$capacity,'includes'=>$includes,'excludes'=>$excludes,'delivery'=>'Proposition à consolider avant ouverture des ventes.','is_public'=>true]);
  $this->call(OfficialActivitiesSeeder::class);
  $p->ideas()->create(['name'=>'Mobiliser une première équipe avant de louer plus grand','pillar'=>'promotion','hypothesis'=>'La fierté de la freguesia peut entraîner des soutiens au-delà des joueurs.','experiment'=>'Rencontrer des volontaires, recueillir les intentions et chiffrer le plus petit format viable.']);
  $this->call(LaunchModelSeeder::class);$this->call(HospitalityOperationsSeeder::class);$this->call(CostingSeeder::class);$this->call(ParticipantFurnitureAndRolesSeeder::class);$this->call(RegistrationCampaignSeeder::class);$this->call(RehearsalProcurementSeeder::class);$this->call(OutreachPresalesSeeder::class);
 }
}
