<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
require_once '../../backend/models/User.php';

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$mensagem = '';
$erro = '';
$limiteFotos = 7;
$urlBaseSupabase = "https://yplpxzmwtkencrrtxmof.supabase.co/storage/v1/object/public/fotos/";

$idParaSelecionar = filter_input(INPUT_GET, 'selecionar', FILTER_VALIDATE_INT);

if ($usuarioId <= 0) {
    header('Location: /PI-2026.1/frontend/Pages/login.php');
    exit;
}

$userModel = new User($pdo);
$usuarioLogado = $userModel->buscarPorId($usuarioId);

$stmtS = $pdo->prepare("SELECT id, titulo, categoria_nome FROM servicos WHERE prestador_id = ? ORDER BY id DESC");
$stmtS->execute([$usuarioId]);
$servicos = $stmtS->fetchAll(PDO::FETCH_ASSOC);

$servicosMap = [];
foreach ($servicos as $s) {
    $servicosMap[$s['id']] = $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['excluir_projeto_titulo'])) {
        $tituloExc = $_POST['excluir_projeto_titulo'];
        $stmtBusca = $pdo->prepare("SELECT url_imagem FROM portfolio_imagens WHERE titulo_projeto = ? AND usuario_id = ?");
        $stmtBusca->execute([$tituloExc, $usuarioId]);
        $fotosParaApagar = $stmtBusca->fetchAll();

        foreach ($fotosParaApagar as $f) {
            apagarArquivoSupabase($f['url_imagem']); 
        }
        
        $stmtDel = $pdo->prepare("DELETE FROM portfolio_imagens WHERE titulo_projeto = ? AND usuario_id = ?");
        if ($stmtDel->execute([$tituloExc, $usuarioId])) {
            header('Location: portfolio.php?ok=3'); 
            exit;
        }
    }

    if (isset($_POST['excluir_foto_id'])) {
        $fotoId = (int)$_POST['excluir_foto_id'];
        $stmtBusca = $pdo->prepare("SELECT url_imagem FROM portfolio_imagens WHERE id = ? AND usuario_id = ?");
        $stmtBusca->execute([$fotoId, $usuarioId]);
        $foto = $stmtBusca->fetch();

        if ($foto) {
            apagarArquivoSupabase($foto['url_imagem']); 
            $stmtDel = $pdo->prepare("DELETE FROM portfolio_imagens WHERE id = ? AND usuario_id = ?");
            if ($stmtDel->execute([$fotoId, $usuarioId])) {
                header('Location: portfolio.php?ok=2'); 
                exit;
            }
        }
    }

    if (isset($_FILES['foto_trabalho'])) {
        $servicoId = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);
        $tituloProjeto = $servicosMap[$servicoId]['titulo'] ?? '';

        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM portfolio_imagens WHERE usuario_id = ? AND titulo_projeto = ?");
        $stmtC->execute([$usuarioId, $tituloProjeto]);
        $jaTem = (int)$stmtC->fetchColumn();

        $arquivos = $_FILES['foto_trabalho'];
        $qtdNovas = is_array($arquivos['name']) ? count(array_filter($arquivos['name'])) : 0;

        if (!$servicoId) {
            $erro = 'Selecione um serviço relacionado.';
        } elseif (($jaTem + $qtdNovas) > $limiteFotos) {
            $erro = "Limite atingido! Este serviço já tem $jaTem fotos. Máximo permitido: $limiteFotos.";
        } elseif ($qtdNovas > 0) {
            $sucessoUpload = false;

            for ($i = 0; $i < $qtdNovas; $i++) {
                $tmpNome = $arquivos['tmp_name'][$i];
                $nomeOri = $arquivos['name'][$i];

                if (is_uploaded_file($tmpNome)) {
                    $caminhoNoSupabase = fazerUploadPortfolioSupabase($tmpNome, $nomeOri);

                    if ($caminhoNoSupabase) {
                        $stmtIns = $pdo->prepare("INSERT INTO portfolio_imagens (usuario_id, titulo_projeto, url_imagem) VALUES (?, ?, ?)");
                        $stmtIns->execute([$usuarioId, $tituloProjeto, $caminhoNoSupabase]);
                        $sucessoUpload = true;
                    }
                }
            }
            if ($sucessoUpload) {
                // Redirecionamento limpa o POST e evita duplicidade por F5
                header('Location: portfolio.php?ok=1'); 
                exit;
            } else {
                $erro = "Erro ao enviar fotos para o armazenamento online.";
            }
        }
    }
}

$status = $_GET['ok'] ?? '';
if ($status === '1') $mensagem = 'Fotos adicionadas com sucesso!';
if ($status === '2') $mensagem = 'A foto foi removida com sucesso!';
if ($status === '3') $mensagem = 'O projeto e todas as suas fotos foram excluídos!';
if ($idParaSelecionar && !$status) {
    $mensagem = 'Serviço cadastrado com sucesso! Agora adicione fotos para o seu portfólio.';
}

$stmtP = $pdo->prepare("SELECT * FROM portfolio_imagens WHERE usuario_id = ? ORDER BY data_upload DESC");
$stmtP->execute([$usuarioId]);
$projetosAgrupados = [];
foreach ($stmtP->fetchAll(PDO::FETCH_ASSOC) as $item) {
    $projetosAgrupados[$item['titulo_projeto']][] = $item;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Portfólio | ReformAí</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { sans: ['Manrope', 'sans-serif'] }, colors: { orange: '#F97316', sidebar: '#16213E', bg: '#F8F9FA' } } }
    }
  </script>
</head>
<body class="bg-bg text-gray-800 flex h-screen overflow-hidden font-sans">

  <div id="modalConfirm" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl scale-95 transition-all">
      <div class="p-6 text-center">
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 id="modalTitle" class="text-xl font-black text-slate-900 mb-2">Excluir?</h3>
        <p id="modalDesc" class="text-sm text-gray-500">Essa ação removerá os registos e os arquivos permanentemente.</p>
      </div>
      <div class="bg-gray-50 p-4 flex gap-3">
        <button onclick="fecharModal()" class="flex-1 py-3 text-sm font-bold text-gray-500 hover:bg-gray-200 rounded-xl transition-colors">Cancelar</button>
        <form id="modalForm" method="POST" class="flex-1">
            <input type="hidden" name="" id="modalInput">
            <button type="submit" class="w-full py-3 text-sm font-bold bg-red-500 text-white hover:bg-red-600 rounded-xl shadow-lg shadow-red-500/20 transition-all">Confirmar</button>
        </form>
      </div>
    </div>
  </div>

  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'portfolio');
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
        <div class="flex items-center gap-2 text-gray-400">
          <button onclick="history.back()" class="hover:text-gray-600 p-1 -ml-1 rounded-lg hover:bg-gray-100"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg></button>
          <a href="./dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors">Início</a>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
          <span class="text-gray-800 font-bold text-lg tracking-tight">Portfólio</span>
        </div>
        
        <a href="perfil.php" class="hover:opacity-80 transition-opacity">
            <div class="w-10 h-10 rounded-full bg-orange flex items-center justify-center text-white font-bold text-sm overflow-hidden border-2 border-orange/20">
                <?php if(!empty($usuarioLogado['foto_perfil']) && $usuarioLogado['foto_perfil'] !== 'default.png'): ?>
                    <img src="<?= $urlBaseSupabase . $usuarioLogado['foto_perfil'] ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <?= strtoupper(mb_substr($usuarioLogado['nome'] ?? 'U', 0, 1)) ?>
                <?php endif; ?>
            </div>
        </a>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-6">
      <div class="mb-6">
        <h2 class="text-4xl font-extrabold text-slate-900 tracking-tight">Meus Trabalhos</h2>
        <p class="text-sm text-gray-500 mt-1">Gerencie seu histórico visual de serviços.</p>
      </div>

      <?php if ($mensagem): ?><div class="mb-4 bg-emerald-50 text-emerald-700 px-4 py-3 rounded-xl border border-emerald-100 text-sm font-bold"><?= $mensagem ?></div><?php endif; ?>
      <?php if ($erro): ?><div class="mb-4 bg-red-50 text-red-600 px-4 py-3 rounded-xl border border-red-100 text-sm font-bold"><?= $erro ?></div><?php endif; ?>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        <section class="xl:col-span-4 bg-white border border-gray-200 rounded-2xl p-6 shadow-sm h-fit">
          <h3 class="text-2xl font-extrabold text-slate-900 mb-6">Novo Trabalho</h3>
          
          <form method="POST" enctype="multipart/form-data" class="space-y-5" onsubmit="const btn=this.querySelector('button[type=submit]'); btn.disabled=true; btn.innerHTML='Enviando...';">
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-2 uppercase tracking-widest">Serviço Realizado</label>
              <select name="servico_id" required class="w-full border border-gray-200 rounded-xl px-4 py-3.5 text-sm focus:border-orange outline-none bg-white font-bold text-slate-700">
                  <option value="">Selecione o serviço...</option>
                  <?php foreach ($servicos as $s): ?>
                      <option value="<?= $s['id'] ?>" <?= ($idParaSelecionar == $s['id']) ? 'selected' : '' ?>>
                          <?= htmlspecialchars($s['titulo']) ?>
                      </option>
                  <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-2 uppercase tracking-widest">Fotos (Limite 7)</label>
              <label class="border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center block cursor-pointer hover:border-orange hover:bg-orange/5 transition-all">
                <input type="file" name="foto_trabalho[]" multiple accept="image/*" required class="hidden" onchange="document.getElementById('file-msg').innerText = this.files.length + ' fotos prontas'" />
                <p id="file-msg" class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Selecionar Imagens</p>
              </label>
            </div>

            <button type="submit" class="w-full bg-orange text-white py-4 rounded-xl font-bold text-sm shadow-lg shadow-orange/20 hover:bg-orange-600 transition-all uppercase">Vincular Fotos</button>
          </form>
        </section>

        <section class="xl:col-span-8 space-y-8">
          <?php foreach ($projetosAgrupados as $titulo => $fotos): ?>
            <?php 
              $tag = 'PROJETO';
              foreach($servicos as $s) { if($s['titulo'] === $titulo) { $tag = strtoupper($s['categoria_nome'] ?: 'PROJETO'); break; } }
            ?>
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm group">
              <div class="px-6 py-4 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                <div>
                  <p class="text-[10px] font-black text-orange uppercase tracking-widest mb-1"><?= $tag ?></p>
                  <h4 class="text-base font-black text-slate-800 uppercase leading-none"><?= htmlspecialchars($titulo) ?></h4>
                </div>
                <button onclick="confirmarExcluirTudo('<?= addslashes($titulo) ?>')" class="flex items-center gap-2 text-[10px] font-bold text-red-300 hover:text-red-500 transition-colors uppercase tracking-widest">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  Remover Tudo
                </button>
              </div>
              
              <div class="p-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <?php foreach ($fotos as $f): ?>
                  <div class="relative group aspect-square rounded-2xl overflow-hidden bg-gray-50 border border-gray-100">
                    <img src="<?= $urlBaseSupabase . htmlspecialchars($f['url_imagem']) ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Trabalho" />
                    
                    <button type="button" onclick="confirmarExcluirFoto(<?= $f['id'] ?>)" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 bg-white text-red-500 p-2 rounded-xl shadow-xl hover:bg-red-500 hover:text-white transition-all">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </div>
                <?php endforeach; ?>

                <?php if(count($fotos) < $limiteFotos): ?>
                   <div class="aspect-square rounded-2xl border-2 border-dashed border-gray-100 flex items-center justify-center text-gray-200">
                      <span class="text-[10px] font-bold"><?= count($fotos) ?>/7</span>
                   </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>

          <?php if (empty($projetosAgrupados)): ?>
            <div class="h-64 rounded-3xl border-2 border-dashed border-gray-200 flex flex-col items-center justify-center text-gray-400">
                <p class="font-bold uppercase tracking-widest text-xs">Seu portfólio está vazio</p>
            </div>
          <?php endif; ?>
        </section>
      </div>
    </div>
  </main>

  <script>
    const modal = document.getElementById('modalConfirm');
    const modalInput = document.getElementById('modalInput');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');

    function confirmarExcluirFoto(id) {
        modalTitle.innerText = "Remover esta foto?";
        modalDesc.innerText = "A imagem será apagada permanentemente do armazenamento.";
        modalInput.name = "excluir_foto_id";
        modalInput.value = id;
        abrirModal();
    }

    function confirmarExcluirTudo(titulo) {
        modalTitle.innerText = "Excluir projeto?";
        modalDesc.innerText = `Isto apagará permanentemente todas as fotos vinculadas ao serviço "${titulo}".`;
        modalInput.name = "excluir_projeto_titulo";
        modalInput.value = titulo;
        abrirModal();
    }

    function abrirModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function fecharModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    window.onclick = (e) => { if (e.target == modal) fecharModal(); }
  </script>
</body>
</html>