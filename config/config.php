<?php
// ============================================
// CONFIGURAÇÃO GLOBAL DO SISTEMA
// ============================================

// Evita headers duplicados se session já iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definições de Diretórios
define('BASE_PATH', dirname(dirname(__FILE__)));

define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('DATA_PATH', BASE_PATH . '/data');
define('PAGES_PATH', BASE_PATH . '/pages');

// URL base do aplicativo
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$basePathReal = str_replace('\\', '/', realpath(BASE_PATH));
$baseUrl = '';
if ($docRoot && str_starts_with($basePathReal, $docRoot)) {
    $baseUrl = substr($basePathReal, strlen($docRoot));
    $baseUrl = '/' . trim($baseUrl, '/');
    if ($baseUrl === '/') {
        $baseUrl = '';
    }
}
if ($baseUrl === '') {
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    if (basename($scriptDir) === 'pages' || basename($scriptDir) === 'config') {
        $baseUrl = str_replace('\\', '/', dirname($scriptDir));
    } else {
        $baseUrl = $scriptDir;
    }
    $baseUrl = str_replace('\\', '/', $baseUrl);
    $baseUrl = ($baseUrl === '/' || $baseUrl === '\\') ? '' : rtrim($baseUrl, '/');
}
define('BASE_URL', $baseUrl);

// Dados CSV
define('USERS_FILE', DATA_PATH . '/usuarios.csv');
define('CONTAS_FILE', DATA_PATH . '/contas.csv');
define('MOVIMENTACOES_FILE', DATA_PATH . '/movimentacoes.csv');
define('TRANSFERENCIAS_FILE', DATA_PATH . '/transferencias.csv');

// Configurações de Sessão
define('SESSION_TIMEOUT', 3600);
define('TIMEZONE', 'America/Sao_Paulo');

// Configurações de Segurança
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_OPTIONS', ['cost' => 12]);

// Título do Sistema
define('SYSTEM_NAME', 'FinApp');
define('SYSTEM_VERSION', '1.0.0');

// Moeda
define('MOEDA', 'R$');
define('LOCALE', 'pt_BR');

// Configurar timezone
date_default_timezone_set(TIMEZONE);

// Garante que o diretório data existe
if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0755, true);
}

// Carrega as funções do banco de dados
require_once CONFIG_PATH . '/database.php';

// Carrega as funções auxiliares
require_once INCLUDES_PATH . '/functions.php';

// Verifica sessão ativa (exceto em login e registro)
$pagina_atual = basename($_SERVER['PHP_SELF']);
if (!in_array($pagina_atual, ['login.php', 'registro.php', 'index.php'])) {
    verificarSessao();
}
?>