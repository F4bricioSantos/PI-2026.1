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


    /**
     * Valida as credenciais e inicia a sessão do usuário
     */
    public function login($email, $senha) {
        // Busca os dados do usuário pelo e-mail
        $usuario = $this->buscarPorEmail($email);

        // Se o usuário existir, verifica se a senha digitada bate com o hash no banco
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            
            // Inicia a sessão se ela ainda não estiver ativa
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Salva informações básicas na sessão para usar no site
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['logado'] = true;

            return true;
        }

        return false; // Credenciais inválidas
    }

    /**
     * Encerra a sessão do usuário (Logout)
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }
}
