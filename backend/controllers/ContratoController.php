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

if ($acao === 'listar_servicos' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $prestadorId = (int)($_GET['prestador_id'] ?? 0);
    $servicos = $contratoModel->listarServicosPrestador($prestadorId);
    echo json_encode($servicos);
    exit;
}

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
    // Apenas o prestador pode aceitar a proposta e mudar o status direto para 'aceito'
    if ($novoStatus === 'aceito') {
        if (!$souPrestador) {
            http_response_code(403);
            echo json_encode(['erro' => 'Apenas o prestador pode aceitar a proposta.']);
            exit;
        }
        $sucesso = $contratoModel->aceitarProposta($contratoId);
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

    // Conclusão: fluxo em duas etapas ou confirmação definitiva
    if ($novoStatus === 'concluido') {
        if (!$souPrestador && !$souCliente) {
            http_response_code(403);
            echo json_encode(['erro' => 'Sem permissão para concluir este contrato.']);
            exit;
        }

        if ($souPrestador) {
            if ($contrato['finalizado_cliente_em'] !== null) {
                // Confirmando finalização definitiva
                $sucesso = $contratoModel->confirmarConclusaoDefinitiva($contratoId);
            } else {
                // Marcando conclusão do prestador primeiro
                $sucesso = $contratoModel->marcarComoFinalizadoPrestador($contratoId);
            }
        } else { // souCliente
            if ($contrato['finalizado_prestador_em'] !== null) {
                // Confirmando finalização definitiva
                $sucesso = $contratoModel->confirmarConclusaoDefinitiva($contratoId);
            } else {
                // Marcando conclusão do cliente primeiro
                $sucesso = $contratoModel->marcarComoFinalizadoCliente($contratoId);
            }
        }

        echo json_encode(['sucesso' => $sucesso]);
        exit;
    }

    // Retornar para "Em andamento"
    if ($novoStatus === 'em_andamento') {
        if (!$souPrestador && !$souCliente) {
            http_response_code(403);
            echo json_encode(['erro' => 'Sem permissão para alterar este contrato.']);
            exit;
        }
        $sucesso = $contratoModel->voltarParaAndamento($contratoId);
        echo json_encode(['sucesso' => $sucesso]);
        exit;
    }
    exit;
}

if ($acao === 'salvar_avaliacao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $contratoId = (int)($data['contrato_id'] ?? 0);
    $nota       = (int)($data['nota']        ?? 0);
    $comentario = trim($data['comentario']   ?? '');

    if ($nota < 1 || $nota > 5) {
        http_response_code(400);
        echo json_encode(['erro' => 'Selecione uma nota de 1 a 5 estrelas.']);
        exit;
    }

    $contrato = $contratoModel->buscarPorId($contratoId);
    if (!$contrato) {
        http_response_code(404);
        echo json_encode(['erro' => 'Contrato inválido.']);
        exit;
    }

    $souCliente   = ($contrato['cliente_id'] == $idUsuarioLogado);
    $souPrestador = ($contrato['prestador_id'] == $idUsuarioLogado);

    if (!$souCliente && !$souPrestador) {
        http_response_code(403);
        echo json_encode(['erro' => 'Você não tem permissão para avaliar este contrato.']);
        exit;
    }

    if ($contrato['status'] !== 'concluido') {
        http_response_code(400);
        echo json_encode(['erro' => 'Este contrato ainda não foi concluído.']);
        exit;
    }

    if ($souCliente && $contrato['avaliado']) {
        http_response_code(400);
        echo json_encode(['erro' => 'Você já avaliou este contrato.']);
        exit;
    }
    if ($souPrestador && $contrato['avaliado_prestador']) {
        http_response_code(400);
        echo json_encode(['erro' => 'Você já avaliou este contrato.']);
        exit;
    }

    $avaliadorTipo = $souCliente ? 'cliente' : 'prestador';

    $sucesso = $contratoModel->salvarAvaliacao(
        $contratoId,
        $contrato['cliente_id'],
        $contrato['prestador_id'],
        $contrato['servico_id'],
        $nota,
        $comentario,
        $avaliadorTipo
    );

    echo json_encode(['sucesso' => $sucesso]);
    exit;
}

http_response_code(400);
echo json_encode(['erro' => 'Ação inválida.']);
exit;