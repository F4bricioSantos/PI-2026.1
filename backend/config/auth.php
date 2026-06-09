<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verifica se existe sessão
if (empty($_SESSION['usuario_id'])) {
    header('Location: /login');
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
    header('Location: /login?erro=usuario_inexistente');
    exit;
}

// 3. Regenera ID da sessão periodicamente (a cada 30 minutos) para prevenir fixation
if (empty($_SESSION['_ultima_regeneracao']) || (time() - $_SESSION['_ultima_regeneracao']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['_ultima_regeneracao'] = time();
}
