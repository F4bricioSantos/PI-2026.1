<?php

$host = "aws-1-sa-east-1.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.yplpxzmwtkencrrtxmof";
$password = "bdreformaai1356";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Conectado com sucesso!";

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}