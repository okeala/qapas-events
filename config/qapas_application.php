<?php
return [
 'id'=>'events','name'=>'QAPAS Events',
 'title'=>array_fill_keys(['fr','pt','en','nl','es','de'],'QAPAS Events'),
 'locales'=>['fr','pt','en','nl','es','de'],
 'public_url'=>env('QAPAS_PUBLIC_URL',env('APP_URL')),
 'platform_url'=>env('QAPAS_PLATFORM_URL'),
];
