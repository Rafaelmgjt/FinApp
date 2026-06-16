<?php
require_once '../config/config.php';

$usuario_id = $_SESSION['usuario_id'];
$erro = $sucesso = '';

$contas = buscarContasUsuario($usuario_id);

// ── CRIAR ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar') {
    $conta_id        = (int)($_POST['conta_id'] ?? 0);
    $tipo            = sanitizarEntrada($_POST['tipo'] ?? '');
    $descricao       = sanitizarEntrada($_POST['descricao'] ?? '');
    $valor           = str_replace(',', '.', $_POST['valor'] ?? '0');
    $data_mov        = sanitizarEntrada($_POST['data_movimentacao'] ?? '');
    $status          = in_array($_POST['status'] ?? '', ['confirmado', 'pendente']) ? $_POST['status'] : 'confirmado';

    $conta_valida = false;
    foreach ($contas as $c) { if ($c['id'] == $conta_id) { $conta_valida = true; break; } }

    if (!$conta_valida) {
        $erro = 'Selecione uma conta válida.';
    } elseif (!in_array($tipo, ['receita', 'despesa'])) {
        $erro = 'Tipo inválido.';
    } elseif (empty($descricao)) {
        $erro = 'Descrição é obrigatória.';
    } elseif (!is_numeric($valor) || (float)$valor <= 0) {
        $erro = 'Valor deve ser maior que zero.';
    } elseif (empty($data_mov)) {
        $erro = 'Data é obrigatória.';
    } else {
        criarMovimentacao($usuario_id, $conta_id, $tipo, $descricao, (float)$valor, $data_mov, $status);
        $sucesso = 'Movimentação criada com sucesso!';
    }
}

// ── TRANSFERÊNCIA ENTRE CONTAS ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'transferir') {
    $conta_origem_id   = (int)($_POST['conta_origem_id'] ?? 0);
    $conta_destino_id  = (int)($_POST['conta_destino_id'] ?? 0);
    $valor             = str_replace(',', '.', $_POST['valor'] ?? '0');
    $descricao         = sanitizarEntrada($_POST['descricao'] ?? 'Transferência entre contas');
    $data_mov          = sanitizarEntrada($_POST['data_movimentacao'] ?? '');

    $conta_origem = buscarContaPorId($conta_origem_id);
    $conta_destino = buscarContaPorId($conta_destino_id);

    if (!$conta_origem || $conta_origem['usuario_id'] != $usuario_id) {
        $erro = 'Conta de origem inválida.';
    } elseif (!$conta_destino || $conta_destino['usuario_id'] != $usuario_id) {
        $erro = 'Conta de destino inválida.';
    } elseif ($conta_origem_id === $conta_destino_id) {
        $erro = 'Conta de origem e destino devem ser diferentes.';
    } elseif (!is_numeric($valor) || (float)$valor <= 0) {
        $erro = 'Valor deve ser maior que zero.';
    } elseif (empty($data_mov)) {
        $erro = 'Data é obrigatória.';
    } else {
        criarTransferenciaConta($usuario_id, $conta_origem_id, $conta_destino_id, (float)$valor, $descricao, $data_mov);
        $sucesso = 'Transferência entre contas realizada com sucesso!';
    }
}

// ── EDITAR ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar') {
    $mov_id          = (int)($_POST['mov_id'] ?? 0);
    $conta_id        = (int)($_POST['conta_id'] ?? 0);
    $tipo            = sanitizarEntrada($_POST['tipo'] ?? '');
    $descricao       = sanitizarEntrada($_POST['descricao'] ?? '');
    $valor           = str_replace(',', '.', $_POST['valor'] ?? '0');
    $data_mov        = sanitizarEntrada($_POST['data_movimentacao'] ?? '');
    $status          = in_array($_POST['status'] ?? '', ['confirmado', 'pendente']) ? $_POST['status'] : 'confirmado';

    $mov = buscarMovimentacaoPorId($mov_id);
    $conta_nova = buscarContaPorId($conta_id);

    if ($mov && !empty($mov['transferencia_id'])) {
        $valor = $mov['valor'];
    }

    if (!$mov || $mov['usuario_id'] != $usuario_id) {
        $erro = 'Movimentação não encontrada.';
    } elseif (!$conta_nova || $conta_nova['usuario_id'] != $usuario_id) {
        $erro = 'Selecione uma conta válida.';
    } elseif (empty($descricao)) {
        $erro = 'Descrição é obrigatória.';
    } elseif (!is_numeric($valor) || (float)$valor <= 0) {
        $erro = 'Valor deve ser maior que zero.';
    } elseif (empty($data_mov)) {
        $erro = 'Data é obrigatória.';
    } else {
        // Desfaz efeito anterior se estava confirmado
        if ($mov['status'] === 'confirmado') {
            atualizarSaldoConta($mov['conta_id'],
                ($mov['tipo'] === 'receita' ? 'despesa' : 'receita'),
                $mov['valor']);
        }

        $dados_novos = [
            'conta_id'          => $conta_id,
            'tipo'              => $tipo,
            'descricao'         => $descricao,
            'valor'             => number_format((float)$valor, 2, '.', ''),
            'data_movimentacao' => $data_mov,
            'status'            => $status,
            'data_confirmacao'  => $status === 'confirmado' ? date('Y-m-d H:i:s') : '',
        ];

        // Reaplica se novo status for confirmado
        if ($status === 'confirmado') {
            atualizarSaldoConta($conta_id, $tipo, (float)$valor);
        }

        // Usa o editarMovimentacao mas sem reduplicate saldo (já fizemos acima)
        $movs = lerCSV(MOVIMENTACOES_FILE);
        foreach ($movs as &$m) {
            if ($m['id'] == $mov_id) {
                foreach ($dados_novos as $k => $v) { $m[$k] = $v; }
                break;
            }
        }
        escreverCSV(MOVIMENTACOES_FILE, $movs);

        // Redireciona para remover o parâmetro ?editar da URL e fechar o modal
        header('Location: movimentacoes.php?msg=dados_salvos');
        exit;
    }
}

// ── DELETAR ────────────────────────────────────────────────────────────────
if (isset($_GET['deletar']) && is_numeric($_GET['deletar'])) {
    $mov = buscarMovimentacaoPorId((int)$_GET['deletar']);
    if ($mov && $mov['usuario_id'] == $usuario_id) {
        deletarMovimentacao((int)$_GET['deletar']);
        $sucesso = 'Movimentação excluída com sucesso!';
    } else {
        $erro = 'Movimentação não encontrada.';
    }
}

// ── CONFIRMAR PENDENTE ─────────────────────────────────────────────────────
if (isset($_GET['confirmar']) && is_numeric($_GET['confirmar'])) {
    $mov = buscarMovimentacaoPorId((int)$_GET['confirmar']);
    if ($mov && $mov['usuario_id'] == $usuario_id && $mov['status'] === 'pendente') {
        $movs = lerCSV(MOVIMENTACOES_FILE);

        // Se for parte de uma transferência, confirma todas as movimentações vinculadas
        if (!empty($mov['transferencia_id'])) {
            $tid = trim((string)$mov['transferencia_id']);
            foreach ($movs as &$m) {
                $m_tid = isset($m['transferencia_id']) ? trim((string)$m['transferencia_id']) : '';
                if ($m_tid != '' && $m_tid == $tid) {
                    // Se ainda não estiver confirmado, aplica saldo e marca como confirmado
                    if (($m['status'] ?? '') !== 'confirmado') {
                        atualizarSaldoConta($m['conta_id'], $m['tipo'], $m['valor']);
                        $m['status'] = 'confirmado';
                        $m['data_confirmacao'] = date('Y-m-d H:i:s');
                    }
                }
            }
            escreverCSV(MOVIMENTACOES_FILE, $movs);
            $sucesso = 'Transferência confirmada!';
        } else {
            // Movimentação isolada
            atualizarSaldoConta($mov['conta_id'], $mov['tipo'], $mov['valor']);
            foreach ($movs as &$m) {
                if ($m['id'] == $mov['id']) {
                    $m['status']           = 'confirmado';
                    $m['data_confirmacao'] = date('Y-m-d H:i:s');
                    break;
                }
            }
            escreverCSV(MOVIMENTACOES_FILE, $movs);
            $sucesso = 'Movimentação confirmada!';
        }
    }
}

// ── FILTROS ────────────────────────────────────────────────────────────────
$filtros = [
    'tipo'        => $_GET['tipo']        ?? '',
    'status'      => $_GET['status']      ?? '',
    'conta_id'    => $_GET['conta_id']    ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim'    => $_GET['data_fim']    ?? '',
    'descricao'   => $_GET['descricao']   ?? '',
];

$conta_filtro = !empty($filtros['conta_id']) && is_numeric($filtros['conta_id'])
    ? (int)$filtros['conta_id'] : null;

$movimentacoes = buscarMovimentacoes($usuario_id, $conta_filtro);
$movimentacoes = filtrarMovimentacoes($movimentacoes, $filtros);
$movimentacoes = ordenarMovimentacoesPorData($movimentacoes);

// Exportar CSV
if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {
    exportarParaCSV($movimentacoes, 'movimentacoes_' . date('Y-m-d') . '.csv');
}

// Totais
$total_receitas = array_sum(array_map(fn($m) => empty($m['transferencia_id']) && $m['tipo'] === 'receita' && $m['status'] === 'confirmado' ? (float)$m['valor'] : 0, $movimentacoes));
$total_despesas = array_sum(array_map(fn($m) => empty($m['transferencia_id']) && $m['tipo'] === 'despesa' && $m['status'] === 'confirmado' ? (float)$m['valor'] : 0, $movimentacoes));

// Dados para o modal de edição
$mov_editar = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $tmp = buscarMovimentacaoPorId((int)$_GET['editar']);
    if ($tmp && $tmp['usuario_id'] == $usuario_id) {
        $mov_editar = $tmp;
    }
}

$msg_url = obterMensagemURL();
?>
<?php include '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="fas fa-exchange-alt"></i> Movimentações</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaMovimentacao">
        <i class="fas fa-plus"></i> Nova Movimentação
    </button>
</div>

<?php if ($erro): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?></div>
<?php endif; ?>
<?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $sucesso; ?></div>
<?php endif; ?>
<?php if ($msg_url): ?>
    <div class="alert alert-info"><?php echo $msg_url; ?></div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-filter"></i> Filtros</div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-bold">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="">Todos</option>
                    <option value="receita"  <?php echo ($filtros['tipo'] === 'receita'  ? 'selected' : ''); ?>>Receita</option>
                    <option value="despesa"  <?php echo ($filtros['tipo'] === 'despesa'  ? 'selected' : ''); ?>>Despesa</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="confirmado" <?php echo ($filtros['status'] === 'confirmado' ? 'selected' : ''); ?>>Confirmado</option>
                    <option value="pendente"   <?php echo ($filtros['status'] === 'pendente'   ? 'selected' : ''); ?>>Pendente</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Conta</label>
                <select name="conta_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($contas as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($filtros['conta_id'] == $c['id'] ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($c['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Data Início</label>
                <input type="date" name="data_inicio" class="form-control" value="<?php echo $filtros['data_inicio']; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Data Fim</label>
                <input type="date" name="data_fim" class="form-control" value="<?php echo $filtros['data_fim']; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Descrição</label>
                <input type="text" name="descricao" class="form-control" placeholder="Buscar..." value="<?php echo htmlspecialchars($filtros['descricao']); ?>">
            </div>
            <div class="col-12 d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                <a href="movimentacoes.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
                <a href="?<?php echo http_build_query(array_merge($filtros, ['exportar' => 'csv'])); ?>" class="btn btn-success ms-auto">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Transferência entre Contas -->
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-exchange-alt"></i> Transferência entre Contas</div>
    <div class="card-body">
        <form method="POST" class="row g-3 align-items-end">
            <input type="hidden" name="acao" value="transferir">
            <div class="col-md-3">
                <label class="form-label fw-bold">Conta de Origem</label>
                <select name="conta_origem_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($contas as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?> (<?php echo formatarReais($c['saldo_atual']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Conta de Destino</label>
                <select name="conta_destino_id" class="form-select" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($contas as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?> (<?php echo formatarReais($c['saldo_atual']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Valor (R$)</label>
                <input type="number" step="0.01" min="0.01" class="form-control" name="valor" placeholder="0,00" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Data</label>
                <input type="date" class="form-control" name="data_movimentacao" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Descrição</label>
                <input type="text" class="form-control" name="descricao" placeholder="Motivo da transferência" required>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-paper-plane"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- Resumo -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="dashboard-card card-receita">
            <h5><i class="fas fa-arrow-up"></i> Total Receitas (filtro)</h5>
            <div class="valor"><?php echo formatarReais($total_receitas); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card card-despesa">
            <h5><i class="fas fa-arrow-down"></i> Total Despesas (filtro)</h5>
            <div class="valor"><?php echo formatarReais($total_despesas); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card card-saldo">
            <h5><i class="fas fa-balance-scale"></i> Saldo do Filtro</h5>
            <div class="valor"><?php echo formatarReais($total_receitas - $total_despesas); ?></div>
        </div>
    </div>
</div>

<!-- Tabela -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list"></i> Movimentações (<?php echo count($movimentacoes); ?> registros)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Conta</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimentacoes)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Nenhuma movimentação encontrada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimentacoes as $mov):
                            $conta_mov = buscarContaPorId($mov['conta_id']);
                        ?>
                        <tr>
                            <td><small class="text-muted"><?php echo $mov['id']; ?></small></td>
                            <td><?php echo formatarData($mov['data_movimentacao']); ?></td>
                            <td><?php echo htmlspecialchars($mov['descricao']); ?></td>
                            <td><small><?php echo $conta_mov ? htmlspecialchars($conta_mov['nome']) : '–'; ?></small></td>
                            <td>
                                <span class="<?php echo obterClasseTipo($mov['tipo']); ?> fw-bold">
                                    <i class="fas fa-<?php echo $mov['tipo'] === 'receita' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo obterLabelTipo($mov['tipo']); ?>
                                </span>
                            </td>
                            <td class="<?php echo obterClasseTipo($mov['tipo']); ?> fw-bold">
                                <?php echo ($mov['tipo'] === 'despesa' ? '- ' : '+ ') . formatarReais($mov['valor']); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $mov['status'] === 'confirmado' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                    <?php echo obterLabelStatus($mov['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <?php if ($mov['status'] === 'pendente'): ?>
                                        <a href="?confirmar=<?php echo $mov['id']; ?>" class="btn btn-sm btn-success" title="Confirmar" onclick="return confirm('Confirmar esta movimentação?')">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?editar=<?php echo $mov['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?deletar=<?php echo $mov['id']; ?>" class="btn btn-sm btn-danger" title="Excluir"
                                       onclick="return confirmarDelecao(<?php echo $mov['id']; ?>, 'movimentação')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== MODAL NOVA MOVIMENTAÇÃO ===================== -->
<div class="modal fade" id="modalNovaMovimentacao" tabindex="-1"
     <?php echo ($erro && ($_POST['acao'] ?? '') === 'criar') ? 'data-bs-show="true"' : ''; ?>>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nova Movimentação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="criar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Conta *</label>
                        <select name="conta_id" class="form-select" required>
                            <option value="">Selecione a conta...</option>
                            <?php foreach ($contas as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Tipo *</label>
                            <select name="tipo" class="form-select" required>
                                <option value="receita">Receita</option>
                                <option value="despesa">Despesa</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="confirmado">Confirmado</option>
                                <option value="pendente">Pendente (futuro)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição *</label>
                        <input type="text" class="form-control" name="descricao" placeholder="Ex: Salário, Aluguel, Mercado..." required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Valor (R$) *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="valor" placeholder="0,00" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Data *</label>
                            <input type="date" class="form-control" name="data_movimentacao" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL EDITAR MOVIMENTAÇÃO ===================== -->
<?php if ($mov_editar): ?>
<div class="modal fade show d-block" id="modalEditarMovimentacao" tabindex="-1" style="background:rgba(0,0,0,.5);">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Movimentação #<?php echo $mov_editar['id']; ?></h5>
                <a href="movimentacoes.php" class="btn-close"></a>
            </div>
            <form method="POST">
                <input type="hidden" name="acao"   value="editar">
                <input type="hidden" name="mov_id" value="<?php echo $mov_editar['id']; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Conta *</label>
                        <select name="conta_id" class="form-select" required>
                            <?php foreach ($contas as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $mov_editar['conta_id'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($c['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Tipo *</label>
                            <select name="tipo" class="form-select" required>
                                <option value="receita" <?php echo ($mov_editar['tipo'] === 'receita' ? 'selected' : ''); ?>>Receita</option>
                                <option value="despesa" <?php echo ($mov_editar['tipo'] === 'despesa' ? 'selected' : ''); ?>>Despesa</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="confirmado" <?php echo ($mov_editar['status'] === 'confirmado' ? 'selected' : ''); ?>>Confirmado</option>
                                <option value="pendente"   <?php echo ($mov_editar['status'] === 'pendente'   ? 'selected' : ''); ?>>Pendente</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição *</label>
                        <input type="text" class="form-control" name="descricao"
                               value="<?php echo htmlspecialchars($mov_editar['descricao']); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Valor (R$) *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="valor"
                                   value="<?php echo $mov_editar['valor']; ?>" <?php echo !empty($mov_editar['transferencia_id']) ? 'readonly' : ''; ?> required>
                            <?php if (!empty($mov_editar['transferencia_id'])): ?>
                                <div class="form-text">Valor de transferência não pode ser alterado.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Data *</label>
                            <input type="date" class="form-control" name="data_movimentacao"
                                   value="<?php echo $mov_editar['data_movimentacao']; ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="movimentacoes.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>