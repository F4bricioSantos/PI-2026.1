<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
require_once '../../backend/models/User.php';

$idUsuarioLogado = $_SESSION['usuario_id'];
$userModel = new User($pdo);
$usuario = $userModel->buscarPorId($idUsuarioLogado);

if (!$usuario || ($usuario['tipo_usuario'] ?? '') !== 'admin') {
    header('Location: /dashboard');
    exit;
}

try {
    $totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $totalServicos = $pdo->query("SELECT COUNT(*) FROM servicos")->fetchColumn();
    $totalContratos = $pdo->query("SELECT COUNT(*) FROM contratos")->fetchColumn();
    $totalAvaliacoes = $pdo->query("SELECT COUNT(*) FROM avaliacoes")->fetchColumn();
    $stmtHoje = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE criado_em::date = CURRENT_DATE"); $stmtHoje->execute(); $novosHoje = $stmtHoje->fetchColumn();
} catch (Exception $e) {
    $totalUsuarios = $totalServicos = $totalContratos = $totalAvaliacoes = $novosHoje = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAÃ­ â€“ Painel Geral</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Manrope','sans-serif']},colors:{orange:{DEFAULT:'#F97316',light:'#FFEDD5',dark:'#EA580C'},sidebar:'#16213E',card:'#1E2A3A',bg:'#F8F9FA'}}}}</script>
  <style>.custom-scroll::-webkit-scrollbar{width:6px}.custom-scroll::-webkit-scrollbar-track{background:transparent}.custom-scroll::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:99px}</style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">
  <div id="sidebar-container" class="fixed inset-y-0 left-0 z-50 w-60 bg-sidebar flex flex-col h-screen transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out"></div>

  <script type="module">
    import { renderSidebar } from '/frontend/src/components/sidebar.js';
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = :id"); $stmtCount->execute([":id" => $idUsuarioLogado]); $temServico = $stmtCount->fetchColumn() > 0;
    renderSidebar('sidebar-container', 'admin', temServico, true, {badgeMensagens:0,badgeAgendamentos:0}, {
      nome: "<?= htmlspecialchars($usuario['nome']) ?>",
      foto: "<?= htmlspecialchars($usuario['foto_perfil'] ?? '') ?>"
    });
  </script>

  <main class="flex-1 flex flex-col overflow-hidden w-full relative">
    <header class="flex items-center justify-between px-4 md:px-8 py-4 md:py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-3">
        <button onclick="window.toggleSidebar()" class="md:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="w-8 h-8 bg-red-100 rounded-lg hidden md:flex items-center justify-center text-red-500 flex-shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <span class="font-bold text-lg tracking-tight">Painel Geral</span>
      </div>
    </header>
    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-6 custom-scroll">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
          <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">UsuÃ¡rios</p>
          <p class="text-3xl font-black text-gray-900 mt-2"><?= $totalUsuarios ?></p>
          <p class="text-xs text-gray-400 mt-1"><?= $novosHoje ?> novos hoje</p>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
          <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">ServiÃ§os</p>
          <p class="text-3xl font-black text-gray-900 mt-2"><?= $totalServicos ?></p>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
          <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Contratos</p>
          <p class="text-3xl font-black text-gray-900 mt-2"><?= $totalContratos ?></p>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
          <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">AvaliaÃ§Ãµes</p>
          <p class="text-3xl font-black text-gray-900 mt-2"><?= $totalAvaliacoes ?></p>
        </div>
      </div>
    </div>
  </main>
</body>
</html>


