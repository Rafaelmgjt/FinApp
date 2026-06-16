<?php
require_once '../config/config.php';

$erro = '';
$sucesso = '';

if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . baseUrl('pages/dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = sanitizarEntrada($_POST['nome'] ?? '');
    $email = sanitizarEntrada($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirma = $_POST['senha_confirma'] ?? '';

    if (empty($nome) || strlen($nome) < 3) {
        $erro = 'Nome deve ter pelo menos 3 caracteres.';
    } elseif (!validarEmail($email)) {
        $erro = 'Email inválido.';
    } else {
        $validacao = validarSenha($senha);
        if (!$validacao['valida']) {
            $erro = implode('<br>', $validacao['erros']);
        } elseif ($senha !== $senha_confirma) {
            $erro = 'As senhas não coincidem.';
        } else {
            $resultado = criarUsuario($nome, $email, $senha);
            if (isset($resultado['erro'])) {
                $erro = $resultado['erro'];
            } else {
                header('Location: ' . baseUrl('pages/login.php') . '?msg=cadastro_sucesso');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - <?php echo SYSTEM_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-color: #0066cc; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .auth-container { width: 100%; max-width: 440px; padding: 20px; }
        .auth-card {
            background: white; border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 40px;
        }
        .logo-area { text-align: center; margin-bottom: 20px; }
        .logo-area i { font-size: 2.5rem; color: var(--primary-color); }
        .auth-card h2 { color: #343a40; text-align: center; font-weight: bold; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #6c757d; margin-bottom: 25px; font-size: .95rem; }
        .form-group { margin-bottom: 18px; }
        .form-group label { font-weight: 600; color: #343a40; margin-bottom: 6px; display: block; }
        .form-control { border-radius: 8px; border: 1px solid #ddd; padding: .7rem; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 .2rem rgba(0,102,204,.25); }
        .btn-register {
            width: 100%; padding: .75rem; background: var(--primary-color);
            border: none; color: white; font-weight: 600; border-radius: 8px; cursor: pointer;
        }
        .btn-register:hover { background: #0052a3; color: white; }
        .link-footer { text-align: center; margin-top: 18px; font-size: .95rem; }
        .link-footer a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
        .link-footer a:hover { text-decoration: underline; }
        .alert { border-radius: 8px; border: none; margin-bottom: 18px; }
        .password-hints { font-size: .8rem; color: #6c757d; margin-top: 5px; }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <div class="logo-area"><i class="fas fa-wallet"></i></div>
        <h2>Criar Conta</h2>
        <p class="subtitle">Preencha os dados para se cadastrar</p>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nome completo</label>
                <input type="text" class="form-control" name="nome" placeholder="Seu nome" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email" placeholder="seu@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" class="form-control" name="senha" placeholder="Mínimo 6 caracteres" required>
                <div class="password-hints">Deve conter letras e números.</div>
            </div>
            <div class="form-group">
                <label>Confirmar Senha</label>
                <input type="password" class="form-control" name="senha_confirma" placeholder="Repita a senha" required>
            </div>
            <button type="submit" class="btn btn-register">
                <i class="fas fa-user-plus"></i> Cadastrar
            </button>
        </form>
        <div class="link-footer">
            Já tem conta? <a href="<?php echo baseUrl('pages/login.php'); ?>">Fazer login</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>