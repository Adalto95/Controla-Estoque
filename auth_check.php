<?php
// auth_check.php - Verificação de autenticação e perfil
// Inclua este arquivo no início de qualquer página que exija login.

session_start();
require_once 'db.php';
$hasDel = false; $hasViewInactive = false;
try { $chk = $conn->query("SHOW COLUMNS FROM permissions LIKE 'delete_product'"); $hasDel = ($chk && $chk->rowCount() > 0); } catch (PDOException $e) { $hasDel = false; }
try { $chk2 = $conn->query("SHOW COLUMNS FROM permissions LIKE 'view_inactive_products'"); $hasViewInactive = ($chk2 && $chk2->rowCount() > 0); } catch (PDOException $e) { $hasViewInactive = false; }
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
                $cols = "view_suppliers, add_supplier, toggle_supplier_status, add_product, edit_product_name, update_stock, toggle_product_status";
                if ($hasDel) { $cols .= ", delete_product"; }
                if ($hasViewInactive) { $cols .= ", view_inactive_products"; }
                $cols .= ", manage_permissions";
                $sql = "SELECT $cols FROM permissions WHERE perfil = :perfil";
                $pstmt = $conn->prepare($sql);
                $pstmt->bindParam(':perfil', $u->perfil);
                $pstmt->execute();
                $perms = $pstmt->fetch();
                $_SESSION['permissions'] = $perms ? (array)$perms : [];
                if (!$hasDel) { $_SESSION['permissions']['delete_product'] = 0; }
                if (!$hasViewInactive) { $_SESSION['permissions']['view_inactive_products'] = 0; }
            }
        }
    } catch (PDOException $e) {}
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Redireciona para a página de login se não estiver logado
    exit();
}

// Atualiza permissões em toda requisição para perfis não-admin
try {
    if (isset($_SESSION['user_profile']) && $_SESSION['user_profile'] !== 'admin') {
        $perfilAtual = $_SESSION['user_profile'];
        $hasDelCol = false; $hasViewInactiveCol = false;
        try { $c = $conn->query("SHOW COLUMNS FROM permissions LIKE 'delete_product'"); $hasDelCol = ($c && $c->rowCount() > 0); } catch (PDOException $e) { $hasDelCol = false; }
        try { $c2 = $conn->query("SHOW COLUMNS FROM permissions LIKE 'view_inactive_products'"); $hasViewInactiveCol = ($c2 && $c2->rowCount() > 0); } catch (PDOException $e) { $hasViewInactiveCol = false; }
        $cols = "view_suppliers, add_supplier, toggle_supplier_status, add_product, edit_product_name, update_stock, toggle_product_status";
        if ($hasDelCol) { $cols .= ", delete_product"; }
        if ($hasViewInactiveCol) { $cols .= ", view_inactive_products"; }
        $cols .= ", manage_permissions";
        $sqlPerm = "SELECT $cols FROM permissions WHERE perfil = :perfil";
        $ps = $conn->prepare($sqlPerm);
        $ps->bindParam(':perfil', $perfilAtual);
        $ps->execute();
        $p = $ps->fetch();
        $_SESSION['permissions'] = $p ? (array)$p : [];
        if (!$hasDelCol) { $_SESSION['permissions']['delete_product'] = 0; }
        if (!$hasViewInactiveCol) { $_SESSION['permissions']['view_inactive_products'] = 0; }
    } else if (isset($_SESSION['user_profile']) && $_SESSION['user_profile'] === 'admin') {
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
    }
} catch (PDOException $e) {}

// Função para verificar o perfil do usuário
function checkProfile($allowedProfiles) {
    if (!isset($_SESSION['user_profile']) || !in_array($_SESSION['user_profile'], $allowedProfiles)) {
        // Redireciona para a página de fornecedores se o perfil não for permitido
        header("Location: suppliers.php");
        exit();
    }
}
?>