<?php
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
require_once '../../backend/models/User.php';
require_once '../../backend/models/Contrato.php';


$idUsuarioLogado = $_SESSION['usuario_id'];
$userModel = new User($pdo);
$usuario   = $userModel->buscarPorId($idUsuarioLogado);

if (!$usuario) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = :id");
$stmtCheck->execute([':id' => $idUsuarioLogado]);
$temServico = $stmtCheck->fetchColumn() > 0;

$urlBaseSupabase    = "https://yplpxzmwtkencrrtxmof.supabase.co/storage/v1/object/public/fotos/";
$urlBaseChatImagens = $urlBaseSupabase . "chat/";

$idDestinatario   = isset($_GET['com']) ? (int)$_GET['com'] : 0;
$nomeDestinatario = "";
$fotoDestinatario = "";

if ($idDestinatario > 0) {
    $stmtDest = $pdo->prepare("SELECT nome, foto_perfil FROM usuarios WHERE id = :id");
    $stmtDest->execute([':id' => $idDestinatario]);
    $dadosDest = $stmtDest->fetch(PDO::FETCH_ASSOC);
    if ($dadosDest) {
        $nomeDestinatario = htmlspecialchars($dadosDest['nome']);
        $fotoDestinatario = (!empty($dadosDest['foto_perfil']) && $dadosDest['foto_perfil'] !== 'default.png')
            ? $dadosDest['foto_perfil']
            : '';
    }
}

$contratoModel = new Contrato($pdo);

$contratoAtivo         = null; // Inicializa para evitar "Undefined variable"
$contratosAtivos       = [];
$outraPessoaTemServico = false;
$contratoParaAvaliar   = null;
$jaContrateiOOutro     = false;

if ($idDestinatario > 0) {
    $contratoAtivo = $contratoModel->buscarContratoAtivo($idUsuarioLogado, $idDestinatario);
    if ($contratoAtivo) {
        $contratosAtivos   = [$contratoAtivo];
        $jaContrateiOOutro = true;
    }
    $outraPessoaTemServico = $contratoModel->checarSeTemServico($idDestinatario);
    if (empty($contratosAtivos)) {
    // Se não houver contrato ativo, busca se existe um recém-concluído para avaliar
    if (!$contratoAtivo) {
        $contratoParaAvaliar = $contratoModel->buscarContratoParaAvaliar($idUsuarioLogado, $idDestinatario);
    }
    }
}

// Busca o contrato mais recente (independente de estar ativo ou concluído) apenas para sincronismo do JS
// Isso evita o loop infinito de recarregamento quando o contrato é finalizado
$contratoSincronismo = null;
if ($idDestinatario > 0) {
    $stmtSync = $pdo->prepare("SELECT id, status, finalizado_prestador_em, finalizado_cliente_em FROM contratos WHERE ((cliente_id = :u1 AND prestador_id = :u2) OR (cliente_id = :u2 AND prestador_id = :u1)) AND status IN ('pendente', 'aceito', 'concluido') ORDER BY criado_em DESC LIMIT 1");
    $stmtSync->execute([':u1' => $idUsuarioLogado, ':u2' => $idDestinatario]);
    $contratoSincronismo = $stmtSync->fetch(PDO::FETCH_ASSOC);
}

$listaServicosDisponiveis = $outraPessoaTemServico
    ? $contratoModel->listarServicosPrestador($idDestinatario)
    : [];

// Busca a contagem global de mensagens não lidas para o usuário logado
$stmtUnreadMsgCount = $pdo->prepare("SELECT COUNT(*) FROM mensagens_chat WHERE destinatario_id = :uid AND lido_em IS NULL AND deletado = 0");
$stmtUnreadMsgCount->execute([':uid' => $idUsuarioLogado]);
$totalMensagensNaoLidas = (int)$stmtUnreadMsgCount->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%23F97316%27 stroke-width=%272.5%27%3E%3Cpath d=%27M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z%27/%3E%3C/svg%3E">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>ReformAí – Mensagens</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Manrope', 'sans-serif'] },
          colors: {
            orange: { DEFAULT: '#F97316', light: '#FFEDD5', dark: '#EA580C' },
            sidebar: '#16213E',
            card: '#1E2A3A',
            bg: '#F8F9FA'
          }
        }
      }
    }
  </script>
  <style>
    #sidebar-container { height: 100dvh; }
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    .msg-container:hover .msg-actions { display: flex !important; }
    .check-enviado { color: rgba(255,255,255,0.45); font-weight: 500; }
    .check-entregue { color: rgba(255,255,255,0.55); font-weight: 700; }
    .check-lido { color: #34B7F1 !important; opacity: 1 !important; font-weight: 900; }
  </style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden" style="height: 100dvh;">

  <div id="sidebar-container" class="fixed inset-y-0 left-0 z-50 w-60 bg-sidebar flex flex-col h-screen transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out"></div>

  <script type="module">
    import { renderSidebar } from '/frontend/src/components/sidebar.js';
    const temServico = <?= $temServico ? 'true' : 'false' ?>;
    const isAdmin    = <?= (isset($usuario['tipo_usuario']) && $usuario['tipo_usuario'] === 'admin') ? 'true' : 'false' ?>;
    renderSidebar('sidebar-container', 'chat', temServico, isAdmin, { badgeMensagens: <?= (int)($totalMensagensNaoLidas ?? 0) ?>, badgeAgendamentos: 0 }, {
      nome: "<?= htmlspecialchars($usuario['nome']) ?>",
      foto: "<?= htmlspecialchars($usuario['foto_perfil'] ?? '') ?>"
    });
  </script>

  <main class="flex-1 h-full flex overflow-hidden p-0 md:p-6 md:gap-6 w-full relative">

    <!-- Lista de contatos -->
    <section class="w-full md:w-80 bg-white md:rounded-2xl border-r md:border border-gray-200 shadow-sm flex-col h-full flex-shrink-0 overflow-hidden <?= ($idDestinatario > 0) ? 'hidden md:flex' : 'flex' ?>">
      <div class="p-4 border-b border-gray-100 flex flex-col gap-3">
        <div class="flex items-center gap-3">
          <button onclick="window.toggleSidebar && window.toggleSidebar()" class="md:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <h1 class="text-base font-bold text-gray-900 tracking-tight">Mensagens</h1>
        </div>
        <input type="text" id="input-busca-contato" placeholder="Buscar conversa..."
               class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2 text-xs focus:border-orange outline-none transition-all text-gray-700 placeholder-gray-400">
      </div>
      <div id="lista-contatos" class="flex-1 overflow-y-auto p-2 space-y-1 custom-scroll"></div>
    </section>

    <!-- Área do chat -->
    <section class="flex-1 bg-white md:rounded-2xl md:border border-gray-200 shadow-sm flex-col h-full overflow-hidden relative <?= ($idDestinatario > 0) ? 'flex' : 'hidden md:flex' ?>">

      <?php if ($idDestinatario > 0): ?>

      <!-- Cabeçalho com nome e avatar -->
      <div class="bg-gray-50 border-b border-gray-200 px-4 md:px-5 py-3 flex items-center gap-3 flex-shrink-0">
        <a href="chat.php" class="md:hidden p-2 -ml-2 text-gray-500 hover:bg-gray-200 rounded-lg transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <a href="ver-perfil.php?id=<?= $idDestinatario ?>" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
          <div id="topo-avatar"></div>
          <div>
            <h2 class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($nomeDestinatario) ?></h2>
          </div>
        </a>
      </div>

      <!-- Barra de contrato -->
      <div class="bg-gray-50 border-b border-gray-200 px-4 md:px-5 py-3 flex flex-col sm:flex-row sm:items-center justify-between flex-shrink-0 relative gap-2 sm:gap-0">
        <?php if ($contratoAtivo):
            $status       = $contratoAtivo['status'];
            $souPrestador = ($contratoAtivo['prestador_id'] == $idUsuarioLogado);

            $corStatus = 'bg-yellow-500';
            if ($status === 'aceito') $corStatus = 'bg-green-500';
            if (!empty($contratoAtivo['finalizado_prestador_em']) || !empty($contratoAtivo['finalizado_cliente_em'])) $corStatus = 'bg-blue-500';
            $dataFormatada = date('d/m/Y', strtotime($contratoAtivo['data_pactuada']));
            $fpEm = $contratoAtivo['finalizado_prestador_em'] ?? null;
            $fcEm = $contratoAtivo['finalizado_cliente_em'] ?? null;
        ?>
            <div class="absolute left-0 top-0 bottom-0 w-1 <?= $corStatus ?>"></div>
            <div class="flex items-center gap-2 pl-2 flex-shrink-0">
              <div class="leading-tight">
                <span class="text-[10px] md:text-xs font-bold text-gray-900 uppercase tracking-wide">
                  CONTRATO: <?= htmlspecialchars($contratoAtivo['nome_servico']) ?>
                </span>
                <p class="text-[10px] md:text-[11px] text-gray-500 font-medium">Data combinada: <?= $dataFormatada ?></p>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 pl-2 sm:pl-0">

              <?php if ($status === 'pendente'): ?>
                  <?php if ($souPrestador): ?>
                      <!-- Prestador: Aceitar / Recusar -->
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'aceito')"
                              class="bg-green-600 hover:bg-green-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all shadow-sm">
                          Aceitar
                      </button>
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'cancelado')"
                              class="bg-white hover:bg-red-50 border border-red-200 text-red-500 font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all">
                          Recusar
                      </button>
                  <?php else: ?>
                      <!-- Usuário: aguardando -->
                      <span class="bg-yellow-100 text-yellow-700 px-2.5 py-1 rounded-lg text-[10px] font-bold">
                          Esperando aceite do prestador
                      </span>
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'cancelado')"
                              class="bg-white hover:bg-red-50 border border-red-200 text-red-500 font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all">
                          Cancelar
                      </button>
                  <?php endif; ?>

              <?php elseif ($status === 'aceito' && !$fpEm && !$fcEm): ?>
                  <!-- ESTADO 2: Em andamento — igual para ambos -->
                  <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-lg text-[10px] font-bold">Em andamento</span>
                  <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'concluido')"
                          class="bg-orange hover:bg-orange-dark text-white font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer shadow-md transition-all">
                      Marcar como concluído
                  </button>
                  <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'cancelado')"
                          class="bg-white hover:bg-red-50 border border-red-200 text-red-500 font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all">
                      Cancelar
                  </button>

              <?php elseif ($status === 'aceito' && $fcEm && !$fpEm): ?>
                  <!-- ESTADO 3: Cliente marcou como concluído primeiro -->
                  <?php if ($souPrestador): ?>
                      <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-lg text-[10px] font-bold animate-pulse">
                          Marcado como concluído pelo cliente
                      </span>
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'concluido')"
                              class="bg-green-600 hover:bg-green-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all shadow-sm">
                          Concluir serviço
                      </button>
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'em_andamento')"
                              class="bg-blue-500 hover:bg-blue-600 text-white font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all">
                          Retornar para "Em andamento"
                      </button>
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'cancelado')"
                              class="bg-white hover:bg-red-50 border border-red-200 text-red-500 font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all">
                          Cancelar
                      </button>
                  <?php else: ?>
                      <div class="flex flex-col gap-0.5 max-w-xs">
                          <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-lg text-[10px] font-bold">
                              Aguardando confirmação do prestador (10 dias)
                          </span>
                          <p class="text-[10px] text-gray-400 italic px-0.5 leading-relaxed">
                              O prestador tem até 10 dias para responder. Após esse prazo, o serviço será concluído automaticamente.
                          </p>
                      </div>
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'cancelado')"
                              class="bg-white hover:bg-red-50 border border-red-200 text-red-500 font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all">
                          Cancelar
                      </button>
                  <?php endif; ?>

              <?php elseif ($status === 'aceito' && $fpEm && !$fcEm): ?>
                  <!-- ESTADO 4: Prestador marcou como concluído primeiro -->
                  <?php if ($souPrestador): ?>
                      <div class="flex flex-col gap-0.5 max-w-xs">
                          <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-lg text-[10px] font-bold">
                              Aguardando confirmação do usuário (10 dias)
                          </span>
                          <p class="text-[10px] text-gray-400 italic px-0.5 leading-relaxed">
                              O usuário tem até 10 dias para responder. Após esse prazo, o serviço será concluído automaticamente.
                          </p>
                      </div>
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'em_andamento')"
                              class="bg-blue-500 hover:bg-blue-600 text-white font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all">
                          Retornar para "Em andamento"
                      </button>
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'cancelado')"
                              class="bg-white hover:bg-red-50 border border-red-200 text-red-500 font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all">
                          Cancelar
                      </button>
                  <?php else: ?>
                      <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-lg text-[10px] font-bold animate-pulse">
                          Marcado como concluído pelo prestador
                      </span>
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'concluido')"
                              class="bg-green-600 hover:bg-green-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all shadow-sm">
                          Concluir serviço
                      </button>
                      <button onclick="alterarStatusContrato(<?= $contratoAtivo['id'] ?>, 'cancelado')"
                              class="bg-white hover:bg-red-50 border border-red-200 text-red-500 font-bold px-3 py-1.5 rounded-xl text-xs cursor-pointer transition-all">
                          Cancelar
                      </button>
                  <?php endif; ?>

              <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- ESTADO 5: Sem contrato ativo — Avaliação / Novo contrato -->
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gray-300"></div>
            <div class="flex items-center justify-between w-full pl-2">
                <?php if ($contratoParaAvaliar): ?>
                    <p class="text-xs text-gray-500 font-medium">Serviço concluído — deixe sua avaliação!</p>
                    <div class="flex items-center gap-2">
                        <a href="./avaliar-prestador.php?contrato_id=<?= $contratoParaAvaliar['id'] ?>&com=<?= $idDestinatario ?>"
                           class="flex items-center gap-1.5 bg-yellow-400 hover:bg-yellow-500 text-white font-bold px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer shadow-md">
                            <svg class="w-3.5 h-3.5 fill-white" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <?= ($contratoParaAvaliar['cliente_id'] == $idUsuarioLogado) ? 'Avaliar prestador' : 'Avaliar usuário' ?>
                        </a>
                        <?php if ($outraPessoaTemServico): ?>
                            <button onclick="abrirModalContrato()" class="bg-orange hover:bg-orange-dark text-white font-bold px-4 py-2 rounded-xl text-xs transition-all cursor-pointer shadow-md">
                                Enviar Contrato
                            </button>
                        <?php endif; ?>
                    </div>
                <?php elseif ($outraPessoaTemServico): ?>
                    <p class="text-xs text-gray-500 font-medium">Combine os detalhes por aqui e feche um serviço!</p>
                    <button onclick="abrirModalContrato()" class="bg-orange hover:bg-orange-dark text-white font-bold px-4 py-2 rounded-xl text-xs transition-all cursor-pointer shadow-md">
                        Enviar Contrato
                    </button>
                <?php else: ?>
                    <p class="text-xs text-gray-400 italic">Nenhum vínculo de serviço ativo ou disponível para este usuário.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
      </div>

      <!-- Mensagens -->
      <div id="chat-mensagens" class="flex-1 overflow-y-auto p-5 space-y-4 custom-scroll bg-[#F4F3F1] flex flex-col min-h-0"></div>

      <!-- Input de envio -->
      <div class="border-t border-gray-200 bg-white flex flex-col flex-shrink-0" style="padding-bottom: env(safe-area-inset-bottom, 0px)">

        <div id="preview-container" class="hidden p-4 bg-gray-50 border-b border-gray-100 items-center gap-4 relative">
          <div class="relative inline-block">
            <img id="img-preview" src="#" alt="Preview"
                 class="max-h-20 max-w-[120px] rounded-xl object-cover border-2 border-white shadow-md">
            <button type="button" onclick="limparPreview()"
                    class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white font-bold text-[10px] rounded-full flex items-center justify-center cursor-pointer shadow hover:bg-red-600 transition-colors">&#10005;</button>
          </div>
          <div class="flex flex-col min-w-0">
            <span id="nome-arquivo-preview" class="text-xs font-semibold text-gray-700 truncate max-w-[200px]">imagem.jpg</span>
            <span class="text-[10px] text-gray-400">Pronto para enviar com legenda</span>
          </div>
        </div>

        <form id="form-chat" class="p-4 flex items-center gap-2" style="padding-bottom:max(1rem,env(safe-area-inset-bottom,1rem))" onsubmit="processarEnvioChat(event)">
          <button type="button" onclick="document.getElementById('input-file').click()"
                  class="p-2 text-gray-400 hover:text-orange transition-colors cursor-pointer rounded-lg hover:bg-gray-50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94a3 3 0 114.243 4.243L8.567 17.808a1.5 1.5 0 11-2.122-2.122l7.693-7.693a.75.75 0 011.06 1.06l-7.693 7.693a1.5 1.5 0 102.122 2.122l10.94-10.94a3 3 0 10-4.243-4.243L3.13 13.068a4.5 4.5 0 006.364 6.364l7.693-7.693a.75.75 0 011.06 1.06z"></path>
            </svg>
          </button>

          <input type="file" id="input-file" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp"
                 onchange="capturarPreviewImagem(this)">

          <input type="text" id="input-mensagem" placeholder="Digite sua mensagem aqui..."
                 class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-xs text-gray-700 outline-none focus:border-gray-300 transition-all"
                 autocomplete="off">

          <button type="submit" id="btn-enviar"
                  class="w-9 h-9 bg-orange hover:bg-orange-dark rounded-xl flex items-center justify-center text-white transition-all cursor-pointer flex-shrink-0 shadow-md shadow-orange/10 disabled:opacity-50">
            <svg class="w-4 h-4 transform rotate-45 -translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
          </button>
        </form>
      </div>

      <?php else: ?>
      <div class="flex-1 flex flex-col items-center justify-center text-gray-400 bg-[#F4F3F1]">
        <p class="text-xs">Selecione uma conversa para visualizar o chat.</p>
      </div>
      <?php endif; ?>

    </section>
  </main>

  <!-- Modal Editar -->
  <div id="modal-editar" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="fecharModalEditar()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 flex flex-col gap-4">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-orange/10 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-orange" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
          </svg>
        </div>
        <div>
          <h3 class="text-sm font-bold text-gray-900">Editar mensagem</h3>
          <p class="text-[11px] text-gray-400">Você tem até 5 minutos para editar</p>
        </div>
      </div>
      <textarea id="modal-editar-input" rows="3"
        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-xs text-gray-700 outline-none focus:border-orange transition-all resize-none custom-scroll"
        placeholder="Digite a nova mensagem..."></textarea>
      <div class="flex gap-2 justify-end">
        <button onclick="fecharModalEditar()" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all cursor-pointer">Cancelar</button>
        <button onclick="confirmarEdicao()" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-orange hover:bg-orange-dark transition-all cursor-pointer shadow-md shadow-orange/20">Salvar alteração</button>
      </div>
    </div>
  </div>

  <!-- Modal Deletar -->
  <div id="modal-deletar" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="fecharModalDeletar()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 flex flex-col gap-4">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
          </svg>
        </div>
        <div>
          <h3 class="text-sm font-bold text-gray-900">Apagar mensagem?</h3>
          <p class="text-[11px] text-gray-400">Esta ação não pode ser desfeita</p>
        </div>
      </div>
      <div class="flex gap-2 justify-end">
        <button onclick="fecharModalDeletar()" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all cursor-pointer">Cancelar</button>
        <button onclick="confirmarDelecao()" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-red-500 hover:bg-red-600 transition-all cursor-pointer shadow-md shadow-red-500/20">Sim, apagar</button>
      </div>
    </div>
  </div>

<script>
  const chatContainer      = document.getElementById('chat-mensagens');
  const inputMensagem      = document.getElementById('input-mensagem');
  const listaContatos      = document.getElementById('lista-contatos');
  const topoAvatarBox      = document.getElementById('topo-avatar');
  const previewContainer   = document.getElementById('preview-container');
  const imgPreview         = document.getElementById('img-preview');
  const nomeArquivoPreview = document.getElementById('nome-arquivo-preview');
  const btnEnviar          = document.getElementById('btn-enviar');
  const inputFile          = document.getElementById('input-file');

  const idUsuarioLogado    = <?= (int)$idUsuarioLogado ?>;
  const urlBaseSupabase    = "<?= htmlspecialchars($urlBaseSupabase) ?>";
  const urlBaseChatImagens = "<?= htmlspecialchars($urlBaseChatImagens) ?>";
  const comQuemId          = new URLSearchParams(window.location.search).get('com');

  const urlApiBase      = '../../backend/controllers/RoteadorChat.php';
  const urlApiMensagens = comQuemId ? `${urlApiBase}?com=${comQuemId}` : null;
  const urlApiContatos  = `${urlApiBase}?acao=listar_contatos&com=${comQuemId || 0}`; // Sempre envia 'com' para a API
  const urlApiUpload    = `${urlApiBase}?acao=upload`;
  const servicosPrestadorJS = <?= json_encode($listaServicosDisponiveis) ?>;

  let imagemNaMensagemFila = null;

  String.prototype.hashCode = function () {
    let h = 0;
    for (let i = 0; i < this.length; i++) h = this.charCodeAt(i) + ((h << 5) - h);
    return h;
  };

  function gerarAvatarHtml(nome, fotoPerfil, tamanhoClasse = 'w-10 h-10') {
    if (fotoPerfil && fotoPerfil !== 'default.png' && fotoPerfil.trim() !== '') {
      const url = fotoPerfil.startsWith('http') ? fotoPerfil : urlBaseSupabase + fotoPerfil;
      return `<img src="${url}" class="${tamanhoClasse} rounded-full object-cover border border-gray-200" onerror="this.style.display='none'">`;
    }
    const cores = ['bg-orange', 'bg-blue-600', 'bg-purple-600', 'bg-green-600', 'bg-teal-600'];
    const cor   = cores[Math.abs((nome || '').hashCode()) % cores.length];
    const ini   = (nome || '?').split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase();
    return `<div class="${tamanhoClasse} rounded-full ${cor} text-white flex items-center justify-center text-xs font-bold shadow-inner">${ini}</div>`;
  }

  if (comQuemId && topoAvatarBox) {
    topoAvatarBox.innerHTML = gerarAvatarHtml("<?= htmlspecialchars($nomeDestinatario) ?>", "<?= htmlspecialchars($fotoDestinatario) ?>");
  }

  async function carregarContatos() {
    try {
      const resp = await fetch(urlApiContatos);
      if (!resp.ok) return;
      const contatos = await resp.json();
      listaContatos.innerHTML = '';
      if (!contatos || contatos.length === 0) {
        listaContatos.innerHTML = '<p class="text-xs text-gray-400 text-center p-4">Nenhuma conversa ativa.</p>';
        return;
      }
      contatos.forEach(c => {
        const selecionado = parseInt(comQuemId) === parseInt(c.id);
        const box = document.createElement('div');
        box.className = `flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all
          ${selecionado ? 'bg-orange/10 border border-orange/20 shadow-sm' : 'hover:bg-gray-50 border border-transparent'}`;
        box.onclick = () => { window.location.href = `chat.php?com=${c.id}`; };
        
        const countBadge = c.unread_count > 0 
          ? `<span class="ml-auto bg-orange text-white text-[10px] font-black px-2 py-0.5 rounded-full animate-pulse flex-shrink-0">${c.unread_count}</span>` 
          : '';
          
        box.innerHTML = `
          <div class="flex-shrink-0">${gerarAvatarHtml(c.nome, c.foto_perfil)}</div>
          <div class="min-w-0 flex-1">
            <span class="font-bold text-xs text-gray-900 truncate block">${c.nome}</span>
            <p class="text-[11px] text-gray-400 truncate mt-0.5">Clique para ver mensagens</p>
          </div>
          ${countBadge}
        `;
        listaContatos.appendChild(box);
      });
    } catch (e) { console.error('Erro ao carregar contatos:', e); }
  }

  async function carregarMensagens() {
    if (!urlApiMensagens || !chatContainer) return;
    try {
      const resp = await fetch(urlApiMensagens);
      if (!resp.ok) return;
      const textoPuro = await resp.text();
      if (!textoPuro.trim().startsWith('[') && !textoPuro.trim().startsWith('{')) return;
      const mensagens = JSON.parse(textoPuro);
      if (!mensagens || mensagens.erro) return;
      
      // Verifica se o usuário estava no final do chat antes de atualizar
      const estavaNoBaixo = (chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight) < 150;
      
      // Se for a primeira vez que carregamos, não queremos transições bruscas
      const primeiraCarga = chatContainer.innerHTML === '';
      
      chatContainer.innerHTML = '';

      if (mensagens.length === 0) {
        chatContainer.innerHTML = '<div class="text-center text-gray-400 text-xs my-auto pt-10">Nenhuma mensagem por aqui ainda...</div>';
        return;
      }

      const agora = Date.now();
      mensagens.forEach(msg => {
        const souEu      = parseInt(msg.remetente_id) === idUsuarioLogado;
        const foiDeletado = parseInt(msg.deletado || 0) === 1;
        const dataISO    = msg.criado_em.replace(' ', 'T') + 'Z';
        const tempoCriacao = new Date(dataISO);
        const horaLabel  = tempoCriacao.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        const minPassados = (agora - tempoCriacao.getTime()) / 60000;
        const dentroPrazo = minPassados <= 5;

        let botoesAcao = '';
        if (souEu && !foiDeletado && dentroPrazo) {
          const btnEditar = !msg.url_imagem
            ? `<button onclick="dispararEdicao(${msg.id}, '${encodeURIComponent(msg.mensagem)}')" title="Editar" class="hover:text-orange p-0.5 cursor-pointer">
                 <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"></path></svg>
               </button>` : '';
          botoesAcao = `
            <div class="msg-actions hidden gap-1 mr-1 text-gray-400 bg-white border border-gray-100 rounded shadow-sm p-1 z-10 self-center">
              ${btnEditar}
              <button onclick="deletarMensagem(${msg.id})" title="Apagar" class="hover:text-red-500 p-0.5 cursor-pointer">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path></svg>
              </button>
            </div>`;
        }

        let conteudo = '';
        if (foiDeletado) {
          conteudo = `
            <span style="display:inline-flex; align-items:center; gap:6px; color:#ef4444; font-style:italic; opacity:.9;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <circle cx="12" cy="12" r="9"></circle>
                    <line x1="6" y1="18" x2="18" y2="6"></line>
                </svg>
                <span>Esta mensagem foi apagada</span>
            </span>`;
        } else {
          if (msg.url_imagem) {
            const nomeSemPrefixo = msg.url_imagem.replace(/^chat\//, '');
            const urlImg = `${urlBaseChatImagens}${nomeSemPrefixo}`;
            conteudo += `<img src="${urlImg}" class="max-w-xs rounded-xl border border-gray-200 max-h-48 object-cover mb-1 block cursor-pointer hover:opacity-90" onclick="window.open('${urlImg}','_blank')" onerror="this.style.display='none'">`;
          }
          if (msg.mensagem && msg.mensagem.trim() !== '') {
            conteudo += `<div class="font-medium text-sm leading-relaxed">${msg.mensagem}</div>`;
          }
        }

        const editadaLabel = (msg.atualizado_em && !foiDeletado) ? `<span class="italic opacity-60"> · editada</span>` : '';
        const classeBalao = souEu
          ? `bg-orange text-white rounded-2xl rounded-tr-none py-2 px-4 text-xs shadow-sm max-w-full break-words ${foiDeletado ? 'opacity-60 bg-gray-300 !text-gray-700 italic' : ''}`
          : `bg-white text-gray-800 rounded-2xl rounded-tl-none py-2 px-4 text-xs shadow-sm max-w-full break-words border border-gray-100 ${foiDeletado ? 'italic text-gray-400' : ''}`;

        const divAlinhamento = document.createElement('div');
        divAlinhamento.className = `msg-container flex items-end gap-1 max-w-[85%] w-full ${souEu ? 'justify-end ml-auto' : 'justify-start'}`;

        let checkmarkHtml = '';
        if (souEu && !foiDeletado) {
          const estaLida    = msg.lido_em !== null && msg.lido_em !== undefined;
          const estaEntregue = msg.entregue_em !== null && msg.entregue_em !== undefined;
          
          if (estaLida) {
            checkmarkHtml = `<span class="check-lido ml-1" title="Lida" style="font-size: 11px;">&#10003;&#10003;</span>`;
          } else if (estaEntregue) {
            checkmarkHtml = `<span class="check-entregue ml-1" title="Entregue" style="font-size: 11px;">&#10003;&#10003;</span>`;
          } else {
            checkmarkHtml = `<span class="check-enviado ml-1" title="Enviada" style="font-size: 11px;">&#10003;</span>`;
          }
        }

        const divBalao = document.createElement('div');
        divBalao.className = classeBalao;
        divBalao.innerHTML = `${conteudo}<span class="block text-[9px] text-right mt-1 select-none opacity-75 ${souEu ? 'text-orange-100' : 'text-gray-400'}">${horaLabel}${editadaLabel}${checkmarkHtml}</span>`;

        divAlinhamento.appendChild(divBalao);
        if (souEu && botoesAcao) divAlinhamento.insertAdjacentHTML('beforeend', botoesAcao);
        chatContainer.appendChild(divAlinhamento);
      });

      // Se estava no final ou é a primeira carga, rola para o fim (Estilo WhatsApp)
      if (estavaNoBaixo || primeiraCarga) {
          chatContainer.scrollTop = chatContainer.scrollHeight;
      }
    } catch (err) { console.error('Erro ao carregar mensagens:', err); }
  }

  function capturarPreviewImagem(input) {
    if (!input.files || input.files.length === 0) return;
    const arquivo = input.files[0];
    imagemNaMensagemFila = arquivo;
    nomeArquivoPreview.textContent = arquivo.name;
    const leitor = new FileReader();
    leitor.onload = e => {
      imgPreview.src = e.target.result;
      previewContainer.classList.remove('hidden');
      previewContainer.classList.add('flex');
      inputMensagem.focus();
    };
    leitor.readAsDataURL(arquivo);
  }

  function limparPreview() {
    imagemNaMensagemFila = null;
    inputFile.value = '';
    imgPreview.src = '#';
    previewContainer.classList.remove('flex');
    previewContainer.classList.add('hidden');
  }

  async function processarEnvioChat(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    const texto = inputMensagem.value.trim();
    if (!texto && !imagemNaMensagemFila) return;
    if (!urlApiMensagens) return;
    btnEnviar.disabled = true;
    let pathParaSalvarNoBanco = null;
    try {
      if (imagemNaMensagemFila) {
        nomeArquivoPreview.textContent = 'Enviando imagem...';
        const formData = new FormData();
        formData.append('imagem', imagemNaMensagemFila);
        let uploadResp;
        try {
          uploadResp = await fetch(urlApiUpload, { method: 'POST', body: formData });
        } catch (errRede) {
          mostrarToast('Não foi possível conectar ao servidor.');
          btnEnviar.disabled = false;
          return;
        }
        const uploadTexto = await uploadResp.text();
        let uploadJson;
        try { uploadJson = JSON.parse(uploadTexto); } catch (_) {
          mostrarToast('Erro no servidor ao enviar imagem.');
          btnEnviar.disabled = false;
          return;
        }
        if (uploadJson.erro) { mostrarToast('Erro ao enviar imagem: ' + uploadJson.erro); btnEnviar.disabled = false; return; }
        if (!uploadJson.path) { mostrarToast('Resposta inesperada do servidor.'); btnEnviar.disabled = false; return; }
        pathParaSalvarNoBanco = uploadJson.path;
      }
      await fetch(urlApiMensagens, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mensagem: texto, url_imagem: pathParaSalvarNoBanco }),
      });
      inputMensagem.value = '';
      limparPreview();
      carregarMensagens();
    } catch (err) { console.error('Falha no fluxo de envio:', err); }
    finally { btnEnviar.disabled = false; }
  }

  let _editarId = null, _editarTextoOriginal = '', _deletarId = null;

  function dispararEdicao(id, textoEncoded) {
    _editarId = id;
    _editarTextoOriginal = decodeURIComponent(textoEncoded);
    const input = document.getElementById('modal-editar-input');
    input.value = _editarTextoOriginal;
    document.getElementById('modal-editar').classList.remove('hidden');
    setTimeout(() => { input.focus(); input.setSelectionRange(input.value.length, input.value.length); }, 50);
  }

  function fecharModalEditar() { document.getElementById('modal-editar').classList.add('hidden'); _editarId = null; }

  async function confirmarEdicao() {
    const novoTexto = document.getElementById('modal-editar-input').value.trim();
    if (!novoTexto || novoTexto === _editarTextoOriginal) { fecharModalEditar(); return; }
    try {
      const resp = await fetch(urlApiMensagens, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: _editarId, message: novoTexto }) });
      const json = await resp.json();
      fecharModalEditar();
      if (json.erro) { mostrarToast(json.erro, 'erro'); return; }
      carregarMensagens();
    } catch (e) { console.error(e); }
  }

  function deletarMensagem(id) { _deletarId = id; document.getElementById('modal-deletar').classList.remove('hidden'); }
  function fecharModalDeletar() { document.getElementById('modal-deletar').classList.add('hidden'); _deletarId = null; }

  async function confirmarDelecao() {
    if (!_deletarId) return;
    try {
      await fetch(urlApiMensagens, { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: _deletarId }) });
      fecharModalDeletar();
      carregarMensagens();
    } catch (e) { console.error(e); }
  }

  function mostrarToast(msg, tipo = 'erro') {
    const cor = tipo === 'erro' ? 'bg-red-500' : 'bg-green-500';
    const t = document.createElement('div');
    t.className = `fixed bottom-6 right-6 z-[999] ${cor} text-white text-xs font-bold px-4 py-3 rounded-xl shadow-lg transition-all`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  }

  window.dispararEdicao        = dispararEdicao;
  window.deletarMensagem       = deletarMensagem;
  window.fecharModalEditar     = fecharModalEditar;
  window.fecharModalDeletar    = fecharModalDeletar;
  window.confirmarEdicao       = confirmarEdicao;
  window.confirmarDelecao      = confirmarDelecao;
  window.limparPreview         = limparPreview;
  window.capturarPreviewImagem = capturarPreviewImagem;
  window.processarEnvioChat    = processarEnvioChat;

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { fecharModalEditar(); fecharModalDeletar(); }
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      if (!document.getElementById('modal-editar').classList.contains('hidden')) confirmarEdicao();
    }
  });

  // ─── iOS keyboard: scroll input into view when focused ───
  if (inputMensagem) {
    inputMensagem.addEventListener('focus', function () {
      setTimeout(() => this.scrollIntoView({ behavior: 'smooth', block: 'center' }), 300);
    });
  }

  // ─── Marcação de leitura ao abrir chat ───
  async function marcarComoLidoNoServidor() {
    if (!comQuemId) return;
    try {
      await fetch(`${urlApiBase}?acao=mark_read`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ remetente_id: parseInt(comQuemId) })
      });
    } catch (e) { /* silencioso */ }
  }

  carregarContatos();
  carregarMensagens();
  marcarComoLidoNoServidor();

  let isReloading = false;

  // ─── Polling de contrato (detecta mudanças e recarrega a barra) ───
  const _contratoStatusInicial = <?= json_encode($contratoSincronismo ? [
      'id' => (string)$contratoSincronismo['id'],
      'status' => (string)$contratoSincronismo['status'],
      'fp' => (bool)$contratoSincronismo['finalizado_prestador_em'],
      'fc' => (bool)$contratoSincronismo['finalizado_cliente_em']
  ] : null) ?>;

  async function _pollContratoStatus() {
    if (!comQuemId || isReloading) return;
    
    if (_contratoStatusInicial && _contratoStatusInicial.status === 'concluido') return;

    try {
      const resp = await fetch(`${urlApiBase}?acao=contract_status&com=${comQuemId}`);
      if (!resp.ok) return;
      const data = await resp.json();
      const novoContrato = data.contrato;
      
      const iniId     = _contratoStatusInicial ? _contratoStatusInicial.id : null;
      const iniStatus = _contratoStatusInicial ? _contratoStatusInicial.status : null;
      const iniFp     = _contratoStatusInicial ? _contratoStatusInicial.fp : false;
      const iniFc     = _contratoStatusInicial ? _contratoStatusInicial.fc : false;
      
      const novoId     = novoContrato ? String(novoContrato.id) : null;
      const novoStatus = novoContrato ? String(novoContrato.status) : null;
      const novoFp     = novoContrato ? !!novoContrato.finalizado_prestador_em : false;
      const novoFc     = novoContrato ? !!novoContrato.finalizado_cliente_em : false;
      
      if (iniId !== novoId || iniStatus !== novoStatus || iniFp !== novoFp || iniFc !== novoFc) {
          isReloading = true;
          window.location.reload();
      }
    } catch (e) { /* silencioso */ }
    finally {
        if (!isReloading) setTimeout(_pollContratoStatus, 4000);
    }
  }

  async function loopMensagens() {
    if (comQuemId && !isReloading) {
      await carregarMensagens();
      await marcarComoLidoNoServidor();
    }
    setTimeout(loopMensagens, 2000);
  }

  async function loopContatos() {
    if (!isReloading) {
      await carregarContatos();
    }
    setTimeout(loopContatos, 5000);
  }

  if (comQuemId) {
    _pollContratoStatus();
    loopMensagens();
  }
  loopContatos();
</script>
<script type="module">
  import { abrirModalContrato, fecharModalContrato, enviarPropostaContrato, alterarStatusContrato } from '/frontend/src/js/contrato.js';
  window.abrirModalContrato      = abrirModalContrato;
  window.fecharModalContrato     = fecharModalContrato;
  window.enviarPropostaContrato  = enviarPropostaContrato;
  window.alterarStatusContrato   = alterarStatusContrato;
</script>
</body>
</html>

