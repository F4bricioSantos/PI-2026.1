<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$mensagem = '';
$erro = '';

if ($usuarioId <= 0) {
    header('Location: /PI-2026.1/frontend/Pages/login.php');
    exit;
}

// 1. BUSCA A BIO (Opcional, se quiser mostrar no topo como antes)
$stmtBio = $pdo->prepare("SELECT bio FROM prestadores_detalhes WHERE usuario_id = :id");
$stmtBio->execute([':id' => $usuarioId]);
$dadosPrestador = $stmtBio->fetch(PDO::FETCH_ASSOC);
$bioAtual = $dadosPrestador['bio'] ?? 'Nenhuma bio cadastrada.';

// 2. LÓGICA DE POST (Ação Editar agora inclui descrição)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'excluir') {
        $idServico = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);
        if ($idServico) {
            $stmt = $pdo->prepare('DELETE FROM servicos WHERE id = :id AND prestador_id = :prestador_id');
            $stmt->execute([':id' => $idServico, ':prestador_id' => $usuarioId]);
            header('Location: gerenciar.php?ok=excluido'); exit;
        }
    }

    if ($acao === 'editar') {
        $idServico = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);
        $titulo    = trim($_POST['titulo'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $valor     = $_POST['valor'] ?? '';
        $descricao = trim($_POST['descricao'] ?? ''); // <--- NOVA DESCRIÇÃO

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
            header('Location: gerenciar.php?ok=editado'); exit;
        }
    }
}

$sucesso = $_GET['ok'] ?? '';
if ($sucesso === 'editado') $mensagem = 'Serviço atualizado com sucesso.';
if ($sucesso === 'excluido') $mensagem = 'Serviço excluído com sucesso.';

// 3. BUSCA SERVIÇOS (Incluso descricao_curta no SELECT)
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
  <title>ReformAí - Gerenciar</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Manrope'] }, colors: { orange: '#F97316', sidebar: '#16213E', bg: '#F8F9FA' } } } }</script>
</head>
<body class="font-sans bg-bg flex h-screen overflow-hidden">
  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'gerenciar');
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="history.back()" class="hover:text-gray-600 transition-colors p-1 -ml-1 rounded-lg hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <a href="./dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors">Início</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-gray-800 font-bold text-lg tracking-tight">Gerenciar</span>
      </div>
      <a href="perfil.php">
      <div class="w-9 h-9 rounded-full bg-orange/80 flex items-center justify-center text-white font-bold text-sm">
          <?= strtoupper(mb_substr($_SESSION['usuario_nome'] ?? 'U', 0, 1)) ?>
      </div></a>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-6">
      <h2 class="text-4xl font-extrabold text-slate-900 mb-6">Gerenciar Serviços</h2>

      <div class="mb-8 bg-white border-l-4 border-orange rounded-2xl p-6 shadow-sm">
        <h3 class="text-xs font-bold text-gray-400 uppercase mb-2">Sua Bio Profissional</h3>
        <p class="text-gray-700 italic">"<?= htmlspecialchars($bioAtual) ?>"</p>
      </div>

      <?php if ($mensagem): ?><div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl"><?= $mensagem ?></div><?php endif; ?>

      <section class="bg-white border rounded-2xl overflow-hidden">
        <div class="grid grid-cols-12 bg-gray-50 border-b font-bold text-gray-700">
          <div class="col-span-4 px-5 py-4">Título</div>
          <div class="col-span-2 px-5 py-4">Categoria</div>
          <div class="col-span-2 px-5 py-4">Preço</div>
          <div class="col-span-4 px-5 py-4 text-right">Ações</div>
        </div>

        <?php foreach ($servicos as $s): ?>
          <div class="grid grid-cols-12 border-b last:border-b-0 items-center">
            <div class="col-span-4 px-5 py-4 text-sm font-semibold"><?= htmlspecialchars($s['titulo']) ?></div>
            <div class="col-span-2 px-5 py-4"><span class="bg-gray-100 px-2 py-1 rounded-full text-xs"><?= htmlspecialchars($s['categoria_nome']) ?></span></div>
            <div class="col-span-2 px-5 py-4 text-sm">R$ <?= number_format((float)$s['valor_base'], 2, ',', '.') ?></div>
            <div class="col-span-4 px-5 py-4 flex justify-end gap-2">
              <button type="button" class="border border-orange text-orange px-4 py-1.5 rounded-lg text-xs font-bold"
                data-editar 
                data-id="<?= $s['id'] ?>"
                data-titulo="<?= htmlspecialchars($s['titulo']) ?>"
                data-categoria="<?= htmlspecialchars($s['categoria_nome']) ?>"
                data-valor="<?= $s['valor_base'] ?>"
                data-descricao="<?= htmlspecialchars($s['descricao_curta'] ?? '') ?>">Editar</button>
              
              <form method="POST" onsubmit="return confirm('Excluir?');">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="servico_id" value="<?= $s['id'] ?>">
                <button type="submit" class="border border-red-300 text-red-500 px-4 py-1.5 rounded-lg text-xs font-bold">Excluir</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </section>
    </div>
  </main>

  <div id="modal-editar" class="hidden fixed inset-0 z-50 bg-black/45 flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold">Editar Serviço</h3>
        <button id="fechar-modal" class="text-2xl">&times;</button>
      </div>

      <form method="POST" class="space-y-4">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" id="edit-id" name="servico_id">

        <div>
          <label class="block text-xs font-bold text-gray-500 mb-1">TÍTULO</label>
          <input type="text" id="edit-titulo" name="titulo" required class="w-full border rounded-xl px-4 py-2.5 text-sm">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-500 mb-1">CATEGORIA</label>
            <input type="text" id="edit-categoria" name="categoria" required class="w-full border rounded-xl px-4 py-2.5 text-sm">
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-500 mb-1">PREÇO (R$)</label>
            <input type="number" id="edit-valor" name="valor" step="0.01" class="w-full border rounded-xl px-4 py-2.5 text-sm">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-500 mb-1">DESCRIÇÃO DO SERVIÇO</label>
          <textarea id="edit-descricao" name="descricao" rows="4" class="w-full border rounded-xl px-4 py-2.5 text-sm resize-none" placeholder="Ex: Detalhes sobre o que você faz..."></textarea>
        </div>

        <div class="pt-4 flex justify-end gap-3">
          <button type="button" id="cancelar-modal" class="px-5 py-2 text-sm font-bold text-gray-400">Cancelar</button>
          <button type="submit" class="bg-orange text-white px-6 py-2 rounded-xl text-sm font-bold">Salvar Alterações</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const modal = document.getElementById('modal-editar');
    const inputId = document.getElementById('edit-id');
    const inputTit = document.getElementById('edit-titulo');
    const inputCat = document.getElementById('edit-categoria');
    const inputVal = document.getElementById('edit-valor');
    const inputDesc = document.getElementById('edit-descricao'); // <--- Pega o textarea

    document.querySelectorAll('[data-editar]').forEach(btn => {
      btn.addEventListener('click', () => {
        // Preenche a modal com os dados do botão
        inputId.value = btn.dataset.id;
        inputTit.value = btn.dataset.titulo;
        inputCat.value = btn.dataset.categoria;
        inputVal.value = btn.dataset.valor;
        inputDesc.value = btn.dataset.descricao; // <--- Passa a descrição para a modal
        modal.classList.remove('hidden');
      });
    });

    const fechar = () => modal.classList.add('hidden');
    document.getElementById('fechar-modal').onclick = fechar;
    document.getElementById('cancelar-modal').onclick = fechar;
  </script>
</body>
</html>