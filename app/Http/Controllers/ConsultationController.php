<?php
namespace App\Http\Controllers;
use App\Models\CostConsultation;
class ConsultationController extends Controller {
 public function email(CostConsultation $consultation,string $locale){
  abort_unless(auth('admin')->user()?->is_active,403);abort_unless(in_array($locale,['fr','pt'],true),404);
  $subject=preg_replace('/[\r\n]+/',' ',$consultation->{'subject_'.$locale});$body=str_replace(["\r\n","\r"],"\n",$consultation->{'body_'.$locale});
  $content="X-Unsent: 1\r\nSubject: =?UTF-8?B?".base64_encode($subject)."?=\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($body),76,"\r\n");
  return response($content)->header('Content-Type','message/rfc822')->header('Cache-Control','private, no-store')->header('Content-Disposition','attachment; filename="qapas-'.$consultation->public_id.'-'.$locale.'.eml"');
 }
}
