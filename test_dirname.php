<?php
$scriptName = '/pages/login.php';
$scriptDir = str_replace('\\', '/', dirname($scriptName));
var_dump(dirname($scriptName));
var_dump($scriptDir);
var_dump(basename($scriptDir));
var_dump($scriptDir === '/');
var_dump(rtrim($scriptDir, '/'));
$scriptName2 = '/index.php';
$scriptDir2 = str_replace('\\', '/', dirname($scriptName2));
var_dump(dirname($scriptName2));
var_dump($scriptDir2);
var_dump(basename($scriptDir2));
var_dump(rtrim($scriptDir2, '/'));
