<?php
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';

$idUsuario = $_SESSION['usuario_id'];
$mensagem = '';
$erro = '';

try {
    $stmt = $pdo->prepare("SELECT nome, email, telefone, foto_perfil, tipo_usuario FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $idUsuario]);
    $usuario = $stmt->fetch();

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = :id");
    $stmtCheck->execute([':id' => $idUsuario]);
    $temServico = $stmtCheck->fetchColumn() > 0;
} catch (Exception $e) {
    $erro = "Erro ao carregar dados.";
}

// Alterar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha') {
    $senhaAtual   = $_POST['senha_atual'] ?? '';
    $novaSenha    = $_POST['nova_senha'] ?? '';
    $confirmar    = $_POST['confirmar_senha'] ?? '';

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

$isAdmin = (isset($usuario['tipo_usuario']) && $usuario['tipo_usuario'] === 'admin');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Configurações</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Manrope', 'sans-serif'] },
          colors: { orange: { DEFAULT: '#F97316', light: '#FFEDD5', dark: '#EA580C' }, sidebar: '#16213E', bg: '#F8F9FA' }
        }
      }
    }
  </script>
  <style>
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }
  </style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <div id="sidebar-container" class="fixed inset-y-0 left-0 z-50 w-60 bg-sidebar flex flex-col h-screen transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out"></div>

  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    const hasServices = <?= json_encode($temServico) ?>;
    const isAdmin = <?= json_encode($isAdmin) ?>;
    renderSidebar('sidebar-container', 'configuracoes', hasServices, isAdmin, {}, {
      nome: "<?= htmlspecialchars($usuario['nome']) ?>",
      foto: "<?= $usuario['foto_perfil'] ?? '' ?>"
    });
  </script>

  <main class="flex-1 flex flex-col min-w-0 relative bg-bg">
    <header class="flex items-center justify-between px-4 md:px-8 py-4 md:py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="window.toggleSidebar && window.toggleSidebar()" class="md:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors">
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

        <!-- Dados da Conta -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
          <div class="px-6 py-5 border-b border-gray-50">
            <div class="flex items-center gap-2">
              <div class="w-1 h-5 bg-orange rounded-full"></div>
              <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Dados da Conta</h2>
            </div>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1">Nome</span>
              <span class="text-sm font-medium text-gray-800"><?= htmlspecialchars($usuario['nome']) ?></span>
            </div>
            <div>
              <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1">E-mail</span>
              <span class="text-sm font-medium text-gray-800"><?= htmlspecialchars($usuario['email']) ?></span>
            </div>
            <div>
              <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wide mb-1">Telefone</span>
              <span class="text-sm font-medium text-gray-800"><?= htmlspecialchars($usuario['telefone'] ?: 'Não informado') ?></span>
            </div>
            <a href="/perfil" class="inline-flex items-center gap-1.5 text-sm font-bold text-orange hover:text-orange-600 transition-colors mt-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Editar no Perfil
            </a>
          </div>
        </div>

        <!-- Alterar Senha -->
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
              <input type="password" name="senha_atual" id="senha_atual" required
                     class="w-full h-[48px] px-4 rounded-xl border border-gray-200 bg-white text-[14px] text-gray-800 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all">
            </div>

            <div>
              <label for="nova_senha" class="block text-[13px] font-semibold text-gray-700 mb-1.5">Nova Senha</label>
              <input type="password" name="nova_senha" id="nova_senha" required minlength="8"
                     class="w-full h-[48px] px-4 rounded-xl border border-gray-200 bg-white text-[14px] text-gray-800 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all">
              <p class="text-[11px] text-gray-400 mt-1.5">Mínimo de 8 caracteres.</p>
            </div>

            <div>
              <label for="confirmar_senha" class="block text-[13px] font-semibold text-gray-700 mb-1.5">Confirmar Nova Senha</label>
              <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="8"
                     class="w-full h-[48px] px-4 rounded-xl border border-gray-200 bg-white text-[14px] text-gray-800 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-all">
            </div>

            <button type="submit" class="w-full h-[48px] bg-orange hover:bg-orange-600 text-white font-bold text-sm rounded-xl transition-all shadow-sm">
              Salvar Nova Senha
            </button>
          </form>
        </div>

      </div>
    </div>
  </main>

</body>
</html>
