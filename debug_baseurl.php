<?php
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['DOCUMENT_ROOT'] = 'C:/xampp/htdocs';

$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$basePathReal = str_replace('\\', '/', realpath(__DIR__));
echo "docRoot=$docRoot\n";
echo "basePathReal=$basePathReal\n";

$baseUrl = '';
if ($docRoot && str_starts_with($basePathReal, $docRoot)) {
    $baseUrl = substr($basePathReal, strlen($docRoot));
    $baseUrl = '/' . trim($baseUrl, '/');
    if ($baseUrl === '/') {
        $baseUrl = '';
    }
}
echo "after if1 baseUrl=$baseUrl\n";
if ($baseUrl === '') {
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    $scriptDir = dirname($scriptName);
    echo "scriptName=$scriptName\n";
    echo "scriptDir=$scriptDir\n";
    if (basename($scriptDir) === 'pages' || basename($scriptDir) === 'config') {
        $baseUrl = dirname($scriptDir);
    } else {
        $baseUrl = $scriptDir;
    }
    echo "before normalize baseUrl=$baseUrl\n";
    $baseUrl = $baseUrl === '/' ? '' : rtrim($baseUrl, '/');
    echo "final baseUrl=$baseUrl\n";
}

define('BASE_URL', $baseUrl);
echo "const=" . BASE_URL . "\n";
echo "url=" . (BASE_URL !== '' ? BASE_URL : '') . "/pages/login.php\n";

function baseUrl($path = '') {
    $path = ltrim($path, '/');
    $base = BASE_URL !== '' ? BASE_URL : '';
    if ($path === '') {
        return $base !== '' ? $base : '/';
    }
    return $base . '/' . $path;
}

echo "baseUrl func=" . baseUrl('pages/login.php') . "\n";
