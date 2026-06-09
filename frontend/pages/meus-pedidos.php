<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
require_once '../../backend/models/User.php';

$idUsuarioLogado = $_SESSION['usuario_id'] ?? 0;

if (!defined('SB_URL')) define('SB_URL', 'https://yplpxzmwtkencrrtxmof.supabase.co');
$urlBaseSupabase = SB_URL . "/storage/v1/object/public/fotos/";

try {
    $userModel = new User($pdo);
    $usuarioLogado = $userModel->buscarPorId($idUsuarioLogado);

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = :id");
    $stmtCheck->execute([':id' => $idUsuarioLogado]);
    $temServico = $stmtCheck->fetchColumn() > 0;
    $sqlPedidos = "
        SELECT 
            c.id AS contrato_id,
            c.status,
            c.criado_em AS data_envio,
            c.data_pactuada AS data_agendamento,
            c.finalizado_prestador_em AS data_conclusao,
            c.avaliado,
            c.favorito AS contrato_favorito,
            s.id AS servico_id,
            COALESCE(s.titulo, 'Serviço Removido') AS servico_titulo,
            u.id AS prestador_id,
            u.nome AS prestador_nome,
            u.foto_perfil AS prestador_foto
        FROM contratos c
        LEFT JOIN servicos s ON s.id = c.servico_id
        JOIN usuarios u ON u.id = c.prestador_id
        WHERE c.cliente_id = :cliente_id
        ORDER BY c.criado_em DESC
    ";
    $stmtPedidos = $pdo->prepare($sqlPedidos);
    $stmtPedidos->execute([':cliente_id' => $idUsuarioLogado]);
    $pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);
    $sqlFavoritos = "
        SELECT 
            s.id AS servico_id,
            s.titulo AS servico_titulo,
            s.categoria_nome,
            s.valor_base,
            u.id AS prestador_id,
            u.nome AS prestador_nome,
            u.foto_perfil AS prestador_foto,
            pd.nicho AS categoria_principal
        FROM favoritos_servicos f
        JOIN servicos s ON s.id = f.servico_id
        JOIN usuarios u ON u.id = s.prestador_id
        LEFT JOIN prestadores_detalhes pd ON pd.usuario_id = u.id
        WHERE f.usuario_id = :usuario_id
        ORDER BY f.criado_em DESC
    ";
    $stmtFav = $pdo->prepare($sqlFavoritos);
    $stmtFav->execute([':usuario_id' => $idUsuarioLogado]);
    $favoritos = $stmtFav->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtFavsList = $pdo->prepare("SELECT servico_id FROM favoritos_servicos WHERE usuario_id = :uid");
    $stmtFavsList->execute([':uid' => $idUsuarioLogado]);
    $favoritosIds = $stmtFavsList->fetchAll(PDO::FETCH_COLUMN);
    if (!$favoritosIds) {
        $favoritosIds = [];
    }

    $stmtUnreadMsgCount = $pdo->prepare("SELECT COUNT(*) FROM mensagens_chat WHERE destinatario_id = :uid AND lido_em IS NULL AND deletado = 0");
    $stmtUnreadMsgCount->execute([':uid' => $idUsuarioLogado]);
    $totalMensagensNaoLidas = (int)$stmtUnreadMsgCount->fetchColumn();
    $isAdmin = (isset($usuarioLogado['tipo_usuario']) && $usuarioLogado['tipo_usuario'] === 'admin');

} catch (PDOException $e) {
    // Fallback preventivo caso a tabela 'favoritos' ainda não esteja criada fisicamente
    $pedidos = isset($pedidos) ? $pedidos : [];
    $favoritos = [];
    $favoritosIds = [];
    $totalMensagensNaoLidas = 0;
    $isAdmin = false;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Meus Pedidos</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Manrope', 'sans-serif'] },
          colors: {
            orange:{DEFAULT:'#F97316',light:'#FFEDD5',dark:'#EA580C'},sidebar:'#16213E',card:'#1E2A3A',bg:'#F8F9FA',
          }
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
    import { renderSidebar } from '/frontend/src/components/sidebar.js';
    const isAdminJS = <?= $isAdmin ? 'true' : 'false' ?>;
    const temServicoJS = <?= $temServico ? 'true' : 'false' ?>;
    const badges = {
      badgeMensagens: <?= (int)($totalMensagensNaoLidas ?? 0) ?>,
      badgeAgendamentos: 0
    };
    renderSidebar('sidebar-container', 'agendamentos', temServicoJS, isAdminJS, badges, {
      nome: "<?= htmlspecialchars($usuarioLogado['nome']) ?>",
      foto: "<?= htmlspecialchars($usuarioLogado['foto_perfil'] ?? '') ?>"
    });
  </script>
  <main class="flex-1 flex flex-col overflow-hidden w-full relative">
    
    <header class="flex items-center justify-between px-4 md:px-8 py-4 md:py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-3 w-full">
        <button onclick="window.toggleSidebar && window.toggleSidebar()" class="md:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div>
          <h1 class="text-xl font-extrabold text-gray-900 tracking-tight uppercase">Meus Pedidos</h1>
          <p class="text-xs text-gray-500">Acompanhe seus serviços contratados</p>
        </div>
      </div>
    </header>
    <div class="bg-white border-b border-gray-200 px-4 md:px-8 flex-shrink-0">
      <nav class="hidden md:flex gap-6">
        <button onclick="switchTab('pedidos')" id="tab-btn-pedidos" class="border-b-2 border-orange text-orange font-bold text-sm py-4 px-2 transition-all">
          Meus pedidos
        </button>
        <button onclick="switchTab('favoritos')" id="tab-btn-favoritos" class="border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-semibold text-sm py-4 px-2 transition-all">
          Favoritos
        </button>
      </nav>

      <nav class="flex md:hidden flex-col py-1">
        <button onclick="switchTab('pedidos')" id="mb-btn-pedidos" class="text-left font-bold text-orange text-sm py-3 border-b-2 border-orange tracking-wider uppercase">
          Meus pedidos
        </button>
        <button onclick="switchTab('favoritos')" id="mb-btn-favoritos" class="text-left font-semibold text-gray-500 text-sm py-3 border-b-2 border-transparent tracking-wider uppercase">
          Favoritos
        </button>
      </nav>
    </div>
    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-6 custom-scroll">
      <div class="max-w-5xl mx-auto space-y-8">

        <div id="content-pedidos" class="tab-content space-y-6">
          
          <div class="flex flex-wrap gap-2 bg-white p-1.5 rounded-xl border border-gray-200 inline-flex shadow-sm">
            <button onclick="filterStatus('all')" class="filter-btn bg-orange text-white text-xs font-bold px-4 py-2 rounded-lg transition-all shadow-sm">TODOS</button>
            <button onclick="filterStatus('pendente')" class="filter-btn text-gray-500 hover:text-gray-800 text-xs font-bold px-4 py-2 rounded-lg transition-all">PENDENTES</button>
            <button onclick="filterStatus('aceito')" class="filter-btn text-gray-500 hover:text-gray-800 text-xs font-bold px-4 py-2 rounded-lg transition-all">EM ANDAMENTO</button>
            <button onclick="filterStatus('concluido')" class="filter-btn text-gray-500 hover:text-gray-800 text-xs font-bold px-4 py-2 rounded-lg transition-all">CONCLUÍDOS</button>
          </div>

          <?php if(empty($pedidos)): ?>
            <div class="text-center py-12 bg-white rounded-2xl border border-gray-100 shadow-sm">
              <p class="text-gray-400 text-sm italic">Nenhum pedido encontrado no sistema.</p>
            </div>
          <?php else: ?>
            <div id="section-pendente" class="status-section space-y-4">
              <h2 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Pendentes</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php 
                $hasPendente = false;
                foreach($pedidos as $pedido): 
                  if($pedido['status'] !== 'pendente') continue; 
                  $hasPendente = true;
                ?>
                  <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex flex-col justify-between">
                    <div>
                      <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex-shrink-0 flex items-center justify-center text-gray-600 font-bold text-xs overflow-hidden border border-gray-200">
                          <?php if(!empty($pedido['prestador_foto']) && $pedido['prestador_foto'] !== 'default.png'): ?>
                            <img src="<?= $urlBaseSupabase . htmlspecialchars($pedido['prestador_foto']) ?>" class="w-full h-full object-cover">
                          <?php else: ?>
                            <?= strtoupper(mb_substr($pedido['prestador_nome'] ?? 'P', 0, 2)) ?>
                          <?php endif; ?>
                        </div>
                        <span class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($pedido['prestador_nome']) ?></span>
                      </div>
                      <h3 class="text-base font-extrabold text-gray-900 mb-4 tracking-tight"><?= htmlspecialchars($pedido['servico_titulo']) ?></h3>
                      
                      <div class="space-y-1.5 text-xs text-gray-500 mb-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <p>Enviado em: <span class="font-semibold text-gray-700"><?= date('d/m/Y', strtotime($pedido['data_envio'])) ?></span></p>
                        <p>Agendado para: <span class="font-semibold text-gray-700"><?= date('d/m/Y', strtotime($pedido['data_agendamento'])) ?></span></p>
                      </div>
                    </div>
                    <div>
                      <div class="mb-4"><span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Status: PENDENTE</span></div>
                      <div class="flex gap-2 pt-2 border-t border-gray-100">
                        <a href="chat.php?com=<?= $pedido['prestador_id'] ?>" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 text-center font-bold py-2 rounded-xl text-xs transition-colors">Abrir chat</a>
                        <?php 
                        $isFav = in_array($pedido['servico_id'], $favoritosIds);
                        ?>
                        <button onclick="toggleFavorito(event, <?= $pedido['servico_id'] ?>)" data-service-id="<?= $pedido['servico_id'] ?>" class="fav-btn px-4 rounded-xl border transition-all text-xs font-bold flex items-center gap-1 <?= $isFav ? 'bg-orange/10 border-orange/20 text-orange' : 'bg-gray-50 border-gray-200 text-gray-500 hover:text-orange hover:border-orange/20' ?>">
                          <svg class="w-3.5 h-3.5 <?= $isFav ? 'fill-orange' : 'fill-none' ?>" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                          </svg>
                          <span class="btn-text"><?= $isFav ? 'Salvo' : 'Favoritar' ?></span>
                        </button>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
                <?php if(!$hasPendente): ?>
                  <p class="text-xs text-gray-400 italic col-span-full">Nenhum contrato pendente.</p>
                <?php endif; ?>
              </div>
            </div>
            <div id="section-aceito" class="status-section space-y-4 pt-2">
              <h2 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Em andamento</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php 
                $hasAceito = false;
                foreach($pedidos as $pedido): 
                  if($pedido['status'] !== 'aceito') continue; 
                  $hasAceito = true;
                ?>
                  <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex flex-col justify-between">
                    <div>
                      <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex-shrink-0 flex items-center justify-center text-gray-600 font-bold text-xs overflow-hidden border border-gray-200">
                          <?php if(!empty($pedido['prestador_foto']) && $pedido['prestador_foto'] !== 'default.png'): ?>
                            <img src="<?= $urlBaseSupabase . htmlspecialchars($pedido['prestador_foto']) ?>" class="w-full h-full object-cover">
                          <?php else: ?>
                            <?= strtoupper(mb_substr($pedido['prestador_nome'] ?? 'P', 0, 2)) ?>
                          <?php endif; ?>
                        </div>
                        <span class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($pedido['prestador_nome']) ?></span>
                      </div>
                      <h3 class="text-base font-extrabold text-gray-900 mb-4 tracking-tight"><?= htmlspecialchars($pedido['servico_titulo']) ?></h3>
                      
                      <div class="space-y-1.5 text-xs text-gray-500 mb-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <p>Enviado em: <span class="font-semibold text-gray-700"><?= date('d/m/Y', strtotime($pedido['data_envio'])) ?></span></p>
                        <p>Agendado para: <span class="font-semibold text-gray-700"><?= date('d/m/Y', strtotime($pedido['data_agendamento'])) ?></span></p>
                      </div>
                    </div>
                    <div>
                      <div class="mb-4"><span class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Status: EM ANDAMENTO</span></div>
                      <div class="flex gap-2 pt-2 border-t border-gray-100">
                        <a href="chat.php?com=<?= $pedido['prestador_id'] ?>" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 text-center font-bold py-2 rounded-xl text-xs transition-colors">Abrir chat</a>
                        <?php 
                        $isFav = in_array($pedido['servico_id'], $favoritosIds);
                        ?>
                        <button onclick="toggleFavorito(event, <?= $pedido['servico_id'] ?>)" data-service-id="<?= $pedido['servico_id'] ?>" class="fav-btn px-4 rounded-xl border transition-all text-xs font-bold flex items-center gap-1 <?= $isFav ? 'bg-orange/10 border-orange/20 text-orange' : 'bg-gray-50 border-gray-200 text-gray-500 hover:text-orange hover:border-orange/20' ?>">
                          <svg class="w-3.5 h-3.5 <?= $isFav ? 'fill-orange' : 'fill-none' ?>" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                          </svg>
                          <span class="btn-text"><?= $isFav ? 'Salvo' : 'Favoritar' ?></span>
                        </button>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
                <?php if(!$hasAceito): ?>
                  <p class="text-xs text-gray-400 italic col-span-full">Nenhum contrato em andamento.</p>
                <?php endif; ?>
              </div>
            </div>
            <div id="section-concluido" class="status-section space-y-4 pt-2">
              <h2 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Concluídos</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php 
                $hasConcluido = false;
                foreach($pedidos as $pedido): 
                  if($pedido['status'] !== 'concluido' && $pedido['status'] !== 'cancelado') continue; 
                  $hasConcluido = true;
                ?>
                  <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex flex-col justify-between">
                    <div>
                      <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex-shrink-0 flex items-center justify-center text-gray-600 font-bold text-xs overflow-hidden border border-gray-200">
                          <?php if(!empty($pedido['prestador_foto']) && $pedido['prestador_foto'] !== 'default.png'): ?>
                            <img src="<?= $urlBaseSupabase . htmlspecialchars($pedido['prestador_foto']) ?>" class="w-full h-full object-cover">
                          <?php else: ?>
                            <?= strtoupper(mb_substr($pedido['prestador_nome'] ?? 'P', 0, 2)) ?>
                          <?php endif; ?>
                        </div>
                        <span class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($pedido['prestador_nome']) ?></span>
                      </div>
                      <h3 class="text-base font-extrabold text-gray-900 mb-4 tracking-tight"><?= htmlspecialchars($pedido['servico_titulo']) ?></h3>
                      
                      <div class="space-y-1.5 text-xs text-gray-500 mb-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <p>Enviado em: <span class="font-semibold text-gray-700"><?= date('d/m/Y', strtotime($pedido['data_envio'])) ?></span></p>
                        <p>
                          <?= $pedido['status'] === 'cancelado' ? 'Cancelado em:' : 'Concluído em:' ?> 
                          <span class="font-semibold text-gray-700"><?= $pedido['data_conclusao'] ? date('d/m/Y', strtotime($pedido['data_conclusao'])) : date('d/m/Y', strtotime($pedido['data_agendamento'])) ?></span>
                        </p>
                      </div>
                    </div>
                    <div>
                      <div class="mb-4">
                        <?php if($pedido['status'] === 'cancelado'): ?>
                          <span class="text-[11px] font-bold text-red-600 uppercase tracking-wider">Status: CANCELADO</span>
                        <?php else: ?>
                          <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Status: CONCLUÍDO</span>
                        <?php endif; ?>
                      </div>
                      <div class="flex gap-2 pt-2 border-t border-gray-100">
                        <a href="chat.php?com=<?= $pedido['prestador_id'] ?>" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 text-center font-bold py-2 rounded-xl text-xs transition-colors">Abrir chat</a>
                        <?php 
                        $isFav = in_array($pedido['servico_id'], $favoritosIds);
                        ?>
                        <button onclick="toggleFavorito(event, <?= $pedido['servico_id'] ?>)" data-service-id="<?= $pedido['servico_id'] ?>" class="fav-btn px-3 rounded-xl border transition-all text-xs font-bold flex items-center gap-1 <?= $isFav ? 'bg-orange/10 border-orange/20 text-orange' : 'bg-gray-50 border-gray-200 text-gray-500 hover:text-orange hover:border-orange/20' ?>">
                          <svg class="w-3.5 h-3.5 <?= $isFav ? 'fill-orange' : 'fill-none' ?>" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                          </svg>
                          <span class="btn-text"><?= $isFav ? 'Salvo' : 'Favoritar' ?></span>
                        </button>
                        
                        <?php if($pedido['status'] === 'concluido' && !$pedido['avaliado']): ?>
                          <a href="avaliar-prestador.php?contrato_id=<?= $pedido['contrato_id'] ?>" class="px-3 bg-orange/10 hover:bg-orange text-orange hover:text-white border border-transparent rounded-xl transition-colors text-xs font-bold flex items-center">Avaliar</a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
                <?php if(!$hasConcluido): ?>
                  <p class="text-xs text-gray-400 italic col-span-full">Nenhum contrato finalizado ou concluído.</p>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div id="content-favoritos" class="tab-content hidden space-y-4">
          <h2 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">Favoritos</h2>
          
          <?php if(empty($favoritos)): ?>
            <div class="text-center py-12 bg-white rounded-2xl border border-gray-100 shadow-sm">
              <p class="text-gray-400 text-sm italic">Nenhum serviço na sua lista de favoritos.</p>
            </div>
          <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <?php foreach($favoritos as $fav): ?>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex flex-col justify-between" data-fav-card-id="<?= $fav['servico_id'] ?>">
                  <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gray-100 flex-shrink-0 flex items-center justify-center text-gray-600 font-bold text-sm overflow-hidden border border-gray-200">
                      <?php if(!empty($fav['prestador_foto']) && $fav['prestador_foto'] !== 'default.png'): ?>
                        <img src="<?= $urlBaseSupabase . htmlspecialchars($fav['prestador_foto']) ?>" class="w-full h-full object-cover">
                      <?php else: ?>
                        <?= strtoupper(mb_substr($fav['prestador_nome'] ?? 'P', 0, 2)) ?>
                      <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                      <h3 class="font-extrabold text-gray-900 text-sm tracking-tight truncate leading-snug"><?= htmlspecialchars($fav['servico_titulo']) ?></h3>
                      <p class="text-xs text-gray-500 font-medium truncate mt-0.5"><?= htmlspecialchars($fav['prestador_nome']) ?></p>
                      <p class="text-xs font-black text-gray-800 mt-1"><?= $fav['valor_base'] > 0 ? 'R$ ' . number_format($fav['valor_base'], 0, ',', '.') : 'A combinar' ?></p>
                    </div>
                  </div>
                  
                  <div class="flex gap-2 pt-3 border-t border-gray-100 mt-auto">
                    <a href="detalhes.php?id=<?= $fav['servico_id'] ?>" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 text-center font-bold py-2 rounded-xl text-xs transition-colors">Detalhes</a>
                    <a href="chat.php?com=<?= $fav['prestador_id'] ?>" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 text-center font-bold py-2 rounded-xl text-xs transition-colors">Abrir chat</a>
                    <button onclick="toggleFavorito(event, <?= $fav['servico_id'] ?>)" class="px-3 bg-orange/10 hover:bg-orange/20 text-orange border border-transparent rounded-xl text-xs font-bold transition-colors">Salvo</button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <script>
    function switchTab(tab) {
      document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
      document.getElementById('content-' + tab).classList.remove('hidden');

      const dtPedidos = document.getElementById('tab-btn-pedidos');
      const dtFavoritos = document.getElementById('tab-btn-favoritos');
      const mbPedidos = document.getElementById('mb-btn-pedidos');
      const mbFavoritos = document.getElementById('mb-btn-favoritos');

      if(tab === 'pedidos') {
        dtPedidos.className = "border-b-2 border-orange text-orange font-bold text-sm py-4 px-2 transition-all";
        dtFavoritos.className = "border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-semibold text-sm py-4 px-2 transition-all";
        mbPedidos.className = "text-left font-bold text-orange text-sm py-3 border-b-2 border-orange tracking-wider uppercase";
        mbFavoritos.className = "text-left font-semibold text-gray-500 text-sm py-3 border-b-2 border-transparent tracking-wider uppercase";
      } else {
        dtPedidos.className = "border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-semibold text-sm py-4 px-2 transition-all";
        dtFavoritos.className = "border-b-2 border-orange text-orange font-bold text-sm py-4 px-2 transition-all";
        mbPedidos.className = "text-left font-semibold text-gray-500 text-sm py-3 border-b-2 border-transparent tracking-wider uppercase";
        mbFavoritos.className = "text-left font-bold text-orange text-sm py-3 border-b-2 border-orange tracking-wider uppercase";
      }
    }

    function filterStatus(status) {
      const buttons = document.querySelectorAll('.filter-btn');
      buttons.forEach(btn => {
        let textNormalizado = btn.innerText.toLowerCase().replace('ú', 'u');
        if(textNormalizado === status || (status === 'all' && textNormalizado === 'todos')) {
          btn.className = "filter-btn bg-orange text-white text-xs font-bold px-4 py-2 rounded-lg transition-all shadow-sm";
        } else {
          btn.className = "filter-btn text-gray-500 hover:text-gray-800 text-xs font-bold px-4 py-2 rounded-lg transition-all";
        }
      });

      const sections = document.querySelectorAll('.status-section');
      sections.forEach(sec => {
        if(status === 'all') {
          sec.classList.remove('hidden');
        } else {
          if(sec.id === 'section-' + status) {
            sec.classList.remove('hidden');
          } else {
            sec.classList.add('hidden');
          }
        }
      });
    }

    async function toggleFavorito(event, servicoId) {
      event.preventDefault();
      event.stopPropagation();
      const btn = event.currentTarget;
      
      try {
        const response = await fetch('../../backend/controllers/FavoritoController.php?acao=toggle', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ servico_id: servicoId })
        });
        const result = await response.json();
        
        if (result.sucesso) {
          // 1. Sincroniza todos os botões com o mesmo servicoId na aba "Meus pedidos"
          const allButtons = document.querySelectorAll(`button[data-service-id="${servicoId}"]`);
          allButtons.forEach(button => {
            const svg = button.querySelector('svg');
            const span = button.querySelector('.btn-text');
            if (result.favoritado) {
              button.className = "fav-btn px-4 rounded-xl border bg-orange/10 border-orange/20 text-orange transition-all text-xs font-bold flex items-center gap-1";
              if (svg) svg.setAttribute('class', 'w-3.5 h-3.5 fill-orange');
              if (span) span.innerText = "Salvo";
            } else {
              button.className = "fav-btn px-4 rounded-xl border bg-gray-50 border-gray-200 text-gray-500 hover:text-orange hover:border-orange/20 transition-all text-xs font-bold flex items-center gap-1";
              if (svg) svg.setAttribute('class', 'w-3.5 h-3.5 fill-none');
              if (span) span.innerText = "Favoritar";
            }
          });

          // 2. Se estiver na aba "Favoritos" e o usuário desfavoritou, removemos o card correspondente da lista de favoritos com uma animação suave
          const card = document.querySelector(`[data-fav-card-id="${servicoId}"]`);
          if (card && !result.favoritado) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(() => {
              card.remove();
              // Se não sobrar nenhum favorito, recarrega para exibir a tela de lista vazia
              const grid = document.querySelector('#content-favoritos .grid');
              if (grid && grid.children.length === 0) {
                window.location.reload();
              }
            }, 300);
          }
        } else {
          alert(result.erro || 'Erro ao atualizar favorito.');
        }
      } catch (error) {
        console.error('Erro ao favoritar:', error);
        alert('Erro de conexão com o servidor.');
      }
    }
  </script>
</body>
</html>
