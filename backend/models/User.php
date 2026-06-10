<?php

class User {
    private $db;

    public function __construct($conexao) {
        $this->db = $conexao;
    }


    public function cadastrar($nome, $email, $cpf, $senha, $telefone = null) {
        try {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nome, email, cpf, senha, telefone) 
                    VALUES (:nome, :email, :cpf, :senha, :telefone)";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':nome'     => $nome,
                ':email'    => $email,
                ':cpf'      => $cpf,
                ':senha'    => $senhaHash,
                ':telefone' => $telefone
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
    public function buscarPorEmail($email) {
        try {
            $sql = "SELECT * FROM usuarios WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    public function buscarPorId($id) {
        try {
            $sql = "SELECT * FROM usuarios WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
    public function verificarExistencia($email, $cpf) {
        try {
            $sql = "SELECT id FROM usuarios WHERE email = :email OR cpf = :cpf";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email, ':cpf' => $cpf]);
            return $stmt->fetch() ? true : false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function login($email, $senha) {
        $usuario = $this->buscarPorEmail($email);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['logado']       = true;
            return true;
        }

        return false;
    }
    public function logout() {
        session_unset();
        session_destroy();
    }
    public function atualizarSenha(string $email, string $novaSenha): bool
    {
        try {
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE usuarios SET senha = :senha WHERE email = :email");
            return $stmt->execute([':senha' => $senhaHash, ':email' => $email]);
        } catch (PDOException $e) {
            return false;
        }
    }
    public function buscarPerfilCompleto(int $id): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id, u.nome, u.email, u.telefone, u.cidade, u.foto_perfil,
                    pd.bio, pd.nicho, pd.experiencia_anos,
                    COALESCE(ROUND(AVG(a.nota)::NUMERIC, 1), 0) AS media_nota,
                    COUNT(a.id)::INT AS total_avaliacoes
                FROM usuarios u
                LEFT JOIN prestadores_detalhes pd ON u.id = pd.usuario_id
                LEFT JOIN avaliacoes a ON (a.prestador_id = u.id OR a.cliente_id = u.id)
                WHERE u.id = :id
                GROUP BY u.id, u.nome, u.email, u.telefone, u.cidade, u.foto_perfil, pd.id, pd.bio, pd.nicho, pd.experiencia_anos
            ");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Erro ao buscar perfil completo: " . $e->getMessage());
            return null;
        }
    }
    public function obterMediaAvaliacoes(int $id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(ROUND(AVG(nota)::NUMERIC, 1), 0) as media, COUNT(*) as total
                FROM avaliacoes 
                WHERE prestador_id = :id OR cliente_id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['media' => 0, 'total' => 0];
        }
    }
}
