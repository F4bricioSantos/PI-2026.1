<?php
require_once __DIR__ . '/../config/Conexao.php';
require_once __DIR__ . '/../config/session_setup.php';
setup_db_session($pdo);
require_once __DIR__ . '/../config/auth.php';

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
            try {
                $upd = $this->pdo->prepare("
                    UPDATE mensagens_chat
                    SET lido_em = NOW()
                    WHERE destinatario_id = :user AND remetente_id = :dest AND lido_em IS NULL
                ");
                $upd->execute([
                    ':user' => $this->idUsuarioLogado,
                    ':dest' => $this->idDestinatarioAtual,
                ]);
            } catch (PDOException $e) {
                error_log("Erro ao marcar mensagens como lidas: " . $e->getMessage());
            }

            $sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;

            $sql = "
                SELECT id, remetente_id, destinatario_id, mensagem,
                       url_imagem, criado_em, atualizado_em, deletado, lido_em, entregue_em
                FROM mensagens_chat
                WHERE ((remetente_id = :user AND destinatario_id = :dest)
                   OR (remetente_id = :dest AND destinatario_id = :user))
            ";
            $params = [
                ':user' => $this->idUsuarioLogado,
                ':dest' => $this->idDestinatarioAtual,
            ];

            if ($sinceId > 0) {
                $sql .= " AND id > :since_id";
                $params[':since_id'] = $sinceId;
            }

            $sql .= " ORDER BY criado_em ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
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
        $stmt = $this->pdo->prepare("
            UPDATE mensagens_chat
            SET mensagem = '',
                url_imagem = NULL,
                deletado = 1
            WHERE id = :id
              AND remetente_id = :user
        ");
        $sucesso = $stmt->execute([
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
                SELECT u.id, u.nome, u.foto_perfil,
                       (SELECT COUNT(*)::INT
                        FROM mensagens_chat m2 
                        WHERE m2.remetente_id = u.id 
                          AND m2.destinatario_id = :user 
                          AND m2.lido_em IS NULL 
                          AND m2.deletado = 0) AS unread_count,
                       (SELECT MAX(criado_em) FROM mensagens_chat m3 
                        WHERE (m3.remetente_id = u.id AND m3.destinatario_id = :user)
                           OR (m3.remetente_id = :user AND m3.destinatario_id = u.id)) as ultima_atividade
                FROM usuarios u
                WHERE u.id != :user
                  AND (
                    u.id = :atual 
                    OR EXISTS (SELECT 1 FROM mensagens_chat m4 
                               WHERE (m4.remetente_id = u.id AND m4.destinatario_id = :user)
                                  OR (m4.destinatario_id = u.id AND m4.remetente_id = :user))
                  )
                ORDER BY (u.id = :atual) DESC, ultima_atividade DESC NULLS LAST
            ");
            $stmt->execute([
                ':user'  => (int)$this->idUsuarioLogado,
                ':atual' => $this->idDestinatarioAtual ? (int)$this->idDestinatarioAtual : 0
            ]);
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

        $bucket      = 'fotos';

        // Validação de tamanho (Limite de 5MB) para evitar estouro de storage
        if ($arquivo['size'] > 5 * 1024 * 1024) {
            http_response_code(413);
            echo json_encode(['erro' => 'A imagem excede o limite de 5MB.']);
            return;
        }

        // Lê o binário do arquivo temporário.
        // O Supabase Storage espera o body BINÁRIO PURO no POST (não multipart/form-data).
        $corposBinario = file_get_contents($arquivo['tmp_name']);
        if ($corposBinario === false) {
            http_response_code(500);
            echo json_encode(['erro' => 'Não foi possível ler o arquivo temporário']);
            return;
        }

        $ch = curl_init(SB_URL . "/storage/v1/object/{$bucket}/{$path}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $corposBinario,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer " . SB_SECRET_KEY,
                "apikey: " . SB_SECRET_KEY,
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

    // ─────────────────────────────────────────────
    // CONTAGEM DE MENSAGENS NÃO LIDAS (polling)
    // Retorna JSON: { total: int, por_contato: { id: count, ... } }
    // ─────────────────────────────────────────────
    public function contarNaoLidas() {
        if (!$this->idUsuarioLogado) {
            echo json_encode(['total' => 0, 'por_contato' => new \stdClass()]);
            return;
        }

        try {
            // Marca todas as mensagens destinadas a este usuário como ENTREGUES
            // (ele está online, pois está fazendo polling)
            try {
                $updEntregue = $this->pdo->prepare("
                    UPDATE mensagens_chat
                    SET entregue_em = NOW()
                    WHERE destinatario_id = :uid AND entregue_em IS NULL AND deletado = 0
                ");
                $updEntregue->execute([':uid' => $this->idUsuarioLogado]);
            } catch (PDOException $e) {
                error_log("Erro ao marcar entregue: " . $e->getMessage());
            }

            // Total global
            $stmtTotal = $this->pdo->prepare("
                SELECT COUNT(*) FROM mensagens_chat
                WHERE destinatario_id = :uid AND lido_em IS NULL AND deletado = 0
            ");
            $stmtTotal->execute([':uid' => $this->idUsuarioLogado]);
            $total = (int)$stmtTotal->fetchColumn();

            // Por contato (remetente)
            $stmtPorContato = $this->pdo->prepare("
                SELECT remetente_id, COUNT(*) as cnt
                FROM mensagens_chat
                WHERE destinatario_id = :uid AND lido_em IS NULL AND deletado = 0
                GROUP BY remetente_id
            ");
            $stmtPorContato->execute([':uid' => $this->idUsuarioLogado]);
            $rows = $stmtPorContato->fetchAll(PDO::FETCH_ASSOC);

            $porContato = new \stdClass();
            foreach ($rows as $r) {
                $porContato->{$r['remetente_id']} = (int)$r['cnt'];
            }

            echo json_encode(['total' => $total, 'por_contato' => $porContato]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro SQL: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────
    // MARCAR COMO LIDO via endpoint dedicado
    // POST ?acao=mark_read JSON: { remetente_id: int }
    // ─────────────────────────────────────────────
    public function marcarComoLido($remetenteId) {
        if (!$this->idUsuarioLogado || !$remetenteId) {
            echo json_encode(['sucesso' => false]);
            return;
        }

        try {
            $upd = $this->pdo->prepare("
                UPDATE mensagens_chat
                SET lido_em = NOW()
                WHERE destinatario_id = :user AND remetente_id = :rem AND lido_em IS NULL
            ");
            $upd->execute([
                ':user' => $this->idUsuarioLogado,
                ':rem'  => (int)$remetenteId,
            ]);
            echo json_encode(['sucesso' => true, 'atualizados' => $upd->rowCount()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro SQL: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────
    // VERIFICAR STATUS DE CONTRATO (polling)
    // GET ?acao=contract_status&com=ID
    // Retorna JSON: { contrato: { id, status, finalizado_prestador_em } | null }
    // ─────────────────────────────────────────────
    public function verificarStatusContrato() {
        if (!$this->idUsuarioLogado || !$this->idDestinatarioAtual) {
            echo json_encode(['contrato' => null]);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT c.id, c.status, c.finalizado_prestador_em, c.finalizado_cliente_em, 
                       c.avaliado, c.avaliado_prestador, c.cliente_id, c.prestador_id
                FROM contratos c
                WHERE ((c.cliente_id = :u1 AND c.prestador_id = :u2)
                   OR  (c.cliente_id = :u2 AND c.prestador_id = :u1))
                  AND c.status IN ('pendente', 'aceito', 'concluido')
                ORDER BY c.criado_em DESC
                LIMIT 1
            ");
            $stmt->execute([
                ':u1' => $this->idUsuarioLogado,
                ':u2' => $this->idDestinatarioAtual,
            ]);
            $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['contrato' => $contrato ?: null]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro SQL: ' . $e->getMessage()]);
        }
    }
}