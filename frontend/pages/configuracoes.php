<?php
require_once '../../backend/config/Conexao.php';
require_once '../../backend/config/session_setup.php';
setup_db_session($pdo);
require_once '../../backend/config/auth.php';
require_once '../../backend/services/EmailService.php';

use Backend\Services\EmailService;

$idUsuario = $_SESSION['usuario_id'];
$mensagem = '';
$erro = '';
$usuario = [];
$temServico = false;

try {
    $stmt = $pdo->prepare("SELECT nome, email, telefone, foto_perfil FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $idUsuario]);
    $usuario = $stmt->fetch();

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = :id");
    $stmtCheck->execute([':id' => $idUsuario]);
    $temServico = $stmtCheck->fetchColumn() > 0;
} catch (Exception $e) {
    $erro = "Erro ao carregar dados.";
}

$isAdmin = ($usuario['tipo_usuario'] ?? false) === 'admin';

// Alterar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if ($acao === 'alterar_senha') {
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha  = $_POST['nova_senha'] ?? '';
        $confirmar  = $_POST['confirmar_senha'] ?? '';

        if (strlen($novaSenha) < 8) {
            $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($novaSenha !== $confirmar) {
            $erro = 'As senhas não coincidem.';
        } else {
            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = :id");
            $stmt->execute([':id' => $idUsuario]);
            $row = $stmt->fetch();

            if (!password_verify($senhaAtual, $row['senha'])) {
                $erro = 'Senha atual incorreta.';
            } else {
                $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
                $upd->execute([':senha' => $hash, ':id' => $idUsuario]);
                $mensagem = 'Senha alterada com sucesso!';
            }
        }
    }

    // Enviar codigo de verificacao para novo email
    if ($acao === 'enviar_codigo') {
        $novoEmail = trim($_POST['novo_email'] ?? '');

        if (!filter_var($novoEmail, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } elseif ($novoEmail === ($usuario['email'] ?? '')) {
            $erro = 'O novo e-mail é igual ao atual.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
            $stmt->execute([':email' => $novoEmail, ':id' => $idUsuario]);
            if ($stmt->fetch()) {
                $erro = 'Este e-mail já está em uso.';
            } else {
                $codigo = (string)rand(100000, 999999);
                $_SESSION['novo_email'] = $novoEmail;
                $_SESSION['codigo_verificacao'] = $codigo;
                $_SESSION['codigo_expira'] = time() + 600;

                $assunto = "Confirmação de novo e-mail — ReformAí";
                $corpo = "<div style='font-family:sans-serif;max-width:460px;padding:30px;border:1px solid #e2e8f0;border-radius:16px;margin:0 auto;background:#fff;'>
                    <h2 style='color:#f97316;margin-top:0;'>Confirme seu novo e-mail</h2>
                    <p style='color:#475569;font-size:14px;'>Use o código abaixo para confirmar o e-mail <strong>$novoEmail</strong> na sua conta ReformAí:</p>
                    <div style='background:#fff7ed;border:1px dashed #fdba74;padding:18px;text-align:center;font-size:26px;font-weight:bold;color:#ea580c;letter-spacing:5px;margin:24px 0;border-radius:8px;'>$codigo</div>
                    <p style='color:#94a3b8;font-size:12px;'>Código válido por 10 minutos.</p></div>";

                $enviado = EmailService::enviar($novoEmail, $usuario['nome'] ?? '', $assunto, $corpo);
                if ($enviado) {
                    $mensagem = 'Código de verificação enviado para ' . htmlspecialchars($novoEmail);
                } else {
                    $_SESSION['codigo_teste'] = $codigo;
                    $erro = 'Falha ao enviar e-mail. Código de teste: ' . $codigo;
                }
            }
        }
    }

    // Confirmar codigo e alterar email
    if ($acao === 'confirmar_email') {
        $codigo = trim($_POST['codigo'] ?? '');
        if (!isset($_SESSION['codigo_verificacao']) || time() > $_SESSION['codigo_expira']) {
            $erro = 'Código expirado. Solicite um novo.';
        } elseif ($codigo !== $_SESSION['codigo_verificacao']) {
            $erro = 'Código incorreto.';
        } else {
            $novoEmail = $_SESSION['novo_email'];
            $upd = $pdo->prepare("UPDATE usuarios SET email = :email WHERE id = :id");
            $upd->execute([':email' => $novoEmail, ':id' => $idUsuario]);
            unset($_SESSION['novo_email'], $_SESSION['codigo_verificacao'], $_SESSION['codigo_expira']);
            $mensagem = 'E-mail alterado com sucesso para ' . htmlspecialchars($novoEmail);
            $usuario['email'] = $novoEmail;
        }
    }

    // Excluir conta
    if ($acao === 'excluir_conta') {
        $confirmacao = trim($_POST['confirmacao'] ?? '');
        if ($confirmacao !== 'EXCLUIR') {
            $erro = 'Digite EXCLUIR para confirmar.';
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM mensagens_chat WHERE remetente_id = :id OR destinatario_id = :id")->execute([':id' => $idUsuario]);
                $pdo->prepare("DELETE FROM favoritos_servicos WHERE usuario_id = :id")->execute([':id' => $idUsuario]);
                $pdo->prepare("DELETE FROM avaliacoes WHERE cliente_id = :id OR prestador_id = :id")->execute([':id' => $idUsuario]);
                $pdo->prepare("DELETE FROM portfolio_imagens WHERE usuario_id = :id")->execute([':id' => $idUsuario]);
                $pdo->prepare("DELETE FROM prestadores_detalhes WHERE usuario_id = :id")->execute([':id' => $idUsuario]);
                $pdo->prepare("DELETE FROM servicos WHERE prestador_id = :id")->execute([':id' => $idUsuario]);
                $pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute([':id' => $idUsuario]);
                $pdo->commit();

                session_unset();
                session_destroy();
                echo "<script>window.location.href='/';</script>";
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $erro = 'Erro ao excluir conta. Tente novamente.';
            }
        }
    }
}

$emailPendente = $_SESSION['novo_email'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Configurações</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Manrope','sans-serif']},colors:{orange:{DEFAULT:'#F97316',light:'#FFEDD5',dark:'#EA580C'},sidebar:'#16213E',bg:'#F8F9FA'}}}}</script>
  <style>.custom-scroll::-webkit-scrollbar{width:6px}.custom-scroll::-webkit-scrollbar-track{background:transparent}.custom-scroll::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:99px}</style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <div id="sidebar-container" class="fixed inset-y-0 left-0 z-50 w-60 bg-sidebar flex flex-col h-screen transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out"></div>

  <main class="flex-1 flex flex-col min-w-0 relative bg-bg">
    <header class="flex items-center justify-between px-4 md:px-8 py-4 md:py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="window.toggleSidebar?.()" class="md:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <svg class="w-5 h-5 text-gray-300 hidden md:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12.22 2h-.44a2 2 0 00-2 2v.18a2 2 0 01-1 1.73l-.43.25a2 2 0 01-2 0l-.15-.08a2 2 0 00-2.73.73l-.22.38a2 2 0 00.73 2.73l.15.1a2 2 0 011 1.72v.51a2 2 0 01-1 1.74l-.15.09a2 2 0 00-.73 2.73l.22.38a2 2 0 002.73.73l.15-.08a2 2 0 012 0l.43.25a2 2 0 011 1.73V20a2 2 0 002 2h.44a2 2 0 002-2v-.18a2 2 0 011-1.73l.43-.25a2 2 0 012 0l.15.08a2 2 0 002.73-.73l.22-.39a2 2 0 00-.73-2.73l-.15-.08a2 2 0 01-1-1.74v-.5a2 2 0 011-1.74l.15-.09a2 2 0 00.73-2.73l-.22-.38a2 2 0 00-2.73-.73l-.15.08a2 2 0 01-2 0l-.43-.25a2 2 0 01-1-1.73V4a2 2 0 00-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
        <span class="text-gray-800 font-bold text-lg tracking-tight">Configurações</span>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-6 md:py-8 custom-scroll">
      <div class="max-w-2xl mx-auto space-y-8">

        <?php if ($mensagem): ?>
          <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium px-5 py-4 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <?= htmlspecialchars($mensagem) ?>
          </div>
        <?php endif; ?>
        <?php if ($erro): ?>
          <div class="bg-red-50 border border-red-200 text-red-600 text-sm font-medium px-5 py-4 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?= htmlspecialchars($erro) ?>
          </div>
        <?php endif; ?>

        <!-- Senha -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
          <div class="px-6 py-5 border-b border-gray-50">
            <div class="flex items-center gap-2">
              <div class="w-1 h-5 bg-orange rounded-full"></div>
              <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Alterar Senha</h2>
            </div>
          </div>
          <form method="POST" class="p-6 space-y-5">
            <input type="hidden" name="acao" value="alterar_senha">
            <div>
              <label for="senha_atual" class="block text-[13px] font-semibold text-gray-700 mb-1.5">Senha Atual</label>
              <input type="password" name="senha_atual" id="senha_atual" required class="w-full h-[48px] px-4 rounded-xl border border-gray-200 bg-white text-[14px] focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all">
            </div>
            <div>
              <label for="nova_senha" class="block text-[13px] font-semibold text-gray-700 mb-1.5">Nova Senha</label>
              <input type="password" name="nova_senha" id="nova_senha" required minlength="8" class="w-full h-[48px] px-4 rounded-xl border border-gray-200 bg-white text-[14px] focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all">
              <p class="text-[11px] text-gray-400 mt-1.5">Mínimo de 8 caracteres.</p>
            </div>
            <div>
              <label for="confirmar_senha" class="block text-[13px] font-semibold text-gray-700 mb-1.5">Confirmar Nova Senha</label>
              <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="8" class="w-full h-[48px] px-4 rounded-xl border border-gray-200 bg-white text-[14px] focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all">
            </div>
            <button type="submit" class="w-full h-[48px] bg-orange hover:bg-orange-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">Salvar Nova Senha</button>
          </form>
        </div>

        <!-- E-mail -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
          <div class="px-6 py-5 border-b border-gray-50">
            <div class="flex items-center gap-2">
              <div class="w-1 h-5 bg-orange rounded-full"></div>
              <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Alterar E-mail</h2>
            </div>
          </div>
          <div class="p-6 space-y-5">
            <div>
              <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1">E-mail Atual</span>
              <span class="text-sm font-medium text-gray-800"><?= htmlspecialchars($usuario['email'] ?? '') ?></span>
            </div>

            <?php if ($emailPendente): ?>
              <form method="POST" class="space-y-4 border-t border-gray-100 pt-5">
                <p class="text-sm text-gray-500">Código enviado para <strong><?= htmlspecialchars($emailPendente) ?></strong></p>
                <input type="hidden" name="acao" value="confirmar_email">
                <div>
                  <label for="codigo" class="block text-[13px] font-semibold text-gray-700 mb-1.5">Código de Verificação</label>
                  <input type="text" name="codigo" id="codigo" required maxlength="6" class="w-full h-[48px] px-4 rounded-xl border border-gray-200 bg-white text-[14px] tracking-[8px] text-center font-bold text-lg focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all" placeholder="000000">
                </div>
                <button type="submit" class="w-full h-[48px] bg-orange hover:bg-orange-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">Confirmar Código</button>
              </form>
            <?php else: ?>
              <form method="POST" class="space-y-4">
                <input type="hidden" name="acao" value="enviar_codigo">
                <div>
                  <label for="novo_email" class="block text-[13px] font-semibold text-gray-700 mb-1.5">Novo E-mail</label>
                  <input type="email" name="novo_email" id="novo_email" required class="w-full h-[48px] px-4 rounded-xl border border-gray-200 bg-white text-[14px] focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all" placeholder="novo@email.com">
                </div>
                <button type="submit" class="w-full h-[48px] bg-orange hover:bg-orange-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">Enviar Código de Verificação</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- Excluir Conta -->
        <div class="bg-white rounded-2xl border border-red-100 shadow-sm">
          <div class="px-6 py-5 border-b border-red-50">
            <div class="flex items-center gap-2">
              <div class="w-1 h-5 bg-red-500 rounded-full"></div>
              <h2 class="text-sm font-bold text-red-600 uppercase tracking-wide">Zona de Perigo</h2>
            </div>
          </div>
          <form method="POST" class="p-6 space-y-5" onsubmit="return confirm('Tem certeza? Esta ação é irreversível.')">
            <input type="hidden" name="acao" value="excluir_conta">
            <p class="text-sm text-gray-600 leading-relaxed">Ao excluir sua conta, todos os seus dados serão removidos permanentemente: serviços, avaliações, mensagens e fotos.</p>
            <div>
              <label for="confirmacao" class="block text-[13px] font-semibold text-gray-700 mb-1.5">Digite <span class="font-bold text-red-500">EXCLUIR</span> para confirmar</label>
              <input type="text" name="confirmacao" id="confirmacao" required class="w-full h-[48px] px-4 rounded-xl border border-red-200 bg-white text-[14px] focus:outline-none focus:ring-2 focus:ring-red-400 transition-all" placeholder="EXCLUIR" autocomplete="off">
            </div>
            <button type="submit" class="w-full h-[48px] bg-red-500 hover:bg-red-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">Excluir Minha Conta</button>
          </form>
        </div>

      </div>
    </div>
  </main>
  <script type="module">
    import { renderSidebar } from '/frontend/src/components/sidebar.js';
    renderSidebar('sidebar-container', 'configuracoes', <?= json_encode($temServico) ?>, <?= json_encode($isAdmin) ?>, {}, {
      nome: "<?= htmlspecialchars($usuario['nome'] ?? '') ?>",
      foto: "<?= htmlspecialchars($usuario['foto_perfil'] ?? '') ?>"
    });
  </script>
</body>
</html>
