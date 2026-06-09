<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/Conexao.php';
require_once __DIR__ . '/../config/session_setup.php';
setup_db_session($pdo);
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/EmailService.php';

use Backend\Services\EmailService;

$action = $_GET['action'] ?? '';
match ($action) {
    'enviar_codigo_verificacao' => enviarCodigoVerificacao($pdo),
    'cadastrar'                 => cadastrar($pdo),
    'login'                     => login($pdo),
    'logout'                    => logout(),
    'enviar_codigo_reset'       => enviarCodigoReset($pdo),
    'verificar_codigo_reset'    => verificarCodigoReset(),
    'redefinir_senha'           => redefinirSenha($pdo),
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
    $codigoToken = (string)random_int(100000, 999999);
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
            'erro_email' => EmailService::$lastError,
            'mensagem' => 'Código gerado localmente: ' . $codigoToken
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
    responder(200, [
        'sucesso'  => true,
        'redirect' => '/perfil?fluxo=' . $fluxo . '&new=1',
    ]);
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
        responder(200, ['sucesso' => false, 'erro_global' => true, 'mensagem' => 'E-mail ou senha inválidos.']);
    // Regenera ID da sess�o ap�s login para prevenir session fixation
    session_regenerate_id(true);
    responder(200, [
        'sucesso'  => true,
        'redirect' => '/dashboard',
    ]);
}
function logout(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_unset();
    session_destroy();
    header('Content-Type: text/html');
    header('Location: /login');
    exit;
}
function enviarCodigoReset(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        responder(200, ['sucesso' => false, 'mensagem' => 'Insira um e-mail válido.']);
    $user    = new User($pdo);
    $usuario = $user->buscarPorEmail($email);
    if (!$usuario) {
        responder(200, ['sucesso' => true, 'mensagem' => 'Se o e-mail estiver cadastrado, você receberá o código.']);
    }
    $codigoToken = (string)random_int(100000, 999999);
    $_SESSION['reset_token']        = $codigoToken;
    $_SESSION['reset_email']        = $email;
    $_SESSION['reset_token_expira'] = time() + (15 * 60); // 15 minutos
    $_SESSION['reset_verificado']   = false;
    $primeiroNome = explode(' ', $usuario['nome'])[0];
    $assunto      = "Redefinição de senha — ReformAí";
    $corpoHTML    = "
        <div style='font-family: Arial, sans-serif; max-width: 460px; padding: 30px; border: 1px solid #e2e8f0; border-radius: 16px; margin: 0 auto; background-color: #ffffff;'>
            <h2 style='color: #f97316; margin-top: 0; font-size: 22px;'>Olá, {$primeiroNome}!</h2>
            <p style='color: #475569; font-size: 14px; line-height: 1.5;'>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>ReformAí</strong>. Use o código abaixo para continuar:</p>
            <div style='background-color: #fff7ed; border: 1px dashed #fdba74; padding: 18px; text-align: center; font-size: 26px; font-weight: bold; color: #ea580c; letter-spacing: 5px; margin: 24px 0; border-radius: 8px;'>
                {$codigoToken}
            </div>
            <p style='color: #94a3b8; font-size: 12px; line-height: 1.4; border-top: 1px solid #f1f5f9; padding-top: 16px; margin-bottom: 0;'>
                Este código expira em 15 minutos.<br>Se você não solicitou a redefinição, ignore este e-mail — sua senha permanece a mesma.
            </p>
        </div>
    ";
    $emailEnviado = EmailService::enviar($email, $usuario['nome'], $assunto, $corpoHTML);
    if ($emailEnviado) {
        responder(200, ['sucesso' => true, 'mensagem' => 'Código enviado com sucesso!']);
    } else {
        responder(200, [
            'sucesso'              => true,
            'token_desenvolvimento' => $codigoToken,
            'erro_email'           => EmailService::$lastError,
            'mensagem'             => 'Código gerado localmente: ' . $codigoToken,
        ]);
    }
}
function verificarCodigoReset(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    $email  = trim($_POST['email']        ?? '');
    $codigo = trim($_POST['codigo_token'] ?? '');
    if (!isset($_SESSION['reset_token']) || time() > $_SESSION['reset_token_expira'])
        responder(200, ['sucesso' => false, 'mensagem' => 'Código expirado. Solicite um novo.']);
    if ($_SESSION['reset_email'] !== $email)
        responder(200, ['sucesso' => false, 'mensagem' => 'Sessão inválida. Reinicie o processo.']);
    if ($codigo !== $_SESSION['reset_token'])
        responder(200, ['sucesso' => false, 'mensagem' => 'Código incorreto. Verifique sua caixa de entrada.']);
    $_SESSION['reset_verificado'] = true;
    responder(200, ['sucesso' => true]);
}
function redefinirSenha(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    if (empty($_SESSION['reset_verificado']) || $_SESSION['reset_verificado'] !== true)
        responder(200, ['sucesso' => false, 'mensagem' => 'Verificação pendente. Confirme o código primeiro.']);
    if (!isset($_SESSION['reset_token']) || time() > $_SESSION['reset_token_expira'])
        responder(200, ['sucesso' => false, 'mensagem' => 'Sessão expirada. Reinicie o processo.']);
    $email    = trim($_POST['email']          ?? '');
    $nova     = $_POST['nova_senha']          ?? '';
    $confirma = $_POST['confirmar_senha']     ?? '';
    if ($_SESSION['reset_email'] !== $email)
        responder(200, ['sucesso' => false, 'mensagem' => 'Sessão inválida. Reinicie o processo.']);
    if (strlen($nova) < 8)
        responder(200, ['sucesso' => false, 'mensagem' => 'A senha deve ter pelo menos 8 caracteres.']);
    if ($nova !== $confirma)
        responder(200, ['sucesso' => false, 'mensagem' => 'As senhas não coincidem.']);
    $user = new User($pdo);
    $ok   = $user->atualizarSenha($email, $nova);
    if (!$ok)
        responder(200, ['sucesso' => false, 'mensagem' => 'Não foi possível atualizar a senha. Tente novamente.']);
    unset($_SESSION['reset_token'], $_SESSION['reset_token_expira'], $_SESSION['reset_email'], $_SESSION['reset_verificado']);
    responder(200, ['sucesso' => true, 'mensagem' => 'Senha redefinida com sucesso!']);
}
function responder(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados);
    exit;
}

