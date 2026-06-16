<?php
require_once '../config/config.php';

$usuario_id = $_SESSION['usuario_id'];
$erro = $sucesso = '';

// Criar conta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'criar') {
    $nome      = sanitizarEntrada($_POST['nome'] ?? '');
    $descricao = sanitizarEntrada($_POST['descricao'] ?? '');
    $saldo_ini = str_replace(',', '.', $_POST['saldo_inicial'] ?? '0');

    if (empty($nome)) {
        $erro = 'Nome da conta é obrigatório.';
    } elseif (!is_numeric($saldo_ini) || (float)$saldo_ini < 0) {
        $erro = 'Saldo inicial inválido.';
    } else {
        criarConta($usuario_id, $nome, $descricao, (float)$saldo_ini);
        $sucesso = 'Conta criada com sucesso!';
    }
}

// Deletar conta
if (isset($_GET['deletar']) && is_numeric($_GET['deletar'])) {
    $conta = buscarContaPorId((int)$_GET['deletar']);
    if ($conta && $conta['usuario_id'] == $usuario_id) {
        $contas = lerCSV(CONTAS_FILE);
        $contas = array_values(array_filter($contas, fn($c) => $c['id'] != $_GET['deletar']));
        escreverCSV(CONTAS_FILE, $contas);
        $sucesso = 'Conta removida com sucesso!';
    }
}

// Transferência entre usuários
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'transferir') {
    $destino_id        = (int)($_POST['usuario_destino_id'] ?? 0);
    $conta_destino_id  = (int)($_POST['conta_destino_id'] ?? 0);
    $conta_origem_id   = (int)($_POST['conta_origem_id'] ?? 0);
    $valor             = str_replace(',', '.', $_POST['valor'] ?? '0');
    $descricao         = sanitizarEntrada($_POST['descricao'] ?? 'Transferência');
    $data_movimentacao = sanitizarEntrada($_POST['data_movimentacao'] ?? date('Y-m-d'));

    if (!$destino_id || $destino_id == $usuario_id) {
        $erro = 'Selecione um usuário destino válido.';
    } elseif (!is_numeric($valor) || (float)$valor <= 0) {
        $erro = 'Valor inválido.';
    } else {
        $conta_origem = buscarContaPorId($conta_origem_id);
        $conta_destino = buscarContaPorId($conta_destino_id);

        if (!$conta_origem || $conta_origem['usuario_id'] != $usuario_id) {
            $erro = 'Selecione uma conta de origem válida.';
        } elseif (!$conta_destino || $conta_destino['usuario_id'] != $destino_id) {
            $erro = 'Selecione uma conta de destino válida para o usuário selecionado.';
        } else {
            criarTransferenciaComMovimentos($usuario_id, $destino_id, $conta_origem_id, $conta_destino_id, (float)$valor, $descricao, $data_movimentacao);
            $sucesso = 'Transferência realizada com sucesso!';
        }
    }
}

$contas = buscarContasUsuario($usuario_id);
$todos_usuarios = lerCSV(USERS_FILE);
$outros_usuarios = array_filter($todos_usuarios, fn($u) => $u['id'] != $usuario_id);

$contas_por_usuario = [];
foreach ($todos_usuarios as $u) {
    $contas_por_usuario[$u['id']] = buscarContasUsuario($u['id']);
}

$msg_url = obterMensagemURL();
?>
<?php include '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="fas fa-piggy-bank"></i> Minhas Contas</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaConta">
        <i class="fas fa-plus"></i> Nova Conta
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

<!-- Lista de contas -->
<div class="row mb-4">
    <?php if (empty($contas)): ?>
        <div class="col-12"><div class="alert alert-info">Nenhuma conta cadastrada. Crie sua primeira conta!</div></div>
    <?php else: ?>
        <?php foreach ($contas as $conta): ?>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-university"></i> <?php echo htmlspecialchars($conta['nome']); ?></span>
                    <?php if ($conta['nome'] !== 'Conta Principal'): ?>
                        <a href="?deletar=<?php echo $conta['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmarDelecao(<?php echo $conta['id']; ?>, 'conta')">
                            <i class="fas fa-trash"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($conta['descricao']): ?>
                        <p class="text-muted small"><?php echo htmlspecialchars($conta['descricao']); ?></p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mt-2">
                        <div>
                            <small class="text-muted">Saldo Inicial</small>
                            <div><?php echo formatarReais($conta['saldo_inicial']); ?></div>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Saldo Atual</small>
                            <div class="fw-bold fs-5 <?php echo (float)$conta['saldo_atual'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo formatarReais($conta['saldo_atual']); ?>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">Criada em: <?php echo formatarData($conta['data_criacao']); ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Transferência entre usuários -->
<?php if (!empty($outros_usuarios)): ?>
<div class="card mb-4">
    <div class="card-header"><i class="fas fa-exchange-alt"></i> Transferir para outro Usuário</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="acao" value="transferir">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Usuário Destino</label>
                    <select name="usuario_destino_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($outros_usuarios as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nome']); ?> (<?php echo htmlspecialchars($u['email']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Conta Destino</label>
                    <select name="conta_destino_id" class="form-select" required>
                        <option value="">Selecione o usuário primeiro...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Conta de Origem</label>
                    <select name="conta_origem_id" class="form-select" required>
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
                <div class="col-md-4">
                    <label class="form-label fw-bold">Descrição</label>
                    <input type="text" class="form-control" name="descricao" placeholder="Motivo da transferência">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-paper-plane"></i> Transferir</button>
                </div>
            </div>
        </form>

        <script>
            (function(){
                const accountsByUser = <?php echo json_encode($contas_por_usuario, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                const userSel = document.querySelector('select[name="usuario_destino_id"]');
                const destSel = document.querySelector('select[name="conta_destino_id"]');

                function populateDest(uid){
                    destSel.innerHTML = '<option value="">Selecione...</option>';
                    if (!uid || !accountsByUser[uid]) return;
                    accountsByUser[uid].forEach(function(c){
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = (c.nome || ('Conta #' + c.id)) + ' (' + (c.saldo_atual ? ('R$ ' + parseFloat(c.saldo_atual).toFixed(2)) : 'R$ 0,00') + ')';
                        destSel.appendChild(opt);
                    });
                }

                userSel.addEventListener('change', function(){ populateDest(this.value); });
                // se já houver valor selecionado (ex: após erro), tenta popular
                if (userSel.value) populateDest(userSel.value);
            })();
        </script>
    </div>
</div>
<?php endif; ?>

<!-- Extrato de outros usuários (somente leitura) -->
<div class="card">
    <div class="card-header"><i class="fas fa-users"></i> Extrato de Outros Usuários (somente leitura)</div>
    <div class="card-body">
        <?php if (empty($outros_usuarios)): ?>
            <p class="text-muted">Nenhum outro usuário cadastrado.</p>
        <?php else: ?>
            <ul class="nav nav-tabs mb-3" id="tabsUsuarios">
                <?php $first = true; foreach ($outros_usuarios as $u): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $first ? 'active' : ''; ?>" data-bs-toggle="tab" href="#user-<?php echo $u['id']; ?>">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($u['nome']); ?>
                    </a>
                </li>
                <?php $first = false; endforeach; ?>
            </ul>
            <div class="tab-content">
                <?php $first = true; foreach ($outros_usuarios as $u): ?>
                <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" id="user-<?php echo $u['id']; ?>">
                    <?php
                    $contas_outro = buscarContasUsuario($u['id']);
                    $movs_outro   = ordenarMovimentacoesPorData(buscarMovimentacoes($u['id']));
                    $saldo_outro  = calcularSaldoTotal($u['id']);
                    ?>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card text-white bg-primary">
                                <div class="card-body text-center py-2">
                                    <small>Saldo Total</small>
                                    <div class="fw-bold"><?php echo formatarReais($saldo_outro); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr><th>Data</th><th>Descrição</th><th>Tipo</th><th>Valor</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movs_outro)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">Sem movimentações.</td></tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($movs_outro, 0, 15) as $m): ?>
                                    <tr>
                                        <td><?php echo formatarData($m['data_movimentacao']); ?></td>
                                        <td><?php echo htmlspecialchars($m['descricao']); ?></td>
                                        <td><span class="<?php echo obterClasseTipo($m['tipo']); ?>"><?php echo obterLabelTipo($m['tipo']); ?></span></td>
                                        <td class="<?php echo obterClasseTipo($m['tipo']); ?>"><?php echo formatarReais($m['valor']); ?></td>
                                        <td><span class="badge <?php echo $m['status'] === 'confirmado' ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo obterLabelStatus($m['status']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php $first = false; endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nova Conta -->
<div class="modal fade" id="modalNovaConta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nova Conta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="criar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome da Conta *</label>
                        <input type="text" class="form-control" name="nome" placeholder="Ex: Poupança, Caixinha, Investimentos..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição</label>
                        <input type="text" class="form-control" name="descricao" placeholder="Descrição opcional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Saldo Inicial (R$)</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="saldo_inicial" value="0" placeholder="0,00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Criar Conta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>