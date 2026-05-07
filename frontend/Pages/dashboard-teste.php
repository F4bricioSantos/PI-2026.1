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

$cats = $pdo
    ->query("SELECT DISTINCT categoria_nome FROM servicos WHERE categoria_nome IS NOT NULL ORDER BY 1")
    ->fetchAll(PDO::FETCH_COLUMN);
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
            bg:      '#F8F9FA',
          }
        }
      }
    }
  </script>
  <style>
    @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .card-reveal { animation: fadeUp 0.3s ease both; }
    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
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
      <div class="w-9 h-9 rounded-full bg-orange/80 flex items-center justify-center text-white font-bold text-sm">
          <?= strtoupper(mb_substr($usuario['nome'], 0, 1)) ?>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-6 custom-scroll">
      <form method="GET" action="" class="relative mb-5">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar por serviços..." class="w-full bg-white border border-gray-200 rounded-2xl pl-11 pr-5 py-3.5 text-sm shadow-sm focus:border-orange outline-none">
      </form>

      <div class="flex items-center gap-2 flex-wrap mb-7">
        <a href="?categoria=Todos" class="px-5 py-1.5 rounded-full text-xs font-bold transition-all <?= $categoriaAtiva === 'Todos' ? 'bg-orange text-white' : 'bg-white border border-gray-200 text-gray-400' ?>">Todos</a>
        <?php foreach ($cats as $cat): ?>
          <a href="?categoria=<?= urlencode($cat) ?>" class="px-5 py-1.5 rounded-full text-xs font-bold transition-all <?= $categoriaAtiva === $cat ? 'bg-orange text-white' : 'bg-white border border-gray-200 text-gray-400' ?>"><?= htmlspecialchars($cat) ?></a>
        <?php endforeach; ?>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($servicos as $i => $s): ?>
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col gap-3 hover:shadow-md transition-all card-reveal" style="animation-delay:<?= $i * 30 ?>ms">
            
            <div class="flex justify-between items-start">
              <span class="bg-orange/10 text-orange text-[10px] font-extrabold px-2 py-1 rounded uppercase tracking-wider">
                <?= htmlspecialchars($s['categoria_nome'] ?? 'Serviço') ?>
              </span>
              <span class="text-orange font-bold text-base whitespace-nowrap">
                <?= $s['valor_base'] ? 'R$ ' . number_format($s['valor_base'], 0, ',', '.') : 'A combinar' ?>
              </span>
            </div>

            <div>
              <h3 class="font-bold text-gray-900 text-lg leading-tight mb-1">
                <?= htmlspecialchars($s['titulo']) ?>
              </h3>
              <p class="text-gray-400 text-xs italic">
                <?= htmlspecialchars($s['prestador_nome']) ?> • <?= htmlspecialchars($s['cidade'] ?? 'Região') ?>
              </p>
            </div>

            <p class="text-gray-500 text-sm line-clamp-2 h-10">
              <?= htmlspecialchars($s['descricao_curta']) ?>
            </p>

            <div class="flex items-center justify-between mt-2 pt-4 border-t border-gray-50">
              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <span class="text-xs font-bold text-gray-700"><?= $s['media_nota'] ?></span>
                <span class="text-[10px] text-gray-400">(<?= $s['total_avaliacoes'] ?>)</span>
              </div>
              <a href="detalhes.php?id=<?= $s['id'] ?>" class="text-orange text-xs font-bold hover:underline">Ver detalhes</a>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>

  <a href="novo-servico.php" class="fixed bottom-7 right-7 w-14 h-14 bg-orange text-white rounded-2xl flex items-center justify-center shadow-lg hover:scale-105 transition-all z-50">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
  </a>
</body>
</html>