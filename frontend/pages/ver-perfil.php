<?php
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
// Certifique-se de que o modelo User está incluído
require_once '../../backend/models/User.php';

$idUsuarioLogado = $_SESSION['usuario_id'];
$userModel = new User($pdo);
$usuarioLogado = $userModel->buscarPorId($idUsuarioLogado);

// Captura o ID do perfil que está sendo visitado. Se não houver, mostra o do usuário logado.
$idPerfilVisitado = isset($_GET['id']) ? intval($_GET['id']) : $idUsuarioLogado;

// 1. BUSCA DADOS COMPLETOS DO USUÁRIO
$perfil = $userModel->buscarPerfilCompleto($idPerfilVisitado); 
if (!$perfil) {
    header("Location: dashboard.php");
    exit;
}

// 2. VERIFICAÇÃO PARA A SIDEBAR DO USUÁRIO LOGADO (se ele tem serviços)
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = :id");
$stmtCheck->execute([':id' => $idUsuarioLogado]);
$temServicoLogado = $stmtCheck->fetchColumn() > 0;

$isPrestador = !empty($perfil['nicho']);

// 3. BUSCA DE SERVIÇOS (Apenas se for Prestador)
$servicos = [];
if ($isPrestador) {
    $stmtServ = $pdo->prepare("SELECT id, titulo, categoria_nome, valor_base, descricao_curta FROM servicos WHERE prestador_id = :id ORDER BY id DESC");
    $stmtServ->execute([':id' => $idPerfilVisitado]);
    $servicos = $stmtServ->fetchAll(PDO::FETCH_ASSOC);
}

// 4. BUSCA HISTÓRICO DE AVALIAÇÕES (Consistência com detalhes.php)
$stmtAval = $pdo->prepare("
    SELECT a.nota, a.comentario, a.data_avaliacao, u.nome AS avaliador_nome, u.foto_perfil AS avaliador_foto, a.avaliador_tipo 
    FROM avaliacoes a
    JOIN usuarios u ON u.id = (CASE WHEN a.avaliador_tipo = 'prestador' THEN a.prestador_id ELSE a.cliente_id END)
    WHERE (a.prestador_id = :id AND avaliador_tipo = 'cliente')
       OR (a.cliente_id = :id AND avaliador_tipo = 'prestador')
    ORDER BY a.data_avaliacao DESC
");
$stmtAval->execute([':id' => $idPerfilVisitado]);
$avaliacoes = $stmtAval->fetchAll(PDO::FETCH_ASSOC);

$mediaNota = $perfil['media_nota'];
$totalAvaliacoes = $perfil['total_avaliacoes'];

$urlBaseSupabase = "https://yplpxzmwtkencrrtxmof.supabase.co/storage/v1/object/public/fotos/";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Perfil de <?= htmlspecialchars($perfil['nome']) ?></title>
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
    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }
  </style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  
  <script type="module">
    import { renderSidebar } from '/frontend/src/components/sidebar.js';
    const temServico = <?= $temServicoLogado ? 'true' : 'false' ?>;
    renderSidebar('sidebar-container', 'perfil', temServico, false, { badgeMensagens: 0, badgeAgendamentos: 0 }, {
      nome: "<?= htmlspecialchars($usuarioLogado['nome']) ?>",
      foto: "<?= $usuarioLogado['foto_perfil'] ?>"
    });
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">
    
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="history.back()" class="hover:text-gray-600 transition-colors p-1 -ml-1 rounded-lg hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <a href="dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors ml-2">Início</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-gray-800 font-bold text-lg tracking-tight">Perfil</span>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-6 custom-scroll space-y-6">
      
      <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex flex-col md:flex-row items-center gap-5 text-center md:text-left">
          
          <div class="w-24 h-24 rounded-full bg-orange flex items-center justify-center text-white font-bold text-3xl overflow-hidden border-4 border-gray-100 flex-shrink-0 shadow-sm">
            <?php if($perfil['foto_perfil'] && $perfil['foto_perfil'] !== 'default.png'): ?>
              <img src="<?= $urlBaseSupabase . $perfil['foto_perfil'] ?>" class="w-full h-full object-cover">
            <?php else: ?>
              <?= strtoupper(mb_substr($perfil['nome'] ?? '?', 0, 1)) ?>
            <?php endif; ?>
          </div>

          <div class="space-y-1">
            <div class="flex flex-col md:flex-row items-center gap-2">
              <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($perfil['nome']) ?></h2>
              <?php if($isPrestador): ?>
                <span class="bg-orange/10 text-orange text-[10px] font-extrabold px-2 py-0.5 rounded-md uppercase border border-orange/10"><?= htmlspecialchars($perfil['nicho']) ?></span>
              <?php else: ?>
                <span class="bg-blue-50 text-blue-600 text-[10px] font-extrabold px-2 py-0.5 rounded-md uppercase border border-blue-100">Cliente / Comprador</span>
              <?php endif; ?>
            </div>
            
            <p class="text-sm text-gray-500 font-medium"><?= htmlspecialchars($perfil['cidade'] ?? 'Cidade não informada') ?></p>
            
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 pt-1 text-xs text-gray-400">
              <span class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-orange" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <?= htmlspecialchars($perfil['email']) ?>
              </span>
              <?php if($perfil['telefone']): ?>
                <span class="flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-orange" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                  <?= htmlspecialchars($perfil['telefone']) ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="flex flex-col items-center md:items-end gap-3 border-t md:border-t-0 pt-4 md:pt-0 w-full md:w-auto border-gray-100">
          <div class="flex items-center gap-2 bg-orange/5 border border-orange/10 px-4 py-2 rounded-xl">
            <svg class="w-5 h-5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            <div class="text-left">
              <p class="text-xs font-bold text-gray-800 leading-none"><?= $mediaNota > 0 ? number_format($mediaNota, 1) . ' / 5.0' : 'Sem Notas' ?></p>
              <p class="text-[10px] text-gray-400 font-medium mt-0.5"><?= $totalAvaliacoes ?> avaliações</p>
            </div>
          </div>
          <?php if($idPerfilVisitado !== $idUsuarioLogado): ?>
            <a href="chat.php?com=<?= $idPerfilVisitado ?>" class="bg-orange hover:bg-orange-dark text-white text-xs font-bold px-5 py-2.5 rounded-xl transition-all shadow-md shadow-orange/10 text-center w-full md:w-auto">
              <?= $isPrestador ? 'Contratar Prestador' : 'Conversar com Cliente' ?>
            </a>
          <?php endif; ?>
        </div>
      </div>

      <?php if($isPrestador): ?>
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm space-y-3">
          <div class="flex items-center justify-between border-b border-gray-50 pb-2">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Sobre o Profissional</h3>
            <span class="text-xs text-gray-400 font-medium flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
              </svg>
              Experiência: <b><?= intval($perfil['experiencia_anos']) ?> anos</b>
            </span>
          </div>
          <p class="text-sm text-gray-600 leading-relaxed italic">
            "<?= nl2br(htmlspecialchars($perfil['bio'] ?? 'Nenhuma biografia detalhada inserida.')) ?>"
          </p>
        </div>

        <div class="space-y-3">
          <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider pl-1">Serviços Disponíveis</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach($servicos as $s): ?>
              <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex flex-col gap-3 justify-between">
                <div>
                  <span class="text-[9px] font-extrabold text-orange uppercase tracking-wider"><?= htmlspecialchars($s['categoria_nome']) ?></span>
                  <h4 class="font-bold text-gray-900 text-sm mt-0.5 line-clamp-1"><?= htmlspecialchars($s['titulo']) ?></h4>
                  <p class="text-xs text-gray-400 line-clamp-2 mt-1 italic">"<?= htmlspecialchars($s['descricao_curta']) ?>"</p>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-gray-50 mt-2">
                  <div>
                    <p class="text-[8px] text-gray-400 font-bold uppercase">Valor Base</p>
                    <span class="text-sm font-black text-gray-900">R$ <?= number_format($s['valor_base'], 2, ',', '.') ?></span>
                  </div>
                  <a href="detalhes.php?id=<?= $s['id'] ?>" class="bg-gray-50 hover:bg-orange hover:text-white text-orange p-2 rounded-lg transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="space-y-3">
        <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider pl-1">
          <?= $isPrestador ? 'Histórico de Avaliações Profissionais' : 'Reputação como Cliente / Comprador' ?>
        </h3>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm divide-y divide-gray-100">
          <?php foreach($avaliacoes as $a): ?>
            <div class="p-4 flex gap-3 items-start">
              <div class="w-8 h-8 rounded-full bg-gray-200 flex-shrink-0 overflow-hidden border">
                <?php if($a['avaliador_foto'] && $a['avaliador_foto'] !== 'default.png'): ?>
                  <img src="<?= $urlBaseSupabase . $a['avaliador_foto'] ?>" class="w-full h-full object-cover">
                <?php else: ?>
                  <div class="w-full h-full bg-orange/10 text-orange font-bold text-xs flex items-center justify-center">
                    <?= strtoupper(substr($a['avaliador_nome'] ?? '?', 0, 1)) ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="flex-1 space-y-1">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <h5 class="text-xs font-bold text-gray-900"><?= htmlspecialchars($a['avaliador_nome']) ?></h5>
                    <?= ($a['avaliador_tipo'] === 'prestador') 
                        ? '<span class="text-[9px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded font-extrabold uppercase tracking-wide">Avaliação como Cliente</span>' 
                        : '<span class="text-[9px] bg-orange/10 text-orange px-1.5 py-0.5 rounded font-extrabold uppercase tracking-wide">Avaliação como Prestador</span>' ?>
                  </div>
                  <span class="text-[10px] text-gray-400"><?= date('d/m/Y', strtotime($a['data_avaliacao'])) ?></span>
                </div>
                <div class="flex items-center gap-0.5">
                  <?php for($i=1; $i<=5; $i++): ?>
                    <svg class="w-3 h-3 <?= $i <= $a['nota'] ? 'fill-orange text-orange' : 'text-gray-200' ?>" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                  <?php endfor; ?>
                </div>
                <p class="text-xs text-gray-500 pt-0.5 leading-relaxed">"<?= htmlspecialchars($a['comentario']) ?>"</p>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if(empty($avaliacoes)): ?>
            <p class="text-xs text-gray-400 p-4 italic">Este perfil ainda não recebeu pontuações ou comentários na plataforma.</p>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>

</body>
</html>