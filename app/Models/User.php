<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
// Reserved for future Platform identities. No public registration or admin permission.
class User extends Authenticatable {
 protected static function booted(): void {static::creating(fn (self $user)=>$user->public_id ??= (string) \Illuminate\Support\Str::uuid());}
 protected $fillable=['name','email','password'];
 protected $hidden=['password','remember_token'];
 protected function casts(): array {return ['password'=>'hashed'];}
}
