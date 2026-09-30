<?php
namespace App\Models;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
class Admin extends Authenticatable implements FilamentUser {
 protected $attributes=['is_active'=>true];
 protected $fillable=['name','email','password','is_active'];
 protected $hidden=['password','remember_token'];
 protected function casts(): array { return ['password'=>'hashed','is_active'=>'boolean']; }
 public function canAccessPanel(Panel $panel): bool {return $this->is_active && $panel->getId()==='admin';}
}
