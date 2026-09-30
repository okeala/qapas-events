<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
abstract class Record extends Model {
 protected $guarded=['id','public_id'];
 public function getRouteKeyName(): string {return 'public_id';}
 protected static function booted(): void {
  static::creating(function (Model $model): void {$model->public_id=(string) Str::uuid();});
  static::saved(function (Model $model): void {
   if (auth('admin')->check()) AuditEntry::create(['admin_id'=>auth('admin')->id(),'action'=>$model->wasRecentlyCreated?'created':'updated','record_type'=>$model->getMorphClass(),'record_public_id'=>$model->public_id,'changed_fields'=>array_keys($model->getChanges())]);
  });
 }
}
