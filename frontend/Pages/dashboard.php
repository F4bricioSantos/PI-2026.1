<?php
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
require_once '../../backend/models/User.php';

$user    = new User($pdo);
$usuario = $user->buscarPorId($_SESSION['usuario_id']);

$categoriaAtiva = trim($_GET['categoria'] ?? 'Todos');
$busca          = trim($_GET['busca']     ?? '');

$where  = ['s.prestador_id != :usuario_id'];
$params = [':usuario_id' => $_SESSION['usuario_id']];

if ($categoriaAtiva !== 'Todos') {
    $where[]              = 'LOWER(s.categoria_nome) = LOWER(:categoria)';
    $params[':categoria'] = $categoriaAtiva;
}
if ($busca !== '') {
    $where[]          = '(s.titulo ILIKE :busca OR s.descricao_curta ILIKE :busca)';
    $params[':busca'] = "%$busca%";
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT
        s.id,
        s.titulo,
        s.categoria_nome,
        s.valor_base,
        s.descricao_curta,
        u.nome  AS prestador_nome,
        u.cidade,
        COALESCE(ROUND(AVG(a.nota)::NUMERIC, 1), 0) AS media_nota,
        COUNT(a.id)::INT                             AS total_avaliacoes
    FROM servicos s
    JOIN  usuarios u  ON u.id = s.prestador_id
    LEFT JOIN avaliacoes a ON a.prestador_id = s.prestador_id
    $whereSql
    GROUP BY s.id, u.nome, u.cidade
    ORDER BY s.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$servicos = $stmt->fetchAll();

// Categorias do banco para os chips
$cats = $pdo
    ->query("SELECT DISTINCT categoria_nome FROM servicos WHERE categoria_nome IS NOT NULL ORDER BY 1")
    ->fetchAll(PDO::FETCH_COLUMN);

// Foto de capa por categoria
$fotosCat = [
    'reforma'    => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&q=80&w=600',
    'elétrica'   => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&q=80&w=600',
    'eletrica'   => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&q=80&w=600',
    'pintura'    => 'https://images.unsplash.com/photo-1589939705384-5185137a7f0f?auto=format&fit=crop&q=80&w=600',
    'hidráulica' => 'https://images.unsplash.com/photo-1504148455328-436306343aa1?auto=format&fit=crop&q=80&w=600',
    'hidraulica' => 'https://images.unsplash.com/photo-1504148455328-436306343aa1?auto=format&fit=crop&q=80&w=600',
    'design'     => 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&q=80&w=600',
    'telhado'    => 'https://images.unsplash.com/photo-1632759162352-f117d873d630?auto=format&fit=crop&q=80&w=600',
    'jardinagem' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?auto=format&fit=crop&q=80&w=600',
    'limpeza'    => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&q=80&w=600',
    'marcenaria' => 'https://images.unsplash.com/photo-1588854337115-1c67d9247e4d?auto=format&fit=crop&q=80&w=600',
    'default'    => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&q=80&w=600',
];

function getFoto(string $cat, array $map): string {
    return $map[mb_strtolower(trim($cat))] ?? $map['default'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAi – Início</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Manrope', 'sans-serif'] },
          colors: {
            orange:  { DEFAULT: '#F97316', light: '#FFEDD5', dark: '#EA580C' },
            sidebar: '#16213E',
            card:    '#1E2A3A',
            bg:      '#F8F9FA',
          }
        }
      }
    }
  </script>
  <style>
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .card-reveal { animation: fadeUp 0.3s ease both; }
  </style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <!-- ══════════════ SIDEBAR (mantida igual ao original) ══════════════ -->
  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'inicio');
  </script>

  <!-- ══════════════ MAIN ══════════════ -->
  <main class="flex-1 flex flex-col overflow-hidden">

    <!-- Top bar -->
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
      <div class="flex items-center gap-4">
        <button class="relative text-gray-400 hover:text-gray-700 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 01-3.46 0"/>
          </svg>
          <span class="absolute -top-1 -right-1 w-2 h-2 bg-orange rounded-full"></span>
        </button>
        <!-- Avatar com inicial do nome -->
        <div title="<?= htmlspecialchars($usuario['nome']) ?>"
             class="w-9 h-9 rounded-full bg-orange/80 flex-shrink-0 cursor-pointer hover:opacity-90 transition-opacity flex items-center justify-center text-white font-bold text-sm">
          <?= strtoupper(mb_substr($usuario['nome'], 0, 1)) ?>
        </div>
      </div>
    </header>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto px-8 py-6">

      <!-- Search -->
      <form method="GET" action="" class="relative mb-5">
        <?php if ($categoriaAtiva !== 'Todos'): ?>
          <input type="hidden" name="categoria" value="<?= htmlspecialchars($categoriaAtiva) ?>">
        <?php endif; ?>
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
        </svg>
        <input
          type="text"
          name="busca"
          value="<?= htmlspecialchars($busca) ?>"
          placeholder="Buscar por serviços (ex: encanador, pintor, eletricista)..."
          class="w-full bg-white border border-gray-200 rounded-2xl pl-11 pr-5 py-3.5 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-orange transition-colors shadow-sm"
        />
      </form>

      <!-- Filter chips — gerados do banco -->
      <div class="flex items-center gap-2 flex-wrap mb-7">

        <a href="?categoria=Todos<?= $busca ? '&busca=' . urlencode($busca) : '' ?>"
           class="px-5 py-1.5 rounded-full text-sm font-semibold transition-all
                  <?= $categoriaAtiva === 'Todos' ? 'bg-orange text-white' : 'border border-gray-300 text-gray-500 hover:border-orange hover:text-orange' ?>">
          Todos
        </a>

        <?php foreach ($cats as $cat): ?>
          <a href="?categoria=<?= urlencode($cat) ?><?= $busca ? '&busca=' . urlencode($busca) : '' ?>"
             class="px-5 py-1.5 rounded-full text-sm font-semibold transition-all
                    <?= $categoriaAtiva === $cat ? 'bg-orange text-white' : 'border border-gray-300 text-gray-500 hover:border-orange hover:text-orange' ?>">
            <?= htmlspecialchars($cat) ?>
          </a>
        <?php endforeach; ?>

      </div>

      <!-- Cards Grid -->
      <div class="grid grid-cols-3 gap-5">

        <?php if (empty($servicos)): ?>

          <div class="col-span-3 flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 bg-orange/10 rounded-full flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-orange" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
              </svg>
            </div>
            <p class="font-bold text-gray-700 text-lg">Nenhum serviço encontrado</p>
            <p class="text-gray-400 text-sm mt-1">
              Tente outra categoria ou
              <a href="dashboard.php" class="text-orange font-semibold hover:underline">limpar filtros</a>.
            </p>
          </div>

        <?php else: ?>

          <?php foreach ($servicos as $i => $s): ?>
            <div class="bg-white rounded-2xl overflow-hidden flex flex-col shadow-lg
                        hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 card-reveal"
                 style="animation-delay:<?= $i * 40 ?>ms">

              <div class="relative">
                <img
                  src="<?= getFoto($s['categoria_nome'] ?? '', $fotosCat) ?>"
                  alt="<?= htmlspecialchars($s['titulo']) ?>"
                  class="w-full h-44 object-cover"
                />
                <span class="absolute top-3 left-3 bg-orange text-white text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wide">
                  <?= htmlspecialchars($s['categoria_nome'] ?? 'Serviço') ?>
                </span>
              </div>

              <div class="p-5 flex flex-col gap-2 flex-1">
                <div class="flex items-start justify-between gap-2">
                  <h3 class="font-bold text-gray-900 text-base leading-snug">
                    <?= htmlspecialchars($s['titulo']) ?>
                  </h3>
                  <span class="text-orange font-extrabold text-base whitespace-nowrap">
                    <?= $s['valor_base'] ? 'R$ ' . number_format($s['valor_base'], 0, ',', '.') : 'A combinar' ?>
                  </span>
                </div>

                <p class="text-gray-400 text-xs">
                  <?= htmlspecialchars($s['prestador_nome']) ?>
                  <?= $s['cidade'] ? ' • ' . htmlspecialchars($s['cidade']) : '' ?>
                </p>

                <?php if (!empty($s['descricao_curta'])): ?>
                  <p class="text-gray-500 text-xs line-clamp-2">
                    <?= htmlspecialchars($s['descricao_curta']) ?>
                  </p>
                <?php endif; ?>

                <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100">
                  <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24">
                      <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <span class="text-xs font-semibold text-gray-700">
                      <?= $s['media_nota'] > 0 ? number_format($s['media_nota'], 1) : '–' ?>
                    </span>
                    <span class="text-xs text-gray-400">
                      (<?= $s['total_avaliacoes'] ?> avaliação<?= $s['total_avaliacoes'] != 1 ? 'ões' : '' ?>)
                    </span>
                  </div>
                  <a href="detalhes.php?id=<?= $s['id'] ?>" class="text-orange text-xs font-bold hover:underline">
                    Ver detalhes
                  </a>
                </div>
              </div>

            </div>
          <?php endforeach; ?>

        <?php endif; ?>

      </div><!-- /grid -->
    </div><!-- /scroll -->
  </main>

  <!-- FAB -->
  <a href="novo-servico.php"
     title="Publicar novo serviço"
     class="fixed bottom-7 right-7 w-[52px] h-[52px] bg-orange text-white rounded-full flex items-center justify-center shadow-lg hover:opacity-90 transition-opacity z-50">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
      <line x1="12" y1="5" x2="12" y2="19"/>
      <line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
  </a>

</body>
</html>