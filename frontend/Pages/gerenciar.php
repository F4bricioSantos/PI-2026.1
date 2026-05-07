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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'excluir') {
        $idServico = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);

        if (!$idServico) {
            $erro = 'Serviço inválido para exclusão.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM servicos WHERE id = :id AND prestador_id = :prestador_id');
            $ok = $stmt->execute([
                ':id' => $idServico,
                ':prestador_id' => $usuarioId,
            ]);

            if ($ok && $stmt->rowCount() > 0) {
                header('Location: /PI-2026.1/frontend/Pages/gerenciar.php?ok=excluido');
                exit;
            }

            $erro = 'Não foi possível excluir o serviço.';
        }
    }

    if ($acao === 'editar') {
        $idServico = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);
        $titulo = trim($_POST['titulo'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $valor = $_POST['valor'] ?? '';

        if (!$idServico) {
            $erro = 'Serviço inválido para edição.';
        } elseif ($titulo === '' || $categoria === '') {
            $erro = 'Título e categoria são obrigatórios.';
        } elseif ($valor !== '' && (!is_numeric($valor) || (float)$valor < 0)) {
            $erro = 'Preço inválido.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE servicos
                 SET titulo = :titulo, categoria_nome = :categoria, valor_base = :valor
                 WHERE id = :id AND prestador_id = :prestador_id'
            );

            $ok = $stmt->execute([
                ':titulo' => $titulo,
                ':categoria' => $categoria,
                ':valor' => $valor !== '' ? (float)$valor : null,
                ':id' => $idServico,
                ':prestador_id' => $usuarioId,
            ]);

            if ($ok) {
                header('Location: /PI-2026.1/frontend/Pages/gerenciar.php?ok=editado');
                exit;
            }

            $erro = 'Não foi possível salvar as alterações.';
        }
    }
}

$sucesso = $_GET['ok'] ?? '';
if ($sucesso === 'editado') {
    $mensagem = 'Serviço atualizado com sucesso.';
}
if ($sucesso === 'excluido') {
    $mensagem = 'Serviço excluído com sucesso.';
}

$stmtServicos = $pdo->prepare(
    'SELECT s.id, s.titulo, s.categoria_nome, s.valor_base,
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí - Gerenciar Serviços</title>
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
            bg: '#F8F9FA',
          }
        }
      }
    }
  </script>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">
  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'gerenciar');
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <h1 class="text-gray-800 font-bold text-lg tracking-tight">ReformAí</h1>
      <a href="/PI-2026.1/frontend/Pages/novo-servico.php" class="bg-orange hover:bg-orange-600 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-colors">+ Novo Serviço</a>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-6">
      <div class="mb-6">
        <h2 class="text-4xl font-extrabold text-slate-900 tracking-tight">Gerenciar Serviços</h2>
        <p class="text-sm text-gray-500 mt-1">Visualize e edite seus serviços cadastrados no catálogo.</p>
      </div>

      <?php if ($mensagem): ?>
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700 text-sm font-semibold"><?= htmlspecialchars($mensagem) ?></div>
      <?php endif; ?>
      <?php if ($erro): ?>
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-600 text-sm font-semibold"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <section class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="grid grid-cols-12 bg-gray-50 border-b border-gray-200 text-sm font-bold text-gray-700">
          <div class="col-span-4 px-5 py-4">Título</div>
          <div class="col-span-2 px-5 py-4">Categoria</div>
          <div class="col-span-2 px-5 py-4">Preço</div>
          <div class="col-span-2 px-5 py-4">Avaliação</div>
          <div class="col-span-2 px-5 py-4 text-right">Ações</div>
        </div>

        <?php if (empty($servicos)): ?>
          <?php
            $exemplos = [
              ['titulo' => 'Pintura Residencial Interna', 'categoria' => 'Reformas', 'preco' => 'R$ 850,00', 'nota' => '4,8'],
              ['titulo' => 'Manutenção Elétrica Geral', 'categoria' => 'Reparos', 'preco' => 'R$ 220,00', 'nota' => '4,5'],
              ['titulo' => 'Instalação de Piso Vinílico', 'categoria' => 'Obras', 'preco' => 'R$ 1.500,00', 'nota' => '5,0'],
            ];
          ?>
          <?php foreach ($exemplos as $servico): ?>
            <div class="grid grid-cols-12 border-b last:border-b-0 border-gray-100 items-center">
              <div class="col-span-4 px-5 py-4 text-sm font-semibold text-slate-800"><?= htmlspecialchars($servico['titulo']) ?></div>
              <div class="col-span-2 px-5 py-4">
                <span class="inline-flex bg-gray-100 text-gray-700 text-xs font-semibold px-2.5 py-1 rounded-full"><?= htmlspecialchars($servico['categoria']) ?></span>
              </div>
              <div class="col-span-2 px-5 py-4 text-sm text-gray-600"><?= htmlspecialchars($servico['preco']) ?></div>
              <div class="col-span-2 px-5 py-4">
                <span class="inline-flex items-center gap-1 bg-orange-50 text-orange-700 text-xs font-bold px-2.5 py-1 rounded-md">★ <?= htmlspecialchars($servico['nota']) ?></span>
              </div>
              <div class="col-span-2 px-5 py-4 flex items-center justify-end gap-2">
                <button type="button" class="border border-orange text-orange text-xs font-bold px-4 py-1.5 rounded-lg opacity-70 cursor-not-allowed">Editar</button>
                <button type="button" class="border border-red-300 text-red-500 text-xs font-bold px-4 py-1.5 rounded-lg opacity-70 cursor-not-allowed">Excluir</button>
              </div>
            </div>
          <?php endforeach; ?>
          <div class="px-5 py-4 text-xs text-gray-400 bg-gray-50">Exemplo visual. Cadastre seu primeiro serviço para habilitar ações reais.</div>
        <?php else: ?>
          <?php foreach ($servicos as $servico): ?>
            <div class="grid grid-cols-12 border-b last:border-b-0 border-gray-100 items-center">
              <div class="col-span-4 px-5 py-4 text-sm font-semibold text-slate-800"><?= htmlspecialchars($servico['titulo']) ?></div>
              <div class="col-span-2 px-5 py-4">
                <span class="inline-flex bg-gray-100 text-gray-700 text-xs font-semibold px-2.5 py-1 rounded-full"><?= htmlspecialchars($servico['categoria_nome'] ?: 'Sem categoria') ?></span>
              </div>
              <div class="col-span-2 px-5 py-4 text-sm text-gray-600"><?= $servico['valor_base'] !== null ? 'R$ ' . number_format((float)$servico['valor_base'], 2, ',', '.') : 'A combinar' ?></div>
              <div class="col-span-2 px-5 py-4">
                <span class="inline-flex items-center gap-1 bg-orange-50 text-orange-700 text-xs font-bold px-2.5 py-1 rounded-md">★ <?= number_format((float)$servico['media_nota'], 1, ',', '.') ?></span>
              </div>
              <div class="col-span-2 px-5 py-4 flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="border border-orange text-orange hover:bg-orange hover:text-white text-xs font-bold px-4 py-1.5 rounded-lg transition-colors"
                  data-editar
                  data-id="<?= (int)$servico['id'] ?>"
                  data-titulo="<?= htmlspecialchars($servico['titulo'], ENT_QUOTES) ?>"
                  data-categoria="<?= htmlspecialchars($servico['categoria_nome'] ?? '', ENT_QUOTES) ?>"
                  data-valor="<?= htmlspecialchars((string)($servico['valor_base'] ?? ''), ENT_QUOTES) ?>"
                >Editar</button>

                <form method="POST" onsubmit="return confirm('Deseja excluir este serviço?');">
                  <input type="hidden" name="acao" value="excluir" />
                  <input type="hidden" name="servico_id" value="<?= (int)$servico['id'] ?>" />
                  <button type="submit" class="border border-red-300 text-red-500 hover:bg-red-500 hover:text-white text-xs font-bold px-4 py-1.5 rounded-lg transition-colors">Excluir</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </div>
  </main>

  <div id="modal-editar" class="hidden fixed inset-0 z-50 bg-black/45 p-4">
    <div class="max-w-lg mx-auto mt-16 bg-white rounded-2xl border border-gray-100 shadow-2xl p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-extrabold text-slate-900">Editar Serviço</h3>
        <button id="fechar-modal" type="button" class="text-gray-400 hover:text-gray-700 text-lg">×</button>
      </div>

      <form method="POST" class="space-y-4">
        <input type="hidden" name="acao" value="editar" />
        <input type="hidden" id="edit-servico-id" name="servico_id" />

        <div>
          <label class="block text-xs font-bold text-gray-700 mb-2">Título</label>
          <input id="edit-titulo" name="titulo" type="text" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-orange" />
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 mb-2">Categoria</label>
          <input id="edit-categoria" name="categoria" type="text" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-orange" />
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 mb-2">Preço (R$)</label>
          <input id="edit-valor" name="valor" type="number" min="0" step="0.01" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-orange" />
        </div>

        <div class="pt-2 flex justify-end gap-2">
          <button type="button" id="cancelar-modal" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm font-bold">Cancelar</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-orange hover:bg-orange-600 text-white text-sm font-bold">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const modal = document.getElementById('modal-editar');
    const btnFechar = document.getElementById('fechar-modal');
    const btnCancelar = document.getElementById('cancelar-modal');
    const inputId = document.getElementById('edit-servico-id');
    const inputTitulo = document.getElementById('edit-titulo');
    const inputCategoria = document.getElementById('edit-categoria');
    const inputValor = document.getElementById('edit-valor');

    const abrirModal = (dados) => {
      inputId.value = dados.id || '';
      inputTitulo.value = dados.titulo || '';
      inputCategoria.value = dados.categoria || '';
      inputValor.value = dados.valor || '';
      modal.classList.remove('hidden');
    };

    const fecharModal = () => {
      modal.classList.add('hidden');
    };

    document.querySelectorAll('[data-editar]').forEach((botao) => {
      botao.addEventListener('click', () => {
        abrirModal({
          id: botao.dataset.id,
          titulo: botao.dataset.titulo,
          categoria: botao.dataset.categoria,
          valor: botao.dataset.valor,
        });
      });
    });

    btnFechar.addEventListener('click', fecharModal);
    btnCancelar.addEventListener('click', fecharModal);

    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        fecharModal();
      }
    });
  </script>
</body>
</html>
