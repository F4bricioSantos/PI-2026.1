<?php
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
require_once '../../backend/models/User.php';

$idUsuarioLogado = $_SESSION['usuario_id'];
$userModel = new User($pdo);

// Captura o ID do perfil que está sendo visitado. Se não houver, mostra o do usuário logado.
$idPerfilVisitado = isset($_GET['id']) ? intval($_GET['id']) : $idUsuarioLogado;

// 1. BUSCA DADOS BÁSICOS DO USUÁRIO VISITADO (Seja ele Cliente ou Prestador)
$stmtUser = $pdo->prepare("SELECT nome, email, telefone, cidade, foto_perfil FROM usuarios WHERE id = :id");
$stmtUser->execute([':id' => $idPerfilVisitado]);
$perfil = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    header("Location: dashboard.php");
    exit;
}

// 2. VERIFICAÇÃO PARA A SIDEBAR DO USUÁRIO LOGADO (Se ele tem serviço cadastrado)
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = :id");
$stmtCheck->execute([':id' => $idUsuarioLogado]);
$temServicoLogado = $stmtCheck->fetchColumn() > 0;

// 3. BUSCA DETALHES PROFISSIONAIS DO PERFIL VISITADO
$stmtDetails = $pdo->prepare("SELECT bio, nicho, experiencia_anos FROM prestadores_detalhes WHERE usuario_id = :id");
$stmtDetails->execute([':id' => $idPerfilVisitado]);
$detalhesProfissionais = $stmtDetails->fetch(PDO::FETCH_ASSOC);

// Define se o perfil visitado pertence a um prestador de serviços
$isPrestador = !empty($detalhesProfissionais);

// 4. SE FOR PRESTADOR, COLETA APENAS OS SERVIÇOS E AS AVALIAÇÕES (Portfólio removido)
$servicos = [];
$avaliacoes = [];
$mediaNota = 0;

if ($isPrestador) {
    // Serviços ofertados
    $stmtServ = $pdo->prepare("SELECT id, titulo, categoria_nome, valor_base, descricao_curta FROM servicos WHERE prestador_id = :id ORDER BY id DESC");
    $stmtServ->execute([':id' => $idPerfilVisitado]);
    $servicos = $stmtServ->fetchAll(PDO::FETCH_ASSOC);

    // Avaliações recebidas
    $stmtAval = $pdo->prepare("
        SELECT a.nota, a.comentario, a.data_avaliacao, u.nome AS cliente_nome, u.foto_perfil AS cliente_foto 
        FROM avaliacoes a
        JOIN usuarios u ON u.id = a.cliente_id
        WHERE a.prestador_id = :id 
        ORDER BY a.data_avaliacao DESC
    ");
    $stmtAval->execute([':id' => $idPerfilVisitado]);
    $avaliacoes = $stmtAval->fetchAll(PDO::FETCH_ASSOC);

    // Cálculo da média de estrelas
    $stmtMedia = $pdo->prepare("SELECT COALESCE(ROUND(AVG(nota)::NUMERIC, 1), 0) FROM avaliacoes WHERE prestador_id = :id");
    $stmtMedia->execute([':id' => $idPerfilVisitado]);
    $mediaNota = $stmtMedia->fetchColumn();
}

// Configuração do seu bucket Supabase para as fotos
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
    import { renderSidebar } from '../src/components/sidebar.js';
    const temServico = <?= $temServicoLogado ? 'true' : 'false' ?>;
    renderSidebar('sidebar-container', '', temServico, false, { badgeMensagens: 0, badgeAgendamentos: 0 });
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">
    
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-800">
        <div class="w-8 h-8 bg-orange/10 rounded-lg flex items-center justify-center text-orange">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <span class="font-bold text-lg tracking-tight">Perfil do Usuário</span>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-6 custom-scroll space-y-6">
      
      <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex flex-col md:flex-row items-center gap-5 text-center md:text-left">
          <div class="w-24 h-24 rounded-full bg-orange flex items-center justify-center text-white font-bold text-3xl overflow-hidden border-4 border-gray-100 flex-shrink-0 shadow-sm">
            <?php if($perfil['foto_perfil'] && $perfil['foto_perfil'] !== 'default.png'): ?>
              <img src="<?= $urlBaseSupabase . $perfil['foto_perfil'] ?>" class="w-full h-full object-cover">
            <?php else: ?>
              <?= strtoupper(mb_substr($perfil['nome'], 0, 1)) ?>
            <?php endif; ?>
          </div>

          <div class="space-y-1">
            <div class="flex flex-col md:flex-row items-center gap-2">
              <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($perfil['nome']) ?></h2>
              <?php if($isPrestador): ?>
                <span class="bg-orange/10 text-orange text-[10px] font-extrabold px-2 py-0.5 rounded-md uppercase border border-orange/10"><?= htmlspecialchars($detalhesProfissionais['nicho']) ?></span>
              <?php else: ?>
                <span class="bg-gray-100 text-gray-500 text-[10px] font-extrabold px-2 py-0.5 rounded-md uppercase">Cliente</span>
              <?php endif; ?>
            </div>
            
            <p class="text-sm text-gray-500 font-medium"><?= htmlspecialchars($perfil['cidade'] ?? 'Cidade não informada') ?></p>
            
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 pt-1 text-xs text-gray-400">
              <span class="flex items-center gap-1">✉️ <?= htmlspecialchars($perfil['email']) ?></span>
              <?php if($perfil['telefone']): ?>
                <span class="flex items-center gap-1">📞 <?= htmlspecialchars($perfil['telefone']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if($isPrestador): ?>
          <div class="flex flex-col items-center md:items-end gap-3 border-t md:border-t-0 pt-4 md:pt-0 w-full md:w-auto border-gray-100">
            <div class="flex items-center gap-2 bg-orange/5 border border-orange/10 px-4 py-2 rounded-xl">
              <svg class="w-5 h-5 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              <div class="text-left">
                <p class="text-xs font-bold text-gray-800 leading-none"><?= $mediaNota > 0 ? number_format($mediaNota, 1) . ' / 5.0' : 'Novo Prestador' ?></p>
                <p class="text-[10px] text-gray-400 font-medium mt-0.5"><?= count($avaliacoes) ?> avaliações</p>
              </div>
            </div>
            <?php if($idPerfilVisitado !== $idUsuarioLogado): ?>
              <a href="chat.php?com_usuario_id=<?= $idPerfilVisitado ?>" class="bg-orange hover:bg-orange-dark text-white text-xs font-bold px-5 py-2.5 rounded-xl transition-all shadow-md shadow-orange/10 text-center w-full md:w-auto">Conversar no Chat</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php if($idPerfilVisitado !== $idUsuarioLogado): ?>
            <div class="flex flex-col items-center md:items-end w-full md:w-auto">
              <a href="chat.php?com_usuario_id=<?= $idPerfilVisitado ?>" class="bg-orange hover:bg-orange-dark text-white text-xs font-bold px-5 py-2.5 rounded-xl transition-all shadow-md shadow-orange/10 text-center w-full md:w-auto">Abrir Chat com Cliente</a>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <?php if($isPrestador): ?>
        
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm space-y-3">
          <div class="flex items-center justify-between border-b border-gray-50 pb-2">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Sobre o Profissional</h3>
            <span class="text-xs text-gray-400 font-medium">⏳ Experiência: <b><?= intval($detalhesProfissionais['experiencia_anos']) ?> anos</b></span>
          </div>
          <p class="text-sm text-gray-600 leading-relaxed italic">
            "<?= nl2br(htmlspecialchars($detalhesProfissionais['bio'] ?? 'Nenhuma biografia detalhada inserida.')) ?>"
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
            <?php if(empty($servicos)): ?>
              <p class="text-xs text-gray-400 italic pl-1">Nenhum serviço anunciado no momento.</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="space-y-3">
          <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider pl-1">Avaliações dos Clientes</h3>
          <div class="bg-white rounded-2xl border border-gray-200 shadow-sm divide-y divide-gray-100">
            <?php foreach($avaliacoes as $a): ?>
              <div class="p-4 flex gap-3 items-start">
                <div class="w-8 h-8 rounded-full bg-gray-200 flex-shrink-0 overflow-hidden border">
                  <?php if($a['cliente_foto'] && $a['cliente_foto'] !== 'default.png'): ?>
                    <img src="<?= $urlBaseSupabase . $a['cliente_foto'] ?>" class="w-full h-full object-cover">
                  <?php else: ?>
                    <div class="w-full h-full bg-orange/10 text-orange font-bold text-xs flex items-center justify-center">
                      <?= strtoupper(substr($a['cliente_nome'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="flex-1 space-y-1">
                  <div class="flex items-center justify-between">
                    <h5 class="text-xs font-bold text-gray-900"><?= htmlspecialchars($a['cliente_nome']) ?></h5>
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
              <p class="text-xs text-gray-400 p-4 italic">Este profissional ainda não recebeu avaliações.</p>
            <?php endif; ?>
          </div>
        </div>

      <?php else: ?>
        <div class="bg-white rounded-2xl p-8 border border-dashed border-gray-200 flex flex-col items-center justify-center text-center">
          <div class="w-14 h-14 bg-orange/5 text-orange text-2xl rounded-2xl flex items-center justify-center mb-3 border border-orange/10">👤</div>
          <h3 class="font-bold text-base text-gray-900">Perfil de Cliente</h3>
          <p class="text-xs text-gray-400 mt-1 max-w-sm leading-relaxed">
            Este usuário está cadastrado na plataforma como contratante. Você pode utilizar o botão acima para retornar à sala de chat e dar andamento às negociações de serviços.
          </p>
        </div>
      <?php endif; ?>

    </div>
  </main>

</body>
</html>