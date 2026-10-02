<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserSeedingTest extends TestCase
{
    use RefreshDatabase;

    private function configure(bool $admin = false): void
    {
        config(['seeding' => [
            'enabled' => true,
            'name' => 'Organisateur local',
            'email' => '  Organizer@example.test  ',
            'password' => 'A-local-password-for-this-test',
            'filament_access' => $admin,
        ]]);
    }

    public function test_default_seeding_does_not_create_accounts(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('admins', 0);
    }

    public function test_user_seeding_assigns_a_uuid_and_never_grants_implicit_admin_access(): void
    {
        $this->configure();
        $this->seed(UserSeeder::class);
        $user = User::firstOrFail();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $user->public_id);
        $this->assertSame('organizer@example.test', $user->email);
        $this->assertTrue(Hash::check('A-local-password-for-this-test', $user->password));
        $this->assertDatabaseCount('admins', 0);
        $this->actingAs($user, 'web')->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_explicit_filament_seeding_allows_login_with_the_chosen_credentials(): void
    {
        $this->configure(true);
        $this->seed(UserSeeder::class);
        $this->assertDatabaseCount('users', 1);
        $admin = Admin::firstOrFail();
        $this->assertTrue(Hash::check('A-local-password-for-this-test', $admin->password));
        $this->assertTrue(Auth::guard('admin')->attempt([
            'email' => 'organizer@example.test', 'password' => 'A-local-password-for-this-test',
        ]));
        $this->get('/admin')->assertOk();
        $this->get('/admin/event-plan')->assertOk();
    }

    public function test_reseeding_preserves_identity_passwords_and_admin_suspension(): void
    {
        $this->configure(true);
        $this->seed(UserSeeder::class);
        $user = User::firstOrFail();
        $uuid = $user->public_id;
        $user->update(['name' => 'Nom modifié', 'password' => 'A-changed-user-password-for-tests']);
        Admin::firstOrFail()->update(['is_active' => false, 'password' => 'A-changed-admin-password-for-tests']);
        config(['seeding.password' => 'Another-password-do-not-overwrite']);
        $this->seed(UserSeeder::class);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('admins', 1);
        $this->assertSame($uuid, $user->fresh()->public_id);
        $this->assertSame('Nom modifié', $user->fresh()->name);
        $this->assertTrue(Hash::check('A-changed-user-password-for-tests', $user->fresh()->password));
        $admin = Admin::firstOrFail();
        $this->assertTrue(Hash::check('A-changed-admin-password-for-tests', $admin->password));
        $this->assertFalse($admin->is_active);
        $this->actingAs($admin, 'admin')->get('/admin')->assertForbidden();
    }

    public function test_adding_filament_to_an_existing_user_preserves_the_users_password(): void
    {
        $this->configure();
        $this->seed(UserSeeder::class);
        config(['seeding.filament_access' => true, 'seeding.password' => 'A-different-new-password-for-tests']);
        $this->seed(UserSeeder::class);
        $this->assertTrue(Hash::check('A-local-password-for-this-test', Admin::firstOrFail()->password));
        $this->assertFalse(Hash::check('A-different-new-password-for-tests', Admin::firstOrFail()->password));
    }

    public function test_interactive_command_seeds_both_accounts_and_restores_configuration(): void
    {
        $previous = config('seeding');
        $this->artisan('events:seed-user --admin')
            ->expectsQuestion('Nom', 'Compte IDE')
            ->expectsQuestion('Email', 'ide@example.test')
            ->expectsQuestion('Mot de passe (16 caractères minimum)', 'A-chosen-password-for-the-IDE')
            ->assertExitCode(0);
        $this->assertDatabaseHas('users', ['email' => 'ide@example.test']);
        $this->assertTrue(Hash::check('A-chosen-password-for-the-IDE', Admin::firstOrFail()->password));
        $this->assertSame($previous, config('seeding'));
    }

    public function test_interactive_command_without_admin_seeds_only_a_user(): void
    {
        $this->artisan('events:seed-user')
            ->expectsQuestion('Nom', 'Compte public')
            ->expectsQuestion('Email', 'user@example.test')
            ->expectsQuestion('Mot de passe (16 caractères minimum)', 'A-chosen-password-for-a-user')
            ->assertExitCode(0);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('admins', 0);
    }

    public function test_invalid_credentials_create_neither_user_nor_admin(): void
    {
        $this->configure(true);
        config(['seeding.password' => 'short']);
        try {
            $this->seed(UserSeeder::class);
            $this->fail('Invalid credentials must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('password', $exception->errors());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('admins', 0);
    }

    public function test_production_refuses_bulk_seeding_and_the_interactive_seed_command(): void
    {
        $this->configure(true);
        app()->instance('env', 'production');
        $this->artisan('events:seed-user --admin')->assertExitCode(1);
        try {
            (new UserSeeder)->run();
            $this->fail('Production seeding must be rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('local et testing', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('admins', 0);
    }
}
