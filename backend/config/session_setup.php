<?php
function setup_db_session(?PDO $pdo = null): void {
    if ($pdo === null) {
        $host   = getenv('DB_HOST');
        $port   = getenv('DB_PORT');
        $dbname = getenv('DB_NAME');
        $user   = getenv('DB_USER');
        $pass   = getenv('DB_PASS');
        if (!$host || !$dbname) return;
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            return;
        }
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
            session_id TEXT PRIMARY KEY,
            data TEXT NOT NULL DEFAULT '',
            last_active TIMESTAMP DEFAULT NOW()
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sessions_last_active ON sessions(last_active)");
    } catch (PDOException $e) {
        return;
    }

    $maxlifetime = 30 * 24 * 3600;
    ini_set('session.gc_maxlifetime', $maxlifetime);
    ini_set('session.cookie_lifetime', $maxlifetime);
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    session_set_cookie_params([
        'lifetime' => $maxlifetime,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_set_save_handler(
        function () use ($pdo) { return true; },
        function () use ($pdo) { $pdo = null; return true; },
        function ($id) use ($pdo): string {
            $stmt = $pdo->prepare("SELECT data FROM sessions WHERE session_id = ? AND last_active > NOW() - INTERVAL '30 days'");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row ? $row['data'] : '';
        },
        function ($id, $data) use ($pdo): bool {
            $stmt = $pdo->prepare("INSERT INTO sessions (session_id, data, last_active) VALUES (?, ?, NOW()) ON CONFLICT (session_id) DO UPDATE SET data = EXCLUDED.data, last_active = NOW()");
            $stmt->execute([$id, $data]);
            return true;
        },
        function ($id) use ($pdo): bool {
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = ?");
            $stmt->execute([$id]);
            return true;
        },
        function ($max) use ($pdo): int {
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE last_active < NOW() - INTERVAL '30 days'");
            $stmt->execute();
            return $stmt->rowCount();
        }
    );
}
