<?php

class ServiceController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function listarServicosAtivos(int $idUsuarioLogado) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.nome AS prestador_nome, u.cidade, u.foto_perfil AS prestador_foto
                FROM servicos s
                JOIN usuarios u ON u.id = s.prestador_id
                WHERE s.prestador_id != :uid
                ORDER BY s.id DESC
            ");
            $stmt->execute([':uid' => $idUsuarioLogado]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}