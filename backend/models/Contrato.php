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
            SET finalizado_prestador_em = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    public function atualizarStatus($id, $status) {
        $stmt = $this->pdo->prepare("
            UPDATE contratos SET status = :status WHERE id = :id
        ");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function buscarContratoParaAvaliar($clienteId, $prestadorId) {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM contratos
            WHERE cliente_id = :cliente_id
              AND prestador_id = :prestador_id
              AND status = 'concluido'
              AND avaliado = false
            ORDER BY criado_em DESC
            LIMIT 1
        ");
        $stmt->execute([':cliente_id' => $clienteId, ':prestador_id' => $prestadorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Conclui automaticamente contratos onde o prestador entregou
    // há mais de 15 dias e o cliente não confirmou
    public function executarRotinaConclusaoAutomatica() {
        $this->pdo->query("
            UPDATE contratos 
            SET status = 'concluido' 
            WHERE status = 'aceito' 
              AND finalizado_prestador_em IS NOT NULL 
              AND finalizado_prestador_em <= CURRENT_TIMESTAMP - INTERVAL '15 days'
        ");
    }
}