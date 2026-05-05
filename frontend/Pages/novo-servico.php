<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['usuario_id'])) {
    header('Location: /PI-2026.1/frontend/Pages/login.php');
    exit;
}

require_once '../../backend/config/Conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo    = trim($_POST['titulo']    ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $valor     = $_POST['valor']          ?? '';
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($titulo) || empty($categoria)) {
        $erro = 'Título e categoria são obrigatórios.';
    } elseif ($valor !== '' && (!is_numeric($valor) || $valor < 0)) {
        $erro = 'Informe um valor numérico válido.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO servicos (prestador_id, titulo, categoria_nome, valor_base, descricao_curta)
            VALUES (:prestador_id, :titulo, :categoria, :valor, :descricao)
        ");
        $ok = $stmt->execute([
            ':prestador_id' => $_SESSION['usuario_id'],
            ':titulo'       => $titulo,
            ':categoria'    => $categoria,
            ':valor'        => $valor !== '' ? (float)$valor : null,
            ':descricao'    => $descricao ?: null,
        ]);

        if ($ok) {
            header('Location: /PI-2026.1/frontend/Pages/dashboard.php');
            exit;
        } else {
            $erro = 'Erro ao salvar o serviço. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí – Novo Serviço</title>
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

  <!-- SIDEBAR — igual ao original -->
  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'novo-servico');
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">

    <!-- Top bar — igual ao original -->
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="history.back()" aria-label="Voltar" class="hover:text-gray-600 transition-colors p-1 -ml-1 rounded-lg hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <a href="./dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors">Início</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-gray-800 font-bold text-lg tracking-tight">Novo Serviço</span>
      </div>
      <div class="flex items-center gap-4">
        <button aria-label="Notificações" class="relative text-gray-400 hover:text-gray-700 transition-colors p-2 rounded-xl hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
          <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-orange rounded-full"></span>
        </button>
        <div class="w-9 h-9 rounded-full bg-orange/80 flex-shrink-0 cursor-pointer hover:opacity-90 transition-opacity flex items-center justify-center text-white font-bold text-sm">
          <?= strtoupper(mb_substr($_SESSION['usuario_nome'], 0, 1)) ?>
        </div>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-10 custom-scroll">
      <div class="max-w-4xl mx-auto">

        <span class="inline-block px-3 py-1 bg-slate-100 text-slate-500 text-[10px] font-bold uppercase tracking-widest rounded-full mb-4">
          Área do Prestador
        </span>
        <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mb-2">Novo Serviço</h1>
        <p class="text-gray-500 mb-10">Cadastre os detalhes do seu novo serviço para começar a receber orçamentos.</p>

        <?php if ($erro): ?>
          <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-600 text-sm font-medium">
            <?= htmlspecialchars($erro) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-8">

          <div class="space-y-2">
            <label class="text-sm font-bold text-slate-700 ml-1">Título do Serviço</label>
            <input type="text" name="titulo"
              value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>"
              placeholder="Ex: Pintura Residencial de Alto Padrão"
              class="w-full bg-white border border-gray-200 rounded-2xl px-6 py-4 text-base focus:outline-none focus:border-orange focus:ring-4 focus:ring-orange/5 transition-all placeholder:text-gray-300 shadow-sm">
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-2">
              <label class="text-sm font-bold text-slate-700 ml-1">Categoria</label>
              <div class="relative">
                <select name="categoria"
                  class="w-full bg-white border border-gray-200 rounded-2xl px-6 py-4 text-base focus:outline-none focus:border-orange focus:ring-4 focus:ring-orange/5 transition-all appearance-none cursor-pointer shadow-sm">
                  <option value="" disabled <?= empty($_POST['categoria']) ? 'selected' : '' ?>>Selecione uma categoria</option>
                  <option value="Reforma"    <?= ($_POST['categoria'] ?? '') === 'Reforma'    ? 'selected' : '' ?>>Reforma</option>
                  <option value="Elétrica"   <?= ($_POST['categoria'] ?? '') === 'Elétrica'   ? 'selected' : '' ?>>Elétrica</option>
                  <option value="Pintura"    <?= ($_POST['categoria'] ?? '') === 'Pintura'    ? 'selected' : '' ?>>Pintura</option>
                  <option value="Hidráulica" <?= ($_POST['categoria'] ?? '') === 'Hidráulica' ? 'selected' : '' ?>>Hidráulica</option>
                  <option value="Jardinagem" <?= ($_POST['categoria'] ?? '') === 'Jardinagem' ? 'selected' : '' ?>>Jardinagem</option>
                  <option value="Limpeza"    <?= ($_POST['categoria'] ?? '') === 'Limpeza'    ? 'selected' : '' ?>>Limpeza</option>
                  <option value="Marcenaria" <?= ($_POST['categoria'] ?? '') === 'Marcenaria' ? 'selected' : '' ?>>Marcenaria</option>
                  <option value="Telhado"    <?= ($_POST['categoria'] ?? '') === 'Telhado'    ? 'selected' : '' ?>>Telhado</option>
                  <option value="Design"     <?= ($_POST['categoria'] ?? '') === 'Design'     ? 'selected' : '' ?>>Design de Interiores</option>
                </select>
                <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-bold text-slate-700 ml-1">Preço Estimado (R$)</label>
              <div class="relative">
                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-gray-400 font-medium">R$</span>
                <input type="number" name="valor" min="0" step="0.01"
                  value="<?= htmlspecialchars($_POST['valor'] ?? '') ?>"
                  placeholder="0,00"
                  class="w-full bg-white border border-gray-200 rounded-2xl pl-14 pr-6 py-4 text-base focus:outline-none focus:border-orange focus:ring-4 focus:ring-orange/5 transition-all shadow-sm">
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-bold text-slate-700 ml-1">Descrição Detalhada</label>
            <textarea name="descricao" rows="6"
              placeholder="Descreva as etapas do serviço, materiais inclusos e prazos médios..."
              class="w-full bg-white border border-gray-200 rounded-3xl px-6 py-4 text-base focus:outline-none focus:border-orange focus:ring-4 focus:ring-orange/5 transition-all placeholder:text-gray-300 resize-none shadow-sm"><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
          </div>

          <div class="flex items-center justify-end gap-6 pt-4">
            <a href="dashboard.php" class="text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors">Cancelar</a>
            <button type="submit" class="bg-orange hover:bg-orange-600 text-white px-10 py-4 rounded-2xl font-bold text-base shadow-lg shadow-orange/20 transition-all hover:scale-[1.02] active:scale-95 flex items-center gap-3">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Salvar Serviço
            </button>
          </div>

        </form>

        <!-- Pré-visualização — igual ao original -->
        <div class="mt-16 bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl p-12 text-center">
          <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-300 shadow-sm border border-slate-100">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
          </div>
          <h3 class="text-slate-700 font-bold mb-1">Pré-visualização do anúncio</h3>
          <p class="text-slate-400 text-sm max-w-xs mx-auto leading-relaxed">
            Preencha os campos acima para ver como seu serviço aparecerá para os clientes.
          </p>
        </div>

        <footer class="mt-20 pt-8 border-t border-gray-100 text-center">
          <p class="text-[10px] font-bold text-gray-300 uppercase tracking-widest">ReformAí © 2024 - Prestador Dashboard</p>
        </footer>

      </div>
    </div>
  </main>

  <script>
  // Pega os campos do formulário
  const inputTitulo = document.querySelector('input[name="titulo"]');
  const selectCat   = document.querySelector('select[name="categoria"]');
  const inputValor  = document.querySelector('input[name="valor"]');
  const areaDesc    = document.querySelector('textarea[name="descricao"]');

  // Pega os elementos da área de pré-visualização (vamos criar um card real aqui)
  const previewArea = document.querySelector('.mt-16'); // O container da prévia

  function atualizarPrevia() {
    const titulo = inputTitulo.value || "Título do seu serviço";
    const cat    = selectCat.value   || "Categoria";
    const valor  = inputValor.value  ? "R$ " + inputValor.value : "A combinar";
    const desc   = areaDesc.value    || "Sua descrição aparecerá aqui...";

    // Substitui o conteúdo da área de prévia por um card igual ao do dashboard
    previewArea.innerHTML = `
      <div class="max-w-sm mx-auto bg-white rounded-2xl overflow-hidden shadow-xl text-left border border-gray-100">
        <div class="p-5 flex flex-col gap-2">
          <span class="text-orange text-[10px] font-bold uppercase">${cat}</span>
          <h3 class="font-bold text-gray-900 text-lg">${titulo}</h3>
          <p class="text-gray-500 text-xs line-clamp-2">${desc}</p>
          <div class="flex items-center justify-between mt-4">
            <span class="text-orange font-extrabold">${valor}</span>
            <span class="text-xs text-gray-400">Prévia do Anúncio</span>
          </div>
        </div>
      </div>
    `;
  }

  [inputTitulo, selectCat, inputValor, areaDesc].forEach(el => {
    el.addEventListener('input', atualizarPrevia);
  });
</script>

</body>
</html>