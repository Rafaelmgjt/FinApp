<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SYSTEM_NAME; ?> - v<?php echo SYSTEM_VERSION; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0066cc;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        body.dark-theme {
            --primary-color: #4f5d73;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #17a2b8;
            --light-color: #212529;
            --dark-color: #e9ecef;
            background-color: #121416;
            color: #f8f9fa;
        }

        body.dark-theme .navbar {
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
        }

        body.dark-theme .dropdown-menu {
            background-color: #1c2025;
            border: 1px solid #2a2f37;
        }

        body.dark-theme .dropdown-item {
            color: #f8f9fa;
        }

        body.dark-theme .dropdown-item i {
            color: #f8f9fa;
        }

        body.dark-theme .dropdown-item:hover,
        body.dark-theme .dropdown-item:focus {
            background-color: #2a2f37;
            color: #ffffff;
        }

        body.dark-theme .dropdown-divider {
            border-top-color: #343a40;
        }


        
        body.dark-theme .card,
        body.dark-theme .table,
        body.dark-theme .modal-content,
        body.dark-theme .auth-card,
        body.dark-theme .form-control,
        body.dark-theme .form-select,
        body.dark-theme .footer,
        body.dark-theme .list-group-item,
        body.dark-theme .table-responsive, 
        body.dark-theme .table-light {
            background-color: #1c2025;
            color: #f8f9fa;
            border-color: #2a2f37;
        }
        
        body.dark-theme .table-secondary{
            background-color: #2a2f37;
        }

        body.dark-theme .table thead {
            background-color: #262b33;
        }

        body.dark-theme .table tbody tr:hover {
            background-color: #2a2f37;
            color: #f8f9fa;
        }

        body.dark-theme .card-header,
        body.dark-theme .modal-header {
            background: linear-gradient(135deg, #343a40 0%, #212529 100%);
            color: #f8f9fa;
        }

        body.dark-theme .form-control,
        body.dark-theme .form-select {
            background-color: #1f242a;
            color: #f8f9fa;
            border-color: #2a2f37;
        }

        body.dark-theme .btn-primary,
        body.dark-theme .btn-success,
        body.dark-theme .btn-danger,
        body.dark-theme .btn-secondary {
            color: #ffffff;
        }

        body.dark-theme .alert {
            border-color: #2a2f37;
        }

        body.dark-theme .alert-success {
            background-color: #1f3520;
            color: #c8f7d4;
        }

        body.dark-theme .alert-danger {
            background-color: #3f1e22;
            color: #f7c6c8;
        }

        body.dark-theme .alert-warning {
            background-color: #3a2b12;
            color: #f0d88f;
        }

        body.dark-theme .alert-info {
            background-color: #163a45;
            color: #b8e0f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0052a3 100%);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            color: white !important;
        }
        
        .container-main {
            flex: 1;
            padding: 30px 0;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0052a3 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
            padding: 1.25rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #0052a3;
            border-color: #0052a3;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #218838;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
        }
        
        .table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }
        
        .table thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table thead th {
            font-weight: 600;
            color: var(--dark-color);
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .table-responsive {
            border-radius: 8px;
        }
        
        .dashboard-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin-bottom: 20px;
        }
        
        .dashboard-card h5 {
            font-size: 0.9rem;
            font-weight: 600;
            opacity: 0.9;
        }
        
        .dashboard-card .valor {
            font-size: 2rem;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .card-receita {
            background: linear-gradient(135deg, var(--success-color) 0%, #20c997 100%);
        }
        
        .card-despesa {
            background: linear-gradient(135deg, var(--danger-color) 0%, #e74c3c 100%);
        }
        
        .card-saldo {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0052a3 100%);
        }
        
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: var(--success-color);
        }
        
        .badge-danger {
            background-color: var(--danger-color);
        }
        
        .badge-warning {
            background-color: var(--warning-color);
            color: black;
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            margin-top: 50px;
            text-align: center;
        }
        
        .text-success {
            color: var(--success-color) !important;
        }
        
        .text-danger {
            color: var(--danger-color) !important;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0052a3 100%);
            color: white;
            border-radius: 8px 8px 0 0;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .list-group-item {
            border: 1px solid #ddd;
            padding: 1rem;
        }
        
        .list-group-item:first-child {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        
        .list-group-item:last-child {
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        
        .nav-tabs .nav-link {
            color: var(--dark-color);
            border: none;
            border-bottom: 3px solid transparent;
        }
        
        .nav-tabs .nav-link.active {
            background-color: transparent;
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }
        
        .auth-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, #0052a3 100%);
        }
        
        .auth-card {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .auth-card h2 {
            color: var(--dark-color);
            margin-bottom: 30px;
            text-align: center;
            font-weight: bold;
        }
        
        .auth-card .form-group {
            margin-bottom: 20px;
        }
        
        .auth-card label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .auth-card .btn {
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .auth-card .link-footer {
            text-align: center;
            margin-top: 20px;
        }
        
        .auth-card a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .auth-card a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .dashboard-card .valor {
                font-size: 1.5rem;
            }
            
            .table {
                font-size: 0.9rem;
            }
            
            .auth-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo baseUrl('index.php'); ?>">
                <i class="fas fa-wallet"></i> <?php echo SYSTEM_NAME; ?>
            </a>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item me-2">
                            <button id="themeToggleBtn" class="btn btn-sm btn-outline-light" type="button">
                                <i class="fas fa-moon"></i> Dark
                            </button>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl('pages/dashboard.php'); ?>">
                                <i class="fas fa-chart-line"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl('pages/contas.php'); ?>">
                                <i class="fas fa-piggy-bank"></i> Contas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl('pages/movimentacoes.php'); ?>">
                                <i class="fas fa-exchange-alt"></i> Movimentações
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo baseUrl('pages/relatorios.php'); ?>">
                                <i class="fas fa-file-chart-line"></i> Relatórios
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo substr(obterUsuarioAtual()['nome'], 0, 15); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo baseUrl('pages/perfil.php'); ?>">
                                        <i class="fas fa-user-edit"></i> Perfil
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo baseUrl('logout.php'); ?>">
                                        <i class="fas fa-sign-out-alt"></i> Sair
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Main Container -->
    <div class="container-main">
        <div class="container">