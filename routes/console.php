<?php
use App\Models\Admin;
use App\Models\Interest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
Artisan::command('events:admin {email}',function(){
 $email=mb_strtolower(trim($this->argument('email')));
 Validator::make(['email'=>$email],['email'=>'required|email|max:254'])->validate();
 if (Admin::where('email',$email)->exists()) {$this->error('Compte déjà présent ; aucun mot de passe modifié.');return 1;}
 $name=$this->ask('Nom');$password=$this->secret('Mot de passe (16 caractères minimum)');
 Validator::make(['name'=>$name,'password'=>$password],['name'=>'required|string|max:120','password'=>'required|string|min:16'])->validate();
 Admin::create(['name'=>$name,'email'=>$email,'password'=>$password,'is_active'=>true]);
 $this->info('Administrateur créé.');return 0;
})->purpose('Créer un administrateur local, distinct des comptes Platform');
Artisan::command('events:prune-interests',function(){
 $count=Interest::whereDoesntHave('registration')->where('created_at','<',now()->subDays(config('events.interest_retention_days')))->delete();
 $this->info($count.' demandes anciennes supprimées.');
})->purpose('Appliquer la durée de conservation des demandes initiales');
Schedule::command('events:prune-interests')->dailyAt('03:00');

\Illuminate\Support\Facades\Artisan::command('events:ticket-sms',function(){ $count=app(\App\Domain\Tickets\TicketSms::class)->dispatch();$this->info($count.' SMS acceptés par le prestataire. Voir les états de livraison.');});
\Illuminate\Support\Facades\Schedule::command('events:ticket-sms')->everyMinute()->withoutOverlapping();
