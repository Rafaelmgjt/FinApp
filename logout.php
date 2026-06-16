<?php
require_once 'config/config.php';
session_destroy();
header('Location: ' . BASE_URL . '/pages/login.php?msg=logout_sucesso');
exit;
?>