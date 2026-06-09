<?php
require_once '../../backend/config/Conexao.php';
require_once '../../backend/config/session_setup.php';
setup_db_session($pdo);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../../backend/config/auth.php';
require_once '../../backend/models/User.php';

$idUsuario = (int)$_SESSION['usuario_id'];
$mensagem = '';
$erro = '';
if (!defined('SB_URL')) define('SB_URL', getenv('SB_URL') ?: 'https://yplpxzmwtkencrrtxmof.supabase.co');
$urlBaseSupabase = SB_URL . "/storage/v1/object/public/fotos/";
 
try {
    $stmtBio = $pdo->prepare("SELECT bio FROM prestadores_detalhes WHERE usuario_id = :id");
    $stmtBio->execute([':id' => $idUsuario]);
    $dadosPrestador = $stmtBio->fetch(PDO::FETCH_ASSOC);
    $bioAtual = $dadosPrestador['bio'] ?? 'Nenhuma bio cadastrada.';

    $userModel = new User($pdo);
    $usuarioLogado = $userModel->buscarPorId($idUsuario);
} catch (Exception $e) {
    $erro = "Erro ao carregar dados: " . $e->getMessage();
}

// 3. LÓGICA DE PROCESSAMENTO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // VALIDAÇÃO CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Requisição inválida (CSRF).");
    }

    // --- LÓGICA DE EXCLUSÃO ---
    if ($acao === 'excluir') {
        $idServico = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);
        
        if ($idServico) {
            $stmtRef = $pdo->prepare("SELECT titulo FROM servicos WHERE id = :id AND prestador_id = :prestador_id");
            $stmtRef->execute([':id' => $idServico, ':prestador_id' => $idUsuario]);
            $servicoParaExcluir = $stmtRef->fetch(PDO::FETCH_ASSOC);

            if ($servicoParaExcluir) {
                // Impede exclusão Apenas se houver contratos 'pendentes' ou 'aceitos' (em andamento)
                $stmtContratos = $pdo->prepare("SELECT COUNT(*) FROM contratos WHERE servico_id = :id AND status IN ('pendente', 'aceito')");
                $stmtContratos->execute([':id' => $idServico]);
                if ((int)$stmtContratos->fetchColumn() > 0) {
                    echo "<script>window.location.href='gerenciar.php?erro=contrato_ativo';</script>";
                    exit;
                }

                $tituloProjeto = $servicoParaExcluir['titulo'];

                // Deleta fotos do Supabase (se a função existir)
                $stmtFotos = $pdo->prepare("SELECT url_imagem FROM portfolio_imagens WHERE titulo_projeto = :titulo AND usuario_id = :u_id");
                $stmtFotos->execute([':titulo' => $tituloProjeto, ':u_id' => $idUsuario]);
                $fotos = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);

                foreach ($fotos as $f) {
                    if (function_exists('apagarArquivoSupabase')) {
                        apagarArquivoSupabase($f['url_imagem']);
                    }
                }

                // Deleta referências no banco
                $pdo->prepare("DELETE FROM portfolio_imagens WHERE titulo_projeto = :titulo AND usuario_id = :u_id")
                    ->execute([':titulo' => $tituloProjeto, ':u_id' => $idUsuario]);

                $stmt = $pdo->prepare('DELETE FROM servicos WHERE id = :id AND prestador_id = :prestador_id');
                $stmt->execute([':id' => $idServico, ':prestador_id' => $idUsuario]);

                echo "<script>window.location.href='gerenciar.php?ok=excluido';</script>";
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
                ':prestador_id' => $idUsuario,
            ]);
            echo "<script>window.location.href='gerenciar.php?ok=editado';</script>";
            exit;
        }
    }
}

// Mensagens de Feedback
$sucesso = $_GET['ok'] ?? '';
if ($sucesso === 'editado') $mensagem = 'Serviço atualizado com sucesso.';
if ($sucesso === 'excluido') $mensagem = 'Serviço e fotos removidos do sistema.';

// 4. BUSCA LISTA DE SERVIÇOS
$stmtServicos = $pdo->prepare(
    'SELECT s.id, s.titulo, s.categoria_nome, s.valor_base, s.descricao_curta
     FROM servicos s
     WHERE s.prestador_id = :id
     ORDER BY s.id DESC'
);
$stmtServicos->execute([':id' => $idUsuario]);
$servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);

// CORREÇÃO DA SIDEBAR: Verifica se existem serviços para definir como "Pro"
$temServico = count($servicos) > 0;

// CSRF: sempre gera token novo para o formulario
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
        <p class="text-sm text-gray-500">Isso removerá o serviço e as fotos permanentemente.</p>
      </div>
      <div class="bg-gray-50 p-4 flex gap-3">
        <button onclick="fecharModalExcluir()" class="flex-1 py-3 text-sm font-bold text-gray-500 hover:bg-gray-200 rounded-xl transition-colors">Cancelar</button>
        <form method="POST" class="flex-1">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="servico_id" id="excluir-id">
            <button type="submit" class="w-full py-3 text-sm font-bold bg-red-500 text-white hover:bg-red-600 rounded-xl shadow-lg transition-all">Confirmar</button>
        </form>
      </div>
    </div>
  </div>

  <div id="sidebar-container" class="fixed inset-y-0 left-0 z-50 w-60 bg-sidebar flex flex-col h-screen transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out"></div>

  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    const isPro = <?= $temServico ? 'true' : 'false' ?>;
    const isAdmin = <?= (isset($usuarioLogado['tipo_usuario']) && $usuarioLogado['tipo_usuario'] === 'admin') ? 'true' : 'false' ?>;
    renderSidebar('sidebar-container', 'gerenciar', isPro, isAdmin, {}, {
      nome: "<?= htmlspecialchars($usuarioLogado['nome']) ?>",
      foto: "<?= $usuarioLogado['foto_perfil'] ?>"
    });
  </script>

  <main class="flex-1 flex flex-col overflow-hidden w-full relative">
    <header class="flex items-center justify-between px-4 md:px-8 py-4 md:py-5 border-b border-gray-200 bg-white flex-shrink-0">
        <div class="flex items-center gap-2 text-gray-400">
          <button onclick="window.toggleSidebar && window.toggleSidebar()" class="md:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors flex-shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <button onclick="history.back()" class="hover:text-gray-600 p-1 md:-ml-1 rounded-lg hover:bg-gray-100 hidden md:block">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <a href="./dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors">Início</a>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
          <span class="text-gray-800 font-bold text-lg tracking-tight">Gerenciar</span>
        </div>
    </header>
    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-6 custom-scroll">
      <h2 class="text-4xl font-extrabold text-slate-900 mb-6 tracking-tight">Seus Serviços</h2>

      <?php if ($mensagem): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl font-bold text-sm flex items-center gap-3">
            <?= $mensagem ?>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['erro']) && $_GET['erro'] === 'contrato_ativo'): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-600 rounded-2xl font-bold text-sm flex items-center gap-3">
            Não é possível excluir este serviço enquanto houver contratos pendentes ou em andamento.
        </div>
      <?php endif; ?>
      <section class="bg-white border rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto custom-scroll">
          <div class="min-w-[600px]">
            <div class="grid grid-cols-12 bg-gray-50 border-b font-bold text-gray-700 uppercase text-[10px] tracking-widest">
              <div class="col-span-4 px-5 py-4">Serviço</div>
              <div class="col-span-3 px-5 py-4 text-center">Categoria</div>
              <div class="col-span-2 px-5 py-4 text-center">Base</div>
              <div class="col-span-3 px-5 py-4 text-right">Opções</div>
            </div>
            <?php if(empty($servicos)): ?>
                <div class="p-10 text-center text-gray-400 text-sm italic">Nenhum serviço cadastrado.</div>
            <?php else: ?>
                <?php foreach ($servicos as $s): ?>
                  <div class="grid grid-cols-12 border-b last:border-b-0 items-center hover:bg-gray-50/50 transition-colors">
                    <div class="col-span-4 px-5 py-4 text-sm font-bold text-slate-800 uppercase truncate"><?= htmlspecialchars($s['titulo']) ?></div>
                    <div class="col-span-3 px-5 py-4 text-center"><span class="bg-gray-100 px-3 py-1 rounded-full text-[10px] font-black text-gray-500 uppercase truncate inline-block max-w-full"><?= htmlspecialchars($s['categoria_nome']) ?></span></div>
                    <div class="col-span-2 px-5 py-4 text-center text-sm font-bold text-slate-600">R$ <?= number_format((float)$s['valor_base'], 2, ',', '.') ?></div>
                    <div class="col-span-3 px-5 py-4 flex justify-end gap-2">
                      <button type="button" class="bg-white border border-gray-200 text-slate-700 px-3 py-2 rounded-xl text-xs font-bold hover:border-orange hover:text-orange transition-all"
                        data-editar 
                        data-id="<?= $s['id'] ?>"
                        data-titulo="<?= htmlspecialchars($s['titulo']) ?>"
                        data-categoria="<?= htmlspecialchars($s['categoria_nome']) ?>"
                        data-valor="<?= $s['valor_base'] ?>"
                        data-descricao="<?= htmlspecialchars($s['descricao_curta'] ?? '') ?>">Editar</button>
                      <button type="button" onclick="abrirModalExcluir(<?= $s['id'] ?>)" class="bg-white border border-gray-200 text-red-400 px-3 py-2 rounded-xl text-xs font-bold hover:bg-red-50 hover:text-red-500 transition-all">Excluir</button>
                    </div>
                  </div>
                <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
    </div>
  </main>

  <div id="modal-editar" class="hidden fixed inset-0 z-50 bg-black/45 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg bg-white rounded-3xl shadow-2xl p-8">
        <h3 class="text-2xl font-black text-slate-900 uppercase mb-6">Editar Serviço</h3>
        <form method="POST" class="space-y-4">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="acao" value="editar">
          <input type="hidden" id="edit-id" name="servico_id">
          <div><label class="block text-[10px] font-black text-gray-400 mb-1 uppercase">Título</label>
          <input type="text" id="edit-titulo" name="titulo" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:border-orange"></div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-[10px] font-black text-gray-400 mb-1 uppercase">Categoria</label>
            <input type="text" id="edit-categoria" name="categoria" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:border-orange"></div>
            <div><label class="block text-[10px] font-black text-gray-400 mb-1 uppercase">Valor (R$)</label>
            <input type="number" id="edit-valor" name="valor" step="0.01" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:border-orange"></div>
          </div>
          <div><label class="block text-[10px] font-black text-gray-400 mb-1 uppercase">Descrição</label>
          <textarea id="edit-descricao" name="descricao" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:border-orange resize-none"></textarea></div>
          <div class="pt-4 flex gap-3">
            <button type="button" onclick="fecharModalEditar()" class="flex-1 py-3.5 text-sm font-bold text-gray-400">Cancelar</button>
            <button type="submit" class="flex-1 bg-orange text-white py-3.5 rounded-2xl text-sm font-black shadow-lg">Salvar</button>
          </div>
        </form>
    </div>
  </div>

  <script>
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

    window.onclick = (e) => {
        if (e.target == modalEditar) fecharModalEditar();
        if (e.target == modalExcluir) fecharModalExcluir();
    }
  </script>
</body>
</html>