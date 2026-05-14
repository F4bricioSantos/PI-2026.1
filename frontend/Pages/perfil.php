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
    $mensagem = "Erro de conexão: " . $e->getMessage();
}

// 2. PROCESSAMENTO DO FORMULÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $cidade    = filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_SPECIAL_CHARS);
    $telefone  = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS);
    $emailNovo = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    try {
        $nomeFotoNova = $dadosBD['foto_perfil'];
        if (isset($_POST['remover_foto'])) {
            if (function_exists('gerenciarFotoSupabase')) {
                gerenciarFotoSupabase(null, $dadosBD['foto_perfil']); 
            }
            $nomeFotoNova = 'default.png';
        } elseif (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            if (function_exists('gerenciarFotoSupabase')) {
                $nomeFotoNova = gerenciarFotoSupabase($_FILES['foto_perfil']['tmp_name'], $dadosBD['foto_perfil']);
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

        if ($emailNovo !== $dadosBD['email']) {
            $campos[] = "email = :email";
            $params[':email'] = $emailNovo;
        }

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
        echo "<script>window.location.href='perfil.php?sucesso=1';</script>";
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensagem = (strpos($e->getMessage(), 'usuarios_email_key') !== false) ? "O e-mail informado já está em uso." : "Erro técnico: " . $e->getMessage();
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
  <title>ReformAí – Meu Perfil</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { sans: ['Manrope', 'sans-serif'] }, colors: { orange: '#F97316', sidebar: '#16213E', bg: '#F8F9FA' } } }
    }
  </script>
</head>
<body class="font-sans bg-bg text-gray-800 flex h-screen overflow-hidden">

  <div id="sidebar-container" class="w-60 bg-sidebar flex-shrink-0 h-screen"></div>

  <script type="module">
    import { renderSidebar } from '../src/components/sidebar.js';
    
    // No perfil.php a variável que você usou no topo é $temServico
    const isPro = <?= ($temServico) ? 'true' : 'false' ?>;
    
    // Renderiza definindo 'perfil' como a página ativa
    renderSidebar('sidebar-container', 'perfil', isPro);
</script>
  <main class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white">
      <h1 class="text-gray-800 font-bold text-lg tracking-tight">Meu Perfil</h1>
      <div class="flex gap-3">
        <?php if(isset($_GET['sucesso']) && empty($mensagem)): ?>
            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-100">Alterações salvas!</span>
        <?php endif; ?>
        <?php if($mensagem): ?>
            <span class="text-xs font-bold text-red-600 bg-red-50 px-3 py-1 rounded-lg border border-red-100"><?= $mensagem ?></span>
        <?php endif; ?>
      </div>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-8">
      <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-8 max-w-6xl mx-auto">
        
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
                <i class="ph ph-map-pin"></i> <?= htmlspecialchars($dados['cidade'] ?: 'Não informada') ?>
            </p>
          </div>
        </div>

        <div class="lg:col-span-8">
          <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
              <h3 class="text-lg font-bold text-slate-900 mb-6">Informações Pessoais</h3>
              
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
                <input type="email" name="email" value="<?= htmlspecialchars($dados['email'] ?? '') ?>" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none transition-all">
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
                            <label class="text-xs font-bold text-gray-400 uppercase ml-1">Experiência (anos)</label>
                            <input type="number" name="experiencia" value="<?= $dados['experiencia_anos'] ?? 0 ?>" class="w-full bg-orange-50/30 border border-orange-100 rounded-xl px-4 py-3 text-sm outline-none">
                        </div>
                    </div>
                    <div class="space-y-1.5 mt-6">
                        <label class="text-xs font-bold text-gray-400 uppercase ml-1">Bio Profissional</label>
                        <textarea name="bio" rows="4" class="w-full bg-orange-50/30 border border-orange-100 rounded-xl px-4 py-3 text-sm outline-none resize-none"><?= htmlspecialchars($dados['bio'] ?? '') ?></textarea>
                    </div>
                <?php else: ?>
                    <div class="p-6 bg-blue-50 rounded-3xl border border-blue-100 flex items-center justify-between">
                        <p class="text-xs text-blue-700 font-medium max-w-[70%]">Quer trabalhar com a ReformAí? Comece cadastrando seu primeiro serviço para liberar as ferramentas de prestador!</p>
                        <a href="novo-servico.php" class="bg-blue-600 text-white text-[10px] font-bold px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">CADASTRAR SERVIÇO</a>
                    </div>
                <?php endif; ?>
              </div>

              <div class="flex justify-end pt-8">
                <button type="submit" class="bg-orange hover:bg-orange-600 text-white px-12 py-3.5 rounded-xl font-black text-sm shadow-lg transition-transform active:scale-95">SALVAR ALTERAÇÕES</button>
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