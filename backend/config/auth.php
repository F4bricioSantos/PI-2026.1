<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verifica se existe sessão
if (empty($_SESSION['usuario_id'])) {
    header('Location: /PI-2026.1/frontend/pages/login.php');
    exit;
}

// 2. Verifica se o usuário da sessão ainda existe no Banco de Dados
require_once __DIR__ . '/Conexao.php'; // Garante a conexão
$stmtCheckAuth = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id");
$stmtCheckAuth->execute([':id' => $_SESSION['usuario_id']]);

if (!$stmtCheckAuth->fetch()) {
    // Se o usuário foi deletado do banco, destrói a sessão e expulsa
    session_unset();
    session_destroy();
    header('Location: /PI-2026.1/frontend/pages/login.php?erro=usuario_inexistente');
    exit;
}