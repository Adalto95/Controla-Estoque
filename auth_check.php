<?php
// auth_check.php - Verificação de autenticação e perfil
// Inclua este arquivo no início de qualquer página que exija login.

session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Redireciona para a página de login se não estiver logado
    exit();
}

// Função para verificar o perfil do usuário
function checkProfile($allowedProfiles) {
    if (!isset($_SESSION['user_profile']) || !in_array($_SESSION['user_profile'], $allowedProfiles)) {
        // Redireciona para a página de fornecedores se o perfil não for permitido
        header("Location: suppliers.php");
        exit();
    }
}
?>