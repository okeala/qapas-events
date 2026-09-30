<?php
return [
 // No fictional Quinta position: initial viewport covers mainland Portugal until a feature is drawn/imported.
 'center'=>[39.6,-8.0], 'zoom'=>7,
 'osm_url'=>'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
 'imagery_url'=>env('EVENTS_IMAGERY_URL','https://cartografia.dgterritorio.gov.pt/wms/ortos2025'),
 'imagery_layer'=>env('EVENTS_IMAGERY_LAYER','Ortos2025-RGB     '),
 'imagery_attribution'=>env('EVENTS_IMAGERY_ATTRIBUTION','Direção-Geral do Território — ortofotos 2025'),
];
