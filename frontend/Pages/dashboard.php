<?php
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
require_once '../../backend/models/User.php';

$user    = new User($pdo);
$usuario = $user->buscarPorId($_SESSION['usuario_id']);

// --- CONFIGURAÇÃO SUPABASE ---
// URL base para as fotos públicas (ajuste conforme o seu projeto se necessário)
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

// Construção da Query SQL
$where  = ['s.prestador_id != :usuario_id'];
$params = [':usuario_id' => $_SESSION['usuario_id']];

if ($categoriaAtiva !== 'Todos') {
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
           COALESCE(ROUND(AVG(a.nota)::NUMERIC, 1), 0) AS media_nota,
           COUNT(a.id)::INT AS total_avaliacoes
    FROM servicos s
    JOIN usuarios u ON u.id = s.prestador_id
    LEFT JOIN avaliacoes a ON a.prestador_id = s.prestador_id
    $whereSql
    GROUP BY s.id, u.nome, u.cidade, u.foto_perfil
    ORDER BY s.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$servicos = $stmt->fetchAll();

$categoriasGerais = ["Reformas", "Pintura e Textura", "Elétrica", "Hidráulica", "Pisos e Revestimentos", "Alvenaria e Construção"];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Início</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Manrope', 'sans-serif'] },
          colors: { orange: { DEFAULT: '#F97316', dark: '#EA580C' }, sidebar: '#16213E', bg: '#F8F9FA' }
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

  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'inicio');
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-800">
        <div class="w-8 h-8 bg-orange/10 rounded-lg flex items-center justify-center text-orange">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
          </svg>
        </div>
        <span class="font-bold text-lg tracking-tight">Início</span>
      </div>
      
      <a href="perfil.php" class="hover:opacity-80 transition-opacity">
        <div class="w-10 h-10 rounded-full bg-orange flex items-center justify-center text-white font-bold text-sm overflow-hidden border-2 border-orange/20">
          <?php if($usuario['foto_perfil'] && $usuario['foto_perfil'] !== 'default.png'): ?>
            <img src="<?= $urlBaseSupabase . $usuario['foto_perfil'] ?>" class="w-full h-full object-cover">
          <?php else: ?>
            <?= strtoupper(mb_substr($usuario['nome'], 0, 1)) ?>
          <?php endif; ?>
        </div>
      </a>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-6 custom-scroll">
      
      <form id="filtroForm" method="GET" action="" class="space-y-4 mb-8">
        <div class="flex gap-3">
          <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="O que você precisa?" class="flex-1 bg-white border border-gray-200 rounded-2xl px-5 py-3.5 text-sm focus:border-orange outline-none shadow-sm transition-all">
          
          <div class="relative w-60">
            <input type="text" id="inputCidade" name="cidade" autocomplete="off" value="<?= htmlspecialchars($cidade) ?>" placeholder="Cidade" class="w-full bg-white border border-gray-200 rounded-2xl px-5 py-3.5 text-sm focus:border-orange outline-none shadow-sm transition-all">
            <div id="listaCidades" class="hidden absolute top-full left-0 w-full bg-white border border-gray-100 rounded-xl mt-1 shadow-xl z-50 max-h-48 overflow-y-auto"></div>
          </div>

          <div class="flex items-center bg-white border border-gray-200 rounded-2xl px-4 shadow-sm focus-within:border-orange transition-all">
            <input type="number" name="preco_min" value="<?= htmlspecialchars($precoMin) ?>" placeholder="Min" class="w-16 py-3.5 text-sm outline-none bg-transparent">
            <span class="text-gray-300 mx-2">|</span>
            <input type="number" name="preco_max" value="<?= htmlspecialchars($precoMax) ?>" placeholder="Max" class="w-16 py-3.5 text-sm outline-none bg-transparent">
          </div>

          <button type="submit" class="bg-orange text-white px-8 rounded-2xl font-bold text-sm hover:bg-orange-dark shadow-md shadow-orange/20 transition-all">Achar</button>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <?php $urlParams = "&busca=".urlencode($busca)."&cidade=".urlencode($cidade)."&preco_min=".$precoMin."&preco_max=".$precoMax; ?>
          <a href="?categoria=Todos<?= $urlParams ?>" class="px-5 py-2 rounded-full text-[11px] font-bold transition-all <?= $categoriaAtiva === 'Todos' ? 'bg-orange text-white' : 'bg-white text-gray-500 border border-gray-200 hover:border-orange hover:text-orange' ?>">TODOS</a>
          
          <?php foreach ($categoriasGerais as $cat): ?>
            <a href="?categoria=<?=urlencode($cat).$urlParams?>" class="px-5 py-2 rounded-full text-[11px] font-bold transition-all <?= $categoriaAtiva === $cat ? 'bg-orange text-white' : 'bg-white text-gray-500 border border-gray-200 hover:border-orange hover:text-orange' ?>">
              <?= mb_strtoupper($cat) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </form>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-10">
        <?php foreach ($servicos as $i => $s): ?>
          <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 card-reveal flex flex-col gap-4" style="animation-delay:<?= $i * 50 ?>ms">
            
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200">
                  <?php if($s['prestador_foto'] && $s['prestador_foto'] !== 'default.png'): ?>
                    <img src="<?= $urlBaseSupabase . $s['prestador_foto'] ?>" class="w-full h-full object-cover">
                  <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-orange/10 text-orange font-bold text-xs">
                      <?= strtoupper(substr($s['prestador_nome'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div>
                  <h4 class="text-sm font-bold text-gray-900 leading-tight"><?= htmlspecialchars($s['prestador_nome']) ?></h4>
                  <p class="text-[10px] text-gray-400 font-medium"><?= htmlspecialchars(explode(' - ', $s['cidade'])[0]) ?></p>
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
              <p class="text-xs text-gray-400 line-clamp-2 h-8 leading-relaxed italic">"<?= htmlspecialchars($s['descricao_curta']) ?>"</p>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-50 mt-auto">
              <div>
                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tight">Valor Base</p>
                <span class="text-base font-black text-gray-900">
                    <?= $s['valor_base'] ? 'R$ ' . number_format($s['valor_base'], 0, ',', '.') : 'A combinar' ?>
                </span>
              </div>
              <a href="detalhes.php?id=<?= $s['id'] ?>" class="bg-gray-50 hover:bg-orange hover:text-white text-orange p-2.5 rounded-xl transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
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
  </script>
</body>
</html>