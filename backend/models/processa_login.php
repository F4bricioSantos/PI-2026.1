<?php
require_once 'conexão.php'; 
require_once 'User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];

    $userObj = new User($conn);

    if ($userObj->login($email, $senha)) {
        header("Location: dashboard.php"); // Página após o login
        exit;
    } else {
        header("Location: login.php?erro=1");
        exit;
    }
}
