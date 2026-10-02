<?php

namespace App\Console\Commands;

use Database\Seeders\UserSeeder;
use Illuminate\Console\Command;

class SeedUser extends Command
{
    protected $signature = 'events:seed-user {--admin : Créer également le compte Filament}';

    protected $description = 'Seeder un utilisateur local avec accès Filament explicitement choisi';

    public function handle(): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Commande réservée aux environnements local et testing.');
            return self::FAILURE;
        }
        $previous = config('seeding');
        try {
            config(['seeding' => [
                'enabled' => true,
                'name' => $this->ask('Nom'),
                'email' => $this->ask('Email'),
                'password' => $this->secret('Mot de passe (16 caractères minimum)'),
                'filament_access' => $this->option('admin') === true,
            ]]);
            (new UserSeeder)->run();
        } finally {
            config(['seeding' => $previous]);
        }
        $this->info('Utilisateur seedé. Les comptes existants et leurs mots de passe sont conservés.');
        if ($this->option('admin')) {
            $this->info('Compte Filament créé ou conservé : /admin/login. Une suspension existante reste applicable.');
        }
        return self::SUCCESS;
    }
}
