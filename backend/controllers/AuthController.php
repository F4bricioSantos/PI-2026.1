<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/Conexao.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/EmailService.php';

use Backend\Services\EmailService;

$action = $_GET['action'] ?? '';

match ($action) {
    'enviar_codigo_verificacao' => enviarCodigoVerificacao($pdo),
    'cadastrar'                 => cadastrar($pdo),
    'login'                     => login($pdo),
    'logout'                    => logout(),
    default                     => responder(405, ['sucesso' => false, 'mensagem' => 'Ação não reconhecida.'])
};

function enviarCodigoVerificacao(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);

    $nome  = trim($_POST['nome']  ?? '');
    $cpf   = trim($_POST['cpf']   ?? '');
    $email = trim($_POST['email'] ?? '');

    $erros = [];

    if (empty($nome))
        $erros['nome'] = 'Por favor, insira seu nome completo.';

    $cpfNumerico = preg_replace('/\D/', '', $cpf);
    if (strlen($cpfNumerico) !== 11)
        $erros['cpf'] = 'Por favor, insira um CPF válido.';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $erros['email'] = 'Por favor, insira um e-mail válido.';

    if (!empty($erros))
        responder(200, ['sucesso' => false, 'erros' => $erros]);

    $user   = new User($pdo);
    $existe = $user->verificarExistencia($email, $cpfNumerico);

    if ($existe === true)
        responder(200, ['sucesso' => false, 'erros' => ['email' => 'Este e-mail ou CPF já está cadastrado.']]);

    $codigoToken = (string)rand(100000, 999999);

    $_SESSION['registro_token'] = $codigoToken;
    $_SESSION['registro_token_expira'] = time() + (15 * 60);

    $primeiroNome = explode(' ', $nome)[0];
    $assunto   = "Seu código de verificação — ReformAí";
    $corpoHTML = "
        <div style='font-family: Arial, sans-serif; max-width: 460px; padding: 30px; border: 1px solid #e2e8f0; border-radius: 16px; margin: 0 auto; background-color: #ffffff;'>
            <h2 style='color: #f97316; margin-top: 0; font-size: 22px;'>Olá, {$primeiroNome}!</h2>
            <p style='color: #475569; font-size: 14px; line-height: 1.5;'>Seu código de segurança para confirmar e finalizar o seu cadastro no <strong>ReformAí</strong> é:</p>
            <div style='background-color: #fff7ed; border: 1px dashed #fdba74; padding: 18px; text-align: center; font-size: 26px; font-weight: bold; color: #ea580c; letter-spacing: 5px; margin: 24px 0; border-radius: 8px;'>
                {$codigoToken}
            </div>
            <p style='color: #94a3b8; font-size: 12px; line-height: 1.4; border-top: 1px solid #f1f5f9; padding-top: 16px; margin-bottom: 0;'>
                Este código expira em 15 minutos.<br>Se você não iniciou este cadastro no nosso sistema, por favor ignore este e-mail.
            </p>
        </div>
    ";

    $emailEnviado = EmailService::enviar($email, $nome, $assunto, $corpoHTML);

    if ($emailEnviado) {
        responder(200, ['sucesso' => true, 'mensagem' => 'Código enviado com sucesso!']);
    } else {
        responder(200, [
            'sucesso' => true, 
            'token_desenvolvimento' => $codigoToken,
            'mensagem' => 'Código gerado localmente (SMTP instável): ' . $codigoToken
        ]);
    }
}

function cadastrar(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);

    $nome        = trim($_POST['nome']            ?? '');
    $cpf         = trim($_POST['cpf']             ?? '');
    $email       = trim($_POST['email']           ?? '');
    $senha       = $_POST['senha']                ?? '';
    $confirma    = $_POST['confirmar-senha']      ?? '';
    $telefone    = trim($_POST['telefone']        ?? '') ?: null;
    $fluxo       = trim($_POST['fluxo']           ?? 'cliente');
    $codigoToken = trim($_POST['codigo_token']    ?? '');

    if (!isset($_SESSION['registro_token']) || time() > $_SESSION['registro_token_expira']) {
        responder(200, ['sucesso' => false, 'mensagem' => 'Código de verificação expirou. Solicite um novo envio.']);
    }

    if ($codigoToken !== $_SESSION['registro_token']) {
        responder(200, ['sucesso' => false, 'mensagem' => 'Código incorreto. Verifique sua caixa de entrada.']);
    }

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
        responder(200, ['sucesso' => false, 'erros' => $erros]);

    $user   = new User($pdo);
    $existe = $user->verificarExistencia($email, $cpfNumerico);

    if ($existe === true)
        responder(200, ['sucesso' => false, 'erros' => ['email' => 'Este e-mail ou CPF já está cadastrado.']]);

    $ok = $user->cadastrar($nome, $email, $cpfNumerico, $senha, $telefone);

    if (!$ok)
        responder(200, ['sucesso' => false, 'mensagem' => 'Não foi possível realizar o cadastro.']);

    unset($_SESSION['registro_token']);
    unset($_SESSION['registro_token_expira']);

    $novo = $user->buscarPorEmail($email);
    $_SESSION['usuario_id']   = $novo['id'];
    $_SESSION['usuario_nome'] = $novo['nome'];
    $_SESSION['logado']       = true;
    $_SESSION['fluxo']        = $fluxo;

    if ($fluxo === 'prestador') {
        responder(200, [
            'sucesso'  => true,
            'redirect' => '/PI-2026.1/frontend/Pages/novo-servico.php',
        ]);
    } else {
        responder(200, [
            'sucesso'  => true,
            'redirect' => '/PI-2026.1/frontend/Pages/dashboard.php',
        ]);
    }
}

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
        responder(200, ['sucesso' => false, 'erros' => $erros]);

    $user = new User($pdo);
    $ok   = $user->login($email, $senha);

    if (!$ok)
        responder(200, ['sucesso' => false, 'mensagem' => 'E-mail ou senha inválidos.']);

    responder(200, [
        'sucesso'  => true,
        'redirect' => '/PI-2026.1/frontend/Pages/dashboard.php',
    ]);
}

function logout(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_unset();
    session_destroy();
    header('Content-Type: text/html');
    header('Location: /PI-2026.1/frontend/Pages/login.php');
    exit;
}

function responder(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados);
    exit;
}