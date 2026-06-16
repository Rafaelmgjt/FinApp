<?php
$source = dirname('/index.php');
echo "source=" . $source . "\n";
$replaced = str_replace('\\', '/', $source);
echo "replaced=" . $replaced . "\n";
var_dump($source);
var_dump($replaced);
