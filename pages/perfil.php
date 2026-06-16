<?php
require_once '../config/config.php';

$usuario = obterUsuarioAtual();
$usuario_id = $_SESSION['usuario_id'];
$erro = $sucesso = '';

// Atualizar nome/email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar') {
    $nome  = sanitizarEntrada($_POST['nome'] ?? '');
    $email = sanitizarEntrada($_POST['email'] ?? '');

    if (strlen($nome) < 3) {
        $erro = 'Nome deve ter pelo menos 3 caracteres.';
    } elseif (!validarEmail($email)) {
        $erro = 'Email inválido.';
    } else {
        // Verifica e-mail duplicado
        $existente = buscarUsuarioPorEmail($email);
        if ($existente && $existente['id'] != $usuario_id) {
            $erro = 'Este email já está em uso por outro usuário.';
        } else {
            $usuarios = lerCSV(USERS_FILE);
            foreach ($usuarios as &$u) {
                if ($u['id'] == $usuario_id) {
                    $u['nome']  = $nome;
                    $u['email'] = $email;
                    break;
                }
            }
            escreverCSV(USERS_FILE, $usuarios);
            $_SESSION['usuario_nome']  = $nome;
            $_SESSION['usuario_email'] = $email;
            $sucesso = 'Dados atualizados com sucesso!';
            $usuario = obterUsuarioAtual();
        }
    }
}

// Alterar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'senha') {
    $senha_atual  = $_POST['senha_atual'] ?? '';
    $senha_nova   = $_POST['senha_nova'] ?? '';
    $senha_conf   = $_POST['senha_confirma'] ?? '';

    if (!password_verify($senha_atual, $usuario['senha'])) {
        $erro = 'Senha atual incorreta.';
    } else {
        $v = validarSenha($senha_nova);
        if (!$v['valida']) {
            $erro = implode('<br>', $v['erros']);
        } elseif ($senha_nova !== $senha_conf) {
            $erro = 'As novas senhas não coincidem.';
        } else {
            $usuarios = lerCSV(USERS_FILE);
            foreach ($usuarios as &$u) {
                if ($u['id'] == $usuario_id) {
                    $u['senha'] = password_hash($senha_nova, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
                    break;
                }
            }
            escreverCSV(USERS_FILE, $usuarios);
            $sucesso = 'Senha alterada com sucesso!';
        }
    }
}

$contas     = buscarContasUsuario($usuario_id);
$movs       = buscarMovimentacoes($usuario_id);
$saldo_total = calcularSaldoTotal($usuario_id);
$total_receitas = calcularTotalReceitas($usuario_id);
$total_despesas = calcularTotalDespesas($usuario_id);
?>
<?php include '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title"><i class="fas fa-user-circle"></i> Meu Perfil</h3>
</div>

<?php if ($erro): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $erro; ?></div>
<?php endif; ?>
<?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $sucesso; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Cards de resumo -->
    <div class="col-12 mb-4">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="dashboard-card card-saldo">
                    <h5><i class="fas fa-wallet"></i> Saldo Total</h5>
                    <div class="valor"><?php echo formatarReais($saldo_total); ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dashboard-card card-receita">
                    <h5><i class="fas fa-arrow-up"></i> Total Receitas</h5>
                    <div class="valor"><?php echo formatarReais($total_receitas); ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dashboard-card card-despesa">
                    <h5><i class="fas fa-arrow-down"></i> Total Despesas</h5>
                    <div class="valor"><?php echo formatarReais($total_despesas); ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="dashboard-card" style="background:linear-gradient(135deg,#6f42c1,#9b59b6);">
                    <h5><i class="fas fa-list"></i> Movimentações</h5>
                    <div class="valor"><?php echo count($movs); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dados do perfil -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-user me-2"></i>Dados Pessoais</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="atualizar">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome</label>
                        <input type="text" class="form-control" name="nome"
                               value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" name="email"
                               value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Membro desde</label>
                        <input type="text" class="form-control" value="<?php echo formatarData($usuario['data_criacao']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Último acesso</label>
                        <input type="text" class="form-control" value="<?php echo formatarDataHora($usuario['ultimo_acesso']); ?>" readonly>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i> Salvar Alterações
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Alterar senha -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-lock me-2"></i>Alterar Senha</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="senha">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Senha Atual</label>
                        <input type="password" class="form-control" name="senha_atual" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nova Senha</label>
                        <input type="password" class="form-control" name="senha_nova" required>
                        <small class="text-muted">Mínimo 6 caracteres, com letras e números.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" name="senha_confirma" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fas fa-key me-1"></i> Alterar Senha
                    </button>
                </form>
            </div>
        </div>

        <!-- Minhas contas resumo -->
        <div class="card mt-0">
            <div class="card-header"><i class="fas fa-piggy-bank me-2"></i>Minhas Contas</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($contas as $c): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($c['nome']); ?></strong>
                            <?php if ($c['descricao']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($c['descricao']); ?></small>
                            <?php endif; ?>
                        </div>
                        <span class="fw-bold <?php echo (float)$c['saldo_atual'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo formatarReais($c['saldo_atual']); ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($contas)): ?>
                        <li class="list-group-item text-muted text-center">Nenhuma conta cadastrada.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>