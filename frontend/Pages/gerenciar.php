<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';
require_once '../../backend/models/User.php';

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$mensagem = '';
$erro = '';
$urlBaseSupabase = "https://yplpxzmwtkencrrtxmof.supabase.co/storage/v1/object/public/fotos/";

if ($usuarioId <= 0) {
    header('Location: /PI-2026.1/frontend/Pages/login.php');
    exit;
}

// 1. BUSCA DADOS DO PRESTADOR PARA O HEADER
$stmtBio = $pdo->prepare("SELECT bio FROM prestadores_detalhes WHERE usuario_id = :id");
$stmtBio->execute([':id' => $usuarioId]);
$dadosPrestador = $stmtBio->fetch(PDO::FETCH_ASSOC);
$bioAtual = $dadosPrestador['bio'] ?? 'Nenhuma bio cadastrada.';

$userModel = new User($pdo);
$usuarioLogado = $userModel->buscarPorId($usuarioId);

// 2. LÓGICA DE PROCESSAMENTO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // --- LÓGICA DE EXCLUSÃO COM SUPABASE ---
    if ($acao === 'excluir') {
        $idServico = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);
        
        if ($idServico) {
            // A. Busca o título do serviço para identificar as fotos relacionadas
            $stmtRef = $pdo->prepare("SELECT titulo FROM servicos WHERE id = :id AND prestador_id = :prestador_id");
            $stmtRef->execute([':id' => $idServico, ':prestador_id' => $usuarioId]);
            $servicoParaExcluir = $stmtRef->fetch(PDO::FETCH_ASSOC);

            if ($servicoParaExcluir) {
                $tituloProjeto = $servicoParaExcluir['titulo'];

                // B. Busca as URLs das imagens no portfólio para apagar do Supabase
                $stmtFotos = $pdo->prepare("SELECT url_imagem FROM portfolio_imagens WHERE titulo_projeto = :titulo AND usuario_id = :u_id");
                $stmtFotos->execute([':titulo' => $tituloProjeto, ':u_id' => $usuarioId]);
                $fotos = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);

                foreach ($fotos as $f) {
                    // Chama a função global do seu Conexao.php para deletar o arquivo físico
                    apagarArquivoSupabase($f['url_imagem']);
                }

                // C. Deleta as referências das fotos no banco de dados
                $pdo->prepare("DELETE FROM portfolio_imagens WHERE titulo_projeto = :titulo AND usuario_id = :u_id")
                    ->execute([':titulo' => $tituloProjeto, ':u_id' => $usuarioId]);

                // D. Por fim, deleta o serviço (e avaliações se houver CASCADE)
                $stmt = $pdo->prepare('DELETE FROM servicos WHERE id = :id AND prestador_id = :prestador_id');
                $stmt->execute([':id' => $idServico, ':prestador_id' => $usuarioId]);

                header('Location: gerenciar.php?ok=excluido'); 
                exit;
            }
        }
    }

    // --- LÓGICA DE EDIÇÃO ---
    if ($acao === 'editar') {
        $idServico = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);
        $titulo    = trim($_POST['titulo'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $valor     = $_POST['valor'] ?? '';
        $descricao = trim($_POST['descricao'] ?? '');

        if ($idServico && $titulo !== '' && $categoria !== '') {
            $stmt = $pdo->prepare(
                'UPDATE servicos 
                 SET titulo = :titulo, categoria_nome = :categoria, valor_base = :valor, descricao_curta = :descricao
                 WHERE id = :id AND prestador_id = :prestador_id'
            );
            $stmt->execute([
                ':titulo'    => $titulo,
                ':categoria' => $categoria,
                ':valor'     => $valor !== '' ? (float)$valor : null,
                ':descricao' => $descricao ?: null,
                ':id'        => $idServico,
                ':prestador_id' => $usuarioId,
            ]);
            header('Location: gerenciar.php?ok=editado'); 
            exit;
        }
    }
}

// Mensagens de Feedback
$sucesso = $_GET['ok'] ?? '';
if ($sucesso === 'editado') $mensagem = 'Serviço atualizado com sucesso.';
if ($sucesso === 'excluido') $mensagem = 'Serviço e fotos removidos do sistema.';

// 3. BUSCA LISTA DE SERVIÇOS ATUALIZADA
$stmtServicos = $pdo->prepare(
    'SELECT s.id, s.titulo, s.categoria_nome, s.valor_base, s.descricao_curta,
            COALESCE(ROUND(AVG(a.nota)::NUMERIC, 1), 0) AS media_nota
     FROM servicos s
     LEFT JOIN avaliacoes a ON a.prestador_id = s.prestador_id
     WHERE s.prestador_id = :id
     GROUP BY s.id
     ORDER BY s.id DESC'
);
$stmtServicos->execute([':id' => $usuarioId]);
$servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>ReformAí - Gerenciar Serviços</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Manrope'] }, colors: { orange: '#F97316', sidebar: '#16213E', bg: '#F8F9FA' } } } }</script>
</head>
<body class="font-sans bg-bg flex h-screen overflow-hidden">

  <div id="modal-excluir" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl scale-95 transition-all">
      <div class="p-6 text-center">
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="text-xl font-black text-slate-900 mb-2">Excluir serviço?</h3>
        <p class="text-sm text-gray-500">Isso removerá o serviço e todas as fotos do portfólio vinculadas a ele permanentemente.</p>
      </div>
      <div class="bg-gray-50 p-4 flex gap-3">
        <button onclick="fecharModalExcluir()" class="flex-1 py-3 text-sm font-bold text-gray-500 hover:bg-gray-200 rounded-xl transition-colors">Cancelar</button>
        <form method="POST" class="flex-1">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="servico_id" id="excluir-id">
            <button type="submit" class="w-full py-3 text-sm font-bold bg-red-500 text-white hover:bg-red-600 rounded-xl shadow-lg transition-all">Confirmar</button>
        </form>
      </div>
    </div>
  </div>

  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'gerenciar');
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
        <div class="flex items-center gap-2 text-gray-400">
          <button onclick="history.back()" class="hover:text-gray-600 p-1 -ml-1 rounded-lg hover:bg-gray-100"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg></button>
          <a href="./dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors">Início</a>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
          <span class="text-gray-800 font-bold text-lg tracking-tight">Gerenciar</span>
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
      <h2 class="text-4xl font-extrabold text-slate-900 mb-6 tracking-tight">Seus Serviços</h2>

      <div class="mb-8 bg-white border-l-4 border-orange rounded-2xl p-6 shadow-sm">
        <h3 class="text-xs font-bold text-gray-400 uppercase mb-2">Bio Atual</h3>
        <p class="text-gray-700 italic text-sm">"<?= htmlspecialchars($bioAtual) ?>"</p>
      </div>

      <?php if ($mensagem): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl font-bold text-sm flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <?= $mensagem ?>
        </div>
      <?php endif; ?>

      <section class="bg-white border rounded-2xl overflow-hidden shadow-sm">
        <div class="grid grid-cols-12 bg-gray-50 border-b font-bold text-gray-700 uppercase text-[10px] tracking-widest">
          <div class="col-span-4 px-5 py-4">Serviço</div>
          <div class="col-span-2 px-5 py-4 text-center">Categoria</div>
          <div class="col-span-2 px-5 py-4 text-center">Base</div>
          <div class="col-span-4 px-5 py-4 text-right">Opções</div>
        </div>

        <?php if(empty($servicos)): ?>
            <div class="p-10 text-center text-gray-400 text-sm italic">Você ainda não cadastrou serviços.</div>
        <?php endif; ?>

        <?php foreach ($servicos as $s): ?>
          <div class="grid grid-cols-12 border-b last:border-b-0 items-center hover:bg-gray-50/50 transition-colors">
            <div class="col-span-4 px-5 py-4 text-sm font-bold text-slate-800 uppercase"><?= htmlspecialchars($s['titulo']) ?></div>
            <div class="col-span-2 px-5 py-4 text-center"><span class="bg-gray-100 px-3 py-1 rounded-full text-[10px] font-black text-gray-500 uppercase"><?= htmlspecialchars($s['categoria_nome']) ?></span></div>
            <div class="col-span-2 px-5 py-4 text-center text-sm font-bold text-slate-600">R$ <?= number_format((float)$s['valor_base'], 2, ',', '.') ?></div>
            <div class="col-span-4 px-5 py-4 flex justify-end gap-2">
              <button type="button" class="bg-white border border-gray-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold hover:border-orange hover:text-orange transition-all"
                data-editar 
                data-id="<?= $s['id'] ?>"
                data-titulo="<?= htmlspecialchars($s['titulo']) ?>"
                data-categoria="<?= htmlspecialchars($s['categoria_nome']) ?>"
                data-valor="<?= $s['valor_base'] ?>"
                data-descricao="<?= htmlspecialchars($s['descricao_curta'] ?? '') ?>">Editar</button>
              
              <button type="button" onclick="abrirModalExcluir(<?= $s['id'] ?>)" class="bg-white border border-gray-200 text-red-400 px-4 py-2 rounded-xl text-xs font-bold hover:bg-red-50 hover:border-red-200 hover:text-red-500 transition-all">Excluir</button>
            </div>
          </div>
        <?php endforeach; ?>
      </section>
    </div>
  </main>

  <div id="modal-editar" class="hidden fixed inset-0 z-50 bg-black/45 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg bg-white rounded-3xl shadow-2xl p-8">
       <div class="flex justify-between items-center mb-6">
         <h3 class="text-2xl font-black text-slate-900 uppercase tracking-tighter">Editar Serviço</h3>
         <button onclick="fecharModalEditar()" class="text-gray-400 hover:text-slate-900 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
       </div>
       <form method="POST" class="space-y-4">
         <input type="hidden" name="acao" value="editar"><input type="hidden" id="edit-id" name="servico_id">
         <div><label class="block text-[10px] font-black text-gray-400 mb-1 uppercase tracking-widest">Título do Serviço</label><input type="text" id="edit-titulo" name="titulo" required class="w-full border-gray-200 border rounded-xl px-4 py-3 text-sm font-bold outline-none focus:border-orange"></div>
         <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-[10px] font-black text-gray-400 mb-1 uppercase tracking-widest">Categoria</label><input type="text" id="edit-categoria" name="categoria" required class="w-full border-gray-200 border rounded-xl px-4 py-3 text-sm font-bold outline-none focus:border-orange"></div>
            <div><label class="block text-[10px] font-black text-gray-400 mb-1 uppercase tracking-widest">Valor Base (R$)</label><input type="number" id="edit-valor" name="valor" step="0.01" class="w-full border-gray-200 border rounded-xl px-4 py-3 text-sm font-bold outline-none focus:border-orange"></div>
         </div>
         <div><label class="block text-[10px] font-black text-gray-400 mb-1 uppercase tracking-widest">Descrição do Serviço</label><textarea id="edit-descricao" name="descricao" rows="3" class="w-full border-gray-200 border rounded-xl px-4 py-3 text-sm font-bold outline-none focus:border-orange resize-none"></textarea></div>
         <div class="pt-4 flex gap-3"><button type="button" onclick="fecharModalEditar()" class="flex-1 py-3.5 text-sm font-bold text-gray-400 uppercase tracking-widest">Cancelar</button><button type="submit" class="flex-1 bg-orange text-white py-3.5 rounded-2xl text-sm font-black shadow-lg shadow-orange/20 uppercase tracking-widest">Salvar Alterações</button></div>
       </form>
    </div>
  </div>

  <script>
    // Lógica Modal Editar
    const modalEditar = document.getElementById('modal-editar');
    const inputId = document.getElementById('edit-id');
    const inputTit = document.getElementById('edit-titulo');
    const inputCat = document.getElementById('edit-categoria');
    const inputVal = document.getElementById('edit-valor');
    const inputDesc = document.getElementById('edit-descricao');

    document.querySelectorAll('[data-editar]').forEach(btn => {
      btn.addEventListener('click', () => {
        inputId.value = btn.dataset.id;
        inputTit.value = btn.dataset.titulo;
        inputCat.value = btn.dataset.categoria;
        inputVal.value = btn.dataset.valor;
        inputDesc.value = btn.dataset.descricao;
        modalEditar.classList.remove('hidden');
        modalEditar.classList.add('flex');
      });
    });

    const fecharModalEditar = () => modalEditar.classList.add('hidden');

    // Lógica Modal Excluir
    const modalExcluir = document.getElementById('modal-excluir');
    const inputExcluirId = document.getElementById('excluir-id');

    function abrirModalExcluir(id) {
        inputExcluirId.value = id;
        modalExcluir.classList.remove('hidden');
        modalExcluir.classList.add('flex');
    }

    function fecharModalExcluir() {
        modalExcluir.classList.add('hidden');
        modalExcluir.classList.remove('flex');
    }

    // Fechar ao clicar fora
    window.onclick = (e) => {
        if (e.target == modalEditar) fecharModalEditar();
        if (e.target == modalExcluir) fecharModalExcluir();
    }
  </script>
</body>
</html>