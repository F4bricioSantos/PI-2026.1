<?php
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';

$idUsuario = $_SESSION['usuario_id']; 
$mensagem = "";

// 1. BUSCA DADOS ATUAIS
try {
    $stmtAtuais = $pdo->prepare("SELECT email, foto_perfil, cidade FROM usuarios WHERE id = :id");
    $stmtAtuais->execute([':id' => $idUsuario]);
    $dadosBD = $stmtAtuais->fetch();

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = :id");
    $stmtCheck->execute([':id' => $idUsuario]);
    $temServico = $stmtCheck->fetchColumn() > 0;
} catch (Exception $e) {
    $mensagem = "Erro de conexÃ£o: " . $e->getMessage();
}

// 2. PROCESSAMENTO DO FORMULÃRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $cidade    = filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_SPECIAL_CHARS);
    $telefone  = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS);

    try {
        $nomeFotoNova = $dadosBD['foto_perfil'];
        if (isset($_POST['remover_foto'])) {
            if (function_exists('gerenciarFotoSupabase')) {
                gerenciarFotoSupabase(null, $dadosBD['foto_perfil']); 
            }
            $nomeFotoNova = 'default.png';
        } elseif (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            if (function_exists('gerenciarFotoSupabase')) {
                $result = gerenciarFotoSupabase($_FILES['foto_perfil']['tmp_name'], $dadosBD['foto_perfil']);
                if ($result !== false) $nomeFotoNova = $result;
            }
        }

        $pdo->beginTransaction();

        $campos = ["nome = :nome", "cidade = :cidade", "telefone = :telefone", "foto_perfil = :foto"];
        $params = [
            ':nome'     => $nome, 
            ':cidade'   => $cidade, 
            ':telefone' => $telefone, 
            ':foto'     => $nomeFotoNova, 
            ':id'       => $idUsuario
        ];

        $sqlUser = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = :id";
        $pdo->prepare($sqlUser)->execute($params);

        if ($temServico) {
            $bio = $_POST['bio'] ?? '';
            $nicho = $_POST['nicho'] ?? '';
            $exp = (isset($_POST['experiencia']) && $_POST['experiencia'] !== '') ? (int)$_POST['experiencia'] : 0;

            $sqlDet = "INSERT INTO prestadores_detalhes (usuario_id, bio, nicho, experiencia_anos) 
                       VALUES (:id, :bio, :nicho, :exp)
                       ON CONFLICT (usuario_id) DO UPDATE SET 
                       bio = EXCLUDED.bio, nicho = EXCLUDED.nicho, experiencia_anos = EXCLUDED.experiencia_anos";
            $pdo->prepare($sqlDet)->execute([':id'=>$idUsuario, ':bio'=>$bio, ':nicho'=>$nicho, ':exp'=>$exp]);
        }

        $pdo->commit();

        // Fluxo condicional: se for novo cadastro, redireciona para o prÃ³ximo passo
        if (isset($_GET['new']) && $_GET['new'] === '1') {
            $fluxo = $_GET['fluxo'] ?? 'cliente';
            $destino = ($fluxo === 'prestador') ? 'novo-servico.php' : 'dashboard.php';
            echo "<script>window.location.href='{$destino}';</script>";
        } else {
            echo "<script>window.location.href='perfil.php?sucesso=1';</script>";
        }
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensagem = (strpos($e->getMessage(), 'usuarios_email_key') !== false) ? "O e-mail informado jÃ¡ estÃ¡ em uso." : "Erro tÃ©cnico: " . $e->getMessage();
    }
}

// 3. BUSCA DADOS FINAIS
$sql = "SELECT u.*, pd.bio, pd.nicho, pd.experiencia_anos FROM usuarios u LEFT JOIN prestadores_detalhes pd ON pd.usuario_id = u.id WHERE u.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $idUsuario]);
$dados = $stmt->fetch();

if (!defined('SB_URL')) define('SB_URL', ''); 
$urlBaseSupabase = SB_URL . "/storage/v1/object/public/fotos/";
$fotoExibicao = ($dados['foto_perfil'] == 'default.png' || empty($dados['foto_perfil'])) ? null : $urlBaseSupabase . $dados['foto_perfil'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ReformAÃ­ â€“ Meu Perfil</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { sans: ['Manrope', 'sans-serif'] }, colors:{orange:{DEFAULT:'#F97316',light:'#FFEDD5',dark:'#EA580C'},sidebar:'#16213E',card:'#1E2A3A',bg:'#F8F9FA'} } }
    }
  </script>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <div id="sidebar-container" class="fixed inset-y-0 left-0 z-50 w-60 bg-sidebar flex flex-col h-screen transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out"></div>

  <script type="module">
    import { renderSidebar } from '/frontend/src/components/sidebar.js';
    
    // No perfil.php a variÃ¡vel que vocÃª usou no topo Ã© $temServico
    const isPro = <?= ($temServico) ? 'true' : 'false' ?>;
    const isAdmin = <?= (isset($dados['tipo_usuario']) && $dados['tipo_usuario'] === 'admin') ? 'true' : 'false' ?>;
    
    // Renderiza a sidebar - a lÃ³gica interna agora cuida da marcaÃ§Ã£o ativa
    renderSidebar('sidebar-container', 'perfil', isPro, isAdmin, {}, {
      nome: "<?= htmlspecialchars($dados['nome']) ?>",
      foto: "<?= htmlspecialchars($dados['foto_perfil'] ?? '') ?>",
      id: "<?= $idUsuario ?>"
    });
</script> 
  <main class="flex-1 flex flex-col overflow-hidden w-full relative bg-bg">
    <header class="flex items-center justify-between px-4 md:px-8 py-4 md:py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2 text-gray-400">
        <button onclick="window.toggleSidebar && window.toggleSidebar()" class="md:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none transition-colors flex-shrink-0">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <button onclick="history.back()" class="hover:text-gray-600 p-1 md:-ml-1 rounded-lg hover:bg-gray-100 hidden md:block">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <a href="./dashboard.php" class="text-gray-400 text-sm hover:text-orange transition-colors">InÃ­cio</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-gray-800 font-bold text-lg tracking-tight">Meu Perfil</span>
      </div>
      <div class="flex items-center gap-3">
        <?php if(isset($_GET['sucesso']) && empty($mensagem)): ?>
            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-100">AlteraÃ§Ãµes salvas!</span>
        <?php endif; ?>
        <?php if(isset($_GET['new']) && $_GET['new'] === '1'): ?>
            <span class="text-xs font-bold text-orange bg-orange-50 px-3 py-1 rounded-lg border border-orange-100 italic">Bem-vindo! Complete seu perfil para continuar.</span>
        <?php endif; ?>
        <?php if($mensagem): ?>
            <span class="text-xs font-bold text-red-600 bg-red-50 px-3 py-1 rounded-lg border border-red-100"><?= $mensagem ?></span>
        <?php endif; ?>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto px-4 md:px-8 py-6 md:py-8 custom-scroll">
      <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-8 max-w-6xl mx-auto">
        
        <div class="lg:col-span-4">
          <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 text-center">
            <div class="relative w-32 h-32 mx-auto mb-6">
               <?php if($fotoExibicao): ?>
                 <img id="preview" src="<?= $fotoExibicao ?>" class="w-full h-full rounded-full object-cover border-4 border-gray-50 shadow-sm">
               <?php else: ?>
                 <div id="placeholder" class="w-full h-full bg-slate-100 rounded-full flex items-center justify-center border-4 border-gray-50 shadow-sm">
                    <span class="text-3xl font-black text-slate-300"><?= strtoupper(substr($dados['nome'] ?? 'R', 0, 1)) ?></span>
                 </div>
               <?php endif; ?>

               <label class="absolute bottom-0 right-0 bg-blue-600 hover:bg-blue-700 text-white w-9 h-9 rounded-full flex items-center justify-center cursor-pointer border-2 border-white shadow-lg transition-all hover:scale-110">
                  <i class="ph-bold ph-pencil-simple text-sm"></i>
                  <input type="file" name="foto_perfil" id="foto_input" class="hidden" accept="image/*">
               </label>

               <?php if($fotoExibicao): ?>
                 <button type="submit" name="remover_foto" value="1" class="absolute bottom-0 -left-2 bg-white hover:bg-red-50 text-red-500 w-9 h-9 rounded-full flex items-center justify-center border-2 border-gray-100 shadow-lg transition-all hover:scale-110">
                    <i class="ph-bold ph-trash text-sm"></i>
                 </button>
               <?php endif; ?>
            </div>

            <h2 class="text-xl font-extrabold text-slate-900"><?= htmlspecialchars($dados['nome'] ?? '') ?></h2>
            <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mt-1">
                <i class="ph ph-map-pin"></i> <?= htmlspecialchars($dados['cidade'] ?: 'NÃ£o informada') ?>
            </p>
          </div>
        </div>

        <div class="lg:col-span-8">
          <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
              <h3 class="text-lg font-bold text-slate-900 mb-6">InformaÃ§Ãµes Pessoais</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-gray-400 uppercase ml-1">Nome Completo</label>
                  <input type="text" name="nome" value="<?= htmlspecialchars($dados['nome'] ?? '') ?>" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-gray-400 uppercase ml-1">Telefone</label>
                  <input type="text" name="telefone" value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none transition-all">
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-400 uppercase ml-1">Estado (UF)</label>
                    <select id="uf" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none"></select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-400 uppercase ml-1">Cidade</label>
                    <select id="cidade" name="cidade" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none" disabled></select>
                    <input type="hidden" id="cidade_atual" value="<?= htmlspecialchars($dados['cidade'] ?? '') ?>">
                </div>
              </div>

              <div class="space-y-1.5 pt-4 pb-4">
                <label class="text-xs font-bold text-gray-400 uppercase ml-1">E-mail</label>
                <div class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm text-gray-600 flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <span><?= htmlspecialchars($dados['email'] ?? '') ?></span>
                </div>
                <p class="text-[11px] text-gray-400 ml-1">Para alterar, vÃ¡ em <a href="/configuracoes" class="text-orange font-semibold hover:underline">ConfiguraÃ§Ãµes</a></p>
              </div>

              <div class="pt-8 border-t border-gray-100">
                <?php if($temServico): ?>
                    <h3 class="text-lg font-bold text-orange mb-6">Dados de Prestador</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-400 uppercase ml-1">Nicho / Especialidade</label>
                            <input type="text" name="nicho" value="<?= htmlspecialchars($dados['nicho'] ?? '') ?>" class="w-full bg-orange-50/30 border border-orange-100 rounded-xl px-4 py-3 text-sm outline-none">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-400 uppercase ml-1">ExperiÃªncia (anos)</label>
                            <input type="number" name="experiencia" value="<?= $dados['experiencia_anos'] ?? 0 ?>" class="w-full bg-orange-50/30 border border-orange-100 rounded-xl px-4 py-3 text-sm outline-none">
                        </div>
                    </div>
                    <div class="space-y-1.5 mt-6">
                        <label class="text-xs font-bold text-gray-400 uppercase ml-1">Bio Profissional</label>
                        <textarea name="bio" rows="4" class="w-full bg-orange-50/30 border border-orange-100 rounded-xl px-4 py-3 text-sm outline-none resize-none"><?= htmlspecialchars($dados['bio'] ?? '') ?></textarea>
                    </div>
                <?php else: ?>
                    <div class="p-6 bg-blue-50 rounded-3xl border border-blue-100 flex items-center justify-between">
                        <p class="text-xs text-blue-700 font-medium max-w-[70%]">Quer trabalhar com a ReformAÃ­? Comece cadastrando seu primeiro serviÃ§o para liberar as ferramentas de prestador!</p>
                        <a href="novo-servico.php" class="bg-blue-600 text-white text-[10px] font-bold px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">CADASTRAR SERVIÃ‡O</a>
                    </div>
                <?php endif; ?>
              </div>

              <div class="flex justify-end pt-8">
                <button type="submit" class="w-full md:w-auto bg-orange hover:bg-orange-600 text-white px-12 py-4 md:py-3.5 rounded-xl font-black text-sm shadow-lg transition-transform active:scale-95">SALVAR ALTERAÃ‡Ã•ES</button>
              </div>
          </div>
        </div>
      </form>
    </div>
  </main>

  <script>
    // Scripts de Preview e IBGE (Mantidos conforme originais)
    document.getElementById('foto_input').onchange = e => {
        const [file] = e.target.files;
        if (file) {
            const reader = new FileReader();
            reader.onload = event => {
                let img = document.getElementById('preview');
                if(!img) {
                    img = document.createElement('img');
                    img.id = 'preview';
                    img.className = "w-full h-full rounded-full object-cover border-4 border-gray-50 shadow-sm";
                    document.getElementById('placeholder')?.replaceWith(img);
                }
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const ufSelect = document.getElementById('uf');
        const cidadeSelect = document.getElementById('cidade');
        const cidadeAtual = document.getElementById('cidade_atual').value;

        fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome')
            .then(res => res.json())
            .then(estados => {
                ufSelect.innerHTML = '<option value="">UF</option>';
                estados.forEach(e => ufSelect.add(new Option(e.nome, e.sigla)));
                if(cidadeAtual.includes('-')) {
                    const ufCravada = cidadeAtual.split('-')[1].trim();
                    ufSelect.value = ufCravada;
                    ufSelect.dispatchEvent(new Event('change'));
                }
            });

        ufSelect.onchange = async () => {
            if(!ufSelect.value) return;
            cidadeSelect.disabled = true;
            const res = await fetch(`https://servicodados.ibge.gov.br/api/v1/localidades/estados/${ufSelect.value}/municipios`);
            const cidades = await res.json();
            cidadeSelect.innerHTML = '<option value="">Cidade</option>';
            cidades.forEach(c => {
                const val = `${c.nome} - ${ufSelect.value}`;
                const opt = new Option(c.nome, val);
                if(val === cidadeAtual) opt.selected = true;
                cidadeSelect.add(opt);
            });
            cidadeSelect.disabled = false;
        };
    });
  </script>
</body>
</html>


