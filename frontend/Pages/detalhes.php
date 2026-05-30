<?php
header('Content-Type: text/html; charset=UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';

$idServico = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$idUsuarioLogado = $_SESSION['usuario_id'] ?? 0;

if (!$idServico) {
    header('Location: dashboard.php');
    exit;
}

if (!defined('SB_URL')) define('SB_URL', 'https://yplpxzmwtkencrrtxmof.supabase.co');
$urlBaseSupabase = SB_URL . "/storage/v1/object/public/fotos/";

try {
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = :id");
    $stmtCheck->execute([':id' => $idUsuarioLogado]);
    $temServico = $stmtCheck->fetchColumn() > 0;

    $sqlServico = "
        SELECT 
            s.*, 
            u.nome AS prestador_nome, 
            u.cidade, 
            u.email AS prestador_email, 
            u.telefone,
            u.foto_perfil AS prestador_foto,
            pd.bio,
            pd.nicho,
            pd.experiencia_anos,
            COALESCE(ROUND(AVG(a.nota)::NUMERIC, 1), 0) AS media_nota,
            COUNT(a.id)::INT AS total_avaliacoes
        FROM servicos s
        JOIN usuarios u ON u.id = s.prestador_id
        LEFT JOIN prestadores_detalhes pd ON pd.usuario_id = u.id
        LEFT JOIN avaliacoes a ON a.prestador_id = u.id
        WHERE s.id = :id
        GROUP BY s.id, u.id, pd.id
    ";

    $stmt = $pdo->prepare($sqlServico);
    $stmt->execute([':id' => $idServico]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$servico) {
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                <h2>Serviço não encontrado.</h2>
                <a href='dashboard.php'>Voltar ao início</a>
             </div>");
    }

    $sqlAvaliacoes = "
        SELECT a.*, u.nome AS cliente_nome, u.foto_perfil AS cliente_foto 
        FROM avaliacoes a
        JOIN usuarios u ON u.id = a.cliente_id
        WHERE a.prestador_id = :prestador_id
        ORDER BY a.data_avaliacao DESC
    ";
    $stmtAval = $pdo->prepare($sqlAvaliacoes);
    $stmtAval->execute([':prestador_id' => $servico['prestador_id']]);
    $avaliacoes = $stmtAval->fetchAll(PDO::FETCH_ASSOC);

    $sqlPortfolio = "SELECT * FROM portfolio_imagens WHERE usuario_id = :prestador_id AND titulo_projeto = :titulo ORDER BY data_upload DESC";
    $stmtPort = $pdo->prepare($sqlPortfolio);
    $stmtPort->execute([
        ':prestador_id' => $servico['prestador_id'],
        ':titulo'       => $servico['titulo']
    ]);
    $portfolio = $stmtPort->fetchAll(PDO::FETCH_ASSOC);

    // Verifica se este serviço está favoritado pelo usuário logado
    $stmtFavCheck = $pdo->prepare("SELECT COUNT(*) FROM favoritos_servicos WHERE usuario_id = :uid AND servico_id = :sid");
    $stmtFavCheck->execute([':uid' => $idUsuarioLogado, ':sid' => $idServico]);
    $estaFavoritado = $stmtFavCheck->fetchColumn() > 0;

} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – <?= htmlspecialchars($servico['titulo']) ?></title>
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
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }
    @keyframes subtle-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
    .verified-dot { animation: subtle-pulse 2s ease-in-out infinite; }
  </style>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <div id="sidebar-container" class="fixed inset-y-0 left-0 z-50 w-60 bg-sidebar flex flex-col h-screen transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out"></div>

  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    
    const hasServices = <?= $temServico ? 'true' : 'false' ?>;
    
    renderSidebar('sidebar-container', 'inicio', hasServices);
  </script>

  <main class="flex-1 flex flex-col overflow-hidden w-full relative">
    <header class="flex items-center justify-between px-4 md:px-8 py-4 md:py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="window.toggleSidebar && window.toggleSidebar()" class="md:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <button onclick="history.back()" class="hover:text-gray-600 transition-colors p-1 md:-ml-1 rounded-lg hover:bg-gray-100 hidden md:block">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <a href="dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors md:ml-2">Início</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-gray-800 font-bold text-lg tracking-tight">Detalhes</span>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-6 md:py-8 custom-scroll">
      <div class="flex flex-col lg:flex-row gap-8 max-w-6xl mx-auto">
        
        <div class="flex-1 space-y-8">
          
          <div class="space-y-2">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 sm:gap-4">
              <h1 class="text-2xl font-extrabold text-gray-900 leading-tight"><?= htmlspecialchars($servico['titulo']) ?></h1>
              <p class="text-2xl font-extrabold text-orange whitespace-nowrap">
                <?= $servico['valor_base'] > 0 ? 'R$ ' . number_format($servico['valor_base'], 2, ',', '.') : 'A combinar' ?>
              </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
              <div class="flex items-center gap-1.5 bg-orange/10 px-3 py-1.5 rounded-lg">
                <svg class="w-4 h-4 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <span class="text-sm font-bold text-orange"><?= $servico['media_nota'] ?: '0.0' ?> (<?= $servico['total_avaliacoes'] ?>)</span>
              </div>
              <span class="text-gray-400 text-sm"><?= htmlspecialchars($servico['cidade'] ?: 'Cidade não informada') ?></span>
              <span class="text-gray-300">|</span>
              <span class="text-sm font-medium text-gray-500"><?= htmlspecialchars($servico['categoria_nome'] ?? 'Geral') ?></span>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
              <div class="w-1 h-5 bg-orange rounded-full"></div>
              <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Sobre o serviço</h2>
            </div>
            <div class="text-gray-600 text-sm leading-relaxed">
                <?= nl2br(htmlspecialchars($servico['descricao_curta'])) ?>
                
                <?php if(!empty($servico['bio']) || !empty($servico['nicho']) || !empty($servico['experiencia_anos'])): ?>
                    <div class="mt-6 pt-6 border-t border-gray-50">
                        <strong class="text-gray-800 block mb-2 font-bold uppercase text-[11px] tracking-wider">Perfil do Prestador</strong>
                        <?php if(!empty($servico['bio'])): ?><p class="italic mb-4 text-gray-500">"<?= nl2br(htmlspecialchars($servico['bio'])) ?>"</p><?php endif; ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl">
                            <?php if(!empty($servico['nicho'])): ?>
                                <div><span class="block text-[10px] font-bold text-gray-400 uppercase">Especialidade</span><span class="text-xs font-bold text-gray-700"><?= htmlspecialchars($servico['nicho']) ?></span></div>
                            <?php endif; ?>
                            <?php if(!empty($servico['experiencia_anos'])): ?>
                                <div><span class="block text-[10px] font-bold text-gray-400 uppercase">Experiência</span><span class="text-xs font-bold text-gray-700"><?= (int)$servico['experiencia_anos'] ?> anos</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-2 mb-6">
              <div class="w-1 h-5 bg-orange rounded-full"></div>
              <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Fotos de Trabalhos Reais</h2>
            </div>
            <?php if(empty($portfolio)): ?>
              <div class="py-10 text-center border-2 border-dashed border-gray-100 rounded-2xl">
                <p class="text-gray-400 text-xs italic">Nenhuma foto cadastrada para este serviço.</p>
              </div>
            <?php else: ?>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach($portfolio as $projeto): ?>
                  <div class="group relative aspect-square bg-gray-100 rounded-xl overflow-hidden border border-gray-100 shadow-sm">
                    <img src="<?= $urlBaseSupabase . htmlspecialchars($projeto['url_imagem']) ?>" 
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                         alt="Foto do Trabalho"
                         onerror="this.src='https://via.placeholder.com/400?text=Imagem+Indisponível'">
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-2 mb-6">
              <div class="w-1 h-5 bg-orange rounded-full"></div>
              <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Avaliações dos Clientes</h2>
            </div>
            <div class="space-y-4">
              <?php if(empty($avaliacoes)): ?>
                <p class="text-gray-400 text-xs italic">Ainda não há avaliações para este prestador.</p>
              <?php else: ?>
                <?php foreach($avaliacoes as $aval): ?>
                  <div class="p-5 rounded-2xl bg-gray-50/70 border border-gray-100 flex items-start gap-4">
                    
                    <div class="w-10 h-10 rounded-xl bg-orange/10 flex-shrink-0 flex items-center justify-center text-orange font-bold text-xs overflow-hidden border border-gray-100 shadow-sm">
                      <?php if(!empty($aval['cliente_foto']) && $aval['cliente_foto'] !== 'default.png'): ?>
                        <img src="<?= $urlBaseSupabase . htmlspecialchars($aval['cliente_foto']) ?>" class="w-full h-full object-cover">
                      <?php else: ?>
                        <?= strtoupper(mb_substr($aval['cliente_nome'] ?? 'U', 0, 2)) ?>
                      <?php endif; ?>
                    </div>

                    <div class="flex-1 min-w-0">
                      <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($aval['cliente_nome']) ?></span>
                        <div class="flex items-center gap-0.5 flex-shrink-0">
                          <?php for($i=1; $i<=5; $i++): ?>
                            <svg class="w-3.5 h-3.5 <?= $i <= $aval['nota'] ? 'fill-orange' : 'fill-gray-300' ?>" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                          <?php endfor; ?>
                        </div>
                      </div>
                      <p class="text-xs text-gray-500 leading-relaxed break-words"><?= nl2br(htmlspecialchars($aval['comentario'])) ?></p>
                    </div>

                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="w-full lg:w-80">
          <div class="sticky top-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-6">
            <div class="flex items-center gap-4">
              
              <a href="ver-perfil.php?id=<?= $servico['prestador_id'] ?>" 
                 class="relative block group cursor-pointer" 
                 title="Ver perfil completo de <?= htmlspecialchars($servico['prestador_nome']) ?>">
                <div class="w-14 h-14 rounded-2xl bg-orange/10 flex items-center justify-center text-orange font-bold text-xl overflow-hidden border border-gray-100 group-hover:border-orange/40 transition-all shadow-sm">
                  <?php if($servico['prestador_foto'] && $servico['prestador_foto'] !== 'default.png'): ?>
                    <img src="<?= $urlBaseSupabase . $servico['prestador_foto'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                  <?php else: ?>
                    <?= strtoupper(mb_substr($servico['prestador_nome'] ?? 'U', 0, 2)) ?>
                  <?php endif; ?>
                </div>
                <div class="verified-dot absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-emerald-400 rounded-full border-2 border-white"></div>
              </a>

              <div>
                <a href="ver-perfil.php?id=<?= $servico['prestador_id'] ?>" 
                   class="font-bold text-gray-900 text-sm hover:text-orange transition-colors block">
                  <?= htmlspecialchars($servico['prestador_nome']) ?>
                </a>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($servico['cidade'] ?: 'Brasil') ?></p>
              </div>

            </div>

            <div class="space-y-2">
              <div class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100 text-gray-700">
                <svg class="w-4 h-4 text-orange" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span class="text-xs font-medium"><?= htmlspecialchars($servico['telefone'] ?: 'Não informado') ?></span>
              </div>
              <div class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100 text-gray-700">
                <svg class="w-4 h-4 text-orange" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span class="text-xs font-medium truncate"><?= htmlspecialchars($servico['prestador_email']) ?></span>
              </div>
            </div>

            <div class="space-y-2.5">
              <a href="chat.php?com=<?= (int)$servico['prestador_id'] ?>" class="w-full bg-orange hover:bg-orange-600 text-white font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                ENTRAR EM CONTATO
              </a>
              <button onclick="toggleFavorito(<?= $servico['id'] ?>)" id="btn-favorito" class="w-full border font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all shadow-sm <?= $estaFavoritado ? 'bg-orange/10 border-orange/20 text-orange' : 'bg-white border-gray-200 hover:border-orange/20 text-gray-700 hover:bg-orange/5 hover:text-orange' ?>">
                <svg id="svg-favorito" class="w-4 h-4 <?= $estaFavoritado ? 'fill-orange text-orange' : 'fill-none text-current' ?>" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                </svg>
                <span id="txt-favorito"><?= $estaFavoritado ? 'SALVO' : 'SALVAR NOS FAVORITOS' ?></span>
              </button>
            </div>
            
          </div>
        </div>
      </div>
    </div>
  </main>
  <script>
    async function toggleFavorito(servicoId) {
      const btn = document.getElementById('btn-favorito');
      const svg = document.getElementById('svg-favorito');
      const txt = document.getElementById('txt-favorito');
      
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
          if (result.favoritado) {
            btn.className = "w-full border font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all shadow-sm bg-orange/10 border-orange/20 text-orange";
            svg.setAttribute('class', 'w-4 h-4 fill-orange text-orange');
            txt.innerText = "SALVO";
          } else {
            btn.className = "w-full border font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all shadow-sm bg-white border-gray-200 hover:border-orange/20 text-gray-700 hover:bg-orange/5 hover:text-orange";
            svg.setAttribute('class', 'w-4 h-4 fill-none text-current');
            txt.innerText = "SALVAR NOS FAVORITOS";
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