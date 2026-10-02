<?php
require __DIR__.'/../vendor/autoload.php';
$path = __DIR__.'/../.env';
$text = file_get_contents($path);
$values = Dotenv\Dotenv::parse($text);
if (($values['APP_ENV'] ?? '') !== 'local') { throw new RuntimeException('La démonstration exige APP_ENV=local.'); }
$text = preg_replace('/^QAPAS_EVENT_DEMO_ENABLED=.*$/m', 'QAPAS_EVENT_DEMO_ENABLED=true', $text, -1, $count);
if (! $count) { $text .= "\nQAPAS_EVENT_DEMO_ENABLED=true\n"; }
file_put_contents($path, $text, LOCK_EX);
