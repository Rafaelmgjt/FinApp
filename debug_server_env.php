<?php
header('Content-Type: text/plain');
function dump($name, $value) { echo "$name: "; var_export($value); echo "\n"; }
dump('PHP_SELF', $_SERVER['PHP_SELF'] ?? null);
dump('SCRIPT_NAME', $_SERVER['SCRIPT_NAME'] ?? null);
dump('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT'] ?? null);
dump('REQUEST_URI', $_SERVER['REQUEST_URI'] ?? null);
dump('HTTP_HOST', $_SERVER['HTTP_HOST'] ?? null);
var_dump(defined('BASE_URL') ? BASE_URL : 'BASE_URL not defined');
?>