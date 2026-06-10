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

                $assunto = "Confirmação de alteração de e-mail — ReformAí";
                $corpo = "<div style='font-family:sans-serif;max-width:460px;padding:30px;border:1px solid #e2e8f0;border-radius:16px;margin:0 auto;background:#fff;'>
                    <h2 style='color:#f97316;margin-top:0;'>Alteração de e-mail solicitada</h2>
                    <p style='color:#475569;font-size:14px;'>Recebemos uma solicitação para alterar o e-mail da sua conta ReformAí para <strong>$novoEmail</strong>.</p>
                    <p style='color:#475569;font-size:14px;'>Use o código abaixo para confirmar:</p>
                    <div style='background:#fff7ed;border:1px dashed #fdba74;padding:18px;text-align:center;font-size:26px;font-weight:bold;color:#ea580c;letter-spacing:5px;margin:24px 0;border-radius:8px;'>$codigo</div>
                    <p style='color:#94a3b8;font-size:12px;'>Se você não solicitou esta alteração, ignore este e-mail.</p></div>";

                $enviado = EmailService::enviar($usuario['email'] ?? '', $usuario['nome'] ?? '', $assunto, $corpo);
                if ($enviado) {
                    $mensagem = 'Código de verificação enviado para seu e-mail atual.';
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
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%23F97316%27 stroke-width=%272.5%27%3E%3Cpath d=%27M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z%27/%3E%3C/svg%3E">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Configurações</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Manrope','sans-serif']},colors:{orange:{DEFAULT:'#F97316',light:'#FFEDD5',dark:'#EA580C'},sidebar:'#16213E',card:'#1E2A3A',bg:'#F8F9FA'}}}}</script>
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

    <div class="flex-1 flex min-h-0">
      <!-- Sub-navegação vertical -->
      <nav class="hidden md:flex flex-col w-52 bg-white border-r border-gray-200 p-4 gap-1 flex-shrink-0 overflow-y-auto">
        <button data-tab="senha" onclick="mudarAba('senha')" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-left transition-all hover:bg-orange-50 hover:text-orange-600 active-tab">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          Senha
        </button>
        <button data-tab="email" onclick="mudarAba('email')" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-left transition-all hover:bg-orange-50 hover:text-orange-600 text-gray-500">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          E-mail
        </button>
        <button data-tab="excluir" onclick="mudarAba('excluir')" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-left transition-all hover:bg-red-50 hover:text-red-600 text-gray-500">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
          Excluir Conta
        </button>
      </nav>

      <!-- Navegação horizontal no mobile -->
      <div class="md:hidden flex border-b border-gray-200 bg-white">
        <button onclick="mudarAba('senha')" class="flex-1 text-center py-3 text-xs font-bold text-orange-600 border-b-2 border-orange-500 active-tab-mobile" data-tab-mobile="senha">Senha</button>
        <button onclick="mudarAba('email')" class="flex-1 text-center py-3 text-xs font-bold text-gray-400 border-b-2 border-transparent" data-tab-mobile="email">E-mail</button>
        <button onclick="mudarAba('excluir')" class="flex-1 text-center py-3 text-xs font-bold text-gray-400 border-b-2 border-transparent" data-tab-mobile="excluir">Excluir</button>
      </div>

      <div class="flex-1 overflow-y-auto px-4 md:px-8 py-6 md:py-8 custom-scroll">
        <div class="max-w-2xl mx-auto">

          <?php if ($mensagem): ?>
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium px-5 py-4 rounded-2xl flex items-center gap-3">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              <?= htmlspecialchars($mensagem) ?>
            </div>
          <?php endif; ?>
          <?php if ($erro): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 text-sm font-medium px-5 py-4 rounded-2xl flex items-center gap-3">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
              <?= htmlspecialchars($erro) ?>
            </div>
          <?php endif; ?>

          <!-- Senha -->
          <div id="aba-senha" class="aba-config">
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
          </div>

          <!-- E-mail -->
          <div id="aba-email" class="aba-config hidden">
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
          </div>

          <!-- Excluir Conta -->
          <div id="aba-excluir" class="aba-config hidden">
            <div class="bg-white rounded-2xl border border-red-100 shadow-sm">
              <div class="px-6 py-5 border-b border-red-50">
                <div class="flex items-center gap-2">
                  <div class="w-1 h-5 bg-red-500 rounded-full"></div>
                  <h2 class="text-sm font-bold text-red-600 uppercase tracking-wide">Zona de Perigo</h2>
                </div>
              </div>
              <div class="p-6 space-y-5">
                <p class="text-sm text-gray-600 leading-relaxed">Ao excluir sua conta, todos os seus dados serão removidos permanentemente: serviços, avaliações, mensagens e fotos.</p>
                <button type="button" onclick="abrirModalExcluir()" class="w-full h-[48px] bg-red-500 hover:bg-red-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm cursor-pointer">Excluir Minha Conta</button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </main>
  <!-- Modal Excluir Conta -->
  <div id="modal-excluir" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="fecharModalExcluir()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 flex flex-col gap-4">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
          </svg>
        </div>
        <div>
          <h3 class="text-sm font-bold text-gray-900">Excluir conta</h3>
          <p class="text-[11px] text-gray-400">Esta ação é irreversível</p>
        </div>
      </div>
      <p class="text-xs text-gray-500 leading-relaxed">Todos os seus dados serão removidos permanentemente: serviços, avaliações, mensagens e fotos.</p>
      <form method="POST" class="flex flex-col gap-4">
        <input type="hidden" name="acao" value="excluir_conta">
        <div>
          <label for="confirmacao-modal" class="block text-[13px] font-semibold text-gray-700 mb-1.5">Digite <span class="font-bold text-red-500">EXCLUIR</span> para confirmar</label>
          <input type="text" name="confirmacao" id="confirmacao-modal" required class="w-full h-[48px] px-4 rounded-xl border border-red-200 bg-white text-[14px] focus:outline-none focus:ring-2 focus:ring-red-400 transition-all" placeholder="EXCLUIR" autocomplete="off">
        </div>
        <div class="flex gap-2 justify-end">
          <button type="button" onclick="fecharModalExcluir()" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all cursor-pointer">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-red-500 hover:bg-red-600 transition-all cursor-pointer shadow-md shadow-red-500/20">Sim, excluir conta</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function mudarAba(aba) {
      window.location.hash = aba;
      mostrarAba(aba);
    }

    function mostrarAba(aba) {
      document.querySelectorAll('.aba-config').forEach(el => el.classList.add('hidden'));
      document.getElementById('aba-' + aba).classList.remove('hidden');

      document.querySelectorAll('[data-tab]').forEach(el => {
        el.classList.remove('active-tab', 'text-orange-600', 'bg-orange-50');
        el.classList.add('text-gray-500');
      });
      const navBtn = document.querySelector('[data-tab="' + aba + '"]');
      if (navBtn) navBtn.classList.add('active-tab', 'text-orange-600', 'bg-orange-50');

      document.querySelectorAll('[data-tab-mobile]').forEach(el => {
        el.classList.remove('text-orange-600', 'border-orange-500');
        el.classList.add('text-gray-400', 'border-transparent');
      });
      const mobBtn = document.querySelector('[data-tab-mobile="' + aba + '"]');
      if (mobBtn) mobBtn.classList.add('text-orange-600', 'border-orange-500');
    }

    document.addEventListener('DOMContentLoaded', () => {
      const hash = window.location.hash.replace('#', '') || 'senha';
      mostrarAba(hash);
    });

    function abrirModalExcluir() {
      document.getElementById('modal-excluir').classList.remove('hidden');
    }
    function fecharModalExcluir() {
      document.getElementById('modal-excluir').classList.add('hidden');
    }
    window.abrirModalExcluir = abrirModalExcluir;
    window.fecharModalExcluir = fecharModalExcluir;
    window.mudarAba = mudarAba;
  </script>

  <script type="module">
    import { renderSidebar } from '/frontend/src/components/sidebar.js';
    renderSidebar('sidebar-container', 'configuracoes', <?= json_encode($temServico) ?>, <?= json_encode($isAdmin) ?>, {}, {
      nome: "<?= htmlspecialchars($usuario['nome'] ?? '') ?>",
      foto: "<?= htmlspecialchars($usuario['foto_perfil'] ?? '') ?>"
    });
  </script>
</body>
</html>

