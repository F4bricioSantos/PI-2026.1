<?php
echo "<h2>Diagnóstico de Ambiente</h2>";

echo "<h3>Variáveis de Ambiente (getenv)</h3>";
echo "<table border='1' cellpadding='5'>";
$vars = ['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS','SB_URL','SB_KEY','SB_SECRET_KEY','SMTP_HOST','SMTP_PORT','SMTP_USER','SMTP_PASS'];
foreach ($vars as $v) {
    $val = getenv($v);
    $display = ($v === 'DB_PASS' || $v === 'SB_KEY' || $v === 'SB_SECRET_KEY' || $v === 'SMTP_PASS') ? ($val ? '***definido***' : 'VAZIO') : ($val ?: 'VAZIO');
    echo "<tr><td>$v</td><td>$display</td></tr>";
}
echo "</table>";

echo "<h3>PHP Extensions</h3>";
echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'OK' : 'FALTA') . "<br>";
echo "pgsql: " . (extension_loaded('pgsql') ? 'OK' : 'FALTA') . "<br>";

echo "<h3>Teste de Conexão</h3>";
try {
    $host = getenv('DB_HOST') ?: 'VAZIO';
    $port = getenv('DB_PORT') ?: 'VAZIO';
    $dbname = getenv('DB_NAME') ?: 'VAZIO';
    $user = getenv('DB_USER') ?: 'VAZIO';
    $pass = getenv('DB_PASS') ?: 'VAZIO';
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    echo "DSN: " . htmlspecialchars($dsn) . "<br>";
    
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Conexão: <span style='color:green'>OK</span><br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Usuários na tabela: " . $row['total'] . "<br>";
    
    $stmt2 = $pdo->query("SELECT email FROM usuarios LIMIT 5");
    echo "Emails cadastrados:<br>";
    while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . htmlspecialchars($r['email']) . "<br>";
    }
    
} catch (Exception $e) {
    echo "Erro: <span style='color:red'>" . htmlspecialchars($e->getMessage()) . "</span><br>";
}
