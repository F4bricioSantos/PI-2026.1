<?php

class Service {
    private $db;

    public function __construct(PDO $conexao) {
        $this->db = $conexao;
    }

    public function listarTodos(int $usuarioId): array {
        try {
            $sql = "
                SELECT
                    s.id,
                    s.titulo,
                    s.categoria_nome,
                    s.valor_base,
                    s.descricao_curta,
                    u.nome  AS prestador_nome,
                    u.cidade,
                    COALESCE(ROUND(AVG(a.nota)::NUMERIC, 1), 0) AS media_nota,
                    COUNT(a.id)::INT                             AS total_avaliacoes
                FROM servicos s
                JOIN usuarios u ON u.id = s.prestador_id
                LEFT JOIN avaliacoes a ON a.prestador_id = s.prestador_id
                WHERE s.prestador_id != :id
                GROUP BY s.id, u.nome, u.cidade
                ORDER BY s.id DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $usuarioId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function buscarPorId(int $id): array|false {
        try {
            $sql = "
                SELECT
                    s.*,
                    u.nome  AS prestador_nome,
                    u.cidade,
                    u.telefone,
                    COALESCE(ROUND(AVG(a.nota)::NUMERIC, 1), 0) AS media_nota,
                    COUNT(a.id)::INT                             AS total_avaliacoes
                FROM servicos s
                JOIN usuarios u ON u.id = s.prestador_id
                LEFT JOIN avaliacoes a ON a.prestador_id = s.prestador_id
                WHERE s.id = :id
                GROUP BY s.id, u.nome, u.cidade, u.telefone
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function listarDoUsuario(int $usuarioId): array {
        try {
            $sql = "
                SELECT s.*,
                    COALESCE(ROUND(AVG(a.nota)::NUMERIC, 1), 0) AS media_nota,
                    COUNT(a.id)::INT                             AS total_avaliacoes
                FROM servicos s
                LEFT JOIN avaliacoes a ON a.prestador_id = s.prestador_id
                WHERE s.prestador_id = :id
                GROUP BY s.id
                ORDER BY s.id DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $usuarioId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function cadastrar(int $prestadorId, string $titulo, string $categoria, ?float $valor, ?string $descricao): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO servicos (prestador_id, titulo, categoria_nome, valor_base, descricao_curta)
                VALUES (:prestador_id, :titulo, :categoria, :valor, :descricao)
            ");
            return $stmt->execute([
                ':prestador_id' => $prestadorId,
                ':titulo'       => $titulo,
                ':categoria'    => $categoria,
                ':valor'        => $valor,
                ':descricao'    => $descricao,
            ]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}