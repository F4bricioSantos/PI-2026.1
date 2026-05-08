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

$stmtServicos = $pdo->prepare("SELECT id, titulo, categoria_nome FROM servicos WHERE prestador_id = :id ORDER BY id DESC");
$stmtServicos->execute([':id' => $usuarioId]);
$servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);

$servicosMap = [];
foreach ($servicos as $servico) {
    $servicosMap[(int)$servico['id']] = $servico;
}

$uploadsDirAbs = __DIR__ . '/../uploads/portfolio';
$uploadsDirWeb = '/PI-2026.1/frontend/uploads/portfolio';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tituloProjeto = trim($_POST['titulo_projeto'] ?? '');
    $descricaoProjeto = trim($_POST['descricao_projeto'] ?? '');
    $servicoRelacionando = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);

    if ($tituloProjeto === '') {
        $erro = 'Informe o título do projeto.';
    } elseif (!isset($_FILES['foto_trabalho']) || ($_FILES['foto_trabalho']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $erro = 'Envie uma foto do trabalho.';
    } else {
        $arquivo = $_FILES['foto_trabalho'];
        $mime = mime_content_type($arquivo['tmp_name']);
        $permitidos = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if (!isset($permitidos[$mime])) {
            $erro = 'Formato inválido. Envie JPG ou PNG.';
        } elseif (($arquivo['size'] ?? 0) > 10 * 1024 * 1024) {
            $erro = 'A imagem deve ter no máximo 10MB.';
        } else {
            if (!is_dir($uploadsDirAbs)) {
                mkdir($uploadsDirAbs, 0775, true);
            }

            $ext = $permitidos[$mime];
            $nomeArquivo = sprintf('portfolio_%d_%s.%s', $usuarioId, bin2hex(random_bytes(6)), $ext);
            $destinoAbs = $uploadsDirAbs . DIRECTORY_SEPARATOR . $nomeArquivo;
            $destinoWeb = $uploadsDirWeb . '/' . $nomeArquivo;

            if (!move_uploaded_file($arquivo['tmp_name'], $destinoAbs)) {
                $erro = 'Não foi possível salvar a imagem enviada.';
            } else {
                $servicoValido = $servicoRelacionando && isset($servicosMap[$servicoRelacionando]);
                $descricaoBanco = $descricaoProjeto;
                if ($servicoValido) {
                    $descricaoBanco = "[servico:{$servicoRelacionando}]\n" . $descricaoProjeto;
                }

                $stmtInsert = $pdo->prepare(
                    "INSERT INTO portfolio_imagens (usuario_id, titulo_projeto, descricao_projeto, url_imagem)
                     VALUES (:usuario_id, :titulo, :descricao, :url)"
                );

                $ok = $stmtInsert->execute([
                    ':usuario_id' => $usuarioId,
                    ':titulo' => $tituloProjeto,
                    ':descricao' => $descricaoBanco !== '' ? $descricaoBanco : null,
                    ':url' => $destinoWeb,
                ]);

                if ($ok) {
                    header('Location: /PI-2026.1/frontend/Pages/portfolio.php?ok=1');
                    exit;
                }

                $erro = 'Não foi possível salvar o projeto no banco.';
            }
        }
    }
}

if (($_GET['ok'] ?? '') === '1') {
    $mensagem = 'Projeto adicionado ao portfólio com sucesso.';
}

$stmtPortfolio = $pdo->prepare(
    "SELECT id, titulo_projeto, descricao_projeto, url_imagem, data_upload
     FROM portfolio_imagens
     WHERE usuario_id = :id
     ORDER BY data_upload DESC"
);
$stmtPortfolio->execute([':id' => $usuarioId]);
$portfolio = $stmtPortfolio->fetchAll(PDO::FETCH_ASSOC);

$items = [];
$mesesPt = [
    1 => 'jan',
    2 => 'fev',
    3 => 'mar',
    4 => 'abr',
    5 => 'mai',
    6 => 'jun',
    7 => 'jul',
    8 => 'ago',
    9 => 'set',
    10 => 'out',
    11 => 'nov',
    12 => 'dez',
];
foreach ($portfolio as $item) {
    $descricaoRaw = (string)($item['descricao_projeto'] ?? '');
    $servicoIdAssoc = null;

    if (preg_match('/^\[servico:(\d+)\]\s*/', $descricaoRaw, $match)) {
        $servicoIdAssoc = (int)$match[1];
        $descricaoRaw = preg_replace('/^\[servico:\d+\]\s*/', '', $descricaoRaw, 1);
    }

    $categoriaTag = 'PROJETO';
    if ($servicoIdAssoc && isset($servicosMap[$servicoIdAssoc])) {
        $categoriaTag = strtoupper($servicosMap[$servicoIdAssoc]['categoria_nome'] ?: 'PROJETO');
    }

    $items[] = [
        'id' => (int)$item['id'],
        'titulo' => $item['titulo_projeto'],
        'descricao' => $descricaoRaw,
        'url' => $item['url_imagem'],
        'data' => $item['data_upload'],
        'tag' => $categoriaTag,
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAí - Gerenciar Portfólio</title>
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
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">
  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>
  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    renderSidebar('sidebar-container', 'portfolio');
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="history.back()" class="hover:text-gray-600 transition-colors p-1 -ml-1 rounded-lg hover:bg-gray-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <a href="./dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors">Início</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-gray-800 font-bold text-lg tracking-tight">Portfolio</span>
      </div>
      <a href="perfil.php">
      <div class="w-9 h-9 rounded-full bg-orange/80 flex items-center justify-center text-white font-bold text-sm">
          <?= strtoupper(mb_substr($_SESSION['usuario_nome'] ?? 'U', 0, 1)) ?>
      </div></a>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-6">
      <div class="mb-6">
        <h2 class="text-4xl font-extrabold text-slate-900 tracking-tight">Configurações do Portfólio</h2>
        <p class="text-sm text-gray-500 mt-1">Gerencie seus projetos realizados e atraia novos clientes.</p>
      </div>

      <?php if ($mensagem): ?>
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700 text-sm font-semibold"><?= htmlspecialchars($mensagem) ?></div>
      <?php endif; ?>
      <?php if ($erro): ?>
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-600 text-sm font-semibold"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <section class="xl:col-span-4 bg-white border border-gray-200 rounded-xl p-5 shadow-sm h-fit">
          <h3 class="text-2xl font-extrabold text-slate-900 mb-5">Novo Trabalho</h3>

          <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-2">Título do Projeto</label>
              <input name="titulo_projeto" type="text" placeholder="Ex: Reforma Cozinha Moderna" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-orange" />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-2">Descrição</label>
              <textarea name="descricao_projeto" rows="3" placeholder="Descreva os serviços realizados..." class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-orange resize-none"></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-2">Serviço Relacionado</label>
              <select name="servico_id" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-orange bg-white">
                <option value="">Selecione um serviço</option>
                <?php foreach ($servicos as $servico): ?>
                  <option value="<?= (int)$servico['id'] ?>"><?= htmlspecialchars($servico['titulo']) ?><?= !empty($servico['categoria_nome']) ? ' (' . htmlspecialchars($servico['categoria_nome']) . ')' : '' ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 mb-2">Fotos do Trabalho</label>
              <label class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center block cursor-pointer hover:border-orange transition-colors">
                <input type="file" name="foto_trabalho" accept="image/png,image/jpeg" required class="hidden" />
                <p class="text-sm text-gray-500">Arraste fotos ou clique para enviar</p>
                <p class="text-xs text-gray-400 mt-1">PNG, JPG até 10MB</p>
              </label>
            </div>

            <button type="submit" class="w-full bg-orange hover:bg-orange-600 text-white py-3 rounded-xl font-bold text-sm transition-colors">Adicionar ao Portfólio</button>
          </form>
        </section>

        <section class="xl:col-span-8">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-extrabold text-slate-900">Meus Trabalhos</h3>
            <p class="text-sm text-gray-500">Total: <?= count($items) ?> projetos</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($items as $item): ?>
              <article class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="h-36 bg-gray-100">
                  <img src="<?= htmlspecialchars($item['url']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="w-full h-full object-cover" />
                </div>
                <div class="p-4">
                  <p class="text-[10px] font-extrabold tracking-wide text-orange mb-1"><?= htmlspecialchars($item['tag']) ?></p>
                  <h4 class="text-lg font-extrabold text-slate-900 leading-tight"><?= htmlspecialchars($item['titulo']) ?></h4>
                  <?php
                    $ts = strtotime($item['data']);
                    $mesAno = $mesesPt[(int)date('n', $ts)] . ' ' . date('Y', $ts);
                  ?>
                  <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($mesAno) ?></p>
                </div>
              </article>
            <?php endforeach; ?>

            <?php if (count($items) === 0): ?>
              <div class="md:col-span-2 h-52 rounded-xl border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-gray-400 text-sm">
                Novo projeto será exibido aqui
              </div>
            <?php endif; ?>
          </div>
        </section>
      </div>
    </div>
  </main>
</body>
</html>



