<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/Conexao.php';
require_once __DIR__ . '/../controllers/ChatController.php';

$controller = new ChatController($pdo);
$metodo     = $_SERVER['REQUEST_METHOD'];
$acao       = $_GET['acao'] ?? '';

if ($metodo === 'GET') { 

    if ($acao === 'listar_contatos') {
        $controller->listarContatosConversas();

    } elseif ($acao === 'unread_count') {
        $controller->contarNaoLidas();

    } elseif ($acao === 'contract_status') {
        $controller->verificarStatusContrato();

    } else {
        $controller->listarMensagens();
    }

} elseif ($metodo === 'POST') { 
    if ($acao === 'upload') {
        $controller->uploadImagem();
    } elseif ($acao === 'mark_read') {
        $dados = json_decode(file_get_contents('php://input'), true) ?? [];
        $remetenteId = (int)($dados['remetente_id'] ?? 0);
        $controller->marcarComoLido($remetenteId);
    } else {
        $dados     = json_decode(file_get_contents('php://input'), true) ?? [];
        $texto     = isset($dados['mensagem'])   ? trim($dados['mensagem'])   : '';
        $urlImagem = isset($dados['url_imagem']) ? trim($dados['url_imagem']) : null;
        $controller->enviarMensagem($texto, $urlImagem ?: null);
    }

} elseif ($metodo === 'PUT') { 
    $dados  = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($dados['id']      ?? 0);
    $texto  = isset($dados['message']) ? trim($dados['message']) : '';
    $controller->editarMensagem($id, $texto);

} elseif ($metodo === 'DELETE') {

    $dados = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($dados['id'] ?? 0);
    $controller->deletarMensagem($id);

} else {
    http_response_code(405);
    echo json_encode(['erro' => 'Método HTTP não suportado']);
}