<?php
namespace App\Policies;
use App\Models\Admin;
class WorkspacePolicy {
 public function viewAny(Admin $admin): bool {return $admin->is_active;}
 public function view(Admin $admin, $record): bool {return $admin->is_active;}
 public function create(Admin $admin): bool {return $admin->is_active;}
 public function update(Admin $admin, $record): bool {return $admin->is_active;}
 public function delete(Admin $admin, $record): bool {return false;}
 public function deleteAny(Admin $admin): bool {return false;}
 public function restore(Admin $admin, $record): bool {return false;}
 public function forceDelete(Admin $admin, $record): bool {return false;}
}
