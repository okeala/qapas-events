<?php
return [
 'id'=>'events','name'=>'Os Jogos do Agricultor',
 'title'=>array_fill_keys(['fr','pt','en','nl','es','de'],'Os Jogos do Agricultor'),
 'locales'=>['fr','pt','en','nl','es','de'],
 'public_url'=>env('QAPAS_PUBLIC_URL',env('APP_URL')),
 'platform_url'=>env('QAPAS_PLATFORM_URL'),
];
