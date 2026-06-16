<?php
require_once '../config/config.php';

$usuario = obterUsuarioAtual();
$usuario_id = $_SESSION['usuario_id'];

// Período padrão: mês atual
$mes_atual = date('Y-m');
$data_inicio = $mes_atual . '-01';
$data_fim    = date('Y-m-t');

if (isset($_GET['data_inicio']) && isset($_GET['data_fim'])) {
    $data_inicio = sanitizarEntrada($_GET['data_inicio']);
    $data_fim    = sanitizarEntrada($_GET['data_fim']);
}

$saldo_total   = calcularSaldoTotal($usuario_id);
$total_receitas = calcularTotalReceitas($usuario_id, $data_inicio, $data_fim);
$total_despesas = calcularTotalDespesas($usuario_id, $data_inicio, $data_fim);
$saldo_periodo  = $total_receitas - $total_despesas;

// Movimentações recentes (últimas 10)
$movimentacoes = buscarMovimentacoes($usuario_id);
$movimentacoes = ordenarMovimentacoesPorData($movimentacoes);
$recentes = array_slice($movimentacoes, 0, 10);

// Contas do usuário
$contas = buscarContasUsuario($usuario_id);

// Movimentações pendentes
$pendentes = array_filter($movimentacoes, fn($m) => $m['status'] === 'pendente');

// Confirmar movimentação pendente
if (isset($_GET['confirmar']) && is_numeric($_GET['confirmar'])) {
    $mov = buscarMovimentacaoPorId((int)$_GET['confirmar']);
    if ($mov && $mov['usuario_id'] == $usuario_id && $mov['status'] === 'pendente') {
        editarMovimentacao($mov['id'], ['status' => 'confirmado', 'data_confirmacao' => date('Y-m-d H:i:s')]);
        header('Location: ' . baseUrl('pages/dashboard.php') . '?msg=confirmado');
        exit;
    }
}

$msg_url = obterMensagemURL();

// Total geral todos usuários
$receitas_todos  = calcularTotalReceitasTodos($data_inicio, $data_fim);
$despesas_todos  = calcularTotalDespesasTodos($data_inicio, $data_fim);
?>
<?php include '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="fas fa-chart-line"></i> Dashboard</h3>
    <span class="text-muted">Bem-vindo, <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>!</span>
</div>

<?php if ($msg_url): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg_url; ?></div>
<?php endif; ?>

<!-- Filtro de Período -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">Data Início</label>
                <input type="date" class="form-control" name="data_inicio" value="<?php echo $data_inicio; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Data Fim</label>
                <input type="date" class="form-control" name="data_fim" value="<?php echo $data_fim; ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-card card-saldo">
            <h5><i class="fas fa-wallet"></i> Saldo Total</h5>
            <div class="valor"><?php echo formatarReais($saldo_total); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-card card-receita">
            <h5><i class="fas fa-arrow-up"></i> Receitas (período)</h5>
            <div class="valor"><?php echo formatarReais($total_receitas); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-card card-despesa">
            <h5><i class="fas fa-arrow-down"></i> Despesas (período)</h5>
            <div class="valor"><?php echo formatarReais($total_despesas); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-card" style="background: linear-gradient(135deg,#6f42c1,#9b59b6); color:white;">
            <h5><i class="fas fa-balance-scale"></i> Saldo do Período</h5>
            <div class="valor"><?php echo formatarReais($saldo_periodo); ?></div>
        </div>
    </div>
</div>

<!-- Cards Todos os Usuários -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-users"></i> Receitas de Todos os Usuários (período)</div>
            <div class="card-body text-center">
                <h4 class="text-success"><?php echo formatarReais($receitas_todos); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-users"></i> Despesas de Todos os Usuários (período)</div>
            <div class="card-body text-center">
                <h4 class="text-danger"><?php echo formatarReais($despesas_todos); ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Pendentes -->
<?php if (!empty($pendentes)): ?>
<div class="card mb-4">
    <div class="card-header" style="background: linear-gradient(135deg,#ffc107,#e0a800); color:#212529;">
        <i class="fas fa-clock"></i> Movimentações Pendentes (<?php echo count($pendentes); ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Data</th><th>Descrição</th><th>Tipo</th><th>Valor</th><th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendentes as $p): ?>
                    <tr>
                        <td><?php echo formatarData($p['data_movimentacao']); ?></td>
                        <td><?php echo htmlspecialchars($p['descricao']); ?></td>
                        <td><span class="<?php echo obterClasseTipo($p['tipo']); ?> fw-bold"><?php echo obterLabelTipo($p['tipo']); ?></span></td>
                        <td class="<?php echo obterClasseTipo($p['tipo']); ?> fw-bold"><?php echo formatarReais($p['valor']); ?></td>
                        <td>
                            <a href="?confirmar=<?php echo $p['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Confirmar esta movimentação?')">
                                <i class="fas fa-check"></i> Confirmar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Contas -->
<div class="row g-5 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-piggy-bank"></i> Minhas Contas</span>
                <a href="<?php echo baseUrl('pages/contas.php'); ?>" class="btn btn-sm btn-light">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($contas)): ?>
                    <p class="text-center text-muted p-3">Nenhuma conta encontrada.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($contas as $conta): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($conta['nome']); ?></strong>
                                <?php if ($conta['descricao']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($conta['descricao']); ?></small>
                                <?php endif; ?>
                            </div>
                            <span class="fw-bold <?php echo (float)$conta['saldo_atual'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo formatarReais($conta['saldo_atual']); ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Últimas Movimentações -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list"></i> Últimas Movimentações</span>
                <a href="<?php echo baseUrl('pages/movimentacoes.php'); ?>" class="btn btn-sm btn-light">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentes)): ?>
                    <p class="text-center text-muted p-3">Nenhuma movimentação encontrada.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentes as $mov): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="<?php echo obterClasseTipo($mov['tipo']); ?> me-1">
                                    <i class="fas fa-<?php echo $mov['tipo'] === 'receita' ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                </span>
                                <small><?php echo htmlspecialchars(substr($mov['descricao'], 0, 30)); ?></small>
                                <?php if ($mov['status'] === 'pendente'): ?>
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:.7rem;">Pendente</span>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted"><?php echo formatarData($mov['data_movimentacao']); ?></small>
                            </div>
                            <span class="<?php echo obterClasseTipo($mov['tipo']); ?> fw-bold">
                                <?php echo ($mov['tipo'] === 'despesa' ? '-' : '+') . formatarReais($mov['valor']); ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>