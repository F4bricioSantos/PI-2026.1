<?php
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
require_once '../../backend/models/User.php';

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

// --- CONFIGURAÃ‡ÃƒO SUPABASE ---
$urlBaseSupabase = "https://yplpxzmwtkencrrtxmof.supabase.co/storage/v1/object/public/fotos/";

// Captura de Filtros
$categoriaAtiva = trim($_GET['categoria'] ?? 'Todos');
$busca          = trim($_GET['busca']     ?? '');
$cidade         = trim($_GET['cidade']    ?? '');
$precoMin       = trim($_GET['preco_min'] ?? '');
$precoMax       = trim($_GET['preco_max'] ?? '');
// Endpoint para o Autocomplete de Cidades (AJAX)
if (isset($_GET['ajax_cidades'])) {
    $termo = $_GET['ajax_cidades'] . '%';
    $stmt = $pdo->prepare("SELECT DISTINCT cidade FROM usuarios WHERE cidade ILIKE ? AND cidade IS NOT NULL LIMIT 5");
    $stmt->execute([$termo]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    exit;
}
// ConstruÃ§Ã£o da Query SQL
$where  = ['s.prestador_id != :usuario_id'];
$params = [':usuario_id' => $idUsuarioLogado];

if ($categoriaAtiva === 'Favoritos') {
    $where[] = 's.id IN (SELECT servico_id FROM favoritos_servicos WHERE usuario_id = :usuario_id_fav)';
    $params[':usuario_id_fav'] = $idUsuarioLogado;
} elseif ($categoriaAtiva !== 'Todos') {
    $where[] = 'LOWER(s.categoria_nome) = LOWER(:categoria)';
    $params[':categoria'] = $categoriaAtiva;
}
if ($busca !== '') {
    $where[] = '(s.titulo ILIKE :busca OR s.descricao_curta ILIKE :busca)';
    $params[':busca'] = "%$busca%";
}
if ($cidade !== '') {
    $where[] = 'u.cidade ILIKE :cidade';
    $params[':cidade'] = "%$cidade%";
}
if ($precoMin !== '' && is_numeric($precoMin)) {
    $where[] = 's.valor_base >= :preco_min';
    $params[':preco_min'] = $precoMin;
}
if ($precoMax !== '' && is_numeric($precoMax)) {
    $where[] = 's.valor_base <= :preco_max';
    $params[':preco_max'] = $precoMax;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT s.id, s.titulo, s.categoria_nome, s.valor_base, s.descricao_curta,
           u.nome AS prestador_nome, u.cidade, u.foto_perfil AS prestador_foto,
           s.prestador_id,
           COALESCE(ROUND(AVG(a.nota)::NUMERIC, 1), 0) AS media_nota,
           COUNT(a.id)::INT AS total_avaliacoes
    FROM servicos s
    JOIN usuarios u ON u.id = s.prestador_id
    LEFT JOIN avaliacoes a ON a.prestador_id = s.prestador_id
    $whereSql
    GROUP BY s.id, u.nome, u.cidade, u.foto_perfil, s.prestador_id
    ORDER BY s.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$servicos = $stmt->fetchAll();
// Busca lista de ids dos serviÃ§os favoritos do usuÃ¡rio logado
$stmtFavs = $pdo->prepare("SELECT servico_id FROM favoritos_servicos WHERE usuario_id = :uid");
$stmtFavs->execute([':uid' => $idUsuarioLogado]);
$favoritosIds = $stmtFavs->fetchAll(PDO::FETCH_COLUMN);
if (!$favoritosIds) {
    $favoritosIds = [];
}
// Busca a contagem global de mensagens nÃ£o lidas para o usuÃ¡rio logado
$stmtUnreadMsgCount = $pdo->prepare("SELECT COUNT(*) FROM mensagens_chat WHERE destinatario_id = :uid AND lido_em IS NULL AND deletado = 0");
$stmtUnreadMsgCount->execute([':uid' => $idUsuarioLogado]);
$totalMensagensNaoLidas = (int)$stmtUnreadMsgCount->fetchColumn();

$categoriasGerais = ["Reformas", "Pintura e Textura", "ElÃ©trica", "HidrÃ¡ulica", "Pisos e Revestimentos", "Alvenaria e ConstruÃ§Ã£o"];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAÃ­ â€“ InÃ­cio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Manrope', 'sans-serif'] },
          colors:{orange:{DEFAULT:'#F97316',light:'#FFEDD5',dark:'#EA580C'},sidebar:'#16213E',card:'#1E2A3A',bg:'#F8F9FA'}
        }
      }
    }
  </script>
  <style>
    @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .card-reveal { animation: fadeUp 0.3s ease both; }
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }
    input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
  </style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <div id="sidebar-container" class="fixed inset-y-0 left-0 z-50 w-60 bg-sidebar flex flex-col h-screen transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out"></div>
  <script type="module">
    import { renderSidebar } from '/frontend/src/components/sidebar.js';
    const temServico = <?= $temServico ? 'true' : 'false' ?>;
    const isAdmin = <?= (isset($usuario['tipo_usuario']) && $usuario['tipo_usuario'] === 'admin') ? 'true' : 'false' ?>;
    const badges = {
      badgeMensagens: <?= (int)($totalMensagensNaoLidas ?? 0) ?>,
      badgeAgendamentos: 0
    };

    // Renderiza passando todos os dados corretamente
    renderSidebar('sidebar-container', 'inicio', temServico, isAdmin, badges, {
      nome: "<?= htmlspecialchars($usuario['nome']) ?>",
      foto: "<?= htmlspecialchars($usuario['foto_perfil'] ?? '') ?>"
    });
  </script>
  <main class="flex-1 flex flex-col overflow-hidden w-full relative">
    <header class="flex items-center justify-between px-4 md:px-8 py-4 md:py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-3 text-gray-800">
        <button onclick="window.toggleSidebar && window.toggleSidebar()" class="md:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="w-8 h-8 bg-orange/10 rounded-lg hidden md:flex items-center justify-center text-orange flex-shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
          </svg>
        </div>
        <span class="font-bold text-lg tracking-tight">InÃ­cio</span>
      </div>
      
      <a href="perfil.php" class="hover:opacity-80 transition-opacity">
        <div class="w-10 h-10 rounded-full bg-orange flex items-center justify-center text-white font-bold text-sm overflow-hidden border-2 border-orange/20">
          <?php if($usuario['foto_perfil'] && $usuario['foto_perfil'] !== 'default.png'): ?>
            <img src="<?= $urlBaseSupabase . $usuario['foto_perfil'] ?>" class="w-full h-full object-cover">
          <?php else: ?>
            <?= strtoupper(mb_substr($usuario['nome'] ?? 'U', 0, 1)) ?>
          <?php endif; ?>
        </div>
      </a>
    </header>
    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-4 md:py-6 custom-scroll">
      
      <form id="filtroForm" method="GET" action="" class="space-y-4 mb-6 md:mb-8">
        <div class="flex flex-col md:flex-row gap-3">
          <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="O que vocÃª precisa?" class="flex-1 bg-white border border-gray-200 rounded-2xl px-5 py-3.5 text-sm focus:border-orange outline-none shadow-sm transition-all w-full">
          
          <div class="relative w-full md:w-60">
            <input type="text" id="inputCidade" name="cidade" autocomplete="off" value="<?= htmlspecialchars($cidade) ?>" placeholder="Cidade" class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-3.5 text-sm focus:border-orange outline-none shadow-sm transition-all">
            <div id="listaCidades" class="hidden absolute top-full left-0 w-full bg-white border border-gray-100 rounded-xl mt-1 shadow-xl z-50 max-h-48 overflow-y-auto"></div>
          </div>

          <div class="flex items-center bg-white border border-gray-200 rounded-2xl px-4 shadow-sm focus-within:border-orange transition-all w-full md:w-auto">
            <input type="number" name="preco_min" value="<?= htmlspecialchars($precoMin) ?>" placeholder="Min" class="w-full md:w-16 py-3.5 text-sm outline-none bg-transparent">
            <span class="text-gray-300 mx-2">|</span>
            <input type="number" name="preco_max" value="<?= htmlspecialchars($precoMax) ?>" placeholder="Max" class="w-full md:w-16 py-3.5 text-sm outline-none bg-transparent">
          </div>

          <button type="submit" class="bg-orange text-white px-8 py-3.5 md:py-0 rounded-2xl font-bold text-sm hover:bg-orange-dark shadow-md shadow-orange/20 transition-all w-full md:w-auto">Achar</button>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <?php $urlParams = "&busca=".urlencode($busca)."&cidade=".urlencode($cidade)."&preco_min=".$precoMin."&preco_max=".$precoMax; ?>
          <a href="?categoria=Todos<?= $urlParams ?>" class="px-5 py-2 rounded-full text-[11px] font-bold transition-all <?= $categoriaAtiva === 'Todos' ? 'bg-orange text-white' : 'bg-white text-gray-500 border border-gray-200 hover:border-orange hover:text-orange' ?>">TODOS</a>
          
          <a href="?categoria=Favoritos<?= $urlParams ?>" class="px-5 py-2 rounded-full text-[11px] font-bold transition-all flex items-center gap-1.5 <?= $categoriaAtiva === 'Favoritos' ? 'bg-orange text-white' : 'bg-white text-gray-500 border border-gray-200 hover:border-orange hover:text-orange' ?>">
            <svg class="w-3 h-3 <?= $categoriaAtiva === 'Favoritos' ? 'fill-white text-white' : 'fill-none text-current' ?>" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
            FAVORITOS
          </a>
          <?php foreach ($categoriasGerais as $cat): ?>
            <a href="?categoria=<?=urlencode($cat).$urlParams?>" class="px-5 py-2 rounded-full text-[11px] font-bold transition-all <?= $categoriaAtiva === $cat ? 'bg-orange text-white' : 'bg-white text-gray-500 border border-gray-200 hover:border-orange hover:text-orange' ?>">
              <?= mb_strtoupper($cat) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </form>

      <?php if (empty($servicos)): ?>
        <div class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-dashed border-gray-200 card-reveal w-full">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mb-4">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Nenhum serviÃ§o encontrado</h3>
            <p class="text-sm text-gray-400 mt-1 max-w-xs text-center leading-relaxed">
                NÃ£o encontramos resultados para os filtros aplicados. Tente mudar a categoria ou limpar a busca.
            </p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-10">
            <?php foreach ($servicos as $i => $s): ?>
              <div onclick="window.location.href='detalhes.php?id=<?= $s['id'] ?>'" class="cursor-pointer bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 card-reveal flex flex-col gap-4" style="animation-delay:<?= $i * 50 ?>ms">
                
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <a href="ver-perfil.php?id=<?= $s['prestador_id'] ?>" onclick="event.stopPropagation();" class="w-11 h-11 rounded-full bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200 block hover:opacity-80 transition-opacity">
                      <?php if($s['prestador_foto'] && $s['prestador_foto'] !== 'default.png'): ?>
                        <img src="<?= $urlBaseSupabase . $s['prestador_foto'] ?>" class="w-full h-full object-cover">
                      <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-orange/10 text-orange font-bold text-xs">
                          <?= strtoupper(substr($s['prestador_nome'] ?? '?', 0, 1)) ?>
                        </div>
                      <?php endif; ?>
                    </a>
                    <div>
                      <h4 class="text-sm font-bold text-gray-900 leading-tight"><?= htmlspecialchars($s['prestador_nome']) ?></h4>
                      <p class="text-[10px] text-gray-400 font-medium"><?= htmlspecialchars(explode(' - ', $s['cidade'] ?? '')[0]) ?></p>
                    </div>
                  </div>
                  
                  <div class="flex items-center gap-1 bg-orange/5 px-2 py-1 rounded-lg">
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <span class="text-xs font-bold text-gray-700"><?= $s['media_nota'] > 0 ? number_format($s['media_nota'], 1) : 'Novo' ?></span>
                  </div>
                </div>

                <div class="space-y-1">
                  <span class="text-[9px] font-extrabold text-orange uppercase tracking-wider"><?= htmlspecialchars($s['categoria_nome']) ?></span>
                  <h3 class="font-bold text-gray-900 text-base line-clamp-1 h-6"><?= htmlspecialchars($s['titulo']) ?></h3>
                  <p class="text-xs text-gray-400 line-clamp-2 leading-relaxed italic">"<?= htmlspecialchars($s['descricao_curta']) ?>"</p>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-50 mt-auto">
                  <div>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tight">Valor Base</p>
                    <span class="text-base font-black text-gray-900">
                        <?= $s['valor_base'] ? 'R$ ' . number_format($s['valor_base'], 0, ',', '.') : 'A combinar' ?>
                    </span>
                  </div>
                  <div class="flex items-center gap-2">
                    <?php $isFav = in_array($s['id'], $favoritosIds); ?>
                    <button onclick="toggleFavorito(event, <?= $s['id'] ?>)" data-service-id="<?= $s['id'] ?>" class="fav-btn p-2.5 rounded-xl border transition-all shadow-sm flex items-center justify-center <?= $isFav ? 'bg-orange/10 border-orange/20 text-orange' : 'bg-gray-50 border-gray-100 text-gray-400 hover:text-orange hover:border-orange/20' ?>" title="<?= $isFav ? 'Remover dos Favoritos' : 'Salvar nos Favoritos' ?>">
                      <svg class="w-4 h-4 <?= $isFav ? 'fill-orange' : 'fill-none' ?>" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                      </svg>
                    </button>
                    <a href="detalhes.php?id=<?= $s['id'] ?>" onclick="event.stopPropagation();" class="bg-gray-50 hover:bg-orange hover:text-white text-orange p-2.5 rounded-xl transition-all shadow-sm">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    const inputCidade = document.getElementById('inputCidade');
    const listaCidades = document.getElementById('listaCidades');
    const filtroForm = document.getElementById('filtroForm');

    inputCidade.addEventListener('input', async (e) => {
        const termo = e.target.value;
        if (termo.length < 2) { listaCidades.classList.add('hidden'); return; }
        const res = await fetch(`?ajax_cidades=${encodeURIComponent(termo)}`);
        const cidades = await res.json();
        if (cidades.length > 0) {
            listaCidades.innerHTML = cidades.map(c => `<div class="px-4 py-3 hover:bg-orange/5 cursor-pointer text-sm border-b border-gray-50 last:border-none item-cidade transition-colors">${c}</div>`).join('');
            listaCidades.classList.remove('hidden');
        } else { listaCidades.classList.add('hidden'); }
    });

    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('item-cidade')) {
            inputCidade.value = e.target.innerText.trim();
            listaCidades.classList.add('hidden');
            filtroForm.submit();
        } else if (e.target !== inputCidade) {
            listaCidades.classList.add('hidden');
        }
    });

    function toggleFavoritoUi(servicoId, favoritado) {
      const allButtons = document.querySelectorAll(`button[data-service-id="${servicoId}"]`);
      allButtons.forEach(button => {
        const svg = button.querySelector('svg');
        if (favoritado) {
          button.className = "fav-btn p-2.5 rounded-xl border bg-orange/10 border-orange/20 text-orange transition-all shadow-sm flex items-center justify-center";
          svg.setAttribute('class', 'w-4 h-4 fill-orange');
          button.setAttribute('title', 'Remover dos Favoritos');
        } else {
          button.className = "fav-btn p-2.5 rounded-xl border bg-gray-50 border-gray-100 text-gray-400 hover:text-orange hover:border-orange/20 transition-all shadow-sm flex items-center justify-center";
          svg.setAttribute('class', 'w-4 h-4 fill-none');
          button.setAttribute('title', 'Salvar nos Favoritos');
        }
      });
    }

    async function toggleFavorito(event, servicoId) {
        event.preventDefault();
        event.stopPropagation();
        
        const btn = event.currentTarget;
        const estaFav = btn.classList.contains('bg-orange/10');
        const novoEstado = !estaFav;

        toggleFavoritoUi(servicoId, novoEstado);
        
        try {
            const response = await fetch('../../backend/controllers/FavoritoController.php?acao=toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ servico_id: servicoId })
            });
            
            const result = await response.json();
            
            if (!result.sucesso) {
                toggleFavoritoUi(servicoId, estaFav);
                return;
            }

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('categoria') === 'Favoritos' && !result.favoritado) {
                const card = btn.closest('.bg-white.rounded-2xl');
                if (card) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        card.remove();
                        const grid = document.querySelector('.grid.grid-cols-1');
                        if (grid && grid.children.length === 0) window.location.reload();
                    }, 300);
                }
            }
        } catch (error) {
            toggleFavoritoUi(servicoId, estaFav);
        }
    }
  </script>
</body>
</html>
