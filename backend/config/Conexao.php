<?php

$host     = "aws-1-sa-east-1.pooler.supabase.com";
$port     = "6543";
$dbname   = "postgres";
$user     = "postgres.yplpxzmwtkencrrtxmof";
$password = "bdreformaai1356";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

} catch (PDOException $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de conexão com o banco.']);
    exit;
}