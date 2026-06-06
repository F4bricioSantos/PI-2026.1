<?php
header('Content-Type: text/html; charset=UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
require_once '../../backend/models/User.php';

$idUsuarioLogado = $_SESSION['usuario_id'] ?? 0;

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

$isAdmin = (isset($usuario['tipo_usuario']) && $usuario['tipo_usuario'] === 'admin');

if (!defined('SB_URL')) define('SB_URL', 'https://yplpxzmwtkencrrtxmof.supabase.co');
$urlBaseSupabase = SB_URL . "/storage/v1/object/public/fotos/";

$contratoId = isset($_GET['contrato_id']) ? (int)$_GET['contrato_id'] : 0;
$comId = isset($_GET['com']) ? (int)$_GET['com'] : 0;

// Busca o contrato e valida
$stmtContrato = $pdo->prepare("
    SELECT 
        c.id AS contrato_id,
        c.cliente_id,
        c.prestador_id,
        c.servico_id,
        c.status,
        c.avaliado,
        c.avaliado_prestador,
        COALESCE(s.titulo, 'Serviço Removido') AS servico_titulo,
        uc.nome AS cliente_nome,
        uc.foto_perfil AS cliente_foto,
        up.nome AS prestador_nome,
        up.foto_perfil AS prestador_foto,
        pd.nicho AS prestador_nicho
    FROM contratos c
    LEFT JOIN servicos s ON c.servico_id = s.id
    JOIN usuarios uc ON c.cliente_id = uc.id
    JOIN usuarios up ON c.prestador_id = up.id
    LEFT JOIN prestadores_detalhes pd ON pd.usuario_id = up.id
    WHERE c.id = :contrato_id
");
$stmtContrato->execute([':contrato_id' => $contratoId]);
$contrato = $stmtContrato->fetch(PDO::FETCH_ASSOC);

if (!$contrato || $contrato['status'] !== 'concluido') {
    header("Location: meus-pedidos.php");
    exit;
}

$souCliente   = ($idUsuarioLogado == $contrato['cliente_id']);
$souPrestador = ($idUsuarioLogado == $contrato['prestador_id']);

if (!$souCliente && !$souPrestador) {
    header("Location: meus-pedidos.php");
    exit;
}

if ($souCliente && $contrato['avaliado']) {
    header("Location: meus-pedidos.php");
    exit;
}

if ($souPrestador && $contrato['avaliado_prestador']) {
    header("Location: chat.php");
    exit;
}

$alvoId = $souCliente ? $contrato['prestador_id'] : $contrato['cliente_id'];
$nomeOutro = $souCliente ? $contrato['prestador_nome'] : $contrato['cliente_nome'];
$fotoOutro = $souCliente ? $contrato['prestador_foto'] : $contrato['cliente_foto'];
$papelOutro = $souCliente ? 'Prestador de Serviço' : 'Cliente';
$subtituloOutro = $souCliente ? ($contrato['prestador_nicho'] ?? 'Geral') : 'Cliente / Comprador';

// Estatísticas reais do alvo
$stmtMedia = $pdo->prepare("
    SELECT AVG(nota) as media, COUNT(nota) as total
    FROM avaliacoes
    WHERE (prestador_id = :id AND avaliador_tipo = 'cliente')
       OR (cliente_id = :id AND avaliador_tipo = 'prestador')
");
$stmtMedia->execute([':id' => $alvoId]);
$mediaDados = $stmtMedia->fetch(PDO::FETCH_ASSOC);

$mediaNota = $mediaDados['media'] ? round((float)$mediaDados['media'], 1) : 0;
$totalAvaliacoes = (int)$mediaDados['total'];

$distribuicao = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
if ($totalAvaliacoes > 0) {
    $stmtDist = $pdo->prepare("
        SELECT nota, COUNT(*) as qtd
        FROM avaliacoes
        WHERE (prestador_id = :id AND avaliador_tipo = 'cliente')
           OR (cliente_id = :id AND avaliador_tipo = 'prestador')
        GROUP BY nota
    ");
    $stmtDist->execute([':id' => $alvoId]);
    $distDados = $stmtDist->fetchAll(PDO::FETCH_ASSOC);
    foreach ($distDados as $d) {
        $distribuicao[(int)$d['nota']] = (int)$d['qtd'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Avaliar <?= ($souCliente) ? 'Prestador' : 'Cliente' ?></title>
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
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }
  </style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <!-- ══════════════ SIDEBAR ══════════════ -->
  <div id="sidebar-container" class="fixed inset-y-0 left-0 z-50 w-60 bg-sidebar flex flex-col h-screen transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    const temServico = <?= $temServico ? 'true' : 'false' ?>;
    const isAdmin    = <?= $isAdmin ? 'true' : 'false' ?>;
    renderSidebar('sidebar-container', 'agendamentos', temServico, isAdmin, {}, {
      nome: "<?= htmlspecialchars($usuario['nome']) ?>",
      foto: "<?= $usuario['foto_perfil'] ?>"
    });
  </script>

  <!-- ══════════════ MAIN ══════════════ -->
  <main class="flex-1 flex flex-col overflow-hidden w-full relative">

    <!-- Top bar -->
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="window.toggleSidebar && window.toggleSidebar()" class="md:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <button onclick="history.back()" aria-label="Voltar" class="hover:text-gray-600 transition-colors p-1 rounded-lg hover:bg-gray-100 hidden md:block">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <a href="./dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors md:ml-2">Início</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-gray-800 font-bold text-lg tracking-tight">Avaliar <?= ($souCliente) ? 'Prestador' : 'Cliente' ?></span>
      </div>
      <div class="flex items-center gap-4">
        <button aria-label="Notificações" class="text-gray-400 hover:text-gray-700 transition-colors p-2 rounded-xl hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        </button>
      </div>
    </header>

    <!-- Scrollable content -->
    <div class="flex-1 overflow-y-auto px-8 py-10 custom-scroll flex flex-col items-center">
      
      <div class="w-full max-w-2xl">
        
        <!-- Card do Avaliado -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-12 flex items-center gap-5">
          <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-300 overflow-hidden border border-gray-200">
            <?php if (!empty($fotoOutro) && $fotoOutro !== 'default.png'): ?>
              <img src="<?= $urlBaseSupabase . htmlspecialchars($fotoOutro) ?>" class="w-full h-full object-cover">
            <?php else: ?>
              <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <?php endif; ?>
          </div>
          <div>
            <span class="text-[10px] font-bold text-orange uppercase tracking-wider block mb-0.5"><?= $papelOutro ?></span>
            <h2 class="text-xl font-extrabold text-slate-900"><?= htmlspecialchars($nomeOutro) ?></h2>
            <p class="text-slate-400 text-sm"><?= htmlspecialchars($contrato['servico_titulo']) ?> (<?= htmlspecialchars($subtituloOutro) ?>)</p>
          </div>
        </div>

        <!-- Área de Avaliação -->
        <div class="text-center space-y-8">
          <div>
            <h3 class="text-slate-900 font-bold text-lg mb-4">Como foi sua experiência?</h3>
            <div id="star-container" class="flex items-center justify-center gap-2 mb-2">
              <svg data-rating="1" class="star w-10 h-10 text-orange fill-orange cursor-pointer transition-colors" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg data-rating="2" class="star w-10 h-10 text-orange fill-orange cursor-pointer transition-colors" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg data-rating="3" class="star w-10 h-10 text-orange fill-orange cursor-pointer transition-colors" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg data-rating="4" class="star w-10 h-10 text-orange fill-orange cursor-pointer transition-colors" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <svg data-rating="5" class="star w-10 h-10 text-slate-200 fill-slate-200 cursor-pointer transition-colors" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <p id="rating-text" class="text-orange font-bold text-sm">Muito bom</p>
          </div>

          <!-- Comentário -->
          <div class="text-left space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Comentário (Opcional)</label>
            <textarea id="comentario-text" rows="5" placeholder="Conte-nos mais sobre o serviço prestado..." 
              class="w-full bg-white border border-gray-100 rounded-2xl px-6 py-4 text-sm focus:outline-none focus:border-orange focus:ring-4 focus:ring-orange/5 transition-all shadow-sm resize-none"></textarea>
          </div>

          <!-- Botões -->
          <div class="space-y-4 pt-4">
            <button id="btn-enviar-avaliacao" onclick="submeterAvaliacao()" class="w-full bg-orange hover:bg-orange-600 text-white py-4 rounded-2xl font-bold text-base shadow-lg shadow-orange/20 transition-all hover:scale-[1.01] active:scale-95 flex items-center justify-center gap-3">
              Enviar avaliação
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14m-7-7l7 7-7 7"/></svg>
            </button>
            <button onclick="window.location.href='<?= $souCliente ? "meus-pedidos.php" : "chat.php?com=" . $comId ?>'" class="w-full bg-slate-50 hover:bg-slate-100 text-slate-600 py-4 rounded-2xl font-bold text-sm transition-colors">
              Voltar
            </button>
          </div>
        </div>

        <!-- Média do Alvo -->
        <div class="mt-20 pt-10 border-t border-gray-100">
          <div class="flex items-center justify-between mb-6">
            <h4 class="text-sm font-bold text-slate-900">Média do <?= ($souCliente) ? 'profissional' : 'cliente' ?></h4>
            <div class="flex items-center gap-1.5 text-orange font-extrabold">
              <span><?= $mediaNota > 0 ? $mediaNota : 'Sem avaliações' ?></span>
              <svg class="w-4 h-4 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
          </div>

          <!-- Barras de Estatística -->
          <div class="space-y-3">
            <?php foreach ([5, 4, 3, 2, 1] as $estrela): 
                $pct = $totalAvaliacoes > 0 ? round(($distribuicao[$estrela] / $totalAvaliacoes) * 100) : 0;
            ?>
            <div class="flex items-center gap-4">
              <span class="text-[10px] font-bold text-slate-400 w-4"><?= $estrela ?></span>
              <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-orange rounded-full" style="width: <?= $pct ?>%"></div>
              </div>
              <span class="text-[10px] font-bold text-slate-400 w-8 text-right"><?= $pct ?>%</span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Footer -->
        <footer class="mt-16 py-8 text-center">
          <p class="text-[11px] text-gray-400 font-medium tracking-wide">
            © 2026 ReformAí - Conectando você aos melhores profissionais.
          </p>
        </footer>

      </div>

    </div>
  </main>

  <script>
    const stars = document.querySelectorAll('.star');
    const ratingText = document.getElementById('rating-text');
    let currentRating = 4;

    const labels = {
      1: 'Péssimo',
      2: 'Ruim',
      3: 'Regular',
      4: 'Muito bom',
      5: 'Excelente'
    };

    function updateStars(rating) {
      stars.forEach(star => {
        const val = parseInt(star.getAttribute('data-rating'));
        if (val <= rating) {
          star.classList.remove('text-slate-200', 'fill-slate-200');
          star.classList.add('text-orange', 'fill-orange');
        } else {
          star.classList.remove('text-orange', 'fill-orange');
          star.classList.add('text-slate-200', 'fill-slate-200');
        }
      });
      ratingText.textContent = labels[rating] || '';
    }

    stars.forEach(star => {
      star.addEventListener('click', () => {
        currentRating = parseInt(star.getAttribute('data-rating'));
        updateStars(currentRating);
      });

      star.addEventListener('mouseover', () => {
        const hoverRating = parseInt(star.getAttribute('data-rating'));
        updateStars(hoverRating);
      });

      star.addEventListener('mouseout', () => {
        updateStars(currentRating);
      });
    });

    async function submeterAvaliacao() {
      const comentario = document.getElementById('comentario-text').value.trim();
      const contratoId = <?= (int)$contratoId ?>;
      const comId = <?= (int)$comId ?>;

      try {
        const resp = await fetch('../../backend/controllers/ContratoController.php?acao=salvar_avaliacao', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            contrato_id: contratoId,
            nota: currentRating,
            comentario: comentario
          })
        });

        const res = await resp.json();
        if (resp.ok && res.sucesso) {
          mostrarToast('Avaliação enviada com sucesso!', 'sucesso');
          setTimeout(() => {
            if (comId > 0) {
              window.location.href = 'chat.php?com=' + comId;
            } else {
              window.location.href = '<?= $souCliente ? "meus-pedidos.php" : "chat.php" ?>';
            }
          }, 1500);
        } else {
          mostrarToast(res.erro || 'Erro ao enviar avaliação.', 'erro');
        }
      } catch (e) {
        mostrarToast('Erro de conexão ao enviar avaliação.', 'erro');
      }
    }

    function mostrarToast(msg, tipo = 'erro') {
      const col = tipo === 'erro' ? 'bg-red-500' : 'bg-green-500';
      const t = document.createElement('div');
      t.className = `fixed bottom-6 right-6 z-[999] ${col} text-white text-xs font-bold px-4 py-3 rounded-xl shadow-lg transition-all`;
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(() => t.remove(), 3500);
    }
  </script>

</body>
</html>
