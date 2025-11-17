<?php
// logout.php - Desconecta o usuário
session_start();
require_once 'db.php';
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $hash = hash('sha256', $token);
    try {
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token_hash = :h");
        $stmt->bindParam(':h', $hash);
        $stmt->execute();
    } catch (PDOException $e) {}
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}
session_unset();   // Remove todas as variáveis de sessão
session_destroy(); // Destrói a sessão
header("Location: index.php"); // Redireciona para a página de login
exit();
?>