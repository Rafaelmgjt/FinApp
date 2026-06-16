<?php
// ============================================
// FUNÇÕES AUXILIARES
// ============================================

function verificarSessao() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . (BASE_URL !== '' ? BASE_URL : '') . '/pages/login.php');
        exit;
    }
    if (isset($_SESSION['ultimo_atividade'])) {
        $tempo_inativo = time() - $_SESSION['ultimo_atividade'];
        if ($tempo_inativo > SESSION_TIMEOUT) {
            session_destroy();
            header('Location: ' . (BASE_URL !== '' ? BASE_URL : '') . '/pages/login.php?msg=sessao_expirada');
            exit;
        }
    }
    $_SESSION['ultimo_atividade'] = time();
}

function baseUrl($path = '') {
    $path = ltrim($path, '/');
    $base = BASE_URL !== '' ? BASE_URL : '';
    if ($path === '') {
        return $base !== '' ? $base : '/';
    }
    return $base . '/' . $path;
}

function obterUsuarioAtual() {
    if (isset($_SESSION['usuario_id'])) {
        return buscarUsuarioPorId($_SESSION['usuario_id']);
    }
    return null;
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validarSenha($senha) {
    $erros = [];
    if (strlen($senha) < 6) $erros[] = 'Senha deve ter no mínimo 6 caracteres';
    if (!preg_match('/[0-9]/', $senha)) $erros[] = 'Senha deve conter pelo menos um número';
    if (!preg_match('/[a-zA-Z]/', $senha)) $erros[] = 'Senha deve conter pelo menos uma letra';
    return ['valida' => empty($erros), 'erros' => $erros];
}

function sanitizarEntrada($entrada) {
    return htmlspecialchars(trim($entrada), ENT_QUOTES, 'UTF-8');
}

function validarValor($valor) {
    return is_numeric($valor) && (float)$valor > 0;
}

function formatarReais($valor) {
    return MOEDA . ' ' . number_format((float)$valor, 2, ',', '.');
}

function formatarData($data) {
    if (empty($data)) return 'N/A';
    return date('d/m/Y', strtotime($data));
}

function formatarDataHora($data) {
    if (empty($data)) return 'N/A';
    return date('d/m/Y H:i', strtotime($data));
}

function obterLabelTipo($tipo) {
    return $tipo === 'receita' ? 'Receita' : 'Despesa';
}

function obterClasseTipo($tipo) {
    return strtolower($tipo) === 'receita' ? 'text-success' : 'text-danger';
}

function obterClasseStatus($status) {
    $classes = ['confirmado' => 'badge bg-success', 'pendente' => 'badge bg-warning text-dark'];
    return $classes[$status] ?? 'badge bg-secondary';
}

function obterLabelStatus($status) {
    return $status === 'confirmado' ? 'Confirmado' : 'Pendente';
}

function calcularSaldoTotal($usuario_id) {
    $contas = buscarContasUsuario($usuario_id);
    $total = 0;
    foreach ($contas as $c) $total += (float)$c['saldo_atual'];
    return $total;
}

function calcularTotalReceitas($usuario_id, $data_inicio = null, $data_fim = null) {
    $movs = buscarMovimentacoes($usuario_id);
    $total = 0;
    foreach ($movs as $m) {
        if (!empty($m['transferencia_id'])) continue;
        if ($m['tipo'] !== 'receita' || $m['status'] !== 'confirmado') continue;
        if ($data_inicio && $m['data_movimentacao'] < $data_inicio) continue;
        if ($data_fim   && $m['data_movimentacao'] > $data_fim)   continue;
        $total += (float)$m['valor'];
    }
    return $total;
}

function calcularTotalDespesas($usuario_id, $data_inicio = null, $data_fim = null) {
    $movs = buscarMovimentacoes($usuario_id);
    $total = 0;
    foreach ($movs as $m) {
        if (!empty($m['transferencia_id'])) continue;
        if ($m['tipo'] !== 'despesa' || $m['status'] !== 'confirmado') continue;
        if ($data_inicio && $m['data_movimentacao'] < $data_inicio) continue;
        if ($data_fim   && $m['data_movimentacao'] > $data_fim)   continue;
        $total += (float)$m['valor'];
    }
    return $total;
}

function calcularTotalReceitasTodos($data_inicio = null, $data_fim = null) {
    $movs = lerCSV(MOVIMENTACOES_FILE);
    $total = 0;
    foreach ($movs as $m) {
        if (!empty($m['transferencia_id'])) continue;
        if ($m['tipo'] !== 'receita' || $m['status'] !== 'confirmado') continue;
        if ($data_inicio && $m['data_movimentacao'] < $data_inicio) continue;
        if ($data_fim   && $m['data_movimentacao'] > $data_fim)   continue;
        $total += (float)$m['valor'];
    }
    return $total;
}

function calcularTotalDespesasTodos($data_inicio = null, $data_fim = null) {
    $movs = lerCSV(MOVIMENTACOES_FILE);
    $total = 0;
    foreach ($movs as $m) {
        if (!empty($m['transferencia_id'])) continue;
        if ($m['tipo'] !== 'despesa' || $m['status'] !== 'confirmado') continue;
        if ($data_inicio && $m['data_movimentacao'] < $data_inicio) continue;
        if ($data_fim   && $m['data_movimentacao'] > $data_fim)   continue;
        $total += (float)$m['valor'];
    }
    return $total;
}

function filtrarMovimentacoes($movs, $filtros) {
    $resultado = [];
    foreach ($movs as $m) {
        $ok = true;
        if (!empty($filtros['tipo'])        && $m['tipo'] !== $filtros['tipo'])                         $ok = false;
        if (!empty($filtros['status'])      && $m['status'] !== $filtros['status'])                     $ok = false;
        if (!empty($filtros['data_inicio']) && $m['data_movimentacao'] < $filtros['data_inicio'])       $ok = false;
        if (!empty($filtros['data_fim'])    && $m['data_movimentacao'] > $filtros['data_fim'])          $ok = false;
        if (!empty($filtros['descricao'])   && stripos($m['descricao'], $filtros['descricao']) === false) $ok = false;
        if ($ok) $resultado[] = $m;
    }
    return $resultado;
}

function ordenarMovimentacoesPorData($movs) {
    usort($movs, fn($a,$b) => strtotime($b['data_movimentacao']) - strtotime($a['data_movimentacao']));
    return $movs;
}

function exportarParaCSV($dados, $nome_arquivo) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $nome_arquivo);
    $out = fopen('php://output', 'w');
    // BOM para Excel reconhecer UTF-8
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    if (!empty($dados)) {
        fputcsv($out, array_keys($dados[0]), ';');
        foreach ($dados as $linha) fputcsv($out, $linha, ';');
    }
    fclose($out);
    exit;
}

function obterMensagemURL() {
    if (!isset($_GET['msg'])) return '';
    $msgs = [
        'cadastro_sucesso'  => 'Cadastro realizado com sucesso!',
        'login_sucesso'     => 'Login realizado com sucesso!',
        'logout_sucesso'    => 'Você saiu do sistema.',
        'sessao_expirada'   => 'Sua sessão expirou. Faça login novamente.',
        'dados_salvos'      => 'Dados salvos com sucesso!',
        'deletado_sucesso'  => 'Registro excluído com sucesso!',
        'nao_autorizado'    => 'Você não tem permissão para este recurso.',
        'confirmado'        => 'Movimentação confirmada!',
        'erro'              => 'Ocorreu um erro. Tente novamente.',
    ];
    $chave = $_GET['msg'];
    return $msgs[$chave] ?? htmlspecialchars($chave);
}
?>