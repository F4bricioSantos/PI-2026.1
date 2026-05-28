<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/Conexao.php';

date_default_timezone_set('America/Sao_Paulo');

class ChatController {
    private $pdo;
    private $idUsuarioLogado;
    private $idDestinatarioAtual;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->idUsuarioLogado      = $_SESSION['usuario_id'] ?? null;
        $this->idDestinatarioAtual  = isset($_GET['com']) ? (int)$_GET['com'] : null;
    }

    // ─────────────────────────────────────────────
    // LISTAR MENSAGENS
    // Retorna o path bruto (ex: "chat/arquivo.jpg").
    // A URL base é montada pelo frontend com urlBaseChatImagens.
    // ─────────────────────────────────────────────
    public function listarMensagens() {
        if (!$this->idUsuarioLogado || !$this->idDestinatarioAtual) {
            echo json_encode([]);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, remetente_id, destinatario_id, mensagem,
                       url_imagem, criado_em, atualizado_em, deletado
                FROM mensagens_chat
                WHERE (remetente_id = :user AND destinatario_id = :dest)
                   OR (remetente_id = :dest AND destinatario_id = :user)
                ORDER BY criado_em ASC
            ");
            $stmt->execute([
                ':user' => $this->idUsuarioLogado,
                ':dest' => $this->idDestinatarioAtual,
            ]);

            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro SQL ao buscar mensagens: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────
    // ENVIAR MENSAGEM
    // Recebe JSON: { mensagem: string, url_imagem: string|null }
    // url_imagem é o path bruto retornado pelo uploadImagem()
    // ─────────────────────────────────────────────
    public function enviarMensagem($mensagemTexto, $urlImagem = null) {
        if (!$this->idUsuarioLogado || !$this->idDestinatarioAtual) {
            http_response_code(401);
            echo json_encode(['erro' => 'Não autorizado ou destinatário inválido']);
            return;
        }

        if (empty($mensagemTexto) && empty($urlImagem)) {
            http_response_code(400);
            echo json_encode(['erro' => 'Mensagem vazia']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO mensagens_chat
                    (remetente_id, destinatario_id, mensagem, url_imagem, criado_em)
                VALUES
                    (:remetente, :destinatario, :mensagem, :url_imagem, NOW())
            ");

            $sucesso = $stmt->execute([
                ':remetente'   => $this->idUsuarioLogado,
                ':destinatario'=> $this->idDestinatarioAtual,
                ':mensagem'    => $mensagemTexto ?: '',
                ':url_imagem'  => !empty($urlImagem) ? $urlImagem : null,
            ]);

            echo json_encode(['sucesso' => $sucesso]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro SQL ao inserir mensagem: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────
    // EDITAR MENSAGEM (janela de 5 minutos)
    // Recebe JSON: { id: int, message: string }
    // ─────────────────────────────────────────────
    public function editarMensagem($idMensagem, $novoTexto) {
        if (!$this->idUsuarioLogado || empty($novoTexto) || !$idMensagem) {
            http_response_code(400);
            echo json_encode(['erro' => 'Dados inválidos']);
            return;
        }

        try {
            $stmtCheck = $this->pdo->prepare(
                "SELECT criado_em FROM mensagens_chat WHERE id = :id AND remetente_id = :user"
            );
            $stmtCheck->execute([':id' => $idMensagem, ':user' => $this->idUsuarioLogado]);
            $msgData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$msgData) {
                http_response_code(404);
                echo json_encode(['erro' => 'Mensagem não encontrada ou sem permissão']);
                return;
            }

            // Usa timestamps brutos para evitar falhas do DateInterval com diff()
            $tempoCriacao    = new DateTime($msgData['criado_em']);
            $agora           = new DateTime();
            $minutosPassados = (int)(($agora->getTimestamp() - $tempoCriacao->getTimestamp()) / 60);

            if ($minutosPassados > 5) {
                http_response_code(403);
                echo json_encode(['erro' => 'O tempo limite de 5 minutos para edição expirou.']);
                return;
            }

            $stmt = $this->pdo->prepare("
                UPDATE mensagens_chat
                SET mensagem = :mensagem, atualizado_em = NOW()
                WHERE id = :id AND remetente_id = :user
            ");
            $sucesso = $stmt->execute([
                ':mensagem' => $novoTexto,
                ':id'       => $idMensagem,
                ':user'     => $this->idUsuarioLogado,
            ]);

            echo json_encode(['sucesso' => $sucesso]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro SQL ao editar: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────
    // DELETAR MENSAGEM (soft delete)
    // Recebe JSON: { id: int }
    // ─────────────────────────────────────────────
    public function deletarMensagem($idMensagem) {
    if (!$this->idUsuarioLogado || !$idMensagem) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados inválidos']);
        return;
    }

    try {
        $mensagemApagada = '
        <span style="
            display:inline-flex;
            align-items:center;
            gap:6px;
            color:#ef4444;
            font-style:italic;
            opacity:.9;
        ">
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="14"
                 height="14"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="#ef4444"
                 stroke-width="2"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 style="flex-shrink:0;">
                <circle cx="12" cy="12" r="9"></circle>
                <line x1="6" y1="18" x2="18" y2="6"></line>
            </svg>
            <span>Esta mensagem foi apagada</span>
        </span>';

        $stmt = $this->pdo->prepare("
            UPDATE mensagens_chat
            SET mensagem = :mensagem,
                url_imagem = NULL,
                deletado = 1
            WHERE id = :id
              AND remetente_id = :user
        ");

        $sucesso = $stmt->execute([
            ':mensagem' => $mensagemApagada,
            ':id'       => $idMensagem,
            ':user'     => $this->idUsuarioLogado,
        ]);

        echo json_encode(['sucesso' => $sucesso]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'erro' => 'Erro SQL ao deletar: ' . $e->getMessage()
        ]);
    }
}
    // ─────────────────────────────────────────────
    // LISTAR CONTATOS (sidebar)
    // ─────────────────────────────────────────────
    public function listarContatosConversas() {
        if (!$this->idUsuarioLogado) {
            echo json_encode([]);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT u.id, u.nome, u.foto_perfil
                FROM usuarios u
                JOIN mensagens_chat m
                  ON (m.remetente_id = u.id OR m.destinatario_id = u.id)
                WHERE (m.remetente_id = :user OR m.destinatario_id = :user)
                  AND u.id != :user
            ");
            $stmt->execute([':user' => $this->idUsuarioLogado]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro SQL ao listar contatos: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────
    // UPLOAD DE IMAGEM (proxy seguro via cURL)
    // Rota: POST RoteadorChat.php?acao=upload
    // O frontend envia multipart/form-data com campo "imagem".
    // O PHP lê o binário e envia direto ao Supabase como body puro (não multipart).
    // Retorna JSON: { path: "chat/timestamp_uniqid.ext" }
    // ─────────────────────────────────────────────
    public function uploadImagem() {
        if (!$this->idUsuarioLogado) {
            http_response_code(401);
            echo json_encode(['erro' => 'Não autorizado']);
            return;
        }

        if (empty($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nenhuma imagem válida recebida', 'filesDebug' => $_FILES]);
            return;
        }

        $arquivo = $_FILES['imagem'];

        // Valida tipo MIME no servidor (não confia no client)
        $mime = mime_content_type($arquivo['tmp_name']);
        $mimesPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $mimesPermitidos)) {
            http_response_code(415);
            echo json_encode(['erro' => 'Tipo de arquivo não suportado: ' . $mime]);
            return;
        }

        $extensoesMime = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $extensao = $extensoesMime[$mime];

        // Nome gerado pelo servidor — sem espaços, acentos ou caracteres especiais
        $nomeFinal = time() . '_' . uniqid('', true) . '.' . $extensao;
        $path      = "chat/{$nomeFinal}";

        $supabaseUrl = 'https://yplpxzmwtkencrrtxmof.supabase.co';
        $supabaseKey = 'sb_secret_JhmF8klpQEn3lG8SpzrYig_74tbfzEH'; // ← Substitua pela sua chave (Settings → API → service_role)
        $bucket      = 'fotos';

        // Lê o binário do arquivo temporário.
        // O Supabase Storage espera o body BINÁRIO PURO no POST (não multipart/form-data).
        // NÃO use CURLFile aqui — CURLFile força multipart e conflita com Content-Type da imagem.
        $corposBinario = file_get_contents($arquivo['tmp_name']);
        if ($corposBinario === false) {
            http_response_code(500);
            echo json_encode(['erro' => 'Não foi possível ler o arquivo temporário']);
            return;
        }

        $ch = curl_init("{$supabaseUrl}/storage/v1/object/{$bucket}/{$path}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $corposBinario,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$supabaseKey}",
                "apikey: {$supabaseKey}",
                "Content-Type: {$mime}",
                "Content-Length: " . strlen($corposBinario),
                "x-upsert: true",
            ],
        ]);

        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErro = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            echo json_encode(['path' => $path]);
        } else {
            http_response_code(500);
            echo json_encode([
                'erro'     => 'Falha no upload para o Supabase',
                'httpCode' => $httpCode,
                'detalhe'  => json_decode($resposta, true),
                'curlErro' => $curlErro ?: null,
            ]);
        }
    }
}