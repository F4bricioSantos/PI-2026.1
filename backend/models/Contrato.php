<?php

class Contrato {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function buscarContratoAtivo($usuario1, $usuario2) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, s.titulo AS nome_servico 
            FROM contratos c
            JOIN servicos s ON c.servico_id = s.id
            WHERE ((c.cliente_id = :u1 AND c.prestador_id = :u2) 
               OR (c.cliente_id = :u2 AND c.prestador_id = :u1))
              AND c.status IN ('pendente', 'aceito')
            LIMIT 1
        ");
        $stmt->execute([':u1' => $usuario1, ':u2' => $usuario2]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function checarSeTemServico($usuarioId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = :id");
        $stmt->execute([':id' => $usuarioId]);
        return $stmt->fetchColumn() > 0;
    }

    public function listarServicosPrestador($prestadorId) {
        $stmt = $this->pdo->prepare("
            SELECT id, titulo AS nome_servico, valor_base AS preco 
            FROM servicos 
            WHERE prestador_id = :id
        ");
        $stmt->execute([':id' => $prestadorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criarContratoImediato($clienteId, $prestadorId, $servicoId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO contratos 
                (cliente_id, prestador_id, servico_id, status, data_pactuada)
            VALUES 
                (:cliente_id, :prestador_id, :servico_id, 'pendente', CURRENT_DATE)
        ");
        return $stmt->execute([
            ':cliente_id'   => $clienteId,
            ':prestador_id' => $prestadorId,
            ':servico_id'   => $servicoId,
        ]);
    }

    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM contratos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function marcarComoFinalizadoPrestador($id) {
        $stmt = $this->pdo->prepare("
            UPDATE contratos 
            SET finalizado_prestador_em = CURRENT_TIMESTAMP,
                atualizado_em = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    public function marcarComoFinalizadoCliente($id) {
        $stmt = $this->pdo->prepare("
            UPDATE contratos 
            SET finalizado_cliente_em = CURRENT_TIMESTAMP,
                atualizado_em = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    public function aceitarProposta($id) {
        $stmt = $this->pdo->prepare("
            UPDATE contratos 
            SET status = 'aceito',
                atualizado_em = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    public function confirmarConclusaoDefinitiva($id) {
        $stmt = $this->pdo->prepare("
            UPDATE contratos 
            SET status = 'concluido', 
                finalizado_prestador_em = COALESCE(finalizado_prestador_em, CURRENT_TIMESTAMP),
                finalizado_cliente_em = COALESCE(finalizado_cliente_em, CURRENT_TIMESTAMP),
                atualizado_em = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    public function voltarParaAndamento($id) {
        $stmt = $this->pdo->prepare("
            UPDATE contratos 
            SET status = 'aceito', 
                finalizado_prestador_em = NULL, 
                finalizado_cliente_em = NULL,
                atualizado_em = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    public function atualizarStatus($id, $status) {
        $stmt = $this->pdo->prepare("
            UPDATE contratos SET status = :status, atualizado_em = CURRENT_TIMESTAMP WHERE id = :id
        ");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function buscarContratoParaAvaliar($usuarioLogadoId, $outroUsuarioId) {
        $stmt = $this->pdo->prepare("
            SELECT id, cliente_id, prestador_id, servico_id
            FROM contratos
            WHERE (
                (cliente_id = :uid AND prestador_id = :oid AND status = 'concluido' AND avaliado = false)
                OR 
                (prestador_id = :uid AND cliente_id = :oid AND status = 'concluido' AND avaliado_prestador = false)
            )
            ORDER BY criado_em DESC
            LIMIT 1
        ");
        $stmt->execute([':uid' => $usuarioLogadoId, ':oid' => $outroUsuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function atualizarStatusParaConcluidoPeloCliente($id) {
        return $this->confirmarConclusaoDefinitiva($id);
    }

    // Executa rotinas automáticas de conclusão (10 dias)
    public function executarRotinaConclusaoAutomatica() {
        try {
            // 1. Prestador finalizou primeiro, usuário não respondeu em 10 dias -> conclui automaticamente
            $this->pdo->query("
                UPDATE contratos 
                SET status = 'concluido', 
                    finalizado_cliente_em = COALESCE(finalizado_cliente_em, CURRENT_TIMESTAMP),
                    atualizado_em = CURRENT_TIMESTAMP
                WHERE status = 'aceito' 
                  AND finalizado_prestador_em IS NOT NULL 
                  AND finalizado_cliente_em IS NULL
                  AND finalizado_prestador_em <= CURRENT_TIMESTAMP - INTERVAL '10 days'
            ");

            // 2. Usuário finalizou primeiro, prestador não respondeu em 10 dias -> conclui automaticamente
            $this->pdo->query("
                UPDATE contratos 
                SET status = 'concluido', 
                    finalizado_prestador_em = COALESCE(finalizado_prestador_em, CURRENT_TIMESTAMP),
                    atualizado_em = CURRENT_TIMESTAMP
                WHERE status = 'aceito' 
                  AND finalizado_cliente_em IS NOT NULL 
                  AND finalizado_prestador_em IS NULL
                  AND finalizado_cliente_em <= CURRENT_TIMESTAMP - INTERVAL '10 days'
            ");
        } catch (PDOException $e) {
            // Grava silenciosamente o erro de banco de dados nos logs para evitar queda do sistema
            error_log("Erro ao rodar rotina de conclusão automática (talvez colunas ausentes): " . $e->getMessage());
        }
    }

    public function salvarAvaliacao($contratoId, $clienteId, $prestadorId, $servicoId, $nota, $comentario, $avaliadorTipo = 'cliente') {
        $this->pdo->beginTransaction();
        try {
            // 1. Insere a avaliação
            $stmt = $this->pdo->prepare("
                INSERT INTO avaliacoes (cliente_id, prestador_id, servico_id, nota, comentario, avaliador_tipo, data_avaliacao)
                VALUES (:cliente_id, :prestador_id, :servico_id, :nota, :comentario, :avaliador_tipo, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                ':cliente_id'    => $clienteId,
                ':prestador_id'  => $prestadorId,
                ':servico_id'    => $servicoId,
                ':nota'          => $nota,
                ':comentario'    => $comentario,
                ':avaliador_tipo'=> $avaliadorTipo
            ]);

            // 2. Marca o contrato como avaliado
            if ($avaliadorTipo === 'cliente') {
                $stmtUpdate = $this->pdo->prepare("
                    UPDATE contratos SET avaliado = true WHERE id = :id
                ");
            } else {
                $stmtUpdate = $this->pdo->prepare("
                    UPDATE contratos SET avaliado_prestador = true WHERE id = :id
                ");
            }
            $stmtUpdate->execute([':id' => $contratoId]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}