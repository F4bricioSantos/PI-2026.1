<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/Conexao.php';
require_once __DIR__ . '/../models/User.php';

$action = $_GET['action'] ?? '';

match ($action) {
    'cadastrar' => cadastrar($pdo),
    'login'     => login($pdo),
    default     => responder(405, ['sucesso' => false, 'mensagem' => 'Ação não reconhecida.'])
};

// ─────────────────────────────────────────────
function cadastrar(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);

    $nome     = trim($_POST['nome']          ?? '');
    $cpf      = trim($_POST['cpf']           ?? '');
    $email    = trim($_POST['email']         ?? '');
    $senha    = $_POST['senha']              ?? '';
    $confirma = $_POST['confirmar-senha']    ?? '';
    $telefone = trim($_POST['telefone']      ?? '') ?: null;

    $erros = [];

    if (empty($nome))
        $erros['nome'] = 'Por favor, insira seu nome completo.';

    $cpfNumerico = preg_replace('/\D/', '', $cpf);
    if (strlen($cpfNumerico) !== 11)
        $erros['cpf'] = 'Por favor, insira um CPF válido.';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $erros['email'] = 'Por favor, insira um e-mail válido.';

    if (strlen($senha) < 8)
        $erros['senha'] = 'A senha deve ter pelo menos 8 caracteres.';

    if ($senha !== $confirma)
        $erros['confirmar-senha'] = 'As senhas não coincidem.';

    if (!empty($erros))
        responder(422, ['sucesso' => false, 'erros' => $erros]);

    $user   = new User($pdo);
    $existe = $user->verificarExistencia($email, $cpfNumerico);

    if ($existe === true)
        responder(409, ['sucesso' => false, 'erros' => ['email' => 'Este e-mail ou CPF já está cadastrado.']]);

    $ok = $user->cadastrar($nome, $email, $cpfNumerico, $senha, $telefone);

    if (!$ok)
        responder(500, ['sucesso' => false, 'mensagem' => 'Não foi possível realizar o cadastro.']);

    responder(201, [
        'sucesso'  => true,
        'mensagem' => 'Cadastro realizado com sucesso!',
        'redirect' => '/PI-2026.1/frontend/pages/login.php',
    ]);
}

// ─────────────────────────────────────────────
function login(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha']      ?? '';

    $erros = [];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $erros['email'] = 'Por favor, insira um e-mail válido.';

    if (empty($senha))
        $erros['senha'] = 'Por favor, insira sua senha.';

    if (!empty($erros))
        responder(422, ['sucesso' => false, 'erros' => $erros]);

    $user = new User($pdo);
    $ok   = $user->login($email, $senha);

    if (!$ok)
        responder(401, ['sucesso' => false, 'erro_global' => 'E-mail ou senha inválidos.']);

    responder(200, [
        'sucesso'  => true,
        'redirect' => '/PI-2026.1/frontend/pages/dashboard.php',
    ]);
}

// ─────────────────────────────────────────────
function responder(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados);
    exit;
}