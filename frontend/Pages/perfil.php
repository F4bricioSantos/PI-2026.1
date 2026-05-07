<?php
require_once '../../backend/config/auth.php';
require_once '../../backend/config/Conexao.php';

$idUsuario = $_SESSION['usuario_id']; //
$mensagem = "";

// 1. VERIFICAÇÃO: O usuário já possui serviços cadastrados?
$sqlCheckServico = "SELECT COUNT(*) FROM servicos WHERE prestador_id = :id"; 
$stmtCheck = $pdo->prepare($sqlCheckServico);
$stmtCheck->execute([':id' => $idUsuario]);
$temServico = $stmtCheck->fetchColumn() > 0;

// 2. LÓGICA PARA SALVAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $cidade    = filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_SPECIAL_CHARS);
    $telefone  = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS);
    $email     = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    // Dados profissionais (Opcionais se for apenas usuário)
    $bio       = $_POST['bio'] ?? null;
    $nicho     = $_POST['nicho'] ?? null;
    $exp_anos  = !empty($_POST['experiencia']) ? (int)$_POST['experiencia'] : null;

    try {
        $pdo->beginTransaction();

        // Atualiza tabela usuarios
        $sqlUser = "UPDATE usuarios SET nome = :nome, cidade = :cidade, telefone = :telefone, email = :email WHERE id = :id";
        $pdo->prepare($sqlUser)->execute([':nome'=>$nome, ':cidade'=>$cidade, ':telefone'=>$telefone, ':email'=>$email, ':id'=>$idUsuario]);

        // Se ele for prestador (ou quiser preencher), atualiza prestadores_detalhes
        $sqlDet = "INSERT INTO prestadores_detalhes (usuario_id, bio, nicho, experiencia_anos) 
                   VALUES (:id, :bio, :nicho, :exp)
                   ON CONFLICT (usuario_id) 
                   DO UPDATE SET bio = EXCLUDED.bio, nicho = EXCLUDED.nicho, experiencia_anos = EXCLUDED.experiencia_anos";
        $pdo->prepare($sqlDet)->execute([':id'=>$idUsuario, ':bio'=>$bio, ':nicho'=>$nicho, ':exp'=>$exp_anos]);

        $pdo->commit();
        $mensagem = "Informações atualizadas!";
        
        // Recarrega para atualizar a variável $temServico se necessário
        header("Refresh:0"); 
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "Erro ao salvar.";
    }
}

// 3. BUSCA DADOS PARA EXIBIR
$sql = "SELECT u.*, pd.bio, pd.nicho, pd.experiencia_anos 
        FROM usuarios u 
        LEFT JOIN prestadores_detalhes pd ON pd.usuario_id = u.id 
        WHERE u.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $idUsuario]);
$dados = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>ReformAí – Meu Perfil</title>
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
    renderSidebar('sidebar-container', 'perfil');
  </script>

  <main class="flex-1 flex flex-col overflow-hidden">
    <header class="flex items-center justify-between px-8 py-5 border-b border-gray-200 bg-white flex-shrink-0">
      <div class="flex items-center gap-2">
        <h1 class="text-gray-800 font-bold text-lg tracking-tight">Meu Perfil</h1>
      </div>
      <?php if($mensagem): ?>
        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-lg"><?= $mensagem ?></span>
      <?php endif; ?>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 max-w-6xl mx-auto">
        
        <div class="lg:col-span-4 space-y-6">
          <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 text-center">
            <div class="w-24 h-24 bg-gray-100 rounded-full mx-auto mb-4 flex items-center justify-center">
               <span class="text-2xl font-bold text-gray-400"><?= strtoupper(substr($dados['nome'], 0, 1)) ?></span>
            </div>
            <h2 class="text-xl font-extrabold text-slate-900"><?= htmlspecialchars($dados['nome']) ?></h2>
            <p class="text-gray-400 text-sm"><?= htmlspecialchars($dados['cidade'] ?: 'Cidade não informada') ?></p>
            
            <?php if($temServico): ?>
                <div class="mt-6 pt-6 border-t border-gray-50 space-y-3">
                    <div class="flex justify-between text-[11px] font-bold text-gray-500 uppercase">
                        <span>Especialidade</span>
                        <span class="text-slate-700"><?= htmlspecialchars($dados['nicho'] ?: 'Não definido') ?></span>
                    </div>
                    <div class="flex justify-between text-[11px] font-bold text-gray-500 uppercase">
                        <span>Experiência</span>
                        <span class="text-slate-700"><?= $dados['experiencia_anos'] ?: 0 ?> anos</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="mt-6 p-4 bg-gray-50 rounded-2xl text-xs text-gray-500 italic">
                    Perfil de Cliente
                </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="lg:col-span-8">
          <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
            <form method="POST" class="space-y-6">
              
              <h3 class="text-lg font-bold text-slate-900">Informações Básicas</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-gray-400 uppercase ml-1">Nome Completo</label>
                  <input type="text" name="nome" value="<?= htmlspecialchars($dados['nome']) ?>" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none">
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-gray-400 uppercase ml-1">Cidade / UF</label>
                  <input type="text" name="cidade" value="<?= htmlspecialchars($dados['cidade']) ?>" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none">
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-gray-400 uppercase ml-1">Telefone</label>
                  <input type="text" name="telefone" value="<?= htmlspecialchars($dados['telefone']) ?>" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none">
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-gray-400 uppercase ml-1">E-mail</label>
                  <input type="email" name="email" value="<?= htmlspecialchars($dados['email']) ?>" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none">
                </div>
              </div>

              <?php if($temServico): ?>
              <div class="pt-8 border-t border-gray-100 animate-in fade-in duration-500">
                <h3 class="text-lg font-bold text-orange mb-4">Configurações de Prestador</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                      <label class="text-xs font-bold text-gray-400 uppercase ml-1">Nicho Principal</label>
                      <input type="text" name="nicho" value="<?= htmlspecialchars($dados['nicho']) ?>" placeholder="Ex: Pintura, Elétrica" class="w-full bg-orange-50/30 border border-orange-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none">
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-xs font-bold text-gray-400 uppercase ml-1">Anos de Experiência</label>
                      <input type="number" name="experiencia" value="<?= $dados['experiencia_anos'] ?>" class="w-full bg-orange-50/30 border border-orange-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none">
                    </div>
                </div>
                <div class="mt-6 space-y-1.5">
                  <label class="text-xs font-bold text-gray-400 uppercase ml-1">Bio Profissional (Aparece nos detalhes do serviço)</label>
                  <textarea name="bio" rows="4" class="w-full bg-orange-50/30 border border-orange-100 rounded-xl px-4 py-3 text-sm focus:border-orange outline-none resize-none"><?= htmlspecialchars($dados['bio']) ?></textarea>
                </div>
              </div>
              <?php else: ?>
                <div class="p-6 bg-blue-50 rounded-3xl border border-blue-100 flex items-center justify-between">
                    <p class="text-xs text-blue-700 font-medium max-w-[70%]">Quer trabalhar com a ReformAí? Comece cadastrando seu primeiro serviço para liberar as ferramentas de prestador!</p>
                    <a href="novo-servico.php" class="bg-blue-600 text-white text-[10px] font-bold px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">CADASTRAR SERVIÇO</a>
                </div>
              <?php endif; ?>

              <div class="flex items-center justify-end gap-4 pt-6">
                <button type="submit" class="bg-orange hover:bg-orange-600 text-white px-10 py-3 rounded-xl font-bold text-sm shadow-lg shadow-orange/20 transition-all hover:scale-[1.02]">
                    SALVAR ALTERAÇÕES
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </main>
</body>
</html>