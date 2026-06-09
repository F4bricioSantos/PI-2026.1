<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Sessao no banco (PostgreSQL) para persistir apos restart do container
(function () {
    $host   = getenv('DB_HOST');
    $port   = getenv('DB_PORT');
    $dbname = getenv('DB_NAME');
    $user   = getenv('DB_USER');
    $pass   = getenv('DB_PASS');
    if (!$host || !$dbname) return;
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
            session_id TEXT PRIMARY KEY,
            data TEXT NOT NULL DEFAULT '',
            last_active TIMESTAMP DEFAULT NOW()
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sessions_last_active ON sessions(last_active)");

        $maxlifetime = 30 * 24 * 3600; // 30 dias
        ini_set('session.gc_maxlifetime', $maxlifetime);
        ini_set('session.cookie_lifetime', $maxlifetime);
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);

        session_set_save_handler(
            function () use ($pdo) { return true; }, // open
            function () use ($pdo) {                 // close
                $pdo = null;
                return true;
            },
            function ($id) use ($pdo): string {      // read
                $stmt = $pdo->prepare("SELECT data FROM sessions WHERE session_id = ? AND last_active > NOW() - INTERVAL '30 days'");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                return $row ? $row['data'] : '';
            },
            function ($id, $data) use ($pdo): bool { // write
                $stmt = $pdo->prepare("INSERT INTO sessions (session_id, data, last_active) VALUES (?, ?, NOW()) ON CONFLICT (session_id) DO UPDATE SET data = EXCLUDED.data, last_active = NOW()");
                $stmt->execute([$id, $data]);
                return true;
            },
            function ($id) use ($pdo): bool {         // destroy
                $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = ?");
                $stmt->execute([$id]);
                return true;
            },
            function ($max) use ($pdo): int {          // gc
                $stmt = $pdo->prepare("DELETE FROM sessions WHERE last_active < NOW() - INTERVAL '30 days'");
                $stmt->execute();
                return $stmt->rowCount();
            }
        );
        session_set_cookie_params([
            'lifetime' => $maxlifetime,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } catch (PDOException $e) {
        // fallback silencioso para sessions em arquivo
    }
})();

$routes = [
    ''                  => '/frontend/index.php',
    '/login'            => '/frontend/pages/login.php',
    '/cadastro'         => '/frontend/pages/cadastro.php',
    '/dashboard'        => '/frontend/pages/dashboard.php',
    '/perfil'           => '/frontend/pages/perfil.php',
    '/gerenciar'        => '/frontend/pages/gerenciar.php',
    '/portfolio'        => '/frontend/pages/portfolio.php',
    '/novo-servico'     => '/frontend/pages/novo-servico.php',
    '/chat'             => '/frontend/pages/chat.php',
    '/meus-pedidos'     => '/frontend/pages/meus-pedidos.php',
    '/detalhes'         => '/frontend/pages/detalhes.php',
    '/esqueci-senha'    => '/frontend/pages/esqueci-senha.php',
    '/avaliar-prestador'=> '/frontend/pages/avaliar-prestador.php',
    '/ver-perfil'       => '/frontend/pages/ver-perfil.php',
    '/admin'            => '/frontend/pages/admin_dashboard.php',
    '/logout'           => '/backend/controllers/AuthController.php',
];

if ($uri === '/test-env') { diagnostic(); exit; }

if (isset($routes[$uri])) {
    $file = __DIR__ . $routes[$uri];
    if ($uri === '/logout') {
        $_GET['action'] = 'logout';
    }
    chdir(dirname($file));
    require $file;
    exit;
}

// Fallback: se a URL termina com .php, tenta sem (para links antigos)
if (str_ends_with($uri, '.php') && isset($routes[substr($uri, 0, -4)])) {
    $uri = substr($uri, 0, -4);
    $file = __DIR__ . $routes[$uri];
    chdir(dirname($file));
    require $file;
    exit;
}

http_response_code(404);
echo 'Not Found';

function diagnostic(): void {
    echo "<h2>Diagnóstico de Ambiente</h2>";
    echo "<h3>Variáveis de Ambiente (getenv)</h3>";
    echo "<table border='1' cellpadding='5'>";
    $vars = ['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS','SB_URL','SB_KEY','SB_SECRET_KEY','SMTP_HOST','SMTP_PORT','SMTP_USER','SMTP_PASS'];
    foreach ($vars as $v) {
        $val = getenv($v);
        $display = (in_array($v, ['DB_PASS','SB_KEY','SB_SECRET_KEY','SMTP_PASS'])) ? ($val ? '***definido***' : 'VAZIO') : ($val ?: 'VAZIO');
        echo "<tr><td>$v</td><td>$display</td></tr>";
    }
    echo "</table>";
    echo "<h3>PHP Extensions</h3>";
    echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'OK' : 'FALTA') . "<br>";
    echo "pgsql: " . (extension_loaded('pgsql') ? 'OK' : 'FALTA') . "<br>";
    echo "<h3>Teste de Conexão</h3>";
    try {
        $host   = getenv('DB_HOST')   ?: 'VAZIO';
        $port   = getenv('DB_PORT')   ?: 'VAZIO';
        $dbname = getenv('DB_NAME')   ?: 'VAZIO';
        $user   = getenv('DB_USER')   ?: 'VAZIO';
        $pass   = getenv('DB_PASS')   ?: 'VAZIO';
        $dsn    = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
        echo "DSN: " . htmlspecialchars($dsn) . "<br>";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "Conexão: <span style='color:green'>OK</span><br>";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Usuários na tabela: " . $row['total'] . "<br>";
        $stmt2 = $pdo->query("SELECT email FROM usuarios");
        echo "Emails cadastrados:<br>";
        while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . htmlspecialchars($r['email']) . "<br>";
        }
    } catch (Exception $e) {
        echo "Erro: <span style='color:red'>" . htmlspecialchars($e->getMessage()) . "</span><br>";
    }
}
