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
$acao = $_GET['acao'] ?? '';

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
        $stmt = $pdo->prepare("SELECT id FROM favoritos_servicos WHERE usuario_id = :uid AND servico_id = :sid");
        $stmt->execute([':uid' => $idUsuarioLogado, ':sid' => $servicoId]);
        $favorito = $stmt->fetch();
        $novoEstado = false;
        if ($favorito) {
            $del = $pdo->prepare("DELETE FROM favoritos_servicos WHERE usuario_id = :uid AND servico_id = :sid");
            $del->execute([':uid' => $idUsuarioLogado, ':sid' => $servicoId]);
            $novoEstado = false;
        } else { 
            $ins = $pdo->prepare("INSERT INTO favoritos_servicos (usuario_id, servico_id) VALUES (:uid, :sid)");
            $ins->execute([':uid' => $idUsuarioLogado, ':sid' => $servicoId]);
            $novoEstado = true;
        }
        $upd = $pdo->prepare("
            UPDATE contratos 
            SET favorito = :estado, atualizado_em = NOW()
            WHERE (cliente_id = :uid OR prestador_id = :uid) AND servico_id = :sid AND status NOT IN ('concluido', 'cancelado')
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
