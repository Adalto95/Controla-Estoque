<?php
// auth_check.php - Verificação de autenticação e perfil
// Inclua este arquivo no início de qualquer página que exija login.

session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $hash = hash('sha256', $token);
    try {
        $stmt = $conn->prepare("SELECT rt.user_id, u.nome, u.perfil FROM remember_tokens rt JOIN usuarios u ON u.id = rt.user_id WHERE rt.token_hash = :h AND rt.expires_at > NOW()");
        $stmt->bindParam(':h', $hash);
        $stmt->execute();
        $u = $stmt->fetch();
        if ($u) {
            $_SESSION['user_id'] = $u->user_id;
            $_SESSION['user_name'] = $u->nome;
            $_SESSION['user_profile'] = $u->perfil;
            if ($u->perfil === 'admin') {
                $_SESSION['permissions'] = [
                    'view_suppliers' => 1,
                    'add_supplier' => 1,
                    'toggle_supplier_status' => 1,
                    'add_product' => 1,
                    'edit_product_name' => 1,
                    'update_stock' => 1,
                    'toggle_product_status' => 1,
                    'manage_permissions' => 1
                ];
            } else {
                $pstmt = $conn->prepare("SELECT view_suppliers, add_supplier, toggle_supplier_status, add_product, edit_product_name, update_stock, toggle_product_status, delete_product, manage_permissions FROM permissions WHERE perfil = :perfil");
                $pstmt->bindParam(':perfil', $u->perfil);
                $pstmt->execute();
                $perms = $pstmt->fetch();
                $_SESSION['permissions'] = $perms ? (array)$perms : [];
            }
        }
    } catch (PDOException $e) {}
}

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