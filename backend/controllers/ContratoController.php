<?php
require_once '../config/auth.php';
require_once '../config/Conexao.php';
require_once '../models/Contrato.php';

header('Content-Type: application/json');

$idUsuarioLogado = $_SESSION['usuario_id'] ?? null;
if (!$idUsuarioLogado) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado.']);
    exit;
}

$contratoModel = new Contrato($pdo);
$contratoModel->executarRotinaConclusaoAutomatica();

$acao = $_GET['acao'] ?? '';

// ─── Listar serviços do prestador ────────────────────────────────────────────
if ($acao === 'listar_servicos' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $prestadorId = (int)($_GET['prestador_id'] ?? 0);
    $servicos = $contratoModel->listarServicosPrestador($prestadorId);
    echo json_encode($servicos);
    exit;
}

// ─── Propor contrato ─────────────────────────────────────────────────────────
if ($acao === 'propor_contrato' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data        = json_decode(file_get_contents('php://input'), true);
    $prestadorId = (int)($data['prestador_id'] ?? 0);
    $servicoId   = (int)($data['servico_id']   ?? 0);

    if ($idUsuarioLogado === $prestadorId) {
        http_response_code(400);
        echo json_encode(['erro' => 'Você não pode contratar a si mesmo.']);
        exit;
    }

    if (!$servicoId) {
        http_response_code(400);
        echo json_encode(['erro' => 'Nenhum serviço foi selecionado.']);
        exit;
    }

    if (!$contratoModel->checarSeTemServico($prestadorId)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Este usuário não possui serviços cadastrados.']);
        exit;
    }

    $sucesso = $contratoModel->criarContratoImediato($idUsuarioLogado, $prestadorId, $servicoId);
    echo json_encode(['sucesso' => $sucesso]);
    exit;
}

// ─── Mudar status ─────────────────────────────────────────────────────────────
if ($acao === 'mudar_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $contratoId = (int)$data['id'];
    $novoStatus = $data['status'];

    $contrato = $contratoModel->buscarPorId($contratoId);
    if (!$contrato) {
        http_response_code(404);
        echo json_encode(['erro' => 'Contrato inválido.']);
        exit;
    }

    $souPrestador = ($idUsuarioLogado == $contrato['prestador_id']);
    $souCliente   = ($idUsuarioLogado == $contrato['cliente_id']);

    // Apenas o prestador pode aceitar a proposta
    if ($novoStatus === 'aceito') {
        if (!$souPrestador) {
            http_response_code(403);
            echo json_encode(['erro' => 'Apenas o prestador pode aceitar a proposta.']);
            exit;
        }
        $sucesso = $contratoModel->atualizarStatus($contratoId, 'aceito');
        echo json_encode(['sucesso' => $sucesso]);
        exit;
    }

    // Cancelamento: qualquer uma das partes pode cancelar
    if ($novoStatus === 'cancelado') {
        if (!$souPrestador && !$souCliente) {
            http_response_code(403);
            echo json_encode(['erro' => 'Sem permissão para cancelar este contrato.']);
            exit;
        }
        $sucesso = $contratoModel->atualizarStatus($contratoId, 'cancelado');
        echo json_encode(['sucesso' => $sucesso]);
        exit;
    }

    // Conclusão: fluxo em duas etapas
    if ($novoStatus === 'concluido') {
        if ($souPrestador) {
            // Etapa 1: prestador entrega — marca finalizado_prestador_em
            $sucesso = $contratoModel->marcarComoFinalizadoPrestador($contratoId);
            echo json_encode(['sucesso' => $sucesso]);
            exit;
        }

        if ($souCliente) {
            // Etapa 2: cliente confirma — só pode se o prestador já entregou
            if ($contrato['finalizado_prestador_em'] === null) {
                http_response_code(400);
                echo json_encode(['erro' => 'O prestador ainda não marcou o serviço como entregue.']);
                exit;
            }
            $sucesso = $contratoModel->atualizarStatus($contratoId, 'concluido');
            echo json_encode(['sucesso' => $sucesso]);
            exit;
        }

        http_response_code(403);
        echo json_encode(['erro' => 'Sem permissão para concluir este contrato.']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['erro' => 'Ação inválida.']);
    exit;
}