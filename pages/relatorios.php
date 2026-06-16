<?php
require_once '../config/config.php';

$usuario_id = $_SESSION['usuario_id'];
$usuario    = obterUsuarioAtual();

// ── Período padrão: mês atual ──────────────────────────────────────────────
$data_inicio = isset($_GET['data_inicio']) ? sanitizarEntrada($_GET['data_inicio']) : date('Y-m-01');
$data_fim    = isset($_GET['data_fim'])    ? sanitizarEntrada($_GET['data_fim'])    : date('Y-m-t');
$visao       = isset($_GET['visao'])       ? sanitizarEntrada($_GET['visao'])       : 'pessoal'; // pessoal | geral

// ── Dados do usuário ───────────────────────────────────────────────────────
$movs_usuario  = buscarMovimentacoes($usuario_id);
$movs_filtrado = filtrarMovimentacoes($movs_usuario, [
    'data_inicio' => $data_inicio,
    'data_fim'    => $data_fim,
]);
$movs_filtrado = ordenarMovimentacoesPorData($movs_filtrado);

// Totais pessoais
$receitas_pessoal  = calcularTotalReceitas($usuario_id, $data_inicio, $data_fim);
$despesas_pessoal  = calcularTotalDespesas($usuario_id, $data_inicio, $data_fim);
$saldo_pessoal     = $receitas_pessoal - $despesas_pessoal;
$saldo_total_contas = calcularSaldoTotal($usuario_id);

// Totais gerais
$receitas_geral = calcularTotalReceitasTodos($data_inicio, $data_fim);
$despesas_geral = calcularTotalDespesasTodos($data_inicio, $data_fim);
$saldo_geral    = $receitas_geral - $despesas_geral;

// ── Dados para gráficos ───────────────────────────────────────────────────
// Por mês (últimos 6 meses)
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $ts   = strtotime("-$i months");
    $chave = date('Y-m', $ts);
    $label = ucfirst(date('m/Y', $ts) ?: date('M/y', $ts));
    $meses[$chave] = ['label' => $label, 'receita' => 0, 'despesa' => 0];
}

foreach ($movs_usuario as $m) {
    if ($m['status'] !== 'confirmado') continue;
    $chave = substr($m['data_movimentacao'], 0, 7);
    if (isset($meses[$chave])) {
        $meses[$chave][$m['tipo']] += (float)$m['valor'];
    }
}

// Por tipo no período
$por_tipo_rec = array_filter($movs_filtrado, fn($m) => empty($m['transferencia_id']) && $m['tipo'] === 'receita' && $m['status'] === 'confirmado');
$por_tipo_des = array_filter($movs_filtrado, fn($m) => empty($m['transferencia_id']) && $m['tipo'] === 'despesa' && $m['status'] === 'confirmado');

// Agrupamento por descrição (top despesas)
$top_despesas = [];
foreach ($por_tipo_des as $m) {
    $key = $m['descricao'];
    $top_despesas[$key] = ($top_despesas[$key] ?? 0) + (float)$m['valor'];
}
arsort($top_despesas);
$top_despesas = array_slice($top_despesas, 0, 5, true);

// Dados de todos os usuários para o relatório geral
$todos_usuarios = lerCSV(USERS_FILE);
$resumo_usuarios = [];
foreach ($todos_usuarios as $u) {
    $resumo_usuarios[] = [
        'nome'    => $u['nome'],
        'email'   => $u['email'],
        'receita' => calcularTotalReceitas($u['id'], $data_inicio, $data_fim),
        'despesa' => calcularTotalDespesas($u['id'], $data_inicio, $data_fim),
        'saldo'   => calcularSaldoTotal($u['id']),
    ];
}

// Contas do usuário
$contas = buscarContasUsuario($usuario_id);

// Exportar
if (isset($_GET['exportar'])) {
    if ($_GET['exportar'] === 'csv') {
        exportarParaCSV($movs_filtrado, 'relatorio_' . $data_inicio . '_' . $data_fim . '.csv');
    }
}

// ── JSON para charts ──────────────────────────────────────────────────────
$chart_labels    = json_encode(array_map(fn($m) => $m['label'],   array_values($meses)));
$chart_receitas  = json_encode(array_map(fn($m) => round($m['receita'], 2), array_values($meses)));
$chart_despesas  = json_encode(array_map(fn($m) => round($m['despesa'], 2), array_values($meses)));
$chart_pie_vals  = json_encode([round($receitas_pessoal, 2), round($despesas_pessoal, 2)]);
?>
<?php include '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="fas fa-chart-bar"></i> Relatórios Financeiros</h3>
    <a href="?<?php echo http_build_query(array_merge($_GET, ['exportar' => 'csv'])); ?>" class="btn btn-success">
        <i class="fas fa-file-csv"></i> Exportar CSV
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">Data Início</label>
                <input type="date" name="data_inicio" class="form-control" value="<?php echo $data_inicio; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Data Fim</label>
                <input type="date" name="data_fim" class="form-control" value="<?php echo $data_fim; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Visão</label>
                <select name="visao" class="form-select">
                    <option value="pessoal" <?php echo $visao === 'pessoal' ? 'selected' : ''; ?>>Minha conta</option>
                    <option value="geral"   <?php echo $visao === 'geral'   ? 'selected' : ''; ?>>Todos os usuários</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="relatorios.php" class="btn btn-secondary"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<?php if ($visao === 'pessoal'): ?>
<!-- ═══════════════ VISÃO PESSOAL ═══════════════ -->

<!-- Cards de Resumo -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-card card-receita">
            <h5><i class="fas fa-arrow-up"></i> Receitas</h5>
            <div class="valor"><?php echo formatarReais($receitas_pessoal); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-card card-despesa">
            <h5><i class="fas fa-arrow-down"></i> Despesas</h5>
            <div class="valor"><?php echo formatarReais($despesas_pessoal); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-card card-saldo">
            <h5><i class="fas fa-balance-scale"></i> Saldo Período</h5>
            <div class="valor"><?php echo formatarReais($saldo_pessoal); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="dashboard-card" style="background: linear-gradient(135deg,#6f42c1,#9b59b6);">
            <h5><i class="fas fa-wallet"></i> Saldo Total Contas</h5>
            <div class="valor"><?php echo formatarReais($saldo_total_contas); ?></div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-chart-line"></i> Receitas vs Despesas (últimos 6 meses)</div>
            <div class="card-body">
                <canvas id="chartLinhas" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-chart-pie"></i> Distribuição no Período</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartPie" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Despesas -->
<?php if (!empty($top_despesas)): ?>
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-trophy"></i> Top 5 Maiores Despesas no Período</div>
    <div class="card-body">
        <?php
        $max_val = max(array_values($top_despesas));
        foreach ($top_despesas as $desc => $val):
            $pct = $max_val > 0 ? round($val / $max_val * 100) : 0;
        ?>
        <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
                <span><?php echo htmlspecialchars($desc); ?></span>
                <strong class="text-danger"><?php echo formatarReais($val); ?></strong>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-danger" style="width: <?php echo $pct; ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Saldo por Conta -->
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-piggy-bank"></i> Saldo por Conta</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr><th>Conta</th><th>Descrição</th><th>Saldo Inicial</th><th>Saldo Atual</th><th>Variação</th></tr>
            </thead>
            <tbody>
                <?php foreach ($contas as $c):
                    $variacao = (float)$c['saldo_atual'] - (float)$c['saldo_inicial'];
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td>
                    <td><small class="text-muted"><?php echo htmlspecialchars($c['descricao']); ?></small></td>
                    <td><?php echo formatarReais($c['saldo_inicial']); ?></td>
                    <td class="fw-bold <?php echo (float)$c['saldo_atual'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatarReais($c['saldo_atual']); ?>
                    </td>
                    <td class="<?php echo $variacao >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo ($variacao >= 0 ? '+' : '') . formatarReais($variacao); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Extrato Detalhado -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-receipt"></i> Extrato Detalhado (<?php echo count($movs_filtrado); ?> registros)</span>
        <button onclick="window.print()" class="btn btn-sm btn-light"><i class="fas fa-print"></i> Imprimir</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Data</th><th>Descrição</th><th>Conta</th><th>Tipo</th><th>Valor</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($movs_filtrado)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Nenhuma movimentação no período.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movs_filtrado as $mov):
                            $conta_mov = buscarContaPorId($mov['conta_id']);
                        ?>
                        <tr>
                            <td><?php echo formatarData($mov['data_movimentacao']); ?></td>
                            <td><?php echo htmlspecialchars($mov['descricao']); ?></td>
                            <td><small><?php echo $conta_mov ? htmlspecialchars($conta_mov['nome']) : '–'; ?></small></td>
                            <td><span class="<?php echo obterClasseTipo($mov['tipo']); ?> fw-bold"><?php echo obterLabelTipo($mov['tipo']); ?></span></td>
                            <td class="<?php echo obterClasseTipo($mov['tipo']); ?> fw-bold">
                                <?php echo ($mov['tipo'] === 'despesa' ? '- ' : '+ ') . formatarReais($mov['valor']); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $mov['status'] === 'confirmado' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                    <?php echo obterLabelStatus($mov['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold">
                            <td colspan="4" >Totais confirmados:</td>
                            <td class="text-success text-end">+<?php echo formatarReais($receitas_pessoal); ?></td>
                            <td></td>
                        </tr>
                        <tr class="fw-bold">
                            <td colspan="4">Despesas:</td>
                            <td class="text-danger text-end">-<?php echo formatarReais($despesas_pessoal); ?></td>
                            <td></td>
                        </tr>
                        <tr class="fw-bold">
                            <td colspan="4">Saldo do período:</td>
                            <td class="<?php echo $saldo_pessoal >= 0 ? 'text-success' : 'text-danger'; ?> text-end">
                                <?php echo formatarReais($saldo_pessoal); ?>
                            </td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ═══════════════ VISÃO GERAL (TODOS OS USUÁRIOS) ═══════════════ -->

<div class="row mb-4">
    <div class="col-md-4">
        <div class="dashboard-card card-receita">
            <h5><i class="fas fa-users"></i> Total Receitas (todos)</h5>
            <div class="valor"><?php echo formatarReais($receitas_geral); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card card-despesa">
            <h5><i class="fas fa-users"></i> Total Despesas (todos)</h5>
            <div class="valor"><?php echo formatarReais($despesas_geral); ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card card-saldo">
            <h5><i class="fas fa-balance-scale"></i> Saldo Geral</h5>
            <div class="valor"><?php echo formatarReais($saldo_geral); ?></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="fas fa-users"></i> Resumo por Usuário (<?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Usuário</th><th>Email</th><th>Receitas</th><th>Despesas</th><th>Saldo Período</th><th>Saldo Total Contas</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($resumo_usuarios as $r):
                        $saldo_p = $r['receita'] - $r['despesa'];
                    ?>
                    <tr <?php echo $r['email'] === $usuario['email'] ? 'class="table-primary"' : ''; ?>>
                        <td>
                            <strong><?php echo htmlspecialchars($r['nome']); ?></strong>
                            <?php if ($r['email'] === $usuario['email']): ?>
                                <span class="badge bg-primary ms-1" style="font-size:.7rem;">Você</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($r['email']); ?></small></td>
                        <td class="text-success fw-bold"><?php echo formatarReais($r['receita']); ?></td>
                        <td class="text-danger fw-bold"><?php echo formatarReais($r['despesa']); ?></td>
                        <td class="fw-bold <?php echo $saldo_p >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo ($saldo_p >= 0 ? '+' : '') . formatarReais($saldo_p); ?>
                        </td>
                        <td class="fw-bold <?php echo $r['saldo'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo formatarReais($r['saldo']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <!-- Totalizador -->
                    <tr class="table-secondary fw-bold">
                        <td colspan="2">TOTAL GERAL</td>
                        <td class="text-success"><?php echo formatarReais($receitas_geral); ?></td>
                        <td class="text-danger"><?php echo formatarReais($despesas_geral); ?></td>
                        <td class="<?php echo $saldo_geral >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo ($saldo_geral >= 0 ? '+' : '') . formatarReais($saldo_geral); ?>
                        </td>
                        <td><?php echo formatarReais(array_sum(array_column($resumo_usuarios, 'saldo'))); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Gráfico Geral -->
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-chart-bar"></i> Receitas vs Despesas por Usuário</div>
    <div class="card-body">
        <canvas id="chartUsuarios" height="80"></canvas>
    </div>
</div>

<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
<?php if ($visao === 'pessoal'): ?>
// Gráfico de linhas
const ctxLinhas = document.getElementById('chartLinhas').getContext('2d');
new Chart(ctxLinhas, {
    type: 'bar',
    data: {
        labels: <?php echo $chart_labels; ?>,
        datasets: [
            {
                label: 'Receitas',
                data: <?php echo $chart_receitas; ?>,
                backgroundColor: 'rgba(40,167,69,0.6)',
                borderColor: '#28a745',
                borderWidth: 2,
                borderRadius: 4,
            },
            {
                label: 'Despesas',
                data: <?php echo $chart_despesas; ?>,
                backgroundColor: 'rgba(220,53,69,0.6)',
                borderColor: '#dc3545',
                borderWidth: 2,
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
    }
});

// Gráfico de pizza
const ctxPie = document.getElementById('chartPie').getContext('2d');
new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: ['Receitas', 'Despesas'],
        datasets: [{
            data: <?php echo $chart_pie_vals; ?>,
            backgroundColor: ['rgba(40,167,69,0.8)', 'rgba(220,53,69,0.8)'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
<?php else: ?>
// Gráfico por usuário
const ctxUsuarios = document.getElementById('chartUsuarios').getContext('2d');
new Chart(ctxUsuarios, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($resumo_usuarios, 'nome')); ?>,
        datasets: [
            {
                label: 'Receitas',
                data: <?php echo json_encode(array_map(fn($r) => round($r['receita'], 2), $resumo_usuarios)); ?>,
                backgroundColor: 'rgba(40,167,69,0.7)',
                borderRadius: 4,
            },
            {
                label: 'Despesas',
                data: <?php echo json_encode(array_map(fn($r) => round($r['despesa'], 2), $resumo_usuarios)); ?>,
                backgroundColor: 'rgba(220,53,69,0.7)',
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
    }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>