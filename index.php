<?php
require_once 'config/config.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . baseUrl('pages/dashboard.php'));
} else {
    header('Location: ' . baseUrl('pages/login.php'));
}
exit;
?>