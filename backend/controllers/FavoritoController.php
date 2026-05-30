<?php
require_once '../config/auth.php';
require_once '../config/Conexao.php';

header('Content-Type: application/json');

$idUsuarioLogado = $_SESSION['usuario_id'] ?? null;
if (!$idUsuarioLogado) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado.']);
    exit;
}

// Execução preventiva de DDL para garantir compatibilidade local/online
try {
    // 1. Garante a tabela favoritos_servicos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS favoritos_servicos (
            id SERIAL PRIMARY KEY,
            usuario_id INT NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
            servico_id INT NOT NULL REFERENCES servicos(id) ON DELETE CASCADE,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT unique_usuario_servico_fav UNIQUE(usuario_id, servico_id)
        )
    ");
    
    // 2. Garante a coluna favorito na tabela contratos
    $pdo->exec("
        ALTER TABLE contratos ADD COLUMN IF NOT EXISTS favorito BOOLEAN NOT NULL DEFAULT false;
    ");
} catch (PDOException $e) {
    error_log("DDL preventiva de favoritos: " . $e->getMessage());
}

$acao = $_GET['acao'] ?? '';

// Ação: Alternar Favorito (Toggle)
if ($acao === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $servicoId = (int)($data['servico_id'] ?? 0);

    if (!$servicoId) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID do serviço inválido.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Verifica se já está favoritado em favoritos_servicos
        $stmt = $pdo->prepare("SELECT id FROM favoritos_servicos WHERE usuario_id = :uid AND servico_id = :sid");
        $stmt->execute([':uid' => $idUsuarioLogado, ':sid' => $servicoId]);
        $favorito = $stmt->fetch();

        $novoEstado = false;

        if ($favorito) {
            // Se já existe, remove de favoritos_servicos
            $del = $pdo->prepare("DELETE FROM favoritos_servicos WHERE usuario_id = :uid AND servico_id = :sid");
            $del->execute([':uid' => $idUsuarioLogado, ':sid' => $servicoId]);
            $novoEstado = false;
        } else {
            // Se não existe, insere em favoritos_servicos
            $ins = $pdo->prepare("INSERT INTO favoritos_servicos (usuario_id, servico_id) VALUES (:uid, :sid)");
            $ins->execute([':uid' => $idUsuarioLogado, ':sid' => $servicoId]);
            $novoEstado = true;
        }

        // 2. Sincroniza a coluna 'favorito' na tabela de 'contratos' para todos os contratos desse serviço
        $upd = $pdo->prepare("
            UPDATE contratos 
            SET favorito = :estado 
            WHERE cliente_id = :uid AND servico_id = :sid
        ");
        $upd->bindValue(':estado', $novoEstado, PDO::PARAM_BOOL);
        $upd->bindValue(':uid', $idUsuarioLogado, PDO::PARAM_INT);
        $upd->bindValue(':sid', $servicoId, PDO::PARAM_INT);
        $upd->execute();

        $pdo->commit();
        echo json_encode(['sucesso' => true, 'favoritado' => $novoEstado]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['erro' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['erro' => 'Ação inválida.']);
exit;
