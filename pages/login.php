<?php
require_once '../config/config.php';

$erro = '';
$sucesso = '';

// Processa login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizarEntrada($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (!validarEmail($email)) {
        $erro = 'Email inválido!';
    } elseif (strlen($senha) < 6) {
        $erro = 'Senha inválida!';
    } else {
        $usuario = autenticarUsuario($email, $senha);
        
        if ($usuario) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['ultimo_atividade'] = time();
            
            header('Location: ' . baseUrl('pages/dashboard.php') . '?msg=login_sucesso');
            exit;
        } else {
            $erro = 'Email ou senha incorretos!';
        }
    }
}

// Se já está logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . baseUrl('pages/dashboard.php'));
    exit;
}

// Verifica mensagens da URL
$msg_url = obterMensagemURL();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SYSTEM_NAME; ?></title>
    <link rel="icon" type="image/ico" href="../assets/img/finapp.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #0066cc;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, #0052a3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }
        
        .auth-container {
            width: 100%;
            max-width: 490px;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
        }
        
        .auth-card h2 {
            color: var(--dark-color);
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .auth-card .subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 0.75rem;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary-color);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            margin-top: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-login:hover {
            background-color: #0052a3;
            color: white;
        }
        
        .link-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.95rem;
        }
        
        .link-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .link-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-area i {
            font-size: 3rem;
            color: var(--primary-color);
        }
        
        .form-check {
            margin-bottom: 20px;
        }
        
        .form-check-input {
            border-radius: 4px;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-label {
            font-weight: 500;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo-area">
                <i class="fas fa-wallet"></i>
            </div>
            
            <h2>Bem-vindo!</h2>
            <p class="subtitle">Entre em sua conta para continuar</p>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($msg_url): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $msg_url; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="seu@email.com" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Sua senha" required>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="lembrar" name="lembrar">
                    <label class="form-check-label" for="lembrar">
                        Lembrar-me
                    </label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>
            
            <div class="link-footer">
                Não tem uma conta? <a href="<?php echo baseUrl('pages/registro.php'); ?>">Cadastre-se aqui</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>