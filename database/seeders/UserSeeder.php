<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (config('seeding.enabled') !== true) {
            return;
        }
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Le seeding des comptes est réservé aux environnements local et testing.');
        }
        $identity = [
            'name' => config('seeding.name'),
            'email' => mb_strtolower(trim((string) config('seeding.email'))),
            'password' => config('seeding.password'),
        ];
        Validator::make($identity, [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:254',
            'password' => 'required|string|min:16',
        ])->validate();
        DB::transaction(function () use ($identity): void {
            $user = User::firstOrCreate(['email' => $identity['email']], $identity);
            if (config('seeding.filament_access') === true) {
                // Preserve existing credentials and suspended administrators.
                Admin::firstOrCreate(['email' => $identity['email']], [
                    'name' => $user->name,
                    'password' => $user->password,
                    'is_active' => true,
                ]);
            }
        });
    }
}
