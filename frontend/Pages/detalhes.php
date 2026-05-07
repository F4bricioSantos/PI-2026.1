<?php
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';

// 1. Captura o ID do serviço e valida
$idServico = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idServico) {
    header('Location: dashboard.php');
    exit;
}

// 2. Busca os detalhes do serviço e do prestador (Incluindo Nicho e Experiência)
$sqlServico = "
    SELECT 
        s.*, 
        u.nome AS prestador_nome, 
        u.cidade, 
        u.email AS prestador_email, 
        u.telefone,
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
$servico = $stmt->fetch();

if (!$servico) {
    die("Serviço não encontrado.");
}

// 3. Busca avaliações detalhadas
$sqlAvaliacoes = "
    SELECT a.*, u.nome AS cliente_nome 
    FROM avaliacoes a
    JOIN usuarios u ON u.id = a.cliente_id
    WHERE a.prestador_id = :prestador_id
    ORDER BY a.data_avaliacao DESC
";
$stmtAval = $pdo->prepare($sqlAvaliacoes);
$stmtAval->execute([':prestador_id' => $servico['prestador_id']]);
$avaliacoes = $stmtAval->fetchAll();

// 4. Busca imagens do portfólio do prestador
$sqlPortfolio = "SELECT * FROM portfolio_imagens WHERE usuario_id = :prestador_id ORDER BY data_upload DESC";
$stmtPort = $pdo->prepare($sqlPortfolio);
$stmtPort->execute([':prestador_id' => $servico['prestador_id']]);
$portfolio = $stmtPort->fetchAll();

// Lógica de fotos de capa por categoria
$fotosCat = [
    'reforma'    => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&q=80&w=600',
    'elétrica'   => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&q=80&w=600',
    'eletrica'   => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&q=80&w=600',
    'pintura'    => 'https://images.unsplash.com/photo-1589939705384-5185137a7f0f?auto=format&fit=crop&q=80&w=600',
    'hidráulica' => 'https://images.unsplash.com/photo-1504148455328-436306343aa1?auto=format&fit=crop&q=80&w=600',
    'hidraulica' => 'https://images.unsplash.com/photo-1504148455328-436306343aa1?auto=format&fit=crop&q=80&w=600',
    'default'    => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&q=80&w=600',
];
$fotoCapa = $fotosCat[mb_strtolower(trim($servico['categoria_nome']))] ?? $fotosCat['default'];
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
            bg:       '#F8F9FA',
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

  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'detalhes');
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="history.back()" class="hover:text-gray-600 transition-colors p-1 -ml-1 rounded-lg hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <span class="text-gray-800 font-bold text-lg tracking-tight ml-2">Detalhes do Serviço</span>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-8 custom-scroll">
      <div class="flex gap-8 max-w-6xl mx-auto">
        
        <div class="flex-1 space-y-8">
          <div class="w-full h-80 rounded-3xl overflow-hidden relative shadow-md">
             <img src="<?= $fotoCapa ?>" class="w-full h-full object-cover" alt="Capa">
          </div>
          
          <div class="space-y-2">
            <div class="flex justify-between items-start">
              <h1 class="text-2xl font-extrabold text-gray-900"><?= htmlspecialchars($servico['titulo']) ?></h1>
              <p class="text-2xl font-extrabold text-orange">
                <?= $servico['valor_base'] ? 'R$ ' . number_format($servico['valor_base'], 2, ',', '.') : 'A combinar' ?>
              </p>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex items-center gap-1.5 bg-orange/10 px-3 py-1.5 rounded-lg">
                <svg class="w-4 h-4 fill-orange" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <span class="text-sm font-bold text-orange"><?= $servico['media_nota'] ?: '0.0' ?> (<?= $servico['total_avaliacoes'] ?>)</span>
              </div>
              <span class="text-gray-400 text-sm"><?= htmlspecialchars($servico['cidade'] ?: 'Cidade não informada') ?></span>
              <span class="text-gray-300">|</span>
              <span class="text-sm font-medium text-gray-500"><?= htmlspecialchars($servico['categoria_nome']) ?></span>
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
                        <strong class="text-gray-800 block mb-2">Sobre o prestador:</strong>
                        <?php if(!empty($servico['bio'])): ?><p class="italic mb-4"><?= nl2br(htmlspecialchars($servico['bio'])) ?></p><?php endif; ?>
                        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl">
                            <?php if(!empty($servico['nicho'])): ?>
                                <div><span class="block text-[10px] font-bold text-gray-400 uppercase">Nicho</span><span class="text-xs font-bold text-gray-700"><?= htmlspecialchars($servico['nicho']) ?></span></div>
                            <?php endif; ?>
                            <?php if(!empty($servico['experiencia_anos'])): ?>
                                <div><span class="block text-[10px] font-bold text-gray-400 uppercase">Experiência</span><span class="text-xs font-bold text-gray-700"><?= $servico['experiencia_anos'] ?> anos</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-2 mb-6">
              <div class="w-1 h-5 bg-orange rounded-full"></div>
              <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Galeria de Projetos</h2>
            </div>
            <?php if(empty($portfolio)): ?>
              <p class="text-gray-400 text-xs italic">Nenhum projeto cadastrado no portfólio.</p>
            <?php else: ?>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach($portfolio as $projeto): ?>
                  <div class="group relative aspect-square bg-gray-100 rounded-xl overflow-hidden border border-gray-100 shadow-sm">
                    <img src="<?= htmlspecialchars($projeto['url_imagem']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" alt="<?= htmlspecialchars($projeto['titulo_projeto']) ?>">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-3">
                      <p class="text-white text-[10px] font-bold uppercase tracking-wider"><?= htmlspecialchars($projeto['titulo_projeto']) ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center gap-2 mb-6">
              <div class="w-1 h-5 bg-orange rounded-full"></div>
              <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Avaliações</h2>
            </div>
            <div class="space-y-4">
              <?php if(empty($avaliacoes)): ?>
                <p class="text-gray-400 text-xs italic">Ainda não há avaliações para este prestador.</p>
              <?php else: ?>
                <?php foreach($avaliacoes as $aval): ?>
                  <div class="p-5 rounded-2xl bg-gray-50/70 border border-gray-100">
                    <div class="flex justify-between items-center mb-3">
                      <span class="text-sm font-bold text-gray-900"><?= htmlspecialchars($aval['cliente_nome']) ?></span>
                      <div class="flex items-center gap-0.5">
                        <?php for($i=1; $i<=5; $i++): ?>
                          <svg class="w-3.5 h-3.5 <?= $i <= $aval['nota'] ? 'fill-orange' : 'fill-gray-300' ?>" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <?php endfor; ?>
                      </div>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed"><?= htmlspecialchars($aval['comentario']) ?></p>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="w-80">
          <div class="sticky top-0 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-6">
            <div class="flex items-center gap-4">
              <div class="relative">
                <div class="w-14 h-14 rounded-2xl bg-orange/10 flex items-center justify-center text-orange font-bold text-xl">
                    <?= strtoupper(mb_substr($servico['prestador_nome'], 0, 2)) ?>
                </div>
                <div class="verified-dot absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-emerald-400 rounded-full border-2 border-white"></div>
              </div>
              <div>
                <p class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($servico['prestador_nome']) ?></p>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($servico['cidade']) ?></p>
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
              <a href="https://wa.me/<?= preg_replace('/\D/', '', $servico['telefone']) ?>" target="_blank" class="w-full bg-orange hover:bg-orange-600 text-white font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                ENTRAR EM CONTATO
              </a>
              <button class="w-full border-2 border-gray-100 hover:border-orange hover:text-orange text-gray-700 font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                AVALIAR PRESTADOR
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>